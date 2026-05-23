# Live Center Scraper Investigation

## 🎯 Overview

**Scraper Name:** `LiveCenterDetailsScraper` (PHP) + `livecenter_scraper.py` (Python)

**Purpose:** Extract detailed match data from profixio.com Live Center including:
- Team matches
- Individual games (Singles S1-S4, Doubles D1-D2)
- Set scores
- Point-by-point progression

**Technology Stack:**
- **Frontend**: Playwright (headless browser automation)
- **Backend**: Python 3
- **Wrapper**: Laravel PHP Service

---

## 📂 File Locations

```
scripts/scraper/livecenter_scraper.py    ← Main Python scraper
app/Services/Scraper/LiveCenterDetailsScraper.php    ← PHP wrapper service
app/Console/Commands/ScraperCommand.php  ← CLI command entry point
database/migrations/2026_02_01_100000_*  ← Database tables for scraped data
```

---

## 🔄 How It Works - Complete Flow

### **Phase 1: User Triggers Scraper (PHP)**

```bash
php artisan scraper:run live_center --date=2025-12-17
```

**What happens in PHP (`LiveCenterDetailsScraper`):**

1. **Validates parameters:**
   - `--date=YYYY-MM-DD` (single date)
   - `--month=YYYY-MM` (entire month)
   - `--year=YYYY` (entire year)
   - `--limit-matches=N` (limit matches for testing)
   - `--skip-points` (skip point-by-point data)

2. **Executes Python script:**
   ```php
   $process = new Process([
       'python3',
       'scripts/scraper/livecenter_scraper.py',
       '--date', '2025-12-17'
   ]);
   $process->mustRun();
   $output = json_decode($process->getOutput(), true);
   ```

3. **Saves data to database:**
   - Creates `live_match_details` records
   - Creates `live_match_games` records (with game_number: S1, S2, D1, etc.)
   - Creates `live_match_sets` records
   - Creates `live_match_points` records (if not skipped)

4. **Returns statistics:**
   ```
   Run ID: 10
   Items Scraped: 1
   Duration: 1m 22s
   ```

---

### **Phase 2: Python Scraper Execution**

#### **Step 2.1: Initialize Browser**
```python
async with async_playwright() as p:
    browser = await p.chromium.launch(headless=True)
    page = await browser.new_page()
```

**Browser URL:** `https://www.profixio.com/fx/livecenter/`

**Automation Flags:**
- `--disable-blink-features=AutomationControlled` (avoid detection)
- `--no-sandbox` (for Docker/DDEV)
- `--disable-dev-shm-usage` (memory optimization)

#### **Step 2.2: Navigate & Login**

1. Visit login page: `https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT`
2. Wait 2 seconds for session establishment
3. Navigate to Live Center: `https://www.profixio.com/fx/livecenter/`
4. Set division filter to "All" (JavaScript: `filter4_id.value = ''`)

#### **Step 2.3: Select Date**

**For `--date=2025-12-17`:**
- Finds dropdown with ID `filter1_id`
- Sets value to `2025-12-17`
- Triggers AJAX: `get_match_list_by_obj('SBTF.SE.BT', dateSelect, 0, 1)`
- Waits 3 seconds for response

**For `--month` or `--year`:**
- Queries dropdown for all matching dates
- Loops through each date

#### **Step 2.4: Extract Team Matches**

**Scrapes from left panel (`#matches` div):**

```javascript
// JavaScript extraction logic:
const rows = matchesDiv.querySelectorAll('tr');
for (let i = 0; i < rows.length; i++) {
    const cells = row.querySelectorAll('td');
    const teamsText = cells[0].innerText;  // "Team1 - Team2"
    const scoreText = cells[1].innerText;  // "5 - 1"

    // Parse teams and score
    const [team1, team2] = teamsText.split(' - ');
    const score = scoreText.match(/(\d+)\s*-\s*(\d+)/);

    // Extract profixio match ID from onclick handler
    const onclick = row.getAttribute('onclick');
    const matchId = onclick.match(/(\d+)/)[1];

    results.push({
        team1_name: team1,
        team2_name: team2,
        team1_score: parseInt(score[1]),
        team2_score: parseInt(score[2]),
        profixio_match_id: matchId,
        status: scoreText.includes('Live') ? 'in_progress' : 'completed',
        row_index: i
    });
}
```

