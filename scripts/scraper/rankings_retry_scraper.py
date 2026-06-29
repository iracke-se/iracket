#!/usr/bin/env python3
"""
Profixio Rankings RETRY Scraper (failed-player recovery)

A SEPARATE, surgical companion to rankings_popup_scraper.py. It does NOT
replace or modify the main scraper — it only re-scrapes a small explicit list
of players that previously failed (popup timed out) for one specific
year/month/gender.

Key differences from the main scraper (deliberately gentle + targeted):
  * Only the players in --targets-file are scraped — everyone else is ignored.
  * Players are grouped by their *estimated* pagination page (from a known rank
    in an adjacent month), so we load only the pages we actually need instead of
    walking the whole ranking list.
  * The #multipurpose popup wait is 60s (the main scraper uses 15s). The original
    failures were heavy-popup players (e.g. top-ranked Möregårdh, Truls) whose
    AJAX render simply needed more than 15s — especially under high concurrency.
  * Concurrency = 1 (one tab at a time) with a 2s gap between players. The retry
    run is intentionally slow and polite to avoid re-triggering the rate-limit /
    load that caused the original timeouts.

Usage:
    python3 rankings_retry_scraper.py --year 2023 --month 06 --gender m \
        --targets-file /tmp/retry_targets.json

    targets-file = JSON array of objects:
        [{"name": "Möregårdh, Truls", "profixio_id": "1234", "est_position": 1}, ...]
    (est_position may be null if unknown — the player is then searched for across
     all pages as a fallback.)

Output (stdout):
    NDJSON — one {"type":"player",...} line per recovered player (same shape as
    the main scraper, so the PHP side saves it identically), followed by one
    {"type":"summary",...} line.
"""

import argparse
import asyncio
import json
import os
import sys
import re
from typing import List, Dict, Optional, Tuple
from playwright.async_api import async_playwright, Page, Browser, BrowserContext


# How long to wait for the popup to become visible. The whole point of the retry
# scraper: heavy popups need far longer than the main scraper's 15s.
POPUP_VISIBLE_TIMEOUT = 60000   # ms
POPUP_CONTENT_TIMEOUT = 30000   # ms
GAP_BETWEEN_PLAYERS = 2.0       # seconds — be polite


class RetryConfig:
    def __init__(self, year: str, month: str, gender: str, targets: List[Dict]):
        self.year = year
        self.month = month
        self.gender = gender  # 'm' or 'k'
        self.targets = targets
        self.base_url = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php"

    def get_rankings_url(self, rid: str, from_offset: int = 0) -> str:
        url = f"{self.base_url}?gender={self.gender}&rid={rid}"
        if from_offset > 0:
            url += f"&from={from_offset}"
        return url


