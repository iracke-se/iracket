# Live Center Scraper - Step-by-Step Walkthrough

## 🚀 You Type This Command

```bash
php artisan scraper:run live_center --date=2025-12-17
```

---

## STEP 1️⃣: Command Entry Point

**File:** `app/Console/Commands/ScraperCommand.php`

```php
// Line 13-31: Command signature
protected $signature = 'scraper:run
    {type : The type of scrape (rankings, players, transitions, series, series_matches, live_center)}
    {--date= : Date to scrape (YYYY-MM-DD format, for live_center)}';

public function handle(): int
{
    $type = $this->argument('type');  // Gets "live_center"
    $date = $this->option('date');     // Gets "2025-12-17"

    // Build parameters
    $parameters = [];
    if ($type === 'live_center') {
        if ($this->option('date')) {
            $parameters['date'] = $this->option('date');  // Stores: ['date' => '2025-12-17']
        }
    }

    // Line 166: Run synchronously
    return $this->runSynchronously($type, $parameters);
}
```

**Result:** `$parameters = ['date' => '2025-12-17']`

---

## STEP 2️⃣: Call PHP Scraper Service

**Still in:** `app/Console/Commands/ScraperCommand.php` (Line 206-257)

```php
protected function runSynchronously(string $type, array $parameters): int
{
    $this->info("Starting live_center scraper...");

    // Line 216: Select the scraper class
    $scraperClass = match ($type) {
        'live_center' => \App\Services\Scraper\LiveCenterDetailsScraper::class,
        // ...
    };

    // Line 235: Instantiate and call it
    $scraper = app($scraperClass);
    $scraper->setConsoleOutput($this);  // For console logging

    // Line 237: CALL THE SCRAPER!
    $run = $scraper->scrape($parameters);

    // Line 241-250: Display results
    $this->table(['Metric', 'Value'], [
        ['Run ID', $run->id],
        ['Status', $run->status],
        ['Items Scraped', $run->items_scraped],
        ['Items Failed', $run->items_failed],
        ['Duration', $run->duration],
    ]);

    return self::SUCCESS;
}
```

**What happens:** PHP calls `LiveCenterDetailsScraper->scrape($parameters)`

---

## STEP 3️⃣: PHP Service Takes Over

**File:** `app/Services/Scraper/LiveCenterDetailsScraper.php`

```php
// Line 18-67: Main execution method
protected function execute(): void
{
    // Line 20: Extract parameters
    $date = $this->getParameter('date');        // "2025-12-17"
    $limitMatches = $this->getParameter('limit_matches');  // null or number
    $skipPoints = $this->getParameter('skip_points', false);  // false

    $this->info("Starting Live Center details scraper for date: {$date}");

    // Line 42: CALL PYTHON SCRIPT!
    $result = $this->executePythonScraper($date, $limitMatches, $skipPoints, null, null);

    if (!$result['success']) {
        throw new \Exception("Python Live Center scraper failed");
    }

    // Line 48: Get the JSON response
    $data = $result['data'];
    // Contains:
    // {
    //   "team_matches_count": 3,
    //   "games_count": 25,
    //   "sets_count": 96,
    //   "points_count": 2400,
    //   "team_matches": [...]
    // }

    // Line 52-54: SAVE TO DATABASE!
    if (!empty($data['team_matches'])) {
        $this->saveTeamMatchesToDatabase($data['team_matches'], $date);
    }

    $this->info("Scrape completed: {$data['team_matches_count']} team matches saved");
}
```

---

## STEP 4️⃣: Execute Python Script

**Still in:** `app/Services/Scraper/LiveCenterDetailsScraper.php` (Line 135-212)

