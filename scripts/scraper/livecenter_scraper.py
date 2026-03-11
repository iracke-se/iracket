#!/usr/bin/env python3
"""
Profixio Live Center API Scraper

Uses callback.php JSON API directly instead of browser automation.
No Chromium browser needed — one login GET for session cookies, then
POST requests to callback.php for all data.

Usage:
    python3 livecenter_scraper.py --date 2025-11-11 [--limit-matches 5] [--skip-points]
    python3 livecenter_scraper.py --month 2025-11
    python3 livecenter_scraper.py --year 2025

Output:
    JSON to stdout with structure:
    {
        "success": true,
        "data": {
            "team_matches_count": 6,
            "games_count": 48,
            "sets_count": 144,
            "points_count": 0,
            "team_matches": [...]
        },
        "errors": []
    }
"""

import argparse
import json
import re
import sys
from typing import List, Dict, Optional, Tuple
import urllib.request
import urllib.parse
import urllib.error
import http.cookiejar
from html.parser import HTMLParser


# ---------------------------------------------------------------------------
# HTTP client
# ---------------------------------------------------------------------------

class ProfixioClient:
    BASE = "https://www.profixio.com/fx"
    ORG  = "SBTF.SE.BT"

    def __init__(self):
        self._jar    = http.cookiejar.CookieJar()
        self._opener = urllib.request.build_opener(
            urllib.request.HTTPCookieProcessor(self._jar),
            urllib.request.HTTPRedirectHandler(),
        )
        self._opener.addheaders = [
            ("User-Agent",      "Mozilla/5.0 (compatible; iRacket-scraper/2.0)"),
            ("Accept",          "application/json, text/javascript, */*; q=0.01"),
            ("Accept-Language", "sv,en;q=0.9"),
        ]

    def login(self) -> bool:
        """Establish guest session — no credentials needed."""
        url = f"{self.BASE}/login.php?login_public={self.ORG}"
        try:
            req  = urllib.request.Request(url)
            resp = self._opener.open(req, timeout=30)
            log_info(f"Login HTTP {resp.status} — cookies: {len(list(self._jar))}")
            return True
        except Exception as e:
            log_error(f"Login failed: {e}")
            return False

    def call_api(self, metode: str, params: dict) -> dict:
        """POST to callback.php and return parsed JSON response."""
        url  = f"{self.BASE}/livecenter/callback.php"
        data = urllib.parse.urlencode({
            "metode": metode,
            "data":   json.dumps({**params, "organisasjon": self.ORG}),
        }).encode("utf-8")

        req = urllib.request.Request(url, data=data)
        req.add_header("Content-Type",     "application/x-www-form-urlencoded; charset=UTF-8")
        req.add_header("X-Requested-With", "XMLHttpRequest")
        req.add_header("Referer",          f"{self.BASE}/livecenter/")

        try:
            resp    = self._opener.open(req, timeout=30)
            content = resp.read().decode("utf-8")
            log_info(f"API {metode} → HTTP {resp.status}, {len(content)} bytes")
            return json.loads(content)
        except urllib.error.HTTPError as e:
            body = e.read().decode("utf-8", errors="replace")
            raise RuntimeError(f"HTTP {e.code} from callback.php [{metode}]: {body[:300]}")
        except json.JSONDecodeError as e:
            raise RuntimeError(f"Non-JSON response from callback.php [{metode}]: {e}")


# ---------------------------------------------------------------------------
# HTML parsers (stdlib html.parser — no external dependencies)
# ---------------------------------------------------------------------------

