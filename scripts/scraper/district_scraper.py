#!/usr/bin/env python3
"""
Profixio District Players Scraper

Scrapes all ranked players grouped by district from profixio.com.
For each district + gender combination, navigates to the filtered ranking
list, parses all players (with pagination), and outputs clean JSON.

Usage:
    python3 district_scraper.py [--gender m|k|both] [--limit-districts N] [--limit-players N]

Output:
    JSON to stdout:
    {
        "success": true,
        "data": {
            "districts_processed": 42,
            "total_players": 3200,
            "districts": [
                {
                    "profixio_id": "32",
                    "name": "Dalarnas Bordtennisförbund",
                    "gender": "m",
                    "players": [
                        {
                            "profixio_player_id": "14450",
                            "surname": "Källberg",
                            "first_name": "Mats",
                            "birth_year": "1969",
                            "club": "Falu BTK",
                            "position": 109,
                            "points": 2287
                        }
                    ]
                }
            ]
        },
        "errors": []
    }
"""

import argparse
import asyncio
import json
import os
import re
import sys
from typing import Dict, List, Optional, Tuple

from playwright.async_api import Browser, BrowserContext, Page, async_playwright

BASE_URL = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php"


class DistrictScraperConfig:
    def __init__(
        self,
        genders: List[str],
        limit_districts: Optional[int] = None,
        limit_players: Optional[int] = None,
    ):
        self.genders = genders
        self.limit_districts = limit_districts
        self.limit_players = limit_players
        self.timeout = 120000  # 120 seconds (same as rankings_popup_scraper.py)


