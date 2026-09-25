#!/usr/bin/env python3
"""
Obtain a Cloudflare clearance for profixio.com.

Since 2026-09 profixio fronts the ranking list (ranking_sbtf_list.php) and the
series pages (serieoppsett.php) with a Cloudflare "managed challenge". Once a
browser passes it, Cloudflare issues a `cf_clearance` cookie bound to the
client IP + user-agent, and every later request that carries that cookie and
the same user-agent — headless Playwright, Browsershot, curl — is served
normally.

Which browser can pass it (verified 2026-09-24):

  camoufox  Firefox with its fingerprint fixed at the C++ level (it reports a
            real-looking GPU). Passes fully headless on a server without a
            GPU or display in a few seconds. Default when installed:
                pip install "camoufox[geoip]" && python3 -m camoufox fetch
  chromium  A *headed* Chromium passes only on a machine with a hardware GPU
            (software rendering is rejected whatever flags are used), so it
            needs $DISPLAY and is only a fallback for desktop use.

Prints one JSON line on stdout:

    {"user_agent": "...", "cookies": [{name, value, domain, path, expires,
     httpOnly, secure, sameSite}, ...], "cleared_in": 3.2, "engine": "camoufox"}

Exit code 0 on success, 1 if the challenge did not clear in time.
Log lines go to stderr.
"""

import argparse
import asyncio
import json
import os
import sys
import time

DEFAULT_URL = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender=m"
CHALLENGE_MARKERS = ("_cf_chl_opt", "challenges.cloudflare.com")
COOKIE_NAMES = ("cf_clearance", "__cf_bm")


def log(message: str) -> None:
    print(f"[INFO] {message}", file=sys.stderr, flush=True)


def camoufox_available() -> bool:
    try:
        import camoufox  # noqa: F401
        return True
    except ImportError:
        return False


def resolve_chromium() -> str | None:
    path = os.environ.get("PUPPETEER_EXECUTABLE_PATH")
    if path and os.path.exists(path):
        return path
    for candidate in ("/usr/bin/chromium", "/usr/bin/chromium-browser", "/usr/bin/google-chrome"):
        if os.path.exists(candidate):
            return candidate
    return None


async def is_challenged(page) -> bool:
    try:
        html = await page.content()
    except Exception:
        # Mid-navigation (the challenge reloads the page when it clears).
        return True
    return any(marker in html for marker in CHALLENGE_MARKERS)


async def wait_for_clearance(page, context, url: str, timeout: float) -> dict:
    """Open the challenged page, wait until Cloudflare lets it through and
    return the user-agent + cookies a later request must carry."""
    started = time.monotonic()
    response = await page.goto(url, wait_until="domcontentloaded", timeout=60_000)
    log(f"First response: HTTP {response.status if response else '?'}")

    deadline = started + timeout
    while True:
        cookies = await context.cookies()
        has_clearance = any(c["name"] == "cf_clearance" for c in cookies)
        if has_clearance or not await is_challenged(page):
            break
        if time.monotonic() > deadline:
            raise RuntimeError(f"Cloudflare challenge did not clear within {timeout:.0f}s")
        await asyncio.sleep(1)

    # Let the post-challenge reload settle so the UA read is from a stable page.
    try:
        await page.wait_for_load_state("load", timeout=15_000)
    except Exception:
        pass
    await asyncio.sleep(1)

    user_agent = await page.evaluate("() => navigator.userAgent")
    cookies = await context.cookies()
    cleared_in = round(time.monotonic() - started, 1)
    log(f"Challenge cleared in {cleared_in}s; cf_clearance={'yes' if has_clearance else 'not needed'}")

    return {
        "user_agent": user_agent,
        "cookies": [
            {
                "name": c["name"],
                "value": c["value"],
                "domain": c["domain"],
                "path": c["path"],
                "expires": c.get("expires", -1),
                "httpOnly": c.get("httpOnly", False),
                "secure": c.get("secure", False),
                "sameSite": c.get("sameSite", "None"),
            }
            for c in cookies
            if c["name"] in COOKIE_NAMES
        ],
        "cleared_in": cleared_in,
    }