```php
protected function executePythonScraper(?string $date, ?int $limitMatches,
                                        bool $skipPoints, ?string $year = null,
                                        ?string $month = null): array
{
    // Line 138: Get Python path
    $pythonBinary = config('scraper.python.binary', 'python3');  // 'python3'
    $scriptPath = base_path('scripts/scraper/livecenter_scraper.py');

    // Line 144-162: Build command
    $arguments = [
        $pythonBinary,          // 'python3'
        $scriptPath,            // '/path/to/livecenter_scraper.py'
    ];

    if ($date) {
        $arguments[] = '--date';
        $arguments[] = $date;   // '2025-12-17'
    }

    if ($limitMatches) {
        $arguments[] = '--limit-matches';
        $arguments[] = (string) $limitMatches;
    }

    if ($skipPoints) {
        $arguments[] = '--skip-points';
    }

    // Line 169: Create process
    $process = new Process($arguments);
    $process->setTimeout(null);  // No timeout - can run long

    // Line 172: Log what we're doing
    $this->info("Executing: python3 scripts/scraper/livecenter_scraper.py --date 2025-12-17");

    // Line 175: RUN THE PROCESS!
    $process->mustRun(function ($type, $buffer) {
        // Real-time output from Python
        $this->info("Python: {$buffer}");
    });

    // Line 187: Get output
    $output = $process->getOutput();
    // This is the JSON returned by Python script

    // Line 193: Parse JSON
    $result = json_decode($output, true);

    // Line 199: Return to caller
    return $result;
}
```

**Command executed:**
```bash
python3 scripts/scraper/livecenter_scraper.py --date 2025-12-17
```

---

## STEP 5️⃣: Python Script Starts

**File:** `scripts/scraper/livecenter_scraper.py`

```python
# Line 63-168: Main run() method
async def run(self) -> Dict:
    """Execute scraping workflow"""

    # Line 65-81: Launch browser
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context()
        page = await context.new_page()
```

**What happens:**
- Playwright opens **headless Chrome browser**
- Browser starts in background
- No visible window (headless mode)

---

## STEP 6️⃣: Browser Navigation

**Still in:** `scripts/scraper/livecenter_scraper.py` (Line 87-108)

```python
# STEP 1: Login
log_info("Establishing session...")
await page.goto(
    'https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT',
    wait_until="domcontentloaded",
    timeout=60000
)
await page.wait_for_timeout(2000)

# STEP 2: Go to Live Center
log_info("Navigating to Live Center...")
await page.goto(
    'https://www.profixio.com/fx/livecenter/',
    wait_until="domcontentloaded",
    timeout=60000
)
await page.wait_for_timeout(2000)

# STEP 3: Set division to "All"
log_info("Setting division to 'All'...")
await page.evaluate("""
    () => {
        const divSelect = document.getElementById('filter4_id');
        if (divSelect) {
            divSelect.value = '';
            divSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
""")
```

**Browser URLs visited:**
1. `https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT`
2. `https://www.profixio.com/fx/livecenter/`

**Page state:** Live Center homepage loaded

---

## STEP 7️⃣: Select Date

**Still in:** `scripts/scraper/livecenter_scraper.py` (Line 206-321)

```python
async def scrape_date(self, date: str) -> Dict:
    """Scrape all team matches for a single date."""

    # Line 209: SET DATE FILTER
    await self.set_date_filter('2025-12-17')

    # In set_date_filter():
    # Line 279-303:
    await page.evaluate(f"""
        () => {{
            const dateSelect = document.getElementById('filter1_id');
            // Find option with value '2025-12-17'
            dateSelect.value = '2025-12-17';
        }}
    """)

    # Line 310-317: Trigger AJAX load
    await page.evaluate("""
        () => {
            const dateSelect = document.getElementById('filter1_id');
            if (typeof get_match_list_by_obj === 'function') {
                get_match_list_by_obj('SBTF.SE.BT', dateSelect, 0, 1);
                // This calls profixio's own AJAX function
            }
        }
    """)

    # Line 320: Wait for AJAX response
    await page.wait_for_timeout(3000)
```

**What happens on page:**
- Date dropdown (`filter1_id`) is set to `2025-12-17`
- JavaScript function `get_match_list_by_obj()` is called
- AJAX request sent to profixio backend
- Match list updates on left panel
- Wait 3 seconds for response