**Example output:**
```json
{
  "team1_name": "Varbergs BTK Orange",
  "team2_name": "Åsa IF Tigers",
  "team1_score": 8,
  "team2_score": 2,
  "profixio_match_id": "12345",
  "status": "completed",
  "row_index": 0
}
```

#### **Step 2.5: Extract Individual Games**

**For each team match:**

1. **Click the match row** to load games panel
2. **Wait 2 seconds** for games to load
3. **Find games table** with pattern `/^[SD]\d$/` (S1, S2, D1, D2, etc.)

**JavaScript extraction:**
```javascript
// Find rows starting with S1, S2, S3, S4, D1, D2
const gameMatch = text.match(/\b([SD]\d)\b/);

// Parse game number and type
const gameNumber = gameMatch[1];  // "S1", "D1", etc.
const gameType = gameNumber.startsWith('D') ? 'doubles' : 'singles';

// Extract player names and score
// Format: "S1 LastName, FirstName - LastName, FirstName 3 - 0"
const nameScorePattern = /([A-ZÅÄÖa-zåäö\s,]+?)\s*-\s*([A-ZÅÄÖa-zåäö\s,]+?)\s+(\d+)\s*-\s*(\d+)/;

// For doubles, also extract partner names
if (gameType === 'doubles') {
    // Extract partner names from the row
}
```

**Example output:**
```json
{
  "game_number": "S1",
  "game_type": "singles",
  "player1_name": "Johansson, Christian",
  "player2_name": "Nilsson, Rickard",
  "player1_sets": 3,
  "player2_sets": 2,
  "winner_name": "Johansson, Christian",
  "profixio_game_id": "g12345"
}
```

#### **Step 2.6: Extract Set Scores**

**For each game, extract sets:**

```javascript
// Find set headers: "1.  2.  3."
const setHeaders = text.match(/1\.\s+2\.\s+3\./);
const scoresLine = nextRow.innerText;

// Parse: "11 - 8    11 - 5    11 - 9"
const setScores = scoresLine.match(/(\d+)\s*-\s*(\d+)/g);

// Result:
// [
//   { set_number: 1, player1_points: 11, player2_points: 8 },
//   { set_number: 2, player1_points: 11, player2_points: 5 },
//   { set_number: 3, player1_points: 11, player2_points: 9 }
// ]
```

#### **Step 2.7: Extract Point-by-Point (Optional)**

**If `--skip-points` is NOT set:**

Scrapes a table with format:
```
Poäng | Serve | Kommentar
------+-------+----------
11-7  | B     |
10-7  | B     |
10-6  | B     | Serve -> B
10-5  | A     |
9-5   | A     | Serve -> A
...
```

**Extracts:**
```json
{
  "point_number": 1,
  "player1_points": 11,
  "player2_points": 7,
  "serve": "B",
  "comment": null
}
```

---

### **Phase 3: Data Structure in Database**

#### **Hierarchy:**
```
live_match_details (1 record per team match)
  ├── live_match_games (multiple games per match)
  │    ├── live_match_sets (3+ sets per game)
  │    │    └── live_match_points (11-30 points per set)
```

#### **Example Data Flow:**

**Team Match:**
```
ID: 1
Varbergs BTK Orange vs Åsa IF Tigers (8-2)
Played: 2025-12-17
```

**Games for Match:**
```
ID: 1 | S1 | Johansson vs Nilsson | 3-2 | Synced Match: #1716
ID: 2 | S2 | Edman vs Korenado | 3-0 | Synced Match: #1717
ID: 3 | S3 | Johansson vs Korenado | 0-3 | Synced Match: #1718
ID: 4 | S4 | Edman vs Nilsson | 3-0 | Synced Match: #1719
ID: 5 | D1 | Johansson/X vs Korenado/Y | 3-2 | Not Synced (Doubles)
```

**Sets for Game S1:**
```
ID: 1 | Set 1 | 11-8
ID: 2 | Set 2 | 11-5
ID: 3 | Set 3 | 9-11
ID: 4 | Set 4 | 8-11
ID: 5 | Set 5 | 11-9
```

**Points for Set 1:**
```
ID: 1 | Point 1 | 1-0 | Serve: A
ID: 2 | Point 2 | 2-0 | Serve: A
ID: 3 | Point 3 | 2-1 | Serve: B
...
ID: 25 | Point 25 | 11-8 | (End of set)
```

---

## 📊 Data Flow Summary