def os_from_user_agent(user_agent: str) -> str:
    if "Windows" in user_agent:
        return "windows"
    if "Macintosh" in user_agent:
        return "macos"
    return "linux"


async def obtain_camoufox(url: str, timeout: float, proxy: str | None, user_agent: str | None = None) -> dict:
    from camoufox.async_api import AsyncCamoufox

    options = {"headless": True, "geoip": True, "humanize": True}
    if user_agent:
        # A clearance is bound to the user-agent. When renewing one for a
        # browser that is already running, ask for exactly its UA (and a
        # matching OS so the fingerprint stays coherent).
        options["config"] = {"navigator.userAgent": user_agent}
        options["os"] = os_from_user_agent(user_agent)
    if proxy:
        options["proxy"] = {"server": proxy}
        log(f"Routing the browser through proxy {proxy}")
    log("Launching Camoufox (headless)")
    async with AsyncCamoufox(**options) as browser:
        page = await browser.new_page()
        result = await wait_for_clearance(page, page.context, url, timeout)
    result["engine"] = "camoufox"
    return result


async def obtain_chromium(url: str, timeout: float, proxy: str | None) -> dict:
    from playwright.async_api import async_playwright

    if not os.environ.get("DISPLAY"):
        raise RuntimeError("DISPLAY is not set — the Chromium engine needs a headed browser on a screen "
                           "(and a hardware GPU). Install camoufox instead: "
                           'pip install "camoufox[geoip]" && python3 -m camoufox fetch')

    async with async_playwright() as p:
        launch_args = {
            "headless": False,
            "args": [
                "--no-sandbox",
                "--disable-setuid-sandbox",
                "--disable-dev-shm-usage",
                "--disable-blink-features=AutomationControlled",
            ],
        }
        chrome_path = resolve_chromium()
        if chrome_path:
            launch_args["executable_path"] = chrome_path
            log(f"Using system Chromium: {chrome_path}")
        if proxy:
            launch_args["proxy"] = {"server": proxy}
            log(f"Routing the browser through proxy {proxy}")

        browser = await p.chromium.launch(**launch_args)
        try:
            # No user-agent override: a spoofed version string contradicts the
            # browser's real fingerprint and makes the check take 10x longer.
            context = await browser.new_context(viewport={"width": 1366, "height": 768})
            page = await context.new_page()
            result = await wait_for_clearance(page, context, url, timeout)
        finally:
            await browser.close()
    result["engine"] = "chromium"
    return result


async def main() -> int:
    parser = argparse.ArgumentParser(description="Obtain a Cloudflare clearance cookie for profixio.com")
    parser.add_argument("--url", default=DEFAULT_URL, help="Challenged URL to open")
    parser.add_argument("--timeout", type=float, default=90, help="Seconds to wait for the challenge to clear")
    parser.add_argument("--engine", choices=["auto", "camoufox", "chromium"],
                        default=os.environ.get("SCRAPER_CF_ENGINE", "auto"),
                        help="auto = camoufox when installed, else headed Chromium (default: auto)")
    parser.add_argument("--user-agent", default=None,
                        help="Ask for a clearance bound to exactly this user-agent (camoufox engine only)")
    parser.add_argument("--proxy", default=os.environ.get("SCRAPER_CF_PROXY") or None,
                        help="Send the browser's traffic through this proxy (e.g. socks5://127.0.0.1:1080). "
                             "The clearance is bound to the IP profixio sees.")
    args = parser.parse_args()

    engine = args.engine
    if engine == "auto":
        engine = "camoufox" if camoufox_available() else "chromium"
        if engine == "chromium":
            log("camoufox is not installed — falling back to headed Chromium "
                '(pip install "camoufox[geoip]" && python3 -m camoufox fetch to fix)')

    try:
        if engine == "camoufox":
            result = await obtain_camoufox(args.url, args.timeout, args.proxy, args.user_agent)
        else:
            result = await obtain_chromium(args.url, args.timeout, args.proxy)
    except Exception as e:
        print(f"[ERROR] {e}", file=sys.stderr, flush=True)
        return 1

    result["proxy"] = args.proxy
    sys.stdout.write(json.dumps(result) + "\n")
    sys.stdout.flush()
    return 0


if __name__ == "__main__":
    sys.exit(asyncio.run(main()))