---

## STEP 8️⃣: Extract Team Matches

**Still in:** `scripts/scraper/livecenter_scraper.py` (Line 322-398)

```python
async def get_team_matches(self) -> List[Dict]:
    """Extract team matches from the left panel"""

    # Line 324-396: JavaScript code runs IN THE BROWSER
    matches = await page.evaluate("""
        () => {
            const matchesDiv = document.getElementById('matches');
            const rows = matchesDiv.querySelectorAll('tr');
            let results = [];

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.querySelectorAll('td');

                // Get text from HTML cells
                const teamsText = cells[0].innerText;  // "Team1 - Team2"
                const scoreText = cells[1].innerText;  // "5 - 1"

                // Parse teams
                const dashIndex = teamsText.indexOf(' - ');
                const team1 = teamsText.substring(0, dashIndex).trim();
                const team2 = teamsText.substring(dashIndex + 3).trim();

                // Parse score
                const scoreMatch = scoreText.match(/(\\d+)\\s*-\\s*(\\d+)/);
                const team1Score = parseInt(scoreMatch[1]);
                const team2Score = parseInt(scoreMatch[2]);

                // Get match ID from onclick attribute
                const onclick = row.getAttribute('onclick');
                const matchIdRegex = onclick.match(/(\\d+)/);
                const matchId = matchIdRegex[1];

                // Determine status
                let status = 'completed';
                if (scoreText.includes('Live') || scoreText.includes('Pågår')) {
                    status = 'in_progress';
                }

                // Store result
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
```

**Example output:**
```python
[
    {
        "team1_name": "Varbergs BTK Orange",
        "team2_name": "Åsa IF Tigers",
        "team1_score": 8,
        "team2_score": 2,
        "profixio_match_id": "12345",
        "status": "completed",
        "row_index": 0
    },
    {
        "team1_name": "Kosta SK",
        "team2_name": "Spårvägens BTK",
        "team1_score": 6,
        "team2_score": 4,
        "profixio_match_id": "12346",
        "status": "completed",
        "row_index": 1
    }
]
```

**Location:** Left panel of profixio.com Live Center page

---

## STEP 9️⃣: Process Each Team Match

**Still in:** `scripts/scraper/livecenter_scraper.py` (Line 228-274)

For each team match found:

```python
for idx, team_match in enumerate(team_matches):
    # Line 229: Log it
    log_info(f"Processing: {team_match['team1_name']} vs {team_match['team2_name']}")

    # Line 233: GET GAMES FOR THIS MATCH
    games = await self.get_games_for_match(team_match)
    log_info(f"Found {len(games)} games")

    # Line 237-255: Process each game
    for game_idx, game in enumerate(games):
        log_info(f"Game {game_idx+1}: {game.get('game_number')} {game.get('player1_name')} vs {game.get('player2_name')}")

        # GET GAME DETAILS (sets and points)
        game_detail = await self.get_game_detail(team_match, game)
        game['sets'] = game_detail.get('sets', [])

        # Count sets and points
        for s in game['sets']:
            total_sets += 1
            total_points += len(s.get('points', []))

    # Line 257-260: Store games with match
    team_match['games'] = games
    team_match['played_at'] = '2025-12-17'
    result_matches.append(team_match)
```

**What happens:**
1. Click first team match row
2. Games panel appears on right side of screen
3. Extract S1, S2, S3, S4 (and D1, D2 if present)
4. For each game, get set scores and points
5. Build complete data structure

---

## STEP 1️⃣0️⃣: Extract Individual Games

**Still in:** `scripts/scraper/livecenter_scraper.py` (Line 400-620)