class RetryScraper:
    def __init__(self, config: RetryConfig):
        self.config = config
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.errors: List[Dict] = []
        self.not_found: List[str] = []
        self._processed = 0
        self._all_rankings: List[Dict] = []
        self._all_matches: List[Dict] = []

    # -------------------------------------------------------------------------
    # Lifecycle
    # -------------------------------------------------------------------------

    async def run(self) -> Dict:
        async with async_playwright() as p:
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
                ],
            }
            if chrome_path:
                launch_args['executable_path'] = chrome_path
                log_info(f"Using system Chromium: {chrome_path}")

            self.browser = await p.chromium.launch(**launch_args)
            self.context = await self.browser.new_context()
            discovery_page = await self.context.new_page()

            try:
                rid = await self.get_rid_for_month(discovery_page)
                log_info(f"Found rid={rid} for {self.config.year}-{self.config.month}")

                offsets = await self.discover_page_offsets(discovery_page, rid)
                log_info(f"Found {len(offsets)} pagination pages: {offsets}")
                await discovery_page.close()

                if not offsets:
                    log_error("No pages found for this month — nothing to retry.")
                    return self._summary()

                await self._process_targets(rid, offsets)
                log_info(f"Retry complete. Recovered {self._processed}/{len(self.config.targets)} players.")
                return self._summary()

            except Exception as e:
                log_error(f"Fatal error: {e}")
                self.errors.append({"player": "*", "error": str(e)})
                return self._summary(success=False)
            finally:
                await self.browser.close()

    def _summary(self, success: bool = True) -> Dict:
        return {
            "success": success,
            "data": {
                "players_processed": self._processed,
                "rankings_count": len(self._all_rankings),
                "matches_count": len(self._all_matches),
                "rankings": self._all_rankings,
                "matches": self._all_matches,
                "not_found": self.not_found,
                "targets_total": len(self.config.targets),
            },
            "errors": self.errors,
        }

    # -------------------------------------------------------------------------
    # Discovery (same logic as the main scraper)
    # -------------------------------------------------------------------------

    async def get_rid_for_month(self, page: Page) -> str:
        target_date = f"{self.config.year}.{self.config.month.zfill(2)}."
        url = f"{self.config.base_url}?gender={self.config.gender}"
        await page.goto(url, wait_until="domcontentloaded", timeout=0)
        await page.wait_for_selector('select[name="rid"]', timeout=0)

        select = await page.query_selector('select[name="rid"]')
        if not select:
            raise Exception("Month dropdown not found")

        available = []
        for option in await select.query_selector_all('option'):
            text = await option.text_content()
            if text:
                available.append(text.strip())
            if text and text.startswith(target_date):
                return await option.get_attribute('value')

        raise Exception(f"Month {target_date} not found in dropdown. Available: {available}")

    async def discover_page_offsets(self, page: Page, rid: str) -> List[int]:
        url = self.config.get_rankings_url(rid, 0)
        await page.goto(url, wait_until="domcontentloaded", timeout=0)
        try:
            await page.wait_for_selector('table tr span.rml_poeng', timeout=0)
        except Exception:
            return []

        offsets = {0}
        for link in await page.query_selector_all("a[href*='from=']"):
            href = await link.get_attribute('href')
            if href:
                m = re.search(r'from=(\d+)', href)
                if m:
                    offsets.add(int(m.group(1)))
        return sorted(offsets)

    # -------------------------------------------------------------------------
    # Targeted processing
    # -------------------------------------------------------------------------

    def _bucket_index_for_position(self, offsets: List[int], est_position: Optional[int]) -> int:
        """Return the index into `offsets` of the page most likely to contain a
        player at rank `est_position`. Rule: the largest offset <= est_position.
        Works whether profixio offsets are 0-based (0,500,..) or 1-based (1,501,..).
        """
        if est_position is None:
            return 0
        chosen = 0
        for i, off in enumerate(offsets):
            if off <= est_position:
                chosen = i
            else:
                break
        return chosen

    async def _process_targets(self, rid: str, offsets: List[int]) -> None:
        # Annotate each target with its estimated bucket index.
        remaining: List[Dict] = []
        for t in self.config.targets:
            t = dict(t)
            t['_bucket'] = self._bucket_index_for_position(offsets, t.get('est_position'))
            remaining.append(t)

        processed_offsets: set = set()

        # ----- Pass 1: group by estimated page, load each needed page once -----
        groups: Dict[int, List[Dict]] = {}
        for t in remaining:
            groups.setdefault(t['_bucket'], []).append(t)

        still_unresolved: List[Dict] = []
        for bucket_idx in sorted(groups.keys()):
            offset = offsets[bucket_idx]
            found_ids = await self._process_offset(rid, offset, groups[bucket_idx])
            processed_offsets.add(offset)
            for t in groups[bucket_idx]:
                if t['profixio_id'] not in found_ids:
                    still_unresolved.append(t)

        # ----- Pass 2: hunt stragglers near their estimated page -----
        # A player's rank can drift across a page boundary between months, so the
        # estimate can be off by a page. Search outward from the estimate.
        for t in list(still_unresolved):
            if t.get('_resolved'):
                continue
            n = len(offsets)
            # Candidate offsets ordered by distance from the estimated bucket.
            order = sorted(range(n), key=lambda i: abs(i - t['_bucket']))
            for i in order:
                offset = offsets[i]
                if offset in processed_offsets:
                    continue
                # Scrape any still-unresolved target that might sit on this page.
                candidates = [u for u in still_unresolved if not u.get('_resolved')]
                found_ids = await self._process_offset(rid, offset, candidates)
                processed_offsets.add(offset)
                for u in candidates:
                    if u['profixio_id'] in found_ids:
                        u['_resolved'] = True
                if t.get('_resolved'):
                    break

        for t in still_unresolved:
            if not t.get('_resolved'):
                self.not_found.append(t['name'])
                log_error(f"Not found on any page: {t['name']} (id={t['profixio_id']}) — listing may have changed.")

    async def _process_offset(self, rid: str, offset: int, candidates: List[Dict]) -> set:
        """Load one ranking page and scrape every candidate present on it.
        Returns the set of profixio_ids that were successfully scraped."""
        found: set = set()
        if not candidates:
            return found

        url = self.config.get_rankings_url(rid, offset)
        tab = await self.context.new_page()
        try:
            log_info(f"[page from={offset}] Loading for {len(candidates)} candidate(s)")
            await tab.goto(url, wait_until="domcontentloaded", timeout=60000)
            try:
                await tab.wait_for_selector('table tr span.rml_poeng', timeout=30000)
            except Exception:
                log_info(f"[page from={offset}] No players on this page — skipping")
                return found

            for target in candidates:
                if target.get('_resolved'):
                    continue
                present = await self._player_present(tab, target['profixio_id'])
                if not present:
                    continue

                ok = await self._scrape_target(tab, target, offset)
                if ok:
                    found.add(target['profixio_id'])
                    target['_resolved'] = True
                await asyncio.sleep(GAP_BETWEEN_PLAYERS)
        except Exception as e:
            log_error(f"[page from={offset}] Page error: {e}")
        finally:
            try:
                await tab.close()
            except Exception:
                pass
        return found

    async def _player_present(self, tab: Page, player_id: str) -> bool:
        return await tab.evaluate(
            f"""() => !!document.querySelector("span.rml_poeng[id*='rml:{player_id}:']")"""
        )

    async def _scrape_target(self, tab: Page, target: Dict, offset: int) -> bool:
        """Open one player's popup (patiently) and scrape ranking + matches.
        One reload-and-retry on failure, then give up and record the error."""
        player = {
            'profixio_id': target['profixio_id'],
            'name': target['name'],
            'born': target.get('born', ''),
            'club': target.get('club', ''),
        }

        for attempt in range(1, 3):
            try:
                await self._click_player(tab, player['profixio_id'])
                rankings = await self._scrape_ranking_history(tab, player)
                matches = await self._scrape_matches(tab, player)
                await self._close_popup(tab)

                self._processed += 1
                self._all_rankings.extend(rankings)
                self._all_matches.extend(matches)
                log_info(
                    f"[{self._processed}] Recovered: {player['name']} (page from={offset}) — "
                    f"{len(rankings)} rankings, {len(matches)} matches"
                )
                await self._emit_player(rankings, matches)
                return True

            except Exception as e:
                try:
                    await self._close_popup(tab)
                except Exception:
                    pass
                if attempt < 2:
                    log_error(f"{player['name']} — attempt {attempt} failed ({e}); reloading and retrying once")
                    try:
                        await tab.reload(wait_until="domcontentloaded", timeout=30000)
                        await tab.wait_for_selector('table tr span.rml_poeng', timeout=30000)
                    except Exception as reload_err:
                        log_error(f"Reload failed: {reload_err}")
                        self.errors.append({"player": player['name'], "error": str(reload_err)})
                        return False
                else:
                    self.errors.append({"player": player['name'], "error": str(e)})
                    log_error(f"{player['name']} — still failing after retry: {e}")
        return False

    async def _emit_player(self, rankings: List[Dict], matches: List[Dict]) -> None:
        payload = {"type": "player", "rankings": rankings, "matches": matches}
        sys.stdout.write(json.dumps(payload) + "\n")
        sys.stdout.flush()

    # -------------------------------------------------------------------------
    # Popup helpers (mirrors the main scraper, but with longer waits)
    # -------------------------------------------------------------------------

    async def _click_player(self, page: Page, player_id: str):
        await page.evaluate(f"""
            () => {{
                const span = document.querySelector("span.rml_poeng[id*='rml:{player_id}:']");
                if (span) {{ span.click(); }}
                else {{ throw new Error('Player span not found for id {player_id}'); }}
            }}
        """)
        await page.wait_for_selector("#multipurpose", state="visible", timeout=POPUP_VISIBLE_TIMEOUT)
        await page.wait_for_selector("#multipurpose table tr", timeout=POPUP_CONTENT_TIMEOUT)

    async def _scrape_ranking_history(self, page: Page, player: Dict) -> List[Dict]:
        target_date = f"{self.config.year}-{self.config.month.zfill(2)}"
        popup = await page.query_selector("#multipurpose")
        if not popup:
            raise Exception("Popup not found")

        for row in await popup.query_selector_all("table tr"):
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

        return []

    async def _scrape_matches(self, page: Page, player: Dict) -> List[Dict]:
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
        await page.evaluate("""
            () => {
                const btn = Array.from(document.querySelectorAll('button'))
                    .find(b => b.textContent.includes('Tilbake'));
                if (btn) btn.click();
            }
        """)
        try:
            await page.wait_for_selector("#multipurpose table tr td span.rmld_poeng", timeout=5000)
        except Exception:
            await page.wait_for_timeout(500)

    async def _close_popup(self, page: Page):
        await page.evaluate("""
            () => {
                const btn = Array.from(document.querySelectorAll('button'))
                    .find(b => b.textContent.includes('Stäng'));
                if (btn) btn.click();
            }
        """)
        try:
            await page.wait_for_selector("#multipurpose", state="hidden", timeout=5000)
        except Exception:
            await page.wait_for_timeout(500)