class TableRowParser(HTMLParser):
    """
    Extracts <tr> rows from an HTML fragment.
    Each row is: {"cells": [...], "onclick": "...", "id": "..."}
    """

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.rows: List[Dict] = []
        self._row: Optional[Dict]  = None
        self._cell: Optional[str]  = None

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "tr":
            self._row = {"cells": [], "onclick": a.get("onclick", ""), "id": a.get("id", "")}
        elif tag == "td" and self._row is not None:
            self._cell = ""
            # td-level onclick if no tr onclick
            if not self._row["onclick"]:
                self._row["onclick"] = a.get("onclick", "")

    def handle_endtag(self, tag):
        if tag == "td" and self._cell is not None:
            if self._row is not None:
                self._row["cells"].append(self._cell.strip())
            self._cell = None
        elif tag == "tr" and self._row is not None:
            if self._row["cells"]:
                self.rows.append(self._row)
            self._row = None

    def handle_data(self, data):
        if self._cell is not None:
            self._cell += data

    @classmethod
    def parse(cls, html: str) -> List[Dict]:
        p = cls()
        # output field may start with JS; strip everything before first <
        first_tag = html.find("<")
        if first_tag > 0:
            html = html[first_tag:]
        p.feed(html)
        return p.rows


def extract_html_from_js_output(output: str) -> str:
    """
    callback.php output is JS code like:
        $('matches').innerHTML = '<tr ...>...</tr>';
    We extract the HTML inside the innerHTML assignment.
    Falls back to returning the full string if no assignment found.
    """
    # Try innerHTML = '...' (single or double quotes, possible escaping)
    m = re.search(r"\.innerHTML\s*=\s*'(.*?)'\s*;", output, re.DOTALL)
    if m:
        return m.group(1).replace("\\'", "'").replace('\\"', '"')
    m = re.search(r'\.innerHTML\s*=\s*"(.*?)"\s*;', output, re.DOTALL)
    if m:
        return m.group(1).replace('\\"', '"').replace("\\'", "'")
    # Return as-is (may already be plain HTML or use different pattern)
    return output


# ---------------------------------------------------------------------------
# Data parsers for each API response type
# ---------------------------------------------------------------------------

def parse_match_list(output: str) -> List[Dict]:
    """
    Parse team matches from get_match_list output.
    Each row: Team1 - Team2 | 5-1 | onclick=get_match(..., match_id, ...)
    """
    html = extract_html_from_js_output(output)
    rows = TableRowParser.parse(html)
    matches = []

    for i, row in enumerate(rows):
        cells   = row["cells"]
        onclick = row["onclick"]
        if len(cells) < 2:
            continue

        teams_text = cells[0].strip()
        score_text = cells[1].strip()

        # Skip header / status rows
        if not teams_text or any(kw in teams_text for kw in ("Uppdaterad", "Inga matcher", "Updated")):
            continue

        # Parse "Team1 - Team2"
        dash = teams_text.find(" - ")
        if dash == -1:
            continue
        team1 = teams_text[:dash].strip()
        team2 = teams_text[dash + 3:].strip()
        if not team1 or not team2:
            continue

        # Parse score "5 - 1"
        team1_score = team2_score = None
        sm = re.search(r"(\d+)\s*-\s*(\d+)", score_text)
        if sm:
            team1_score, team2_score = int(sm.group(1)), int(sm.group(2))

        # Extract match_id from onclick — try multiple formats:
        # get_match('ORG', 12345, 0)   — original format
        # get_match(12345, 0)          — no org prefix
        # get_match('12345', ...)      — string ID
        # data-match-id="12345"        — data attribute (check full row html via onclick string)
        match_id = None
        for pattern in [
            r"get_match\(\\?['\"][\w.]+\\?['\"],\s*(\d+)",  # get_match("ORG", 12345, ...)
            r"get_match\(\s*(\d+)\s*[,)]",                   # get_match(12345, ...)
            r"match_id[=:]\s*['\"]?(\d+)",                   # match_id=12345
            r"kamp_id[=:]\s*['\"]?(\d+)",                    # kamp_id=12345
        ]:
            im = re.search(pattern, onclick)
            if im:
                match_id = im.group(1)
                break

        # Division (may be in a 3rd cell)
        division = cells[2].strip() if len(cells) > 2 else None

        status = "in_progress" if any(kw in score_text for kw in ("Live", "Pågår")) else "completed"

        if not match_id and i < 3:
            # Log first few onclicks to help diagnose format
            log_info(f"  [debug] row {i} onclick='{onclick[:200]}'")

        matches.append({
            "team1_name":        team1,
            "team2_name":        team2,
            "team1_score":       team1_score,
            "team2_score":       team2_score,
            "profixio_match_id": match_id,
            "division":          division,
            "status":            status,
            "row_index":         i,
        })

    return matches