```python
async def get_games_for_match(self, team_match: Dict) -> List[Dict]:
    """Click team match and extract individual games"""

    # Line 406-437: Click the team match row
    clicked = await page.evaluate(f"""
        () => {{
            const matchesDiv = document.getElementById('matches');
            const rows = matchesDiv.querySelectorAll('tr');
            const row = rows[{row_index}];

            row.click();  // CLICK!
        }}
    """)

    # Line 444: Wait for games to load
    await page.wait_for_timeout(2000)

    # Line 447-620: Extract games from detail panel
    games = await page.evaluate("""
        () => {
            // Find the detail panel (could be various containers)
            let detailContainer = null;

            // Strategy 1: Look for specific div IDs
            const containers = [
                document.getElementById('match_details'),
                document.getElementById('match_detail'),
                document.querySelector('.match-details'),
                // ... more selectors ...
            ];

            for (const c of containers) {
                if (c && c.querySelectorAll('tr').length > 0) {
                    detailContainer = c;
                    break;
                }
            }

            // Strategy 2: Look for table with rows starting with S1, S2, etc.
            if (!detailContainer) {
                const allTables = document.querySelectorAll('table');
                for (const table of allTables) {
                    const rows = table.querySelectorAll('tr');
                    for (const row of rows) {
                        const firstCell = row.querySelector('td');
                        const firstCellText = firstCell?.innerText?.trim() || '';

                        // Regex: /^[SD]\\d$/ matches S1, S2, D1, D2, etc.
                        if (/^[SD]\\d$/.test(firstCellText)) {
                            detailContainer = table;
                            break;
                        }
                    }
                    if (detailContainer) break;
                }
            }

            // Now extract games
            const rows = detailContainer.querySelectorAll('tr');
            let games = [];

            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const text = row.innerText || '';

                // Look for game pattern: S1, S2, D1, D2
                const gameMatch = text.match(/\\b([SD]\\d)\\b/);
                if (!gameMatch) continue;

                const gameNumber = gameMatch[1];  // "S1", "D1", etc.
                const gameType = gameNumber.startsWith('D') ? 'doubles' : 'singles';

                // Extract player names
                // Format: "S1 LastName, FirstName - LastName, FirstName 3 - 0"
                const nameScorePattern = /([A-ZÅÄÖa-zåäö\\s,]+?)\\s*-\\s*([A-ZÅÄÖa-zåäö\\s,]+?)\\s+(\\d+)\\s*-\\s*(\\d+)/;
                const match = text.match(nameScorePattern);

                if (match) {
                    games.push({
                        game_number: gameNumber,
                        game_type: gameType,
                        player1_name: match[1].trim(),
                        player2_name: match[2].trim(),
                        player1_sets: parseInt(match[3]),
                        player2_sets: parseInt(match[4]),
                        winner_name: parseInt(match[3]) > parseInt(match[4])
                                    ? match[1].trim()
                                    : match[2].trim(),
                        profixio_game_id: null  // May be extracted from href
                    });
                }
            }

            return games;
        }
    """)

    return games
```

**Example output:**
```python
[
    {
        "game_number": "S1",
        "game_type": "singles",
        "player1_name": "Johansson, Christian",
        "player2_name": "Nilsson, Rickard",
        "player1_sets": 3,
        "player2_sets": 2,
        "winner_name": "Johansson, Christian",
        "profixio_game_id": None
    },
    {
        "game_number": "S2",
        "game_type": "singles",
        "player1_name": "Edman, Magnus",
        "player2_name": "Korenado, Tomas",
        "player1_sets": 3,
        "player2_sets": 0,
        "winner_name": "Edman, Magnus",
        "profixio_game_id": None
    }
]
```

**Location:** Right panel of profixio.com Live Center (match detail panel)

---

## STEP 1️⃣1️⃣: Extract Set Scores

**Still in:** `scripts/scraper/livecenter_scraper.py` (Line 622-780)

