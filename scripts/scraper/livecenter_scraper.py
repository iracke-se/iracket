#!/usr/bin/env python3
"""
Profixio Live Center Scraper

Scrapes detailed match data from profixio.com Live Center using Playwright.
Extracts team matches, individual games, set scores, and point-by-point data.

Usage:
    python3 livecenter_scraper.py --date 2025-11-11 [--limit-matches 5] [--skip-points]

Output:
    JSON to stdout with structure:
    {
        "success": true,
        "data": {
            "team_matches_count": 6,
            "games_count": 48,
            "sets_count": 144,
            "points_count": 2880,
            "team_matches": [...]
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
from typing import List, Dict, Optional
from playwright.async_api import async_playwright, Page, Browser, BrowserContext


class LiveCenterConfig:
    """Configuration for Live Center scraper run"""

    def __init__(self, date: Optional[str] = None, year: Optional[str] = None,
                 month: Optional[str] = None, limit_matches: Optional[int] = None,
                 skip_points: bool = False):
        self.date = date    # format: YYYY-MM-DD (single date mode)
        self.year = year    # format: YYYY (year mode - scrape all dates in year)
        self.month = month  # format: YYYY-MM (month mode - scrape all dates in month)
        self.limit_matches = limit_matches
        self.skip_points = skip_points
        self.login_url = "https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT"
        self.livecenter_url = "https://www.profixio.com/fx/livecenter/"
        self.callback_url = "https://www.profixio.com/fx/livecenter/callback.php"
        self.timeout = 60000


class LiveCenterScraper:
    """Main Live Center scraper class"""

    def __init__(self, config: LiveCenterConfig):
        self.config = config
        self.browser: Optional[Browser] = None
        self.context: Optional[BrowserContext] = None
        self.page: Optional[Page] = None
        self.errors: List[Dict] = []

    async def run(self) -> Dict:
        """Execute scraping workflow"""
        async with async_playwright() as p:
            # Use system Chromium if available — check env var and common paths
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
                    '--disable-setuid-sandbox'
                ]
            }
            if chrome_path:
                launch_args['executable_path'] = chrome_path
                log_info(f"Using system Chromium: {chrome_path}")

            self.browser = await p.chromium.launch(**launch_args)
            self.context = await self.browser.new_context()
            self.page = await self.context.new_page()
            self.page.set_default_timeout(60000)

            try:
                # Step 1: Establish session by visiting login page
                log_info("Establishing session...")
                await self.page.goto(self.config.login_url, wait_until="domcontentloaded", timeout=60000)
                await self.page.wait_for_timeout(2000)

                # Step 2: Navigate to Live Center
                log_info("Navigating to Live Center...")
                await self.page.goto(self.config.livecenter_url, wait_until="domcontentloaded", timeout=60000)
                await self.page.wait_for_timeout(2000)

                # Step 3: Select "All" division
                log_info("Setting division to 'All'...")
                await self.page.evaluate("""
                    () => {
                        const divSelect = document.getElementById('filter4_id');
                        if (divSelect) {
                            divSelect.value = '';
                            divSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                """)
                await self.page.wait_for_timeout(2000)

                # Determine which dates to scrape
                if self.config.year:
                    dates = await self.get_dates_for_year(self.config.year)
                    log_info(f"Found {len(dates)} dates for year {self.config.year}")
                elif self.config.month:
                    dates = await self.get_dates_for_month(self.config.month)
                    log_info(f"Found {len(dates)} dates for month {self.config.month}")
                elif self.config.date:
                    dates = [self.config.date]
                else:
                    raise Exception("No date, month, or year specified")

                if not dates:
                    filter_type = "year" if self.config.year else "month" if self.config.month else "date"
                    filter_value = self.config.year or self.config.month or self.config.date
                    raise Exception(f"No dates found for {filter_type} {filter_value}")

                # Process each date
                all_team_matches = []
                total_games = 0
                total_sets = 0
                total_points = 0

                for date_idx, date in enumerate(dates):
                    log_info(f"--- Date {date_idx+1}/{len(dates)}: {date} ---")
                    result = await self.scrape_date(date)
                    all_team_matches.extend(result['team_matches'])
                    total_games += result['games']
                    total_sets += result['sets']
                    total_points += result['points']

                return {
                    "success": True,
                    "data": {
                        "team_matches_count": len(all_team_matches),
                        "games_count": total_games,
                        "sets_count": total_sets,
                        "points_count": total_points,
                        "team_matches": all_team_matches
                    },
                    "errors": self.errors
                }

            except Exception as e:
                log_error(f"Fatal error: {e}")
                return {
                    "success": False,
                    "data": {
                        "team_matches_count": 0,
                        "games_count": 0,
                        "sets_count": 0,
                        "points_count": 0,
                        "team_matches": []
                    },
                    "errors": [{"error": str(e)}]
                }

            finally:
                await self.browser.close()

    async def get_dates_for_year(self, year: str) -> List[str]:
        """Get all available dates from the dropdown that match the given year."""
        dates = await self.page.evaluate(f"""
            () => {{
                const dateSelect = document.getElementById('filter1_id');
                if (!dateSelect) return [];
                const dates = [];
                for (let i = 0; i < dateSelect.options.length; i++) {{
                    const val = dateSelect.options[i].value;
                    if (val.startsWith('{year}-')) {{
                        dates.push(val);
                    }}
                }}
                return dates;
            }}
        """)
        return dates

    async def get_dates_for_month(self, month: str) -> List[str]:
        """Get all available dates from the dropdown that match the given month (YYYY-MM)."""
        dates = await self.page.evaluate(f"""
            () => {{
                const dateSelect = document.getElementById('filter1_id');
                if (!dateSelect) return [];
                const dates = [];
                for (let i = 0; i < dateSelect.options.length; i++) {{
                    const val = dateSelect.options[i].value;
                    if (val.startsWith('{month}-')) {{
                        dates.push(val);
                    }}
                }}
                return dates;
            }}
        """)
        return dates

    async def scrape_date(self, date: str) -> Dict:
        """Scrape all team matches for a single date. Returns counts and team_matches list."""
        # Set date filter and trigger AJAX load
        await self.set_date_filter(date)

        # Get team matches list
        team_matches = await self.get_team_matches()
        log_info(f"Found {len(team_matches)} team matches for {date}")

        if not team_matches:
            return {'team_matches': [], 'games': 0, 'sets': 0, 'points': 0}

        # Apply limit
        if self.config.limit_matches and len(team_matches) > self.config.limit_matches:
            team_matches = team_matches[:self.config.limit_matches]
            log_info(f"Limited to {len(team_matches)} team matches")

        result_matches = []
        total_games = 0
        total_sets = 0
        total_points = 0

        for idx, team_match in enumerate(team_matches):
            log_info(f"Processing team match {idx+1}/{len(team_matches)}: {team_match.get('team1_name', '?')} vs {team_match.get('team2_name', '?')}")

            try:
                # Click team match to see individual games
                games = await self.get_games_for_match(team_match)
                log_info(f"  Found {len(games)} games")

                # Process each game for set scores
                for game_idx, game in enumerate(games):
                    log_info(f"  Processing game {game_idx+1}/{len(games)}: {game.get('game_number', '?')} {game.get('player1_name', '?')} vs {game.get('player2_name', '?')}")

                    try:
                        game_detail = await self.get_game_detail(team_match, game)
                        game['sets'] = game_detail.get('sets', [])

                        for s in game['sets']:
                            total_sets += 1
                            total_points += len(s.get('points', []))

                    except Exception as e:
                        self.errors.append({
                            "team_match": f"{team_match.get('team1_name')} vs {team_match.get('team2_name')}",
                            "game": game.get('game_number', '?'),
                            "error": str(e)
                        })
                        log_error(f"  Error getting game detail: {e}")
                        game['sets'] = []

                team_match['games'] = games
                team_match['played_at'] = date  # Add date to each team match
                total_games += len(games)
                result_matches.append(team_match)

            except Exception as e:
                self.errors.append({
                    "team_match": f"{team_match.get('team1_name', '?')} vs {team_match.get('team2_name', '?')}",
                    "error": str(e)
                })
                log_error(f"Error processing team match: {e}")

        return {
            'team_matches': result_matches,
            'games': total_games,
            'sets': total_sets,
            'points': total_points,
        }

    async def set_date_filter(self, date: str):
        """Set date filter and trigger AJAX load for match list."""
        log_info(f"Setting date to {date}...")
        date_set = await self.page.evaluate(f"""
            () => {{
                const dateSelect = document.getElementById('filter1_id');
                if (!dateSelect) return {{ success: false, error: 'Date dropdown not found' }};

                let found = false;
                for (let i = 0; i < dateSelect.options.length; i++) {{
                    if (dateSelect.options[i].value === '{date}') {{
                        dateSelect.value = '{date}';
                        found = true;
                        break;
                    }}
                }}

                if (!found) {{
                    let available = [];
                    for (let i = 0; i < Math.min(dateSelect.options.length, 20); i++) {{
                        available.push(dateSelect.options[i].value);
                    }}
                    return {{ success: false, error: 'Date not found', available: available }};
                }}

                return {{ success: true }};
            }}
        """)

        if not date_set.get('success'):
            available = date_set.get('available', [])
            raise Exception(f"Could not set date {date}. Available dates: {available}")

        # Trigger the AJAX load using the page's own function
        await self.page.evaluate("""
            () => {
                const dateSelect = document.getElementById('filter1_id');
                if (dateSelect && typeof get_match_list_by_obj === 'function') {
                    get_match_list_by_obj('SBTF.SE.BT', dateSelect, 0, 1);
                }
            }
        """)

        # Wait for AJAX response
        await self.page.wait_for_timeout(3000)

    async def get_team_matches(self) -> List[Dict]:
        """Extract team matches from the left panel"""
        matches = await self.page.evaluate("""
            () => {
                const matchesDiv = document.getElementById('matches');
                if (!matchesDiv) return [];

                const rows = matchesDiv.querySelectorAll('tr');
                let results = [];

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.querySelectorAll('td');

                    if (cells.length < 2) continue;

                    const teamsText = cells[0]?.innerText?.trim() || '';
                    const scoreText = cells[1]?.innerText?.trim() || '';

                    // Skip non-match rows
                    if (!teamsText || teamsText.includes('Uppdaterad') || teamsText.includes('Inga matcher')) {
                        continue;
                    }

                    // Parse "Team1 - Team2" format
                    const dashIndex = teamsText.indexOf(' - ');
                    if (dashIndex === -1) continue;

                    const team1 = teamsText.substring(0, dashIndex).trim();
                    const team2 = teamsText.substring(dashIndex + 3).trim();

                    if (!team1 || !team2) continue;

                    // Parse score "5 - 1" or "5-1"
                    let team1Score = null;
                    let team2Score = null;
                    const scoreMatch = scoreText.match(/(\\d+)\\s*-\\s*(\\d+)/);
                    if (scoreMatch) {
                        team1Score = parseInt(scoreMatch[1]);
                        team2Score = parseInt(scoreMatch[2]);
                    }

                    // Get match_id from onclick or data attribute
                    const onclick = row.getAttribute('onclick') || cells[0]?.getAttribute('onclick') || '';
                    let matchId = null;
                    const idMatch = onclick.match(/(\\d+)/);
                    if (idMatch) {
                        matchId = idMatch[1];
                    }

                    // Also try data attributes
                    if (!matchId) {
                        matchId = row.getAttribute('data-match-id') || row.getAttribute('id') || null;
                    }

                    // Check status
                    let status = 'completed';
                    if (scoreText.includes('Live') || scoreText.includes('Pågår')) {
                        status = 'in_progress';
                    }

                    results.push({
                        team1_name: team1,
                        team2_name: team2,
                        team1_score: team1Score,
                        team2_score: team2Score,
                        profixio_match_id: matchId,
                        status: status,
                        row_index: i
                    });
                }

                return results;
            }
        """)

        return matches

    async def get_games_for_match(self, team_match: Dict) -> List[Dict]:
        """Click a team match and extract individual games"""
        row_index = team_match.get('row_index', 0)
        match_id = team_match.get('profixio_match_id')

        # Click the team match row to load games
        clicked = await self.page.evaluate(f"""
            () => {{
                const matchesDiv = document.getElementById('matches');
                if (!matchesDiv) return {{ success: false, error: 'matches div not found' }};

                const rows = matchesDiv.querySelectorAll('tr');
                if ({row_index} >= rows.length) return {{ success: false, error: 'row index out of range' }};

                const row = rows[{row_index}];

                // Try clicking the row
                row.click();

                // Also try triggering onclick handler directly
                const onclick = row.getAttribute('onclick');
                if (onclick) {{
                    try {{ eval(onclick); }} catch(e) {{}}
                }}

                // Also try clicking the first cell
                const firstCell = row.querySelector('td');
                if (firstCell) {{
                    const cellOnclick = firstCell.getAttribute('onclick');
                    if (cellOnclick) {{
                        try {{ eval(cellOnclick); }} catch(e) {{}}
                    }}
                    firstCell.click();
                }}

                return {{ success: true }};
            }}
        """)

        if not clicked.get('success'):
            log_error(f"Failed to click team match: {clicked.get('error')}")
            return []

        # Wait for games panel to load
        await self.page.wait_for_timeout(2000)

        # Extract games from the detail panel
        games = await self.page.evaluate("""
            () => {
                // Look for the game detail panel - could be in various containers
                const containers = [
                    document.getElementById('match_details'),
                    document.getElementById('match_detail'),
                    document.getElementById('game_details'),
                    document.querySelector('.match-details'),
                    document.querySelector('.game-list'),
                    document.querySelector('[id*="detail"]'),
                    document.querySelector('[class*="detail"]'),
                ];

                let detailContainer = null;
                for (const c of containers) {
                    if (c && c.querySelectorAll('tr').length > 0) {
                        detailContainer = c;
                        break;
                    }
                }

                // If no specific container found, find the table with actual game rows
                // (S1, S2, etc.) - NOT team match rows that contain "D1" in team names
                if (!detailContainer) {
                    const matchesDiv = document.getElementById('matches');
                    const allTables = document.querySelectorAll('table');
                    for (const table of allTables) {
                        // Skip the team matches table (left panel)
                        if (matchesDiv && matchesDiv.contains(table)) continue;

                        const rows = table.querySelectorAll('tr');
                        for (const row of rows) {
                            const text = row.innerText || '';
                            // Look for actual game rows: "S1", "S2", etc. at the START of a cell
                            const firstCell = row.querySelector('td');
                            const firstCellText = firstCell?.innerText?.trim() || '';
                            if (/^[SD]\\d$/.test(firstCellText)) {
                                detailContainer = table;
                                break;
                            }
                        }
                        if (detailContainer) break;
                    }
                }

                if (!detailContainer) {
                    return [];
                }

                const rows = detailContainer.querySelectorAll('tr');
                let games = [];

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const text = row.innerText || '';

                    // Match game rows: "S1 Player1 vs Player2 3-0" or similar patterns
                    const gameMatch = text.match(/\\b([SD]\\d)\\b/);
                    if (!gameMatch) continue;

                    const cells = row.querySelectorAll('td');
                    if (cells.length < 2) continue;

                    const gameNumber = gameMatch[1];
                    const gameType = gameNumber.startsWith('D') ? 'doubles' : 'singles';

                    // Extract player names and score from the row
                    let player1Name = '';
                    let player2Name = '';
                    let player1Sets = null;
                    let player2Sets = null;
                    let winnerName = null;
                    let player1Partner = null;
                    let player2Partner = null;

                    // Parse the text content - formats vary but commonly:
                    // "S1 LastName, FirstName - LastName, FirstName 3 - 0"
                    // Or cells might be: [game_num] [player1] [player2] [score]

                    // Try to find players from cells
                    let cellTexts = [];
                    for (let j = 0; j < cells.length; j++) {
                        cellTexts.push(cells[j].innerText.trim());
                    }

                    // Common layout: cells contain game info, player names, and score
                    // Try different parsing strategies
                    let fullText = cellTexts.join(' | ');

                    // Strategy 1: Look for names separated by " - " with score at end
                    const nameScorePattern = /([A-ZÅÄÖa-zåäö\\s,]+?)\\s*-\\s*([A-ZÅÄÖa-zåäö\\s,]+?)\\s+(\\d+)\\s*-\\s*(\\d+)/;
                    const nsMatch = text.match(nameScorePattern);

                    if (nsMatch) {
                        player1Name = nsMatch[1].replace(gameNumber, '').trim();
                        player2Name = nsMatch[2].trim();
                        player1Sets = parseInt(nsMatch[3]);
                        player2Sets = parseInt(nsMatch[4]);
                    } else {
                        // Strategy 2: Parse from individual cells
                        for (let j = 0; j < cellTexts.length; j++) {
                            const cellText = cellTexts[j];

                            // Skip game number cell
                            if (cellText === gameNumber) continue;

                            // Check if it looks like a score
                            const scoreM = cellText.match(/^(\\d+)\\s*-\\s*(\\d+)$/);
                            if (scoreM) {
                                player1Sets = parseInt(scoreM[1]);
                                player2Sets = parseInt(scoreM[2]);
                                continue;
                            }

                            // Check if it contains a name (has letters and possibly comma)
                            if (/[A-Za-zÅÄÖåäö]{2,}/.test(cellText) && !player1Name) {
                                player1Name = cellText;
                            } else if (/[A-Za-zÅÄÖåäö]{2,}/.test(cellText) && !player2Name) {
                                player2Name = cellText;
                            }
                        }
                    }

                    // Handle doubles: names might contain " / " for partners
                    if (gameType === 'doubles') {
                        if (player1Name.includes('/')) {
                            const parts = player1Name.split('/');
                            player1Name = parts[0].trim();
                            player1Partner = parts[1]?.trim() || null;
                        }
                        if (player2Name.includes('/')) {
                            const parts = player2Name.split('/');
                            player2Name = parts[0].trim();
                            player2Partner = parts[1]?.trim() || null;
                        }
                    }

                    // Determine winner
                    if (player1Sets !== null && player2Sets !== null) {
                        if (player1Sets > player2Sets) {
                            winnerName = player1Name;
                        } else if (player2Sets > player1Sets) {
                            winnerName = player2Name;
                        }
                    }

                    // Get game id if available
                    let gameId = row.getAttribute('data-game-id') || row.getAttribute('id') || null;
                    const onclickAttr = row.getAttribute('onclick') || '';
                    const gidMatch = onclickAttr.match(/(\\d+)/);
                    if (!gameId && gidMatch) {
                        gameId = gidMatch[1];
                    }

                    if (player1Name || player2Name) {
                        games.push({
                            game_number: gameNumber,
                            game_type: gameType,
                            player1_name: player1Name,
                            player2_name: player2Name,
                            player1_partner_name: player1Partner,
                            player2_partner_name: player2Partner,
                            player1_sets: player1Sets,
                            player2_sets: player2Sets,
                            winner_name: winnerName,
                            profixio_game_id: gameId,
                            row_index: i
                        });
                    }
                }

                return games;
            }
        """)

        # Deduplicate by game_number and filter out team match rows
        log_info(f"  Raw games from JS: {len(games)}")
        seen = set()
        unique_games = []
        for game in games:
            gn = game.get('game_number', '')
            p1 = game.get('player1_name', '')
            p2 = game.get('player2_name', '')

            # Skip duplicates
            if gn in seen:
                log_info(f"  Skipping duplicate game: {gn} {p1}")
                continue

            # Skip team match rows (real player names have "Lastname, Firstname" format)
            if p1 and ',' not in p1 and ',' not in p2:
                log_info(f"  Skipping non-player row: {gn} {p1} vs {p2}")
                continue

            seen.add(gn)
            unique_games.append(game)

        log_info(f"  After dedup: {len(unique_games)} games (filtered {len(games) - len(unique_games)})")
        return unique_games

    async def get_game_detail(self, team_match: Dict, game: Dict) -> Dict:
        """Click a game row and extract set scores.

        Uses Playwright's native locator API instead of JS evaluate to avoid
        querySelector('td') matching deeply nested descendants of wrapper rows.
        """
        game_number = game.get('game_number', '?')
        player1_name = game.get('player1_name', '')

        try:
            # Find <td> elements whose COMPLETE text is exactly the game number
            # text-is() matches exact text content, so a <td> with "S1\nPlayer..." won't match
            game_cells = self.page.locator(f'td:text-is("{game_number}")')
            count = await game_cells.count()
            log_info(f"    Found {count} cells with text '{game_number}'")

            clicked = False
            for i in range(count):
                cell = game_cells.nth(i)

                # Skip cells inside #matches (left panel with team matches)
                is_in_matches = await cell.evaluate('el => !!el.closest("#matches")')
                if is_in_matches:
                    log_info(f"    Skipping cell {i} (inside #matches)")
                    continue

                # Get parent row text to verify player name
                row_text = await cell.evaluate('el => el.closest("tr")?.innerText || ""')
                if player1_name and player1_name not in row_text:
                    log_info(f"    Skipping cell {i} (no player name match)")
                    continue

                # Click the cell
                await cell.click()
                clicked = True
                log_info(f"    Clicked: {row_text[:150].strip()}")
                break

            if not clicked:
                log_info(f"    Could not find clickable cell for {game_number} ({player1_name})")
                return {'sets': []}

        except Exception as e:
            log_info(f"    Error clicking {game_number}: {e}")
            return {'sets': []}

        # Wait for detail panel to load
        await self.page.wait_for_timeout(2000)

        # Get full page text and parse set scores with Python regex
        page_text = await self.page.inner_text('body')

        # Debug: dump relevant section of page text around set headers
        lines = page_text.split('\n')
        for i, line in enumerate(lines):
            if '1.' in line and '2.' in line:
                start = max(0, i - 2)
                end = min(len(lines), i + 5)
                for j in range(start, end):
                    log_info(f"    PAGE[{j}]: {lines[j][:120]}")
                break

        sets = self._parse_set_scores(page_text)

        log_info(f"    Found {len(sets)} sets for {game_number}")

        await self.page.wait_for_timeout(500)
        return {'sets': sets}

    def _parse_set_scores(self, text: str) -> List[Dict]:
        """Parse set scores from page text.

        The Live Center detail panel shows:
            1.      2.      3.      4.
            11 - 8  11 - 9  8 - 11  11 - 8

        We find the "1.  2.  3." header line and grab scores from the next line.
        """
        lines = text.split('\n')
        for i, line in enumerate(lines):
            stripped = line.strip()
            # Look for set number headers like "1.  2.  3." or "1. 2. 3. 4. 5."
            if re.search(r'\b1\.\s+2\.', stripped):
                log_info(f"    Set headers found at line {i}: '{stripped[:80]}'")
                # Search next few lines for score values
                for j in range(i + 1, min(i + 5, len(lines))):
                    scores_line = lines[j].strip()
                    if not scores_line:
                        continue
                    log_info(f"    Scores line {j}: '{scores_line[:80]}'")
                    # Find all "N - N" or "N-N" patterns
                    score_matches = re.findall(r'(\d{1,2})\s*-\s*(\d{1,2})', scores_line)
                    sets = []
                    for p1_str, p2_str in score_matches:
                        p1, p2 = int(p1_str), int(p2_str)
                        # Valid set scores: at least one player reached 11+
                        if p1 >= 11 or p2 >= 11 or (p1 >= 10 and p2 >= 10):
                            sets.append({
                                'set_number': len(sets) + 1,
                                'player1_points': p1,
                                'player2_points': p2,
                                'points': []
                            })
                    if sets:
                        log_info(f"    Parsed sets: {[(s['player1_points'], s['player2_points']) for s in sets]}")
                        return sets
        log_info("    No set headers found in page text")
        return []



def log_info(message: str):
    """Log to stderr (so stdout is clean JSON)"""
    print(f"[INFO] {message}", file=sys.stderr, flush=True)


def log_error(message: str):
    """Log error to stderr"""
    print(f"[ERROR] {message}", file=sys.stderr, flush=True)


async def main():
    parser = argparse.ArgumentParser(description="Scrape Live Center match details from profixio.com")
    group = parser.add_mutually_exclusive_group(required=True)
    group.add_argument('--date', help='Date to scrape (YYYY-MM-DD format)')
    group.add_argument('--month', help='Month to scrape all dates for (YYYY-MM format)')
    group.add_argument('--year', help='Year to scrape all dates for (YYYY format)')
    parser.add_argument('--limit-matches', type=int, help='Limit number of team matches per date')
    parser.add_argument('--skip-points', action='store_true', help='Skip point-by-point data extraction')

    args = parser.parse_args()

    config = LiveCenterConfig(
        date=args.date,
        year=args.year,
        month=args.month,
        limit_matches=args.limit_matches,
        skip_points=args.skip_points,
    )

    scraper = LiveCenterScraper(config)
    result = await scraper.run()

    # Output JSON to stdout
    print(json.dumps(result, indent=2))


if __name__ == "__main__":
    asyncio.run(main())