def parse_games(output: str) -> List[Dict]:
    """
    Parse individual games (S1/S2/D1 etc.) from get_match output.
    """
    html  = extract_html_from_js_output(output)
    rows  = TableRowParser.parse(html)
    games = []
    seen  = set()

    for i, row in enumerate(rows):
        cells = row["cells"]
        if not cells:
            continue

        # First cell should be game code: S1, S2, D1, D2, etc.
        first = cells[0].strip()
        if not re.match(r"^[SD]\d$", first):
            continue
        if first in seen:
            continue

        game_number = first
        game_type   = "doubles" if game_number.startswith("D") else "singles"

        # Extract player names — format: "Lastname, Firstname"
        player1_name = player2_name = ""
        player1_sets = player2_sets = None
        winner_name  = None
        game_id      = None

        # Name cells are anything that looks like a name (contains comma or letters ≥ 2)
        name_cells  = []
        score_found = False
        for cell in cells[1:]:
            sc = re.match(r"^(\d+)\s*-\s*(\d+)$", cell.strip())
            if sc and not score_found:
                player1_sets = int(sc.group(1))
                player2_sets = int(sc.group(2))
                score_found  = True
            elif re.search(r"[A-Za-zÅÄÖåäö]{2,}", cell):
                name_cells.append(cell.strip())

        if len(name_cells) >= 1:
            player1_name = name_cells[0]
        if len(name_cells) >= 2:
            player2_name = name_cells[1]

        # Handle doubles partner split: "Name1 / Name2"
        p1_partner = p2_partner = None
        if game_type == "doubles":
            if "/" in player1_name:
                parts = player1_name.split("/", 1)
                player1_name, p1_partner = parts[0].strip(), parts[1].strip()
            if "/" in player2_name:
                parts = player2_name.split("/", 1)
                player2_name, p2_partner = parts[0].strip(), parts[1].strip()

        # Require at least one valid player name (Lastname, Firstname format)
        if "," not in player1_name and "," not in player2_name:
            continue

        if player1_sets is not None and player2_sets is not None:
            winner_name = player1_name if player1_sets > player2_sets else (
                player2_name if player2_sets > player1_sets else None
            )

        # game id from onclick
        om = re.search(r"(\d+)", row["onclick"])
        if om:
            game_id = om.group(1)

        seen.add(game_number)
        games.append({
            "game_number":          game_number,
            "game_type":            game_type,
            "player1_name":         player1_name,
            "player2_name":         player2_name,
            "player1_partner_name": p1_partner,
            "player2_partner_name": p2_partner,
            "player1_sets":         player1_sets,
            "player2_sets":         player2_sets,
            "winner_name":          winner_name,
            "profixio_game_id":     game_id,
            "row_index":            i,
        })

    return games


def parse_sets(output: str) -> List[Dict]:
    """
    Parse set scores from get_match_detail output.

    The API response contains JS like:
        set_elem_result('game_1', '11 - 7', 1);
        set_elem_result('game_2', '11 - 6', 1);
        set_elem_result('game_3', '13 - 11', 1);
    These are the authoritative set scores.

    The HTML table in the same response contains point-by-point rally data
    (e.g. "13 - 11", "12 - 11", ...) which must NOT be confused with sets.
    """
    sets = []

    # Primary: extract from set_elem_result JS calls (reliable, ordered)
    results = re.findall(
        r"set_elem_result\(\s*'game_(\d+)'\s*,\s*'(\d+)\s*-\s*(\d+)'",
        output,
    )
    for game_num, p1_str, p2_str in results:
        sets.append((int(game_num), int(p1_str), int(p2_str)))

    if sets:
        sets.sort(key=lambda s: s[0])
        return [
            {
                "set_number":      idx + 1,
                "player1_points":  p1,
                "player2_points":  p2,
                "points":          [],
            }
            for idx, (_, p1, p2) in enumerate(sets)
        ]

    # Fallback: regex scan of raw output for score-like patterns (only if no
    # set_elem_result calls found — e.g. older API format)
    score_pairs = re.findall(r"\b(\d{1,2})\s*-\s*(\d{1,2})\b", output)
    fallback = []
    for p1_str, p2_str in score_pairs:
        p1, p2 = int(p1_str), int(p2_str)
        if p1 >= 11 or p2 >= 11 or (p1 >= 10 and p2 >= 10):
            fallback.append((p1, p2))
    # Deduplicate: keep only unique score pairs (first occurrence)
    seen = set()
    unique = []
    for pair in fallback:
        if pair not in seen:
            seen.add(pair)
            unique.append(pair)
    # Heuristic: a match has at most 5 sets in badminton (3 for most formats)
    if len(unique) > 5:
        unique = unique[:5]

    return [
        {
            "set_number":      idx + 1,
            "player1_points":  p1,
            "player2_points":  p2,
            "points":          [],
        }
        for idx, (p1, p2) in enumerate(unique)
    ]