```python
async def get_game_detail(self, team_match: Dict, game: Dict) -> Dict:
    """Get set scores and point-by-point data for a game"""

    # This function looks at the game detail table and extracts:
    # - Set headers: "1.  2.  3."
    # - Score lines: "11 - 8    11 - 5    11 - 9"

    detail = await page.evaluate(f"""
        () => {{
            const gameNumber = '{game["game_number"]}';

            // Find the row with set headers
            // Pattern: "1.  2.  3."
            const setHeaderPattern = /1\\.\\s+2\\.\\s+3\\./;

            let setHeaderRow = null;
            const allText = document.body.innerText;

            // Find each set with scores
            // Pattern: "11 - 8    11 - 5    11 - 9"
            const scorePattern = /(\\d+)\\s*-\\s*(\\d+)/g;
            const matches = [...allText.matchAll(scorePattern)];

            // Group into sets (typically 3 scores per set)
            let sets = [];
            for (let i = 0; i < matches.length; i += 3) {
                sets.push({{
                    set_number: sets.length + 1,
                    player1_points: parseInt(matches[i][1]),
                    player2_points: parseInt(matches[i][2]),
                    points: []  // Will fill if not skip-points
                }});
            }

            return {{ sets: sets }};
        }}
    """)

    return detail
```

**Example output:**
```python
{
    "sets": [
        {
            "set_number": 1,
            "player1_points": 11,
            "player2_points": 8,
            "points": [...]  # Point-by-point data
        },
        {
            "set_number": 2,
            "player1_points": 11,
            "player2_points": 5,
            "points": [...]
        },
        {
            "set_number": 3,
            "player1_points": 9,
            "player2_points": 11,
            "points": [...]
        },
        {
            "set_number": 4,
            "player1_points": 8,
            "player2_points": 11,
            "points": [...]
        },
        {
            "set_number": 5,
            "player1_points": 11,
            "player2_points": 9,
            "points": [...]
        }
    ]
}
```

---

## STEP 1️⃣2️⃣: Return JSON to PHP

**Still in:** `scripts/scraper/livecenter_scraper.py` (Line 141-151)

```python
return {
    "success": True,
    "data": {
        "team_matches_count": 3,
        "games_count": 25,
        "sets_count": 96,
        "points_count": 2400,
        "team_matches": [
            {
                "team1_name": "Varbergs BTK Orange",
                "team2_name": "Åsa IF Tigers",
                "team1_score": 8,
                "team2_score": 2,
                "profixio_match_id": "12345",
                "status": "completed",
                "played_at": "2025-12-17",
                "games": [
                    {
                        "game_number": "S1",
                        "game_type": "singles",
                        "player1_name": "Johansson, Christian",
                        "player2_name": "Nilsson, Rickard",
                        "player1_sets": 3,
                        "player2_sets": 2,
                        "winner_name": "Johansson, Christian",
                        "sets": [
                            {
                                "set_number": 1,
                                "player1_points": 11,
                                "player2_points": 8,
                                "points": [...]
                            },
                            # ... more sets ...
                        ]
                    },
                    # ... more games ...
                ]
            },
            # ... more team matches ...
        ]
    },
    "errors": []
}
```

**Output:** JSON string sent to PHP via stdout

---

## STEP 1️⃣3️⃣: Save to Database - Team Match

**Back in PHP:** `app/Services/Scraper/LiveCenterDetailsScraper.php` (Line 217-323)

```php
protected function saveTeamMatchesToDatabase(array $teamMatches, ?string $date): void
{
    $this->info("Saving {$totalMatches} team matches to database...");

    foreach ($teamMatches as $index => $teamMatch) {
        $this->info("[{$matchNum}/{$totalMatches}] Processing: {$teamMatch['team1_name']} vs {$teamMatch['team2_name']}...");

        // Line 232-239: Check for duplicates
        $existing = LiveMatchDetail::where('team1_name', $teamMatch['team1_name'])
            ->where('team2_name', $teamMatch['team2_name'])
            ->where('played_at', $matchDate)
            ->first();

        if ($existing) {
            $this->info("Skipped (already scraped in run #{$existing->scraper_run_id})");
            continue;
        }

        // Line 247-260: INSERT TEAM MATCH DETAIL
        $detailId = DB::table('live_match_details')->insertGetId([
            'scraper_run_id' => $this->run->id,
            'division' => $teamMatch['division'] ?? null,
            'team1_name' => $teamMatch['team1_name'],
            'team2_name' => $teamMatch['team2_name'],
            'team1_score' => $teamMatch['team1_score'] ?? null,
            'team2_score' => $teamMatch['team2_score'] ?? null,
            'played_at' => $matchDate,
            'profixio_match_id' => $teamMatch['profixio_match_id'] ?? null,
            'status' => $teamMatch['status'] ?? 'completed',
            'is_synced' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->run->incrementScraped();
```

