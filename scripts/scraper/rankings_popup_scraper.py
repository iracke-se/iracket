#!/usr/bin/env python3
"""
Profixio Rankings Popup Scraper

Scrapes player rankings and matches from profixio.com using Playwright.
All pagination pages are discovered upfront; at most --concurrency pages are
open at any time, and each open page walks its players one popup at a time
with a short pause between them. This keeps the request rate low enough that
profixio does not throttle the session (it started doing so in Aug 2026:
after a burst of requests it hangs or serves empty list pages).

Throttle handling:
  * an empty list page or a failed popup/reload is retried with growing
    backoff instead of being skipped;
  * a run of consecutive failures pauses the whole scrape for a cooldown;
  * if the cooldown does not help the run aborts with success=false.

Usage:
    python3 rankings_popup_scraper.py --year 2025 --month 12 --gender m [--limit 10] [--concurrency 3] [--delay 1.0]

Output:
    NDJSON on stdout: one {"type": "player", "rankings": [...], "matches": [...]}
    line per scraped player (the consumer saves these as they arrive), then a
    final {"type": "summary", ...} line with counts only:
    {
        "success": true,
        "data": {
            "players_discovered": 9300,
            "players_processed": 9280,
            "players_failed": 20,
            "pages_failed": 0,
            "coverage": 0.998,
            "rankings_count": 9280,
            "matches_count": 4200
        },
        "errors": []
    }
    success is false (and the exit code 1) when coverage falls below
    MIN_COVERAGE — a green run with a handful of players is worse than a
    red one because nothing downstream notices.
"""

import argparse
import asyncio
import json
import os
import sys
import re
from typing import List, Dict, Optional, Tuple
from playwright.async_api import async_playwright, Page, Browser, BrowserContext


# Identify as a regular desktop Chrome — profixio 403s the HeadlessChrome UA.
USER_AGENT = os.environ.get("SCRAPER_USER_AGENT") or "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
# Discovery navigations (month dropdown, first page) must fail fast instead of
# hanging forever when the site blocks us or changes its markup.
PAGE_LOAD_TIMEOUT_MS = 120_000

# --- Pacing / throttle handling ---------------------------------------------
# Seconds to sleep between two popups on the same tab.
DEFAULT_POPUP_DELAY_S = 1.0
# Backoff (seconds) before retrying a failed list page load, popup or reload.
RETRY_BACKOFF_S = [30, 60, 120]
# After this many consecutive failures across all tabs, pause everything.
CONSECUTIVE_FAILURE_THRESHOLD = 8
# How long a global cooldown lasts, and how many we allow before aborting.
COOLDOWN_S = 300
MAX_COOLDOWNS = 2
# Below this fraction of discovered players the run is reported as failed.
MIN_COVERAGE = 0.90


class RankingsScraperConfig:
    """Configuration for scraper run"""

    def __init__(self, year: str, month: str, gender: str, limit_players: Optional[int] = None,
                 concurrency: int = 3, popup_delay: float = DEFAULT_POPUP_DELAY_S):
        self.year = year
        self.month = month
        self.gender = gender  # 'm' or 'k'
        self.limit_players = limit_players
        self.concurrency = max(1, concurrency)
        self.popup_delay = max(0.0, popup_delay)
        self.base_url = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php"

    def get_rankings_url(self, rid: str, from_offset: int = 0) -> str:
        url = f"{self.base_url}?gender={self.gender}&rid={rid}"
        if from_offset > 0:
            url += f"&from={from_offset}"
        return url


class ScrapeAborted(Exception):
    """Raised when the throttle circuit breaker gives up on the run."""