# ---------------------------------------------------------------------------
# Main scraper
# ---------------------------------------------------------------------------

class LiveCenterScraper:

    def __init__(self, config):
        self.config  = config
        self.client  = ProfixioClient()
        self.errors: List[Dict] = []

    def run(self) -> Dict:
        if not self.client.login():
            return self._error_result("Failed to establish session with profixio.com")

        try:
            dates = self._resolve_dates()
        except Exception as e:
            return self._error_result(str(e))

        if not dates:
            filter_desc = self.config.date or self.config.month or self.config.year or "unknown"
            return self._error_result(f"No dates found for: {filter_desc}")

        log_info(f"Will scrape {len(dates)} date(s)")

        all_matches  = []
        total_games  = total_sets = total_points = 0

        for idx, date in enumerate(dates):
            log_info(f"--- Date {idx+1}/{len(dates)}: {date} ---")
            try:
                result       = self._scrape_date(date)
                all_matches += result["team_matches"]
                total_games += result["games"]
                total_sets  += result["sets"]
                total_points+= result["points"]
            except Exception as e:
                self.errors.append({"date": date, "error": str(e)})
                log_error(f"Error scraping {date}: {e}")

        return {
            "success": True,
            "data": {
                "team_matches_count": len(all_matches),
                "games_count":        total_games,
                "sets_count":         total_sets,
                "points_count":       total_points,
                "team_matches":       all_matches,
            },
            "errors": self.errors,
        }

    # ------------------------------------------------------------------
    # Date resolution
    # ------------------------------------------------------------------

    def _resolve_dates(self) -> List[str]:
        if self.config.date:
            return [self.config.date]

        # Call API without a date to get all available dates
        resp = self.client.call_api("get_match_list", {
            "dato":          "",
            "turn_id":       "",
            "match_id":      0,
            "refresh":       0,
            "selected_date": 0,
        })

        all_dates = self._extract_dates(resp)
        log_info(f"Available dates from API: {len(all_dates)}")

        if self.config.year:
            return [d for d in all_dates if d.startswith(self.config.year + "-")]
        if self.config.month:
            return [d for d in all_dates if d.startswith(self.config.month + "-")]
        return all_dates

    def _extract_dates(self, resp: dict) -> List[str]:
        dates = []

        # Strategy 1: kampdatoer top-level array: [{"kamp_dato": "2025-11-15"}, ...]
        for item in resp.get("kampdatoer") or []:
            if isinstance(item, dict):
                d = item.get("kamp_dato") or item.get("dato") or ""
            else:
                d = str(item)
            if re.match(r"\d{4}-\d{2}-\d{2}", d):
                dates.append(d)

        if dates:
            return sorted(set(dates))

        # Strategy 2: scan the HTML output field for date patterns
        # The output HTML may contain dates in onclick handlers like:
        #   get_match_list('ORG', '2026-01-15', ...)  or  dato=2026-01-15
        output = resp.get("output", "")
        if output:
            # Look for dates in onclick/href/data attributes and JS calls
            found = re.findall(r"['\"](\d{4}-\d{2}-\d{2})['\"]", output)
            found += re.findall(r"dato[=\s]+['\"]?(\d{4}-\d{2}-\d{2})['\"]?", output)
            for d in found:
                dates.append(d)

        if dates:
            return sorted(set(dates))

        # Strategy 3: scan entire raw JSON response for date strings
        raw = json.dumps(resp)
        found = re.findall(r'"(\d{4}-\d{2}-\d{2})"', raw)
        for d in found:
            dates.append(d)

        return sorted(set(dates))

    # ------------------------------------------------------------------
    # Per-date scraping
    # ------------------------------------------------------------------

    def _extract_html_from_resp(self, resp: dict) -> str:
        """
        Find the HTML match table in a get_match_list response.
        Tries known key names first, then scans all string values for <tr> content.
        """
        # Known key names (current and legacy)
        for key in ("output", "html", "innhold", "content", "kamper", "matches", "data"):
            val = resp.get(key, "")
            if val and "<tr" in str(val):
                return str(val)

        # Fallback: scan all string values in the response for the one containing match rows
        best = ""
        for val in resp.values():
            if isinstance(val, str) and "<tr" in val and len(val) > len(best):
                best = val
        if best:
            return best

        # Last resort: scan nested dicts/lists one level deep
        for val in resp.values():
            if isinstance(val, dict):
                for inner in val.values():
                    if isinstance(inner, str) and "<tr" in inner and len(inner) > len(best):
                        best = inner
            elif isinstance(val, list):
                for item in val:
                    if isinstance(item, str) and "<tr" in item and len(item) > len(best):
                        best = item

        return best

    def _scrape_date(self, date: str) -> Dict:
        resp = self.client.call_api("get_match_list", {
            "dato":          date,
            "turn_id":       "",
            "match_id":      0,
            "refresh":       0,
            "selected_date": 1,
        })

        output = self._extract_html_from_resp(resp)
        log_info(f"get_match_list output: {len(output)} chars (keys: {list(resp.keys())})")

        team_matches = parse_match_list(output)
        log_info(f"Parsed {len(team_matches)} team matches for {date}")

        if not team_matches:
            return {"team_matches": [], "games": 0, "sets": 0, "points": 0}

        if self.config.limit_matches:
            team_matches = team_matches[:self.config.limit_matches]

        result_matches = []
        total_games = total_sets = total_points = 0

        for idx, tm in enumerate(team_matches):
            label = f"{tm['team1_name']} vs {tm['team2_name']}"
            log_info(f"  [{idx+1}/{len(team_matches)}] {label}")

            try:
                games = self._scrape_games(tm)
                log_info(f"    {len(games)} games")

                if not self.config.skip_points:
                    skipped = fetched = 0
                    for game in games:
                        gn = game.get("game_number", "?")
                        matched, matched_name = self._game_has_tracked_player(game)
                        if not matched:
                            game["sets"] = []
                            skipped += 1
                            continue
                        if matched_name:
                            log_info(f"    [{gn}] tracked player: {matched_name}")
                        try:
                            sets = self._scrape_sets(tm, game)
                            game["sets"] = sets
                            total_sets  += len(sets)
                            fetched += 1
                        except Exception as e:
                            self.errors.append({"team_match": label, "game": gn, "error": str(e)})
                            log_error(f"    Set error [{gn}]: {e}")
                            game["sets"] = []
                    if self.config.player_names:
                        log_info(f"    Sets: {fetched} fetched, {skipped} skipped (no tracked player)")

                tm["games"]     = games
                tm["played_at"] = date
                total_games    += len(games)
                result_matches.append(tm)

            except Exception as e:
                self.errors.append({"team_match": label, "error": str(e)})
                log_error(f"  Match error: {e}")

        return {
            "team_matches": result_matches,
            "games":        total_games,
            "sets":         total_sets,
            "points":       total_points,
        }

    def _scrape_games(self, team_match: Dict) -> List[Dict]:
        match_id = team_match.get("profixio_match_id")
        if not match_id:
            log_info("    No match_id — cannot fetch games")
            return []

        resp   = self.client.call_api("get_match", {
            "match_id":      match_id,
            "refresh":       0,
            "part_match_id": "",
        })
        output = self._extract_html_from_resp(resp)
        log_info(f"    get_match output: {len(output)} chars")
        return parse_games(output)

    def _scrape_sets(self, team_match: Dict, game: Dict) -> List[Dict]:
        match_id    = team_match.get("profixio_match_id")
        game_number = game.get("game_number", "")
        if not match_id or not game_number:
            return []

        # game_number is "S1","S2","D1" — strip the letter prefix for match_code
        # API may want "1","2" or the full code; try full code first
        resp   = self.client.call_api("get_match_detail", {
            "match_id":   match_id,
            "match_code": game_number,
            "set_number": 0,
        })
        # Pass the raw JS output (not just extracted HTML) so parse_sets can
        # find set_elem_result('game_N', 'P1 - P2', ...) JS calls.
        raw_output = resp.get("data", {}).get("output", "") if isinstance(resp.get("data"), dict) else ""
        if not raw_output:
            raw_output = self._extract_html_from_resp(resp)
        log_info(f"    get_match_detail [{game_number}] output: {len(raw_output)} chars")
        return parse_sets(raw_output)

    def _game_has_tracked_player(self, game: Dict):
        """
        Returns (True, matched_name) if a tracked player is in this game,
        else (False, ""). If no filter is set, always returns (True, "").
        Matching is case-insensitive with bidirectional substring fallback.
        """
        if not self.config.player_names:
            return True, ""

        candidates = [
            (game.get(f) or "").lower()
            for f in ("player1_name", "player2_name",
                      "player1_partner_name", "player2_partner_name")
        ]
        candidates = [c for c in candidates if c]

        for filter_name in self.config.player_names:
            for candidate in candidates:
                if filter_name == candidate or filter_name in candidate or candidate in filter_name:
                    return True, filter_name

        return False, ""

    # ------------------------------------------------------------------
    # Helpers
    # ------------------------------------------------------------------

    def _error_result(self, msg: str) -> Dict:
        return {
            "success": False,
            "data": {"team_matches_count": 0, "games_count": 0,
                     "sets_count": 0, "points_count": 0, "team_matches": []},
            "errors": [{"error": msg}],
        }