**SQL executed:**
```sql
INSERT INTO live_match_details
(scraper_run_id, division, team1_name, team2_name, team1_score, team2_score, played_at, profixio_match_id, status, is_synced, created_at, updated_at)
VALUES
(10, NULL, 'Varbergs BTK Orange', 'Åsa IF Tigers', 8, 2, '2025-12-17', '12345', 'completed', 0, NOW(), NOW());
```

**Database Result:**
```
live_match_details Table:
┌────┬───────────────────────┬─────────────────────┬──────┬──────┬────────────┬─────────────┐
│ id │ team1_name            │ team2_name          │ s1   │ s2   │ played_at  │ is_synced   │
├────┼───────────────────────┼─────────────────────┼──────┼──────┼────────────┼─────────────┤
│ 1  │ Varbergs BTK Orange   │ Åsa IF Tigers       │ 8    │ 2    │ 2025-12-17 │ 0           │
└────┴───────────────────────┴─────────────────────┴──────┴──────┴────────────┴─────────────┘

detailId = 1
```

---

## STEP 1️⃣4️⃣: Save to Database - Games

**Still in:** `app/Services/Scraper/LiveCenterDetailsScraper.php` (Line 265-283)

```php
// Line 265: Get games for this match
$games = $teamMatch['games'] ?? [];
foreach ($games as $game) {
    // Line 267-283: INSERT GAME
    $gameId = DB::table('live_match_games')->insertGetId([
        'live_match_detail_id' => $detailId,  // FK to team match (1)
        'game_number' => $game['game_number'],  // "S1"
        'game_type' => $game['game_type'] ?? 'singles',  // "singles"
        'player1_name' => $game['player1_name'] ?? '',  // "Johansson, Christian"
        'player2_name' => $game['player2_name'] ?? '',  // "Nilsson, Rickard"
        'player1_partner_name' => $game['player1_partner_name'] ?? null,
        'player2_partner_name' => $game['player2_partner_name'] ?? null,
        'player1_sets' => $game['player1_sets'] ?? null,  // 3
        'player2_sets' => $game['player2_sets'] ?? null,  // 2
        'winner_name' => $game['winner_name'] ?? null,  // "Johansson, Christian"
        'profixio_game_id' => $game['profixio_game_id'] ?? null,
        'is_synced' => false,
        'synced_match_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
```

**SQL executed:**
```sql
INSERT INTO live_match_games
(live_match_detail_id, game_number, game_type, player1_name, player2_name, player1_sets, player2_sets, winner_name, is_synced, created_at, updated_at)
VALUES
(1, 'S1', 'singles', 'Johansson, Christian', 'Nilsson, Rickard', 3, 2, 'Johansson, Christian', 0, NOW(), NOW());
```

**Database Result:**
```
live_match_games Table:
┌────┬──────────────────────┬─────────────────┬──────────────────────┬──────────────────┬────────────┬──────────┐
│ id │ live_match_detail_id │ game_number      │ player1_name         │ player2_name     │ player1_sets │ is_synced│
├────┼──────────────────────┼─────────────────┼──────────────────────┼──────────────────┼────────────┼──────────┤
│ 1  │ 1                    │ S1               │ Johansson, Christian │ Nilsson, Rickard │ 3          │ 0        │
│ 2  │ 1                    │ S2               │ Edman, Magnus        │ Korenado, Tomas  │ 3          │ 0        │
│ 3  │ 1                    │ S3               │ Johansson, Christian │ Korenado, Tomas  │ 0          │ 0        │
│ 4  │ 1                    │ S4               │ Edman, Magnus        │ Nilsson, Rickard │ 3          │ 0        │
│ 5  │ 1                    │ D1               │ (doubles)            │ (doubles)        │ 3          │ 0        │
└────┴──────────────────────┴─────────────────┴──────────────────────┴──────────────────┴────────────┴──────────┘

gameId = 1 (for S1)
```