```
COMMAND LINE
    ↓
PHP LiveCenterDetailsScraper
    ↓
Python livecenter_scraper.py
    ↓
Playwright Browser (Headless Chrome)
    ↓
profixio.com Live Center
    ↓
Extract JSON (Team matches, Games, Sets, Points)
    ↓
PHP Saves to Database (4 tables)
    ↓
live_match_* tables
    ↓
php artisan scraper:sync live_center
    ↓
LiveCenterSyncService
    ↓
Find Players & Create Matches (only if both players exist)
```

---

## 🛠️ Configuration & Settings

**File:** `config/scraper.php`

```php
[
    'python' => [
        'binary' => 'python3',
        'script_path' => 'scripts/scraper/livecenter_scraper.py',
    ],
    'live_center' => [
        'login_url' => 'https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT',
        'main_url' => 'https://www.profixio.com/fx/livecenter/',
        'timeout' => 60000, // ms
    ]
]
```

---

## 🔍 Key Features

| Feature | How It Works |
|---------|-------------|
| **Date Selection** | Reads from `filter1_id` dropdown; supports YYYY-MM-DD |
| **Team Matching** | Looks for " - " separator in team names |
| **Score Parsing** | Regex: `(\d+)\s*-\s*(\d+)` |
| **Game Detection** | Pattern: `/^[SD]\d$/` (S1, S2, D1, etc.) |
| **Player Names** | Extracted from game rows; format: "LastName, FirstName" |
| **Point Extraction** | Optional; skippable with `--skip-points` |
| **Error Recovery** | Logs errors, continues with next match |
| **Browser Stealth** | Disables automation detection flags |

---

## 🐛 Error Handling

**Errors are logged but don't stop the scrape:**

```php
self.errors.append({
    "team_match": "Team1 vs Team2",
    "game": "S1",
    "error": "Could not extract set scores"
})
```

**Returns:**
```json
{
  "success": true,
  "data": { ... },
  "errors": [
    { "team_match": "...", "error": "..." }
  ]
}
```

---

## 📈 Performance

- **Timeouts:** 60 seconds per page
- **Waits:** 2-3 seconds between actions (for AJAX)
- **Typical Duration:** 1-2 minutes per date
- **Batch Processing:** Year mode processes multiple dates sequentially

---

## 🎮 Usage Examples

**Single date:**
```bash
php artisan scraper:run live_center --date=2025-12-17
```

**Entire month:**
```bash
php artisan scraper:run live_center --month=2025-12
```

**Year:**
```bash
php artisan scraper:run live_center --year=2025
```

**Skip point-by-point (faster):**
```bash
php artisan scraper:run live_center --date=2025-12-17 --skip-points
```

**Test with limit:**
```bash
php artisan scraper:run live_center --date=2025-12-17 --limit-matches=2
```

**Queue for background processing:**
```bash
php artisan scraper:run live_center --date=2025-12-17 --queue
```

---

## 📝 Output Format

**JSON returned by Python script:**

```json
{
  "success": true,
  "data": {
    "team_matches_count": 3,
    "games_count": 25,
    "sets_count": 96,
    "points_count": 0,
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
                "points": [
                  { "point_number": 1, "player1_points": 1, "player2_points": 0, "serve": "A" },
                  ...
                ]
              }
            ]
          }
        ]
      }
    ]
  },
  "errors": []
}
```

---

## ✅ Sync Behavior (Updated Feb 10, 2026)

**Only syncs matches when BOTH players exist in database:**

```php
$player1 = $this->findUserByName($game->player1_name);
$player2 = $this->findUserByName($game->player2_name);

if (!$player1 || !$player2) {
    // Skip - mark as synced but don't create match
    $game->update(['is_synced' => true]);
    $this->stats['skipped']++;
    Log::info("Skipped Live Center game - player(s) not found", [...]);
    return;
}

// Both players exist - create/link match
```

**Skips:**
- Doubles games (D1, D2) - only singles are synced
- Games where either player not in database

**Creates:**
- New match record with `source: 'scraped'`
- Links via `live_match_game_id` foreign key

---

## 🔗 Database Tables

| Table | Purpose |
|-------|---------|
| `live_match_details` | Team match summary (team1, team2, score, date) |
| `live_match_games` | Individual games (S1, S2, D1, etc.) |
| `live_match_sets` | Set scores for each game |
| `live_match_points` | Point-by-point data (optional) |
| `matches` | Production match table (linked via `live_match_game_id`) |

---

*Investigation completed: Feb 11, 2026*
*Generated for iRacket Live Center Scraper System*