# ---------------------------------------------------------------------------
# Logging (stderr — keep stdout clean JSON)
# ---------------------------------------------------------------------------

def log_info(message: str):
    print(f"[INFO] {message}", file=sys.stderr, flush=True)


def log_error(message: str):
    print(f"[ERROR] {message}", file=sys.stderr, flush=True)


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

async def main():
    parser = argparse.ArgumentParser(description="Retry failed players from profixio.com rankings")
    parser.add_argument('--year', required=True)
    parser.add_argument('--month', required=True)
    parser.add_argument('--gender', required=True, choices=['m', 'k'])
    parser.add_argument('--targets-file', required=True,
                        help='Path to a JSON array of {name, profixio_id, est_position}')
    args = parser.parse_args()

    with open(args.targets_file, 'r', encoding='utf-8') as f:
        targets = json.load(f)

    if not isinstance(targets, list) or not targets:
        sys.stdout.write(json.dumps({
            "type": "summary",
            "success": True,
            "data": {"players_processed": 0, "rankings_count": 0, "matches_count": 0,
                     "rankings": [], "matches": [], "not_found": [], "targets_total": 0},
            "errors": [],
        }) + "\n")
        return

    config = RetryConfig(year=args.year, month=args.month, gender=args.gender, targets=targets)
    scraper = RetryScraper(config)
    result = await scraper.run()
    result["type"] = "summary"
    sys.stdout.write(json.dumps(result) + "\n")
    sys.stdout.flush()


if __name__ == "__main__":
    asyncio.run(main())