---

## STEP 1️⃣5️⃣: Save to Database - Sets

**Still in:** `app/Services/Scraper/LiveCenterDetailsScraper.php` (Line 286-295)

```php
// Line 286: Get sets for this game
$sets = $game['sets'] ?? [];
foreach ($sets as $set) {
    // Line 288-295: INSERT SET
    $setId = DB::table('live_match_sets')->insertGetId([
        'live_match_game_id' => $gameId,  // FK to game (1)
        'set_number' => $set['set_number'],  // 1, 2, 3, 4, 5
        'player1_points' => $set['player1_points'],  // 11, 11, 9, 8, 11
        'player2_points' => $set['player2_points'],  // 8, 5, 11, 11, 9
        'created_at' => now(),
        'updated_at' => now(),
    ]);
```

**SQL executed:**
```sql
-- For game S1 (gameId = 1)
INSERT INTO live_match_sets (live_match_game_id, set_number, player1_points, player2_points, created_at, updated_at)
VALUES
(1, 1, 11, 8, NOW(), NOW()),
(1, 2, 11, 5, NOW(), NOW()),
(1, 3, 9, 11, NOW(), NOW()),
(1, 4, 8, 11, NOW(), NOW()),
(1, 5, 11, 9, NOW(), NOW());
```

**Database Result:**
```
live_match_sets Table:
┌────┬──────────────────────┬──────────────┬────────────────┬────────────────┐
│ id │ live_match_game_id   │ set_number   │ player1_points │ player2_points │
├────┼──────────────────────┼──────────────┼────────────────┼────────────────┤
│ 1  │ 1                    │ 1            │ 11             │ 8              │
│ 2  │ 1                    │ 2            │ 11             │ 5              │
│ 3  │ 1                    │ 3            │ 9              │ 11             │
│ 4  │ 1                    │ 4            │ 8              │ 11             │
│ 5  │ 1                    │ 5            │ 11             │ 9              │
└────┴──────────────────────┴──────────────┴────────────────┴────────────────┘

setId = 1 (for Set 1)
```

---

## STEP 1️⃣6️⃣: Save to Database - Points (Optional)

**Still in:** `app/Services/Scraper/LiveCenterDetailsScraper.php` (Line 298-318)

```php
// Line 298: Get points for this set (if not skipped)
$points = $set['points'] ?? [];
if (!empty($points)) {
    $pointRows = [];
    foreach ($points as $point) {
        // Line 302-311: Build point row
        $pointRows[] = [
            'live_match_set_id' => $setId,  // FK to set (1)
            'point_number' => $point['point_number'],  // 1, 2, 3, ...
            'player1_points' => $point['player1_points'],  // 1, 2, 2, ...
            'player2_points' => $point['player2_points'],  // 0, 0, 1, ...
            'serve' => $point['serve'] ?? null,  // 'A', 'B', 'A', ...
            'comment' => $point['comment'] ?? null,  // null or text
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Line 315-317: Batch insert (500 at a time)
    foreach (array_chunk($pointRows, 500) as $chunk) {
        DB::table('live_match_points')->insert($chunk);
    }
}
```

**SQL executed (batch):**
```sql
INSERT INTO live_match_points
(live_match_set_id, point_number, player1_points, player2_points, serve, created_at, updated_at)
VALUES
(1, 1, 1, 0, 'A', NOW(), NOW()),
(1, 2, 2, 0, 'A', NOW(), NOW()),
(1, 3, 2, 1, 'B', NOW(), NOW()),
(1, 4, 3, 1, 'A', NOW(), NOW()),
(1, 5, 3, 2, 'B', NOW(), NOW()),
... (up to 500 points at a time) ...
(1, 19, 11, 8, 'A', NOW(), NOW());
```