class DistrictScraper:
    def __init__(self, config: DistrictScraperConfig):
        self.config = config
        self.errors: List[Dict] = []

    async def run(self) -> Dict:
        async with async_playwright() as p:
            # Resolve Chromium binary (same logic as rankings_popup_scraper.py)
            chrome_path = os.environ.get("PUPPETEER_EXECUTABLE_PATH", None)
            if not chrome_path or not os.path.exists(chrome_path):
                for candidate in [
                    "/usr/bin/chromium",
                    "/usr/bin/chromium-browser",
                    "/usr/bin/google-chrome",
                ]:
                    if os.path.exists(candidate):
                        chrome_path = candidate
                        break
                else:
                    chrome_path = None

            launch_args: Dict = {
                "headless": True,
                "args": [
                    "--disable-blink-features=AutomationControlled",
                    "--disable-dev-shm-usage",
                    "--no-sandbox",
                    "--disable-setuid-sandbox",
                ],
            }
            if chrome_path:
                launch_args["executable_path"] = chrome_path
                log_info(f"Using system Chromium: {chrome_path}")

            browser: Browser = await p.chromium.launch(**launch_args)
            context: BrowserContext = await browser.new_context()
            page: Page = await context.new_page()
            page.set_default_timeout(self.config.timeout)

            try:
                all_district_results = []
                total_players = 0

                for gender in self.config.genders:
                    log_info(f"Processing gender: {gender}")

                    rid, districts = await self.get_rid_and_districts(page, gender)
                    log_info(
                        f"Found rid={rid}, {len(districts)} districts for gender={gender}"
                    )

                    if self.config.limit_districts:
                        districts = districts[: self.config.limit_districts]
                        log_info(f"Limited to {len(districts)} districts")

                    for idx, district in enumerate(districts):
                        log_info(
                            f"Scraping [{idx + 1}/{len(districts)}] "
                            f"{district['name']} (id={district['profixio_id']}, gender={gender})"
                        )
                        try:
                            players = await self.scrape_district_players(
                                page, rid, district["profixio_id"], gender
                            )
                            if self.config.limit_players:
                                players = players[: self.config.limit_players]
                            log_info(f"  → {len(players)} players")

                            all_district_results.append(
                                {
                                    "profixio_id": district["profixio_id"],
                                    "name": district["name"],
                                    "gender": gender,
                                    "players": players,
                                }
                            )
                            total_players += len(players)

                        except Exception as e:
                            self.errors.append(
                                {
                                    "district": district["name"],
                                    "gender": gender,
                                    "error": str(e),
                                }
                            )
                            log_error(
                                f"Error scraping district {district['name']}: {e}"
                            )

                return {
                    "success": True,
                    "data": {
                        "districts_processed": len(all_district_results),
                        "total_players": total_players,
                        "districts": all_district_results,
                    },
                    "errors": self.errors,
                }

            except Exception as e:
                log_error(f"Fatal error: {e}")
                import traceback
                traceback.print_exc(file=sys.stderr)
                return {
                    "success": False,
                    "data": {
                        "districts_processed": 0,
                        "total_players": 0,
                        "districts": [],
                    },
                    "errors": [{"error": str(e)}],
                }

            finally:
                await browser.close()

    async def get_rid_and_districts(
        self, page: Page, gender: str
    ) -> Tuple[str, List[Dict]]:
        """Load the list page and extract the latest RID and all district options."""
        url = f"{BASE_URL}?gender={gender}"
        await page.goto(url, wait_until="domcontentloaded", timeout=self.config.timeout)

        # Wait for dropdowns to be present (guards against slow server responses)
        await page.wait_for_selector('select[name="rid"]', timeout=15000)

        # Get latest RID from Körning select (first option = most recent period)
        rid_select = await page.query_selector('select[name="rid"]')
        if not rid_select:
            raise Exception("RID select not found on page")

        options = await rid_select.query_selector_all("option")
        if not options:
            raise Exception("No RID options found")

        rid = await options[0].get_attribute("value")
        log_info(f"Using latest RID: {rid}")

        # Get all district options
        distr_select = await page.query_selector('select[name="distr"]')
        if not distr_select:
            raise Exception("District select not found on page")

        distr_options = await distr_select.query_selector_all("option")
        districts = []
        for opt in distr_options:
            val = await opt.get_attribute("value")
            text = await opt.text_content()
            # Skip the empty "all districts" option (value=0 or empty)
            if val and val != "0" and text and text.strip():
                districts.append({"profixio_id": val, "name": text.strip()})

        return rid, districts

    async def scrape_district_players(
        self, page: Page, rid: str, district_id: str, gender: str
    ) -> List[Dict]:
        """Scrape all players for a district, following pagination."""
        url = (
            f"{BASE_URL}?searching=1&rid={rid}&distr={district_id}"
            f"&club=0&licencesubtype=&gender={gender}&age=&ln=&fn="
        )
        await page.goto(url, wait_until="domcontentloaded", timeout=self.config.timeout)

        # Collect all page URLs (current + pagination)
        page_urls = await self.get_pagination_urls(page, url)
        log_info(f"  Pagination: {len(page_urls)} page(s)")

        all_players: List[Dict] = []
        seen_ids = set()

        # Parse current (first) page
        players = await self.parse_player_table(page)
        self._add_unique_players(players, all_players, seen_ids)

        # Follow additional pagination pages
        for purl in page_urls[1:]:
            try:
                await page.goto(
                    purl, wait_until="domcontentloaded", timeout=self.config.timeout
                )
                players = await self.parse_player_table(page)
                self._add_unique_players(players, all_players, seen_ids)
            except Exception as e:
                log_error(f"Error loading pagination page {purl}: {e}")

        return all_players

    def _add_unique_players(
        self,
        new_players: List[Dict],
        all_players: List[Dict],
        seen_ids: set,
    ) -> None:
        for player in new_players:
            # Deduplication key: profixio_player_id if available, else name+birth
            key = player.get("profixio_player_id") or (
                f"{player['surname']}|{player['first_name']}|{player.get('birth_year', '')}"
            )
            if key not in seen_ids:
                seen_ids.add(key)
                all_players.append(player)

    async def get_pagination_urls(self, page: Page, base_url: str) -> List[str]:
        """Find pagination links (numeric links like 1, 501, 1001…) and return their hrefs."""
        urls = [base_url]

        links = await page.query_selector_all("a")
        for link in links:
            text = await link.text_content()
            if not text or not re.match(r"^\s*\d+\s*$", text):
                continue

            href = await link.get_attribute("href")
            if not href:
                continue

            # Make absolute URL if relative
            if not href.startswith("http"):
                href = f"https://www.profixio.com/fx/ranking_sbtf/{href}"

            if href not in urls:
                urls.append(href)

        return urls

    async def parse_player_table(self, page: Page) -> List[Dict]:
        """
        Parse player rows from the rankings table.

        The column count differs depending on whether a district filter is applied:
          - Full ranking (no filter):  7 cols — cells[0]=Placering, cells[1]=WR, cells[2]=Namn, cells[3]=Född, cells[4]=Klubb, cells[5]=Poäng, cells[6]=diff
          - District-filtered results: 5 cols — cells[0]=Placering, cells[1]=Namn, cells[2]=Född, cells[3]=Klubb, cells[4]=Poäng

        To handle both layouts we locate the cell containing span.rml_poeng dynamically,
        then read born/club/points from the 1st/2nd/3rd cells after it.
        Position is always in cells[0].
        """
        players = []
        rows = await page.query_selector_all("table tr")

        for row in rows:
            cells = await row.query_selector_all("td")
            # Need at least 5 cells: position + name + born + club + points
            if len(cells) < 5:
                continue

            # Locate the cell that holds span.rml_poeng (column varies by layout)
            name_span = None
            name_cell_idx = -1
            for i, cell in enumerate(cells):
                span = await cell.query_selector("span.rml_poeng")
                if span:
                    name_span = span
                    name_cell_idx = i
                    break

            if name_span is None:
                continue

            # Ensure enough cells follow the name cell for born/club/points
            if name_cell_idx + 3 >= len(cells):
                continue

            player_name = await name_span.text_content()
            span_id = await name_span.get_attribute("id") or ""

            # Extract profixio_player_id from span id format "rml:14450:391:0"
            profixio_id = None
            m = re.search(r"rml:(\d+):", span_id)
            if m:
                profixio_id = m.group(1)

            # Position is always in cells[0]
            # Handles "WR02 1 (1)", "109 (112)", "14 (32)" — grab digit(s) before "("
            position_text = await cells[0].text_content() or ""
            pos_m = re.search(r"(\d+)\s*\(", position_text)
            position = int(pos_m.group(1)) if pos_m else 0

            # Born, club, points are always the 1st/2nd/3rd cells after the name cell
            born_text  = (await cells[name_cell_idx + 1].text_content() or "").strip()
            club_text  = (await cells[name_cell_idx + 2].text_content() or "").strip()
            points_raw = await cells[name_cell_idx + 3].text_content() or ""

            # Strip Swedish thousand separators (space, dot, comma) then extract digits
            cleaned = points_raw.strip().replace(" ", "").replace(".", "").replace(",", "")
            pts_m = re.search(r"\d+", cleaned)
            points = int(pts_m.group(0)) if pts_m else 0

            # Split "Källberg, Mats" → surname + first_name
            full_name = player_name.strip()
            if "," in full_name:
                parts = full_name.split(",", 1)
                surname    = parts[0].strip()
                first_name = parts[1].strip()
            else:
                # Fallback: last word = surname
                parts = full_name.rsplit(" ", 1)
                surname    = parts[-1].strip()
                first_name = parts[0].strip() if len(parts) > 1 else ""

            players.append(
                {
                    "profixio_player_id": profixio_id,
                    "surname": surname,
                    "first_name": first_name,
                    "birth_year": born_text,
                    "club": club_text,
                    "position": position,
                    "points": points,
                }
            )

        return players


def log_info(msg: str) -> None:
    print(f"[INFO] {msg}", file=sys.stderr, flush=True)


def log_error(msg: str) -> None:
    print(f"[ERROR] {msg}", file=sys.stderr, flush=True)


async def main() -> None:
    parser = argparse.ArgumentParser(
        description="Scrape player-district associations from profixio.com"
    )
    parser.add_argument(
        "--gender",
        default="both",
        choices=["m", "k", "both"],
        help="Gender to scrape (m=male, k=female, both=default)",
    )
    parser.add_argument(
        "--limit-districts",
        type=int,
        help="Limit number of districts scraped (for testing)",
    )
    parser.add_argument(
        "--limit-players",
        type=int,
        help="Limit players per district (for testing)",
    )
    args = parser.parse_args()

    genders = ["m", "k"] if args.gender == "both" else [args.gender]
    config = DistrictScraperConfig(
        genders=genders,
        limit_districts=args.limit_districts,
        limit_players=args.limit_players,
    )

    scraper = DistrictScraper(config)
    result = await scraper.run()

    print(json.dumps(result, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    asyncio.run(main())
