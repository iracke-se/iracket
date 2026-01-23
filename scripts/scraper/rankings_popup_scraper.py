#!/usr/bin/env python3
"""
Profixio Rankings Popup Scraper

Scrapes player rankings and matches from profixio.com using Playwright.
Maintains browser context to handle popup interactions.

Usage:
    python3 rankings_popup_scraper.py --year 2025 --month 12 --gender m [--limit 10]

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
import sys
import re
from typing import List, Dict, Optional
from playwright.async_api import async_playwright, Page, Browser, BrowserContext


class RankingsScraperConfig:
    """Configuration for scraper run"""

    def __init__(self, year: str, month: str, gender: str, limit_players: Optional[int] = None):
        self.year = year
        self.month = month
        self.gender = gender  # 'm' or 'k'
        self.limit_players = limit_players
        self.base_url = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php"
        self.timeout = 60000  # 60 seconds

    def get_rankings_url(self, rid: str) -> str:
        return f"{self.base_url}?gender={self.gender}&rid={rid}"


class RankingsScraper:
    """Main scraper class"""

    def __init__(self, config: RankingsScraperConfig):
        self.config = config
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.page: Optional[Page] = None
        self.errors: List[Dict] = []

    async def run(self) -> Dict:
        """Execute scraping workflow"""
        async with async_playwright() as p:
            # Launch browser (headless in production)
            self.browser = await p.chromium.launch(headless=True)
            self.context = await self.browser.new_context()
            self.page = await self.context.new_page()

            try:
                # Step 1: Get RID for target month
                rid = await self.get_rid_for_month()
                log_info(f"Found rid={rid} for {self.config.year}-{self.config.month}")

                # Step 2: Navigate to rankings page
                await self.navigate_to_rankings(rid)

                # Step 3: Extract players from table
                players = await self.extract_players_from_table()
                log_info(f"Found {len(players)} players")

                # Apply limit
                if self.config.limit_players:
                    players = players[:self.config.limit_players]
                    log_info(f"Limited to {len(players)} players")

                # Step 4: Process each player
                all_rankings = []
                all_matches = []

                for idx, player in enumerate(players):
                    log_info(f"Processing {idx+1}/{len(players)}: {player['name']}")

                    try:
                        # Click player name and wait for popup
                        await self.click_player_name(player['profixio_id'])

                        # Scrape ranking history
                        rankings = await self.scrape_ranking_history(player)
                        all_rankings.extend(rankings)

                        # Scrape matches for current month
                        matches = await self.scrape_matches_for_month(player)
                        all_matches.extend(matches)

                        # Close popup
                        await self.close_popup()

                    except Exception as e:
                        self.errors.append({
                            "player": player['name'],
                            "error": str(e)
                        })
                        log_error(f"Error processing {player['name']}: {e}")
                        # Try to close popup if stuck
                        try:
                            await self.close_popup()
                        except:
                            pass

                return {
                    "success": True,
                    "data": {
                        "players_processed": len(players),
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

    async def get_rid_for_month(self) -> str:
        """Get ranking ID for target month from dropdown"""
        target_date = f"{self.config.year}.{self.config.month.zfill(2)}.01"

        # Navigate to page without rid
        url = f"{self.config.base_url}?gender={self.config.gender}"
        await self.page.goto(url, wait_until="networkidle")

        # Find select element and get options
        select = await self.page.query_selector('select[name="rid"]')
        if not select:
            raise Exception("Month dropdown not found")

        # Get all options
        options = await select.query_selector_all('option')

        for option in options:
            text = await option.text_content()
            if text and text.startswith(target_date):
                rid = await option.get_attribute('value')
                return rid

        raise Exception(f"Month {target_date} not found in dropdown")

    async def navigate_to_rankings(self, rid: str):
        """Navigate to rankings page with specific RID"""
        url = self.config.get_rankings_url(rid)
        await self.page.goto(url, wait_until="networkidle")
        await self.page.wait_for_timeout(2000)  # Extra wait for stability

    async def extract_players_from_table(self) -> List[Dict]:
        """Extract player data from rankings table"""
        players = []

        # Find all table rows with 7 cells (data rows)
        rows = await self.page.query_selector_all("table tr")

        for row in rows:
            cells = await row.query_selector_all("td")
            if len(cells) != 7:
                continue

            # Get name cell (3rd column, index 2)
            name_cell = cells[2]
            name_span = await name_cell.query_selector("span.rml_poeng")

            if not name_span:
                continue

            player_name = await name_span.text_content()
            span_id = await name_span.get_attribute('id')

            # Extract player ID from span id (format: rml:14450:391:0)
            match = re.search(r'rml:(\d+):', span_id)
            if not match:
                continue

            player_id = match.group(1)

            # Get position, birth year, club, and points
            position_text = await cells[0].text_content()
            born_text = await cells[3].text_content()
            club_text = await cells[4].text_content()
            points_text = await cells[5].text_content()

            # Extract numeric position
            pos_match = re.search(r'\d+$', position_text.strip())
            position = int(pos_match.group(0)) if pos_match else 0

            # Clean points value (handle empty strings for players with no points)
            cleaned_points = points_text.strip().replace(' ', '').replace('.', '').replace(',', '')
            points = int(cleaned_points) if cleaned_points else 0

            players.append({
                "profixio_id": player_id,
                "name": player_name.strip(),
                "born": born_text.strip(),
                "club": club_text.strip(),
                "position": position,
                "points": points,
                "span_id": span_id
            })

        return players

    async def click_player_name(self, player_id: str):
        """Click player name span to open popup"""
        # Find span with player ID
        selector = f"span.rml_poeng[id*='rml:{player_id}:']"
        await self.page.click(selector)

        # Wait for popup to become visible
        await self.page.wait_for_selector(
            "#multipurpose",
            state="visible",
            timeout=10000
        )

        # Extra wait for content to load
        await self.page.wait_for_timeout(2000)

    async def scrape_ranking_history(self, player: Dict) -> List[Dict]:
        """Scrape ranking history from popup"""
        rankings = []

        # Get popup content
        popup = await self.page.query_selector("#multipurpose")
        if not popup:
            raise Exception("Popup not found")

        # Find table rows in popup
        rows = await popup.query_selector_all("table tr")

        for row in rows:
            cells = await row.query_selector_all("td")
            if len(cells) < 4:
                continue

            # Extract data
            date_text = await cells[0].text_content()
            position_text = await cells[2].text_content()
            points_diff_text = await cells[3].text_content()

            # Get points span
            points_span = await cells[1].query_selector("span.rmld_poeng")
            if not points_span:
                continue

            points_text = await points_span.text_content()
            rmld_id = await points_span.get_attribute('id')

            # Clean and parse numeric values (handle empty strings)
            cleaned_points = points_text.strip().replace(' ', '').replace('.', '').replace(',', '')
            cleaned_position = position_text.strip()

            rankings.append({
                "profixio_player_id": player['profixio_id'],
                "player_name": player['name'],
                "born": player.get('born', ''),
                "club": player.get('club', ''),
                "ranking_date": date_text.strip(),
                "points": int(cleaned_points) if cleaned_points else 0,
                "position": int(cleaned_position) if cleaned_position else 0,
                "points_diff": points_diff_text.strip(),
                "rmld_id": rmld_id
            })

        return rankings

    async def scrape_matches_for_month(self, player: Dict) -> List[Dict]:
        """Click on current month points to get matches"""
        target_date = f"{self.config.year}-{self.config.month.zfill(2)}"

        # Find the row with target date
        popup = await self.page.query_selector("#multipurpose")
        rows = await popup.query_selector_all("table tr")

        points_span = None

        for row in rows:
            cells = await row.query_selector_all("td")
            if len(cells) < 2:
                continue

            date_cell_text = await cells[0].text_content()
            if date_cell_text.strip().startswith(target_date):
                # Found target month row - click points span
                points_span = await cells[1].query_selector("span.rmld_poeng")
                if points_span:
                    await points_span.click()
                    await self.page.wait_for_timeout(2000)
                    break

        if not points_span:
            # No matches for this month
            return []

        # Parse match data from updated popup
        matches = []
        rows = await popup.query_selector_all("table tr")

        for row in rows:
            cells = await row.query_selector_all("td")
            if len(cells) < 5:
                continue

            result = await cells[0].text_content()
            result = result.strip()

            if result not in ['W', 'L']:
                continue

            opponent_name = await cells[1].text_content()
            opponent_points = await cells[2].text_content()
            match_points = await cells[3].text_content()
            match_date = await cells[4].text_content()

            # Clean and parse numeric values (handle empty strings)
            cleaned_opp_points = opponent_points.strip().replace('+', '').replace(' ', '').replace('.', '').replace(',', '')
            cleaned_match_points = match_points.strip().replace('+', '').replace(' ', '').replace('.', '').replace(',', '')

            matches.append({
                "profixio_player_id": player['profixio_id'],
                "player_name": player['name'],
                "result": result,
                "opponent_name": opponent_name.strip(),
                "opponent_points": int(cleaned_opp_points) if cleaned_opp_points else 0,
                "match_points": int(cleaned_match_points) if cleaned_match_points else 0,
                "match_date": match_date.strip(),
                "scraped_month": target_date
            })

        # Click back button
        await self.click_back_button()

        return matches

    async def click_back_button(self):
        """Click Tilbake button in popup"""
        await self.page.evaluate("""
            () => {
                const buttons = Array.from(document.querySelectorAll('button'));
                const backButton = buttons.find(btn => btn.textContent.includes('Tilbake'));
                if (backButton) backButton.click();
            }
        """)
        await self.page.wait_for_timeout(1000)

    async def close_popup(self):
        """Close popup by clicking close button"""
        await self.page.evaluate("""
            () => {
                const buttons = Array.from(document.querySelectorAll('button'));
                const closeButton = buttons.find(btn => btn.textContent.includes('Stäng'));
                if (closeButton) closeButton.click();
            }
        """)
        await self.page.wait_for_timeout(1000)


def log_info(message: str):
    """Log to stderr (so stdout is clean JSON)"""
    print(f"[INFO] {message}", file=sys.stderr)


def log_error(message: str):
    """Log error to stderr"""
    print(f"[ERROR] {message}", file=sys.stderr)


async def main():
    parser = argparse.ArgumentParser(description="Scrape rankings from profixio.com")
    parser.add_argument('--year', required=True, help='Year (e.g., 2025)')
    parser.add_argument('--month', required=True, help='Month (e.g., 12)')
    parser.add_argument('--gender', required=True, choices=['m', 'k'], help='Gender (m=male, k=female)')
    parser.add_argument('--limit', type=int, help='Limit number of players (for testing)')

    args = parser.parse_args()

    config = RankingsScraperConfig(
        year=args.year,
        month=args.month,
        gender=args.gender,
        limit_players=args.limit
    )

    scraper = RankingsScraper(config)
    result = await scraper.run()

    # Output JSON to stdout
    print(json.dumps(result, indent=2))


if __name__ == "__main__":
    asyncio.run(main())
