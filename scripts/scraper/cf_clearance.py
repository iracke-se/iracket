#!/usr/bin/env python3
"""
Obtain a Cloudflare clearance for profixio.com.

Since 2026-09 profixio fronts the ranking list (ranking_sbtf_list.php) and the
series pages (serieoppsett.php) with a Cloudflare "managed challenge". Headless
Chromium never passes it, a real (headed) Chromium window passes it in a few
seconds without any interaction. Cloudflare then issues a `cf_clearance`
cookie that is bound to the client IP + user-agent, and every later request —
headless or not, Playwright or Browsershot — is served normally as long as it
carries that cookie and the same user-agent.

This script opens a headed browser on $DISPLAY (a virtual one on the server,
see setup-display.sh), waits for the challenge to clear and prints one JSON
line on stdout:

    {"user_agent": "...", "cookies": [{name, value, domain, path, expires,
     httpOnly, secure, sameSite}, ...], "cleared_in": 3.2}

Exit code 0 on success, 1 if the challenge did not clear in time.
Log lines go to stderr.
"""

import argparse
import asyncio
import json
import os
import sys
import time

from playwright.async_api import async_playwright

DEFAULT_URL = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender=m"
CHALLENGE_MARKERS = ("_cf_chl_opt", "challenges.cloudflare.com")


def log(message: str) -> None:
    print(f"[INFO] {message}", file=sys.stderr, flush=True)


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


async def obtain(url: str, timeout: float, proxy: str | None = None) -> dict:
    if not os.environ.get("DISPLAY"):
        raise RuntimeError("DISPLAY is not set — a headed browser is required to pass the challenge "
                           "(run scripts/scraper/setup-display.sh on the server and set SCRAPER_DISPLAY)")

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
                "proxy": proxy,
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
                    if c["name"] in ("cf_clearance", "__cf_bm")
                ],
                "cleared_in": cleared_in,
            }
        finally:
            await browser.close()


async def main() -> int:
    parser = argparse.ArgumentParser(description="Obtain a Cloudflare clearance cookie for profixio.com")
    parser.add_argument("--url", default=DEFAULT_URL, help="Challenged URL to open")
    parser.add_argument("--timeout", type=float, default=90, help="Seconds to wait for the challenge to clear")
    parser.add_argument("--proxy", default=os.environ.get("SCRAPER_CF_PROXY") or None,
                        help="Send the browser's traffic through this proxy (e.g. http://SERVER:18080). "
                             "The clearance is bound to the IP profixio sees, so solving it through the "
                             "production server's relay yields a cookie the server can use.")
    args = parser.parse_args()

    try:
        result = await obtain(args.url, args.timeout, args.proxy)
    except Exception as e:
        print(f"[ERROR] {e}", file=sys.stderr, flush=True)
        return 1

    sys.stdout.write(json.dumps(result) + "\n")
    sys.stdout.flush()
    return 0


if __name__ == "__main__":
    sys.exit(asyncio.run(main()))