# ---------------------------------------------------------------------------
# Config & entry point
# ---------------------------------------------------------------------------

class LiveCenterConfig:
    def __init__(self, date=None, year=None, month=None,
                 limit_matches=None, skip_points=False, player_names=None):
        self.date          = date
        self.year          = year
        self.month         = month
        self.limit_matches = limit_matches
        self.skip_points   = skip_points
        # Frozenset of lowercase names for O(1) lookup. Empty = no filter.
        self.player_names  = frozenset(n.strip().lower() for n in player_names if n.strip()) if player_names else frozenset()


def log_info(msg: str):
    print(f"[INFO] {msg}", file=sys.stderr, flush=True)


def log_error(msg: str):
    print(f"[ERROR] {msg}", file=sys.stderr, flush=True)


def main():
    parser = argparse.ArgumentParser(description="Scrape Live Center from profixio.com (API mode, no browser)")
    group  = parser.add_mutually_exclusive_group(required=True)
    group.add_argument("--date",  help="Single date (YYYY-MM-DD)")
    group.add_argument("--month", help="All dates in month (YYYY-MM)")
    group.add_argument("--year",  help="All dates in year (YYYY)")
    parser.add_argument("--limit-matches", type=int, help="Max team matches per date")
    parser.add_argument("--skip-points",   action="store_true", help="Skip set detail scraping")
    parser.add_argument("--player-names",  default=None,
                        help="Pipe-separated player names to filter set-detail scraping, "
                             "e.g. 'Andersson, Erik|Johansson, Anna' (case-insensitive)")

    args = parser.parse_args()
    config  = LiveCenterConfig(
        date=args.date, year=args.year, month=args.month,
        limit_matches=args.limit_matches, skip_points=args.skip_points,
        player_names=args.player_names.split("|") if args.player_names else None,
    )
    scraper = LiveCenterScraper(config)
    result  = scraper.run()
    print(json.dumps(result, indent=2, ensure_ascii=False))


if __name__ == "__main__":
    main()