class RankingsScraper:
    """Main scraper class"""

    def __init__(self, config: RankingsScraperConfig):
        self.config = config
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.errors: List[Dict] = []
        self._errors_lock = asyncio.Lock()
        self._total_processed = 0
        self._processed_lock = asyncio.Lock()
        self._stdout_lock = asyncio.Lock()

        # Coverage bookkeeping — what the list pages promised vs what we got.
        self._players_discovered = 0
        self._players_failed = 0
        self._pages_failed = 0
        self._seen_player_ids: set = set()

        # Throttle circuit breaker. _resume is cleared while a cooldown is in
        # progress; every network step waits on it first so one blocked tab
        # pauses all of them rather than the rest hammering the site.
        self._consecutive_failures = 0
        self._cooldowns_used = 0
        self._breaker_lock = asyncio.Lock()
        self._resume = asyncio.Event()
        self._resume.set()
        self._abort_reason: Optional[str] = None

    # -------------------------------------------------------------------------
    # Throttle circuit breaker
    # -------------------------------------------------------------------------

    async def _wait_if_paused(self) -> None:
        """Block while a global cooldown is in progress; raise once aborted."""
        if self._abort_reason:
            raise ScrapeAborted(self._abort_reason)
        await self._resume.wait()
        if self._abort_reason:
            raise ScrapeAborted(self._abort_reason)

    async def _note_success(self) -> None:
        async with self._breaker_lock:
            self._consecutive_failures = 0

    async def _note_failure(self, what: str) -> None:
        """Count a failed network step. Trips a cooldown after a streak of them,
        and aborts the run if cooldowns keep failing to help."""
        async with self._breaker_lock:
            self._consecutive_failures += 1
            streak = self._consecutive_failures
            if streak < CONSECUTIVE_FAILURE_THRESHOLD or not self._resume.is_set():
                return

            if self._cooldowns_used >= MAX_COOLDOWNS:
                self._abort_reason = (
                    f"profixio is still throttling after {MAX_COOLDOWNS} cooldowns "
                    f"({streak} consecutive failures, last: {what}) — aborting run"
                )
                log_error(self._abort_reason)
                self._resume.set()  # release waiters so they see the abort
                return

            self._cooldowns_used += 1
            self._resume.clear()
            log_error(
                f"{streak} consecutive failures (last: {what}) — profixio appears to be "
                f"throttling. Pausing all tabs for {COOLDOWN_S}s "
                f"(cooldown {self._cooldowns_used}/{MAX_COOLDOWNS})"
            )

        # Sleep outside the lock so successful tabs can still record results.
        await asyncio.sleep(COOLDOWN_S)
        async with self._breaker_lock:
            self._consecutive_failures = 0
            self._resume.set()
        log_info("Cooldown over — resuming")

    async def _record_player_failure(self, player: Dict, offset: int, error: str) -> None:
        async with self._errors_lock:
            self.errors.append({"player": player['name'], "page_offset": offset, "error": error})
        async with self._processed_lock:
            self._players_failed += 1

    async def run(self) -> Dict:
        """Execute scraping workflow"""
        async with async_playwright() as p:
            # Resolve Chromium executable
            chrome_path = os.environ.get('PUPPETEER_EXECUTABLE_PATH', None)
            if not chrome_path or not os.path.exists(chrome_path):
                for candidate in ['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome']:
                    if os.path.exists(candidate):
                        chrome_path = candidate
                        break
                else:
                    chrome_path = None

            launch_args = {
                'headless': True,
                'args': [
                    '--disable-blink-features=AutomationControlled',
                    '--disable-dev-shm-usage',
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-features=NetworkServiceInProcess',
                    '--no-zygote',
                ]
            }
            if chrome_path:
                launch_args['executable_path'] = chrome_path
                log_info(f"Using system Chromium: {chrome_path}")

            self.browser = await p.chromium.launch(**launch_args)
            # profixio answers the default HeadlessChrome UA with a bare
            # "403 Request forbidden by administrative rules" page.
            self.context = await self.browser.new_context(user_agent=USER_AGENT)

            # Dedicated discovery tab — only used for RID lookup + pagination discovery
            discovery_page = await self.context.new_page()

            try:
                # Step 1: Resolve the RID for the requested month
                rid = await self.get_rid_for_month(discovery_page)
                log_info(f"Found rid={rid} for {self.config.year}-{self.config.month}")

                # Step 2: Discover all pagination page offsets from the first page
                page_offsets = await self.discover_page_offsets(discovery_page, rid)
                log_info(f"Found {len(page_offsets)} pagination pages: {page_offsets}")

                await discovery_page.close()

                # Step 3: Walk the pages, at most `concurrency` open at a time
                log_info(
                    f"Processing with concurrency={self.config.concurrency}, "
                    f"popup delay={self.config.popup_delay}s"
                )
                all_rankings, all_matches = await self._process_all_pages(rid, page_offsets)

                return self._build_summary(all_rankings, all_matches)

            except ScrapeAborted as e:
                log_error(f"Aborted: {e}")
                return self._build_summary([], [], fatal=str(e))

            except Exception as e:
                log_error(f"Fatal error: {e}")
                return self._build_summary([], [], fatal=str(e))

            finally:
                await self.browser.close()

    def _build_summary(self, rankings: List[Dict], matches: List[Dict], fatal: Optional[str] = None) -> Dict:
        """Final summary line. The run only counts as a success when it actually
        covered (nearly) every player the list pages advertised."""
        discovered = self._players_discovered
        processed = self._total_processed
        coverage = (processed / discovered) if discovered else (1.0 if not fatal else 0.0)

        errors = list(self.errors)
        success = fatal is None and coverage >= MIN_COVERAGE
        if fatal:
            errors.append({"error": fatal})
        elif not success:
            errors.append({
                "error": f"Coverage {coverage:.1%} is below the required {MIN_COVERAGE:.0%}: "
                         f"processed {processed} of {discovered} discovered players "
                         f"({self._players_failed} players failed, {self._pages_failed} pages failed)"
            })

        log_info(
            f"Scrape complete. Players discovered: {discovered}, processed: {processed}, "
            f"failed: {self._players_failed}, pages failed: {self._pages_failed}, "
            f"coverage: {coverage:.1%} — {'OK' if success else 'FAILED'}"
        )

        return {
            "success": success,
            "data": {
                "players_discovered": discovered,
                "players_processed": processed,
                "players_failed": self._players_failed,
                "pages_failed": self._pages_failed,
                "coverage": round(coverage, 4),
                "rankings_count": len(rankings),
                "matches_count": len(matches),
            },
            "errors": errors,
        }

    # -------------------------------------------------------------------------
    # Pagination discovery
    # -------------------------------------------------------------------------

    async def get_rid_for_month(self, page: Page) -> str:
        """Get ranking ID for target month from dropdown"""
        target_date = f"{self.config.year}.{self.config.month.zfill(2)}."

        url = f"{self.config.base_url}?gender={self.config.gender}"
        response = await page.goto(url, wait_until="domcontentloaded", timeout=PAGE_LOAD_TIMEOUT_MS)
        if response is not None and response.status >= 400:
            raise Exception(f"HTTP {response.status} from {url} — profixio is refusing the request "
                            f"(check SCRAPER_USER_AGENT / IP block)")
        await page.wait_for_selector('select[name="rid"]', timeout=PAGE_LOAD_TIMEOUT_MS)

        select = await page.query_selector('select[name="rid"]')
        if not select:
            raise Exception("Month dropdown not found")

        options = await select.query_selector_all('option')
        available = []

        for option in options:
            text = await option.text_content()
            if text:
                available.append(text.strip())
            if text and text.startswith(target_date):
                rid = await option.get_attribute('value')
                return rid

        raise Exception(f"Month {target_date} not found in dropdown. Available: {available}")

    async def discover_page_offsets(self, page: Page, rid: str) -> List[int]:
        """
        Navigate to the first rankings page and extract all pagination offsets
        from the page number links (e.g. 1, 501, 1001, 1501 ...).
        Returns a sorted list of from= offset values.
        """
        url = self.config.get_rankings_url(rid, 0)
        await page.goto(url, wait_until="domcontentloaded", timeout=PAGE_LOAD_TIMEOUT_MS)

        try:
            await page.wait_for_selector('table tr span.rml_poeng', timeout=PAGE_LOAD_TIMEOUT_MS)
        except Exception:
            log_info("No players found on first page — empty ranking period?")
            return []

        offsets = set()
        offsets.add(0)  # Page 1 always has offset 0

        # Pagination links contain from= in their href
        links = await page.query_selector_all("a[href*='from=']")
        for link in links:
            href = await link.get_attribute('href')
            if href:
                m = re.search(r'from=(\d+)', href)
                if m:
                    offsets.add(int(m.group(1)))

        # profixio links the first page as from=1, which is the same page as
        # the default (from=0). Scraping both doubled the load on the top 500.
        offsets.discard(1)

        return sorted(offsets)

    # -------------------------------------------------------------------------
    # Page processing
    # -------------------------------------------------------------------------

    async def _goto_list_page(self, tab: Page, url: str, label: str) -> bool:
        """Load a rankings list page on `tab`. Returns False when the page came
        back without any player rows — which, for a page the pagination links
        advertised, means profixio is throttling us rather than that the page
        is empty."""
        await tab.goto(url, wait_until="domcontentloaded", timeout=60000)
        try:
            await tab.wait_for_selector('table tr span.rml_poeng', timeout=30000)
            return True
        except Exception:
            log_error(f"[{label}] List page loaded without player rows")
            return False

    async def _process_all_pages(self, rid: str, offsets: List[int]) -> Tuple[List[Dict], List[Dict]]:
        """
        One task per pagination page, but only `concurrency` of them hold a
        slot (and therefore an open tab) at a time. Each page walks its own
        players sequentially, so total in-flight requests never exceed
        `concurrency`.
        """
        page_slots = asyncio.Semaphore(self.config.concurrency)

        async def process_page(offset: int) -> Tuple[List[Dict], List[Dict]]:
            async with page_slots:
                return await self._process_page(rid, offset)

        results = await asyncio.gather(*[process_page(offset) for offset in offsets], return_exceptions=True)

        all_rankings: List[Dict] = []
        all_matches: List[Dict] = []
        for result in results:
            if isinstance(result, ScrapeAborted):
                raise result
            if isinstance(result, Exception):
                log_error(f"Page task raised unhandled exception: {result}")
                continue
            rankings, matches = result
            all_rankings.extend(rankings)
            all_matches.extend(matches)

        if self._abort_reason:
            raise ScrapeAborted(self._abort_reason)

        return all_rankings, all_matches

    async def _process_page(self, rid: str, offset: int) -> Tuple[List[Dict], List[Dict]]:
        """Load one list page, extract its players, then scrape each popup on
        the same tab. The tab stays open for the whole page."""
        url = self.config.get_rankings_url(rid, offset)
        label = f"page from={offset}"
        tab = await self.context.new_page()

        try:
            players = await self._load_players_with_retry(tab, url, label, offset)
            if not players:
                return [], []

            return await self._scrape_players_on_tab(tab, players, url, label, offset)
        finally:
            try:
                await tab.close()
            except Exception:
                pass

    async def _load_players_with_retry(self, tab: Page, url: str, label: str, offset: int) -> List[Dict]:
        """Load the list page and extract players, backing off and retrying when
        it comes back empty or fails. Players already claimed by another page
        (profixio's page boundaries overlap slightly) are dropped here."""
        attempts = len(RETRY_BACKOFF_S) + 1
        for attempt in range(1, attempts + 1):
            await self._wait_if_paused()
            try:
                log_info(f"[{label}] Navigating to {url} (attempt {attempt})")
                if not await self._goto_list_page(tab, url, label):
                    raise Exception("no player rows on list page")

                players = await self._extract_players_from_page(tab)
                if not players:
                    raise Exception("list page parsed to zero players")

                await self._note_success()
                players = await self._claim_players(players)
                log_info(f"[{label}] Extracted {len(players)} players")
                return players

            except ScrapeAborted:
                raise
            except Exception as e:
                await self._note_failure(f"{label}: {e}")
                if attempt < attempts:
                    wait = RETRY_BACKOFF_S[attempt - 1]
                    log_error(f"[{label}] Attempt {attempt}/{attempts} failed: {e} — retrying in {wait}s")
                    await asyncio.sleep(wait)
                else:
                    log_error(f"[{label}] All {attempts} attempts failed — giving up on this page")
                    async with self._errors_lock:
                        self.errors.append({"page_offset": offset, "error": str(e)})
                    async with self._processed_lock:
                        self._pages_failed += 1
        return []

    async def _claim_players(self, players: List[Dict]) -> List[Dict]:
        """Dedupe across pages and apply --limit; counts what we commit to."""
        claimed: List[Dict] = []
        async with self._processed_lock:
            for player in players:
                pid = player['profixio_id']
                if pid in self._seen_player_ids:
                    continue
                if self.config.limit_players and self._players_discovered >= self.config.limit_players:
                    break
                self._seen_player_ids.add(pid)
                self._players_discovered += 1
                claimed.append(player)
        return claimed

    async def _scrape_players_on_tab(
        self,
        tab: Page,
        players: List[Dict],
        page_url: str,
        label: str,
        offset: int,
    ) -> Tuple[List[Dict], List[Dict]]:
        """Walk the page's players one popup at a time. A failed popup is
        retried after a reload with backoff; a player that still fails is
        recorded and skipped so the rest of the page is not lost."""
        page_rankings: List[Dict] = []
        page_matches: List[Dict] = []
        attempts = len(RETRY_BACKOFF_S) + 1

        for player in players:
            for attempt in range(1, attempts + 1):
                await self._wait_if_paused()
                try:
                    rankings, matches = await asyncio.wait_for(
                        self._scrape_player_on_tab(tab, player, offset),
                        timeout=60,
                    )
                    page_rankings.extend(rankings)
                    page_matches.extend(matches)
                    await self._note_success()
                    break

                except ScrapeAborted:
                    raise
                except (asyncio.TimeoutError, Exception) as e:
                    err = "Timed out after 60s" if isinstance(e, asyncio.TimeoutError) else str(e)
                    await self._note_failure(f"{player['name']}: {err}")

                    try:
                        await self._close_popup(tab)
                    except Exception:
                        pass

                    if attempt < attempts:
                        wait = RETRY_BACKOFF_S[attempt - 1]
                        log_error(f"[{label}] {player['name']}: {err} — retrying in {wait}s")
                        await asyncio.sleep(wait)
                        await self._reload_list_page(tab, page_url, label)
                    else:
                        log_error(f"[{label}] {player['name']}: {err} — skipping after {attempts} attempts")
                        await self._record_player_failure(player, offset, err)

            if self.config.popup_delay:
                await asyncio.sleep(self.config.popup_delay)

        return page_rankings, page_matches

    async def _reload_list_page(self, tab: Page, page_url: str, label: str) -> None:
        """Get the tab back to a usable list page after a failed popup. A
        reload that itself fails is just another throttle signal; the next
        popup attempt will find out whether the page is usable."""
        try:
            await self._wait_if_paused()
            if not await self._goto_list_page(tab, page_url, label):
                await self._note_failure(f"{label}: reload came back empty")
        except ScrapeAborted:
            raise
        except Exception as e:
            log_error(f"[{label}] Reload failed: {e}")
            await self._note_failure(f"{label}: reload failed")

    async def _scrape_player_on_tab(self, tab: Page, player: Dict, offset: int) -> Tuple[List[Dict], List[Dict]]:
        """Scrape one player's popup. The page must already be loaded on tab."""
        await self._click_player(tab, player['profixio_id'])
        rankings = await self._scrape_ranking_history(tab, player)
        matches = await self._scrape_matches(tab, player)
        await self._close_popup(tab)

        async with self._processed_lock:
            self._total_processed += 1
            current = self._total_processed

        log_info(
            f"[{current}] Done: {player['name']} (page from={offset}) — "
            f"{len(rankings)} rankings, {len(matches)} matches"
        )
        # Emit immediately so PHP can save without waiting for full page completion
        await self._emit_player(rankings, matches)
        return rankings, matches

    async def _emit_player(self, rankings: List[Dict], matches: List[Dict]) -> None:
        """Write one NDJSON line to stdout immediately when a player is done.
        PHP reads each line as it arrives and saves it — no data lost on Ctrl+C."""
        payload = {"type": "player", "rankings": rankings, "matches": matches}
        async with self._stdout_lock:
            sys.stdout.write(json.dumps(payload) + "\n")
            sys.stdout.flush()

    # -------------------------------------------------------------------------
    # Page helpers — all accept a Page argument, never use a shared page
    # -------------------------------------------------------------------------

    async def _extract_players_from_page(self, page: Page) -> List[Dict]:
        """Extract all player records from the given page in a single JS evaluation.

        Parsing runs inside the browser via page.evaluate() and returns plain
        data, so NO Playwright ElementHandles are held. This avoids the
        "object has been collected to prevent unbounded heap growth" error that
        the old handle-by-handle approach hit under high concurrency (each page
        held ~4,500 handles; with 7 concurrent pages that overflowed Playwright's
        handle ceiling). It is also far faster — one round-trip instead of thousands.
        """
        players = await page.evaluate(
            """
            () => {
                const out = [];
                const rows = document.querySelectorAll("table tr");
                for (const row of rows) {
                    const cells = row.querySelectorAll("td");
                    if (cells.length !== 7) continue;

                    const nameSpan = cells[2].querySelector("span.rml_poeng");
                    if (!nameSpan) continue;

                    const spanId = nameSpan.getAttribute("id") || "";
                    const m = spanId.match(/rml:(\\d+):/);
                    if (!m) continue;

                    const positionText = (cells[0].textContent || "").trim();
                    const posMatch = positionText.match(/\\d+$/);
                    const position = posMatch ? (parseInt(posMatch[0], 10) || 0) : 0;

                    const cleanedPoints = (cells[5].textContent || "").trim()
                        .replace(/ /g, "").replace(/\\./g, "").replace(/,/g, "");
                    const points = cleanedPoints ? (parseInt(cleanedPoints, 10) || 0) : 0;

                    out.push({
                        profixio_id: m[1],
                        name: (nameSpan.textContent || "").trim(),
                        born: (cells[3].textContent || "").trim(),
                        club: (cells[4].textContent || "").trim(),
                        position: position,
                        points: points,
                        span_id: spanId,
                    });
                }
                return out;
            }
            """
        )
        return players

    async def _click_player(self, page: Page, player_id: str):
        """Click the player's span on the given tab to open the popup"""
        await page.evaluate(f"""
            () => {{
                const span = document.querySelector("span.rml_poeng[id*='rml:{player_id}:']");
                if (span) {{
                    span.click();
                }} else {{
                    throw new Error('Player span not found for id {player_id}');
                }}
            }}
        """)
        await page.wait_for_selector("#multipurpose", state="visible", timeout=15000)
        # Wait for actual table content inside popup, not just the container appearing
        await page.wait_for_selector("#multipurpose table tr", timeout=10000)

    async def _scrape_ranking_history(self, page: Page, player: Dict) -> List[Dict]:
        """Read ranking data for the target month from the popup"""
        target_date = f"{self.config.year}-{self.config.month.zfill(2)}"

        popup = await page.query_selector("#multipurpose")
        if not popup:
            raise Exception("Popup not found")

        rows = await popup.query_selector_all("table tr")
        for row in rows:
            cells = await row.query_selector_all("td")
            if len(cells) < 4:
                continue

            date_text = await cells[0].text_content()
            if not date_text.strip().startswith(target_date):
                continue

            points_span = await cells[1].query_selector("span.rmld_poeng")
            if not points_span:
                continue

            points_text = await points_span.text_content()
            rmld_id = await points_span.get_attribute('id')
            position_text = await cells[2].text_content()
            points_diff_text = await cells[3].text_content()

            cleaned_points = points_text.strip().replace(' ', '').replace('.', '').replace(',', '')
            cleaned_position = position_text.strip()

            return [{
                "profixio_player_id": player['profixio_id'],
                "player_name": player['name'],
                "born": player.get('born', ''),
                "club": player.get('club', ''),
                "ranking_date": date_text.strip(),
                "points": int(cleaned_points) if cleaned_points else 0,
                "position": int(cleaned_position) if cleaned_position else 0,
                "points_diff": points_diff_text.strip(),
                "rmld_id": rmld_id,
            }]

        # No row for this month — player has no ranking this period
        return []

    async def _scrape_matches(self, page: Page, player: Dict) -> List[Dict]:
        """Click the month's points span to load matches, then scrape them"""
        target_date = f"{self.config.year}-{self.config.month.zfill(2)}"

        popup = await page.query_selector("#multipurpose")
        rows = await popup.query_selector_all("table tr")

        points_span = None
        for row in rows:
            cells = await row.query_selector_all("td")
            if len(cells) < 2:
                continue

            date_text = await cells[0].text_content()
            if date_text.strip().startswith(target_date):
                points_span = await cells[1].query_selector("span.rmld_poeng")
                if points_span:
                    span_id = await points_span.get_attribute("id")
                    await page.evaluate(f"""
                        () => {{
                            const span = document.getElementById('{span_id}');
                            if (span) span.click();
                        }}
                    """)
                    # Wait for match rows (W/L) to appear, fall back to fixed wait if none
                    try:
                        await page.wait_for_function(
                            """() => {
                                const rows = document.querySelectorAll('#multipurpose table tr td:first-child');
                                return Array.from(rows).some(td => td.textContent.trim() === 'W' || td.textContent.trim() === 'L');
                            }""",
                            timeout=5000,
                        )
                    except Exception:
                        await page.wait_for_timeout(700)
                    break

        if not points_span:
            # Player has no matches this month
            return []

        matches = []
        seen: set = set()
        table = await popup.query_selector("table")
        rows = await table.query_selector_all(":scope > tbody > tr, :scope > tr") if table else []

        for row in rows:
            cells = await row.query_selector_all(":scope > td")
            if len(cells) < 5:
                continue

            result = (await cells[0].text_content()).strip()
            if result not in ['W', 'L']:
                continue

            opponent_name = (await cells[1].text_content()).strip()
            opponent_points = (await cells[2].text_content()).strip()
            match_points = (await cells[3].text_content()).strip()
            match_date = (await cells[4].text_content()).strip()

            key = (match_date, opponent_name, result)
            if key in seen:
                continue
            seen.add(key)

            cleaned_opp = opponent_points.replace('+', '').replace(' ', '').replace('.', '').replace(',', '')
            cleaned_mp = match_points.replace('+', '').replace(' ', '').replace('.', '').replace(',', '')

            matches.append({
                "profixio_player_id": player['profixio_id'],
                "player_name": player['name'],
                "result": result,
                "opponent_name": opponent_name,
                "opponent_points": int(cleaned_opp) if cleaned_opp else 0,
                "match_points": int(cleaned_mp) if cleaned_mp else 0,
                "match_date": match_date,
                "scraped_month": target_date,
            })

        await self._click_back(page)
        return matches

    async def _click_back(self, page: Page):
        """Click the Tilbake (back) button inside the popup"""
        await page.evaluate("""
            () => {
                const btn = Array.from(document.querySelectorAll('button'))
                    .find(b => b.textContent.includes('Tilbake'));
                if (btn) btn.click();
            }
        """)
        # Wait for ranking history rows to be restored
        try:
            await page.wait_for_selector("#multipurpose table tr td span.rmld_poeng", timeout=5000)
        except Exception:
            await page.wait_for_timeout(500)

    async def _close_popup(self, page: Page):
        """Close the popup by clicking the Stäng (close) button"""
        await page.evaluate("""
            () => {
                const btn = Array.from(document.querySelectorAll('button'))
                    .find(b => b.textContent.includes('Stäng'));
                if (btn) btn.click();
            }
        """)
        # Confirm popup actually closed before moving to next player
        try:
            await page.wait_for_selector("#multipurpose", state="hidden", timeout=5000)
        except Exception:
            await page.wait_for_timeout(500)