**Database Result:**
```
live_match_points Table:
┌────┬──────────────────────┬──────────────┬────────────────┬────────────────┬────────┐
│ id │ live_match_set_id    │ point_number │ player1_points │ player2_points │ serve  │
├────┼──────────────────────┼──────────────┼────────────────┼────────────────┼────────┤
│ 1  │ 1                    │ 1            │ 1              │ 0              │ A      │
│ 2  │ 1                    │ 2            │ 2              │ 0              │ A      │
│ 3  │ 1                    │ 3            │ 2              │ 1              │ B      │
│ 4  │ 1                    │ 4            │ 3              │ 1              │ A      │
│ 5  │ 1                    │ 5            │ 3              │ 2              │ B      │
...
│ 19 │ 1                    │ 19           │ 11             │ 8              │ A      │
└────┴──────────────────────┴──────────────┴────────────────┴────────────────┴────────┘
```

---

## STEP 1️⃣7️⃣: Display Results

**Back in:** `app/Console/Commands/ScraperCommand.php` (Line 241-250)

```php
$this->table(
    ['Metric', 'Value'],
    [
        ['Run ID', $run->id],              // 10
        ['Status', $run->status],          // 'completed'
        ['Items Scraped', $run->items_scraped],  // 25 (games)
        ['Items Failed', $run->items_failed],    // 0
        ['Duration', $run->duration],      // '1m 22s'
    ]
);
```

**Console Output:**
```
+---------------+-----------+
| Metric        | Value     |
+---------------+-----------+
| Run ID        | 10        |
| Status        | completed |
| Items Scraped | 25        |
| Items Failed  | 0         |
| Duration      | 1m 22s    |
+---------------+-----------+

Scrape completed!
```

---

## 📊 Final Database State

### **Complete Data Hierarchy:**

```
live_match_details (1 row)
  ├─ id: 1
  ├─ team1_name: "Varbergs BTK Orange"
  ├─ team2_name: "Åsa IF Tigers"
  ├─ team1_score: 8
  ├─ team2_score: 2
  ├─ played_at: "2025-12-17"
  └─ is_synced: 0

    live_match_games (5 rows)
      ├─ id: 1, game_number: "S1", players: Johansson vs Nilsson, sets: 3-2, is_synced: 0
      ├─ id: 2, game_number: "S2", players: Edman vs Korenado, sets: 3-0, is_synced: 0
      ├─ id: 3, game_number: "S3", players: Johansson vs Korenado, sets: 0-3, is_synced: 0
      ├─ id: 4, game_number: "S4", players: Edman vs Nilsson, sets: 3-0, is_synced: 0
      └─ id: 5, game_number: "D1", (doubles), sets: 3-2, is_synced: 0

        live_match_sets (25 rows)
          ├─ Sets for S1: 5 sets (11-8, 11-5, 9-11, 8-11, 11-9)
          ├─ Sets for S2: 3 sets (11-5, 11-7, 11-3)
          ├─ Sets for S3: 3 sets (8-11, 6-11, 9-11)
          ├─ Sets for S4: 3 sets (11-5, 11-7, 11-3)
          └─ Sets for D1: 5 sets (varies)

            live_match_points (2400 rows)
              └─ Point-by-point data for each set (if not --skip-points)
```

---

## ➡️ Next Step: Sync

```bash
php artisan scraper:sync live_center
```

This:
1. Reads `live_match_games` where `is_synced = 0`
2. Finds `player1_name` and `player2_name` in `users` table
3. **Only** if both players exist:
   - Creates record in `matches` table
   - Sets `live_match_game_id` FK
   - Updates `live_match_games.is_synced = 1`

---

*Complete scraper walkthrough - Feb 11, 2026*
