#!/usr/bin/env python3
"""
Profixio Rankings Popup Scraper

Scrapes player rankings and matches from profixio.com using Playwright.
Processes players in parallel — each player gets its own browser tab,
controlled by a semaphore to avoid overloading the server.

No timeouts are set anywhere — the scraper runs until completion.

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
        self.page: Optional[Page] = None  # used only for RID lookup and player-list extraction
        self.errors: List[Dict] = []
        self._errors_lock = asyncio.Lock()

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
                ]
            }
            if chrome_path:
                launch_args['executable_path'] = chrome_path
                log_info(f"Using system Chromium: {chrome_path}")

            self.browser = await p.chromium.launch(**launch_args)
            self.context = await self.browser.new_context()

            # Main page — used only for navigation between pagination pages and player-list extraction
            self.page = await self.context.new_page()
            self.page.set_default_timeout(0)  # no timeout

            try:
                # Step 1: Resolve the RID for the requested month
                rid = await self.get_rid_for_month()
                log_info(f"Found rid={rid} for {self.config.year}-{self.config.month}")

                # Step 2: Navigate to the first rankings page
                await self.navigate_to_rankings(rid)

                all_rankings: List[Dict] = []
                all_matches: List[Dict] = []
                total_processed = 0
                from_offset = 0
                page_num = 1

                # One semaphore shared across all pages so concurrency is a global cap
                semaphore = asyncio.Semaphore(self.config.concurrency)

                while True:
                    log_info(f"Extracting player list from pagination page {page_num} (from={from_offset})")
                    players = await self._extract_players_from_current_page()
                    log_info(f"Pagination page {page_num}: found {len(players)} players")

                    if not players:
                        log_info("No players on this page — pagination complete")
                        break

                    # Apply global player limit
                    if self.config.limit_players:
                        remaining = self.config.limit_players - total_processed
                        if remaining <= 0:
                            break
                        players = players[:remaining]

                    # URL of this pagination page — every worker tab navigates here to find the player span
                    current_page_url = self.config.get_rankings_url(rid, from_offset)

                    log_info(
                        f"Processing {len(players)} players in parallel "
                        f"(concurrency={self.config.concurrency}, url={current_page_url})"
                    )

                    page_rankings, page_matches = await self._process_players_parallel(
                        players, current_page_url, semaphore, offset=total_processed
                    )

                    all_rankings.extend(page_rankings)
                    all_matches.extend(page_matches)
                    total_processed += len(players)

                    log_info(f"Pagination page {page_num} done — total processed so far: {total_processed}")

                    if self.config.limit_players and total_processed >= self.config.limit_players:
                        break

                    # Profixio pagination is 1-based: 1, 501, 1001, …
                    from_offset = total_processed + 1
                    next_url = self.config.get_rankings_url(rid, from_offset)
                    log_info(f"Navigating main page to: {next_url}")
                    await self.page.goto(next_url, wait_until="domcontentloaded", timeout=0)
                    try:
                        await self.page.wait_for_selector('table tr span.rml_poeng', timeout=0)
                    except Exception:
                        log_info("No players found on next page — all pages done")
                        break

                    page_num += 1

                log_info(f"Scrape complete. Total players processed: {total_processed}")

                return {
                    "success": True,
                    "data": {
                        "players_processed": total_processed,
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
    # Parallel worker
    # -------------------------------------------------------------------------

    async def _process_players_parallel(
        self,
        players: List[Dict],
        page_url: str,
        semaphore: asyncio.Semaphore,
        offset: int,
    ) -> Tuple[List[Dict], List[Dict]]:
        """
        Process every player in the list concurrently.
        Each player gets its own browser tab that:
          1. Navigates to page_url (the same pagination page as the main page)
          2. Finds and clicks the player's span to open the popup
          3. Scrapes ranking + matches from the popup
          4. Closes the tab

        Errors for individual players are caught inside process_one and recorded
        without aborting the rest of the batch.
        """

        async def process_one(player: Dict, idx: int) -> Tuple[List[Dict], List[Dict]]:
            async with semaphore:
                log_info(f"[{offset + idx + 1}] Starting: {player['name']}")
                tab = await self.context.new_page()
                tab.set_default_timeout(0)  # no timeout on worker tabs
                try:
                    await tab.goto(page_url, wait_until="domcontentloaded", timeout=0)
                    await tab.wait_for_selector('table tr span.rml_poeng', timeout=0)

                    await self._click_player(tab, player['profixio_id'])
                    rankings = await self._scrape_ranking_history(tab, player)
                    matches = await self._scrape_matches(tab, player)
                    await self._close_popup(tab)

                    log_info(
                        f"[{offset + idx + 1}] Done: {player['name']} — "
                        f"{len(rankings)} rankings, {len(matches)} matches"
                    )
                    return rankings, matches

                except Exception as e:
                    async with self._errors_lock:
                        self.errors.append({"player": player['name'], "error": str(e)})
                    log_error(f"[{offset + idx + 1}] Error for {player['name']}: {e}")
                    try:
                        await self._close_popup(tab)
                    except Exception:
                        pass
                    return [], []

                finally:
                    try:
                        await tab.close()
                    except Exception:
                        pass

        results = await asyncio.gather(*[process_one(p, i) for i, p in enumerate(players)])

        all_rankings: List[Dict] = []
        all_matches: List[Dict] = []
        for rankings, matches in results:
            all_rankings.extend(rankings)
            all_matches.extend(matches)

        return all_rankings, all_matches

    # -------------------------------------------------------------------------
    # Navigation helpers — use self.page (main page only)
    # -------------------------------------------------------------------------

    async def get_rid_for_month(self) -> str:
        """Get ranking ID for target month from dropdown"""
        target_date = f"{self.config.year}.{self.config.month.zfill(2)}."

        url = f"{self.config.base_url}?gender={self.config.gender}"
        await self.page.goto(url, wait_until="domcontentloaded", timeout=0)
        await self.page.wait_for_selector('select[name="rid"]', timeout=0)

        select = await self.page.query_selector('select[name="rid"]')
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

    async def navigate_to_rankings(self, rid: str):
        """Navigate the main page to the first rankings page"""
        url = self.config.get_rankings_url(rid)
        await self.page.goto(url, wait_until="domcontentloaded", timeout=0)
        try:
            await self.page.wait_for_selector('table tr span.rml_poeng', timeout=0)
        except Exception:
            log_info("No span.rml_poeng found — rankings table may be empty for this period/gender")

    async def _extract_players_from_current_page(self) -> List[Dict]:
        """Extract all player records from the main page's current pagination page"""
        players = []
        rows = await self.page.query_selector_all("table tr")

        for row in rows:
            cells = await row.query_selector_all("td")
            if len(cells) != 7:
                continue

            name_span = await cells[2].query_selector("span.rml_poeng")
            if not name_span:
                continue

            player_name = await name_span.text_content()
            span_id = await name_span.get_attribute('id')

            m = re.search(r'rml:(\d+):', span_id)
            if not m:
                continue

            position_text = await cells[0].text_content()
            born_text = await cells[3].text_content()
            club_text = await cells[4].text_content()
            points_text = await cells[5].text_content()

            pos_match = re.search(r'\d+$', position_text.strip())
            position = int(pos_match.group(0)) if pos_match else 0

            cleaned_points = points_text.strip().replace(' ', '').replace('.', '').replace(',', '')
            points = int(cleaned_points) if cleaned_points else 0

            players.append({
                "profixio_id": m.group(1),
                "name": player_name.strip(),
                "born": born_text.strip(),
                "club": club_text.strip(),
                "position": position,
                "points": points,
                "span_id": span_id,
            })

        return players

    # -------------------------------------------------------------------------
    # Per-tab popup helpers — all accept a Page argument, never use self.page
    # -------------------------------------------------------------------------

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
        await page.wait_for_selector("#multipurpose", state="visible", timeout=0)
        # Allow AJAX content inside popup to finish rendering
        await page.wait_for_timeout(500)

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

    print(json.dumps(result, indent=2))


if __name__ == "__main__":
    asyncio.run(main())