# ---------------------------------------------------------------------------
# Logging helpers — write to stderr so stdout stays clean JSON
# ---------------------------------------------------------------------------

def log_info(message: str):
    print(f"[INFO] {message}", file=sys.stderr, flush=True)


def log_error(message: str):
    print(f"[ERROR] {message}", file=sys.stderr, flush=True)


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

async def main() -> int:
    parser = argparse.ArgumentParser(description="Scrape rankings from profixio.com")
    parser.add_argument('--year', required=True, help='Year (e.g., 2025)')
    parser.add_argument('--month', required=True, help='Month (e.g., 12)')
    parser.add_argument('--gender', required=True, choices=['m', 'k'], help='Gender: m=male, k=female')
    parser.add_argument('--limit', type=int, help='Limit number of players (for testing)')
    parser.add_argument('--concurrency', type=int, default=3,
                        help='Max pages open (= max in-flight requests) at once (default: 3)')
    parser.add_argument('--delay', type=float, default=DEFAULT_POPUP_DELAY_S,
                        help=f'Seconds to pause between popups on a tab (default: {DEFAULT_POPUP_DELAY_S})')

    args = parser.parse_args()

    config = RankingsScraperConfig(
        year=args.year,
        month=args.month,
        gender=args.gender,
        limit_players=args.limit,
        concurrency=args.concurrency,
        popup_delay=args.delay,
    )

    scraper = RankingsScraper(config)
    result = await scraper.run()

    result["type"] = "summary"
    sys.stdout.write(json.dumps(result) + "\n")
    sys.stdout.flush()

    # A non-zero exit makes the PHP side mark the run as failed instead of
    # "completed" with a handful of players.
    return 0 if result["success"] else 1


if __name__ == "__main__":
    sys.exit(asyncio.run(main()))
