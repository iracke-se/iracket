#!/usr/bin/env python3
"""
Profixio Rankings Popup Scraper

Scrapes player rankings and matches from profixio.com using Playwright.
All pagination pages are discovered upfront and processed in parallel —
each page gets its own browser tab. Within each page, players are also
processed in parallel, controlled by a single global semaphore.

Usage:
    python3 rankings_popup_scraper.py --year 2025 --month 12 --gender m [--limit 10] [--concurrency 10]

Output:
    JSON to stdout with structure:
    {
        "success": true,
        "data": {
            "players_processed": 15,
            "rankings_count": 180,
            "matches_count": 42,
            "rankings": [...],
            "matches": [...]
        },
        "errors": []
    }
"""

import argparse
import asyncio
import json
import os
import sys
import re
from typing import List, Dict, Optional, Tuple
from playwright.async_api import async_playwright, Page, Browser, BrowserContext


class RankingsScraperConfig:
    """Configuration for scraper run"""

    def __init__(self, year: str, month: str, gender: str, limit_players: Optional[int] = None, concurrency: int = 10):
        self.year = year
        self.month = month
        self.gender = gender  # 'm' or 'k'
        self.limit_players = limit_players
        self.concurrency = concurrency
        self.base_url = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php"

    def get_rankings_url(self, rid: str, from_offset: int = 0) -> str:
        url = f"{self.base_url}?gender={self.gender}&rid={rid}"
        if from_offset > 0:
            url += f"&from={from_offset}"
        return url


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
            self.context = await self.browser.new_context()

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

                # Step 3: Process all pages in parallel with a single global semaphore
                semaphore = asyncio.Semaphore(self.config.concurrency)
                all_rankings, all_matches = await self._process_all_pages_parallel(rid, page_offsets, semaphore)

                log_info(f"Scrape complete. Total players processed: {self._total_processed}")

                return {
                    "success": True,
                    "data": {
                        "players_processed": self._total_processed,
                        "rankings_count": len(all_rankings),
                        "matches_count": len(all_matches),
                        "rankings": all_rankings,
                        "matches": all_matches
                    },
                    "errors": self.errors
                }

            except Exception as e:
                log_error(f"Fatal error: {e}")
                return {
                    "success": False,
                    "data": {
                        "players_processed": 0,
                        "rankings_count": 0,
                        "matches_count": 0,
                        "rankings": [],
                        "matches": []
                    },
                    "errors": [{"error": str(e)}]
                }

            finally:
                await self.browser.close()

    # -------------------------------------------------------------------------
    # Pagination discovery
    # -------------------------------------------------------------------------

    async def get_rid_for_month(self, page: Page) -> str:
        """Get ranking ID for target month from dropdown"""
        target_date = f"{self.config.year}.{self.config.month.zfill(2)}."

        url = f"{self.config.base_url}?gender={self.config.gender}"
        await page.goto(url, wait_until="domcontentloaded", timeout=0)
        await page.wait_for_selector('select[name="rid"]', timeout=0)

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
        await page.goto(url, wait_until="domcontentloaded", timeout=0)

        try:
            await page.wait_for_selector('table tr span.rml_poeng', timeout=0)
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

        return sorted(offsets)

    # -------------------------------------------------------------------------
    # Parallel page processing
    # -------------------------------------------------------------------------

    async def _process_all_pages_parallel(
        self,
        rid: str,
        offsets: List[int],
        semaphore: asyncio.Semaphore,
    ) -> Tuple[List[Dict], List[Dict]]:
        """
        Launch one task per pagination page, all running simultaneously.
        Each page task opens its own browser tab, extracts its player list,
        then fans out into per-player popup tasks (bounded by semaphore).
        """

        # Limit concurrent page navigations with same semaphore to avoid rate-limiting
        page_semaphore = asyncio.Semaphore(self.config.concurrency)

        async def process_page(offset: int) -> Tuple[List[Dict], List[Dict]]:
            url = self.config.get_rankings_url(rid, offset)
            max_attempts = 3
            players_to_process = None

            for attempt in range(1, max_attempts + 1):
                async with page_semaphore:
                    tab = await self.context.new_page()
                    try:
                        if attempt > 1:
                            wait = 5 * attempt
                            log_info(f"[page from={offset}] Retry {attempt}/{max_attempts} — waiting {wait}s")
                            await asyncio.sleep(wait)

                        log_info(f"[page from={offset}] Navigating to {url} (attempt {attempt})")
                        await tab.goto(url, wait_until="domcontentloaded", timeout=60000)

                        try:
                            await tab.wait_for_selector('table tr span.rml_poeng', timeout=30000)
                        except Exception:
                            log_info(f"[page from={offset}] No players on this page — skipping")
                            return [], []

                        players = await self._extract_players_from_page(tab)
                        log_info(f"[page from={offset}] Extracted {len(players)} players")

                        if not players:
                            return [], []

                        # Respect global player limit — slice before processing
                        if self.config.limit_players:
                            async with self._processed_lock:
                                remaining = self.config.limit_players - self._total_processed
                                if remaining <= 0:
                                    return [], []
                                players = players[:remaining]

                        players_to_process = players
                        break

                    except Exception as e:
                        log_error(f"[page from={offset}] Attempt {attempt}/{max_attempts} failed: {e}")
                        if attempt == max_attempts:
                            log_error(f"[page from={offset}] All {max_attempts} attempts failed — skipping page")
                            async with self._errors_lock:
                                self.errors.append({"page_offset": offset, "error": str(e)})
                            return [], []
                    finally:
                        try:
                            await tab.close()
                        except Exception:
                            pass

            if not players_to_process:
                return [], []

            # Each page gets its own semaphore so all active pages process players
            # concurrently rather than sharing a single global slot pool.
            per_page_semaphore = asyncio.Semaphore(self.config.concurrency)
            return await self._process_players_parallel(players_to_process, url, per_page_semaphore, offset)

        results = await asyncio.gather(*[process_page(offset) for offset in offsets], return_exceptions=True)

        all_rankings: List[Dict] = []
        all_matches: List[Dict] = []
        for result in results:
            if isinstance(result, Exception):
                log_error(f"Page task raised unhandled exception: {result}")
                continue
            rankings, matches = result
            all_rankings.extend(rankings)
            all_matches.extend(matches)

        return all_rankings, all_matches

    # -------------------------------------------------------------------------
    # Parallel player processing (per page)
    # -------------------------------------------------------------------------

    async def _process_players_parallel(
        self,
        players: List[Dict],
        page_url: str,
        semaphore: asyncio.Semaphore,
        offset: int,
    ) -> Tuple[List[Dict], List[Dict]]:
        """
        Process all players using a pool of worker tabs.
        Each worker loads the page ONCE and processes its assigned players
        sequentially — no per-player page reloads.
        1 worker × 10 active pages = 10 concurrent tabs total.
        """
        NUM_WORKERS = 1
        actual_workers = min(NUM_WORKERS, len(players))

        # Distribute players round-robin across workers
        worker_assignments: List[List[Dict]] = [[] for _ in range(actual_workers)]
        for i, player in enumerate(players):
            worker_assignments[i % actual_workers].append(player)

        async def run_worker(assigned_players: List[Dict]) -> Tuple[List[Dict], List[Dict]]:
            tab = await self.context.new_page()
            worker_rankings: List[Dict] = []
            worker_matches: List[Dict] = []

            try:
                await tab.goto(page_url, wait_until="domcontentloaded", timeout=60000)
                await tab.wait_for_selector('table tr span.rml_poeng', timeout=30000)

                for player in assigned_players:
                    for attempt in range(1, 3):
                        try:
                            rankings, matches = await asyncio.wait_for(
                                self._scrape_player_on_tab(tab, player, offset),
                                timeout=60,
                            )
                            worker_rankings.extend(rankings)
                            worker_matches.extend(matches)
                            break

                        except (asyncio.TimeoutError, Exception) as e:
                            is_timeout = isinstance(e, asyncio.TimeoutError)
                            label = "Timed out" if is_timeout else "Error"

                            try:
                                await self._close_popup(tab)
                            except Exception:
                                pass

                            if attempt < 2:
                                log_error(f"{label}: {player['name']} — will retry")
                                try:
                                    await tab.reload(wait_until="domcontentloaded", timeout=30000)
                                    await tab.wait_for_selector('table tr span.rml_poeng', timeout=15000)
                                except Exception as reload_err:
                                    log_error(f"Tab reload failed: {reload_err} — stopping worker")
                                    return worker_rankings, worker_matches
                            else:
                                err_msg = "Timed out after 60s" if is_timeout else str(e)
                                async with self._errors_lock:
                                    self.errors.append({"player": player['name'], "error": err_msg})
                                log_error(f"{label}: {player['name']} — skipping after 2 attempts")

            except Exception as e:
                log_error(f"Worker failed for page from={offset}: {e}")
            finally:
                try:
                    await tab.close()
                except Exception:
                    pass

            return worker_rankings, worker_matches

        results = await asyncio.gather(*[run_worker(assigned) for assigned in worker_assignments])

        all_rankings: List[Dict] = []
        all_matches: List[Dict] = []
        for rankings, matches in results:
            all_rankings.extend(rankings)
            all_matches.extend(matches)

        return all_rankings, all_matches

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

async def main():
    parser = argparse.ArgumentParser(description="Scrape rankings from profixio.com")
    parser.add_argument('--year', required=True, help='Year (e.g., 2025)')
    parser.add_argument('--month', required=True, help='Month (e.g., 12)')
    parser.add_argument('--gender', required=True, choices=['m', 'k'], help='Gender: m=male, k=female')
    parser.add_argument('--limit', type=int, help='Limit number of players (for testing)')
    parser.add_argument('--concurrency', type=int, default=10,
                        help='Max parallel browser tabs per batch (default: 10)')

    args = parser.parse_args()

    config = RankingsScraperConfig(
        year=args.year,
        month=args.month,
        gender=args.gender,
        limit_players=args.limit,
        concurrency=args.concurrency,
    )

    scraper = RankingsScraper(config)
    result = await scraper.run()

    result["type"] = "summary"
    sys.stdout.write(json.dumps(result) + "\n")
    sys.stdout.flush()


if __name__ == "__main__":
    asyncio.run(main())
