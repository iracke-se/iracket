# Implementing Game Score Extraction

This guide shows how to add actual game scores (like 21-19, 21-15) to the match scraper.

## Step 1: Investigate Profixio.com Popup

First, manually check if game scores are available in the profixio popup.

### Manual Testing:
1. Go to: https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender=m
2. Click on a player name → popup opens
3. Click on a month's points → match details appear
4. Check the table columns - are there more than 5 columns?
5. Look for game scores like "21-19, 21-15" or "2-1"

**If scores are visible:** Continue to Step 2
**If scores are NOT visible:** You'll need to find them in series/live center pages

---

## Step 2: Update Python Scraper to Extract Scores

### File: `scripts/scraper/rankings_popup_scraper.py`

#### A. Add score extraction to match scraping:

Find the `scrape_matches_for_month` method (around line 356) and update it:

```python
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
            points_span = await cells[1].query_selector("span.rmld_poeng")
            if points_span:
                span_id = await points_span.get_attribute("id")
                await self.page.evaluate(f"""
                    () => {{
                        const span = document.getElementById('{span_id}');
                        if (span) span.click();
                    }}
                """)
                await self.page.wait_for_timeout(2000)
                break

    if not points_span:
        return []

    # Parse match data from updated popup
    matches = []
    rows = await popup.query_selector_all("table tr")

    for row in rows:
        cells = await row.query_selector_all("td")

        # Log number of cells for debugging
        cell_count = len(cells)

        # Minimum 5 cells required (result, opponent, opp_pts, match_pts, date)
        if cell_count < 5:
            continue

        result = await cells[0].text_content()
        result = result.strip()

        if result not in ['W', 'L']:
            continue

        opponent_name = await cells[1].text_content()
        opponent_points = await cells[2].text_content()
        match_points = await cells[3].text_content()
        match_date = await cells[4].text_content()

        # NEW: Extract game score if 6th column exists
        game_score = ""
        if cell_count >= 6:
            game_score = await cells[5].text_content()
            game_score = game_score.strip()

        # NEW: Or check if score is in any other cell (flexible approach)
        # Sometimes the score might be in the result column or combined
        if not game_score and result not in ['W', 'L']:
            # Maybe result cell contains both W/L and score
            if '(' in result or '-' in result:
                # Extract pattern like "W (2-1)" or "2-1"
                game_score = result

        # Clean numeric values
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
            "scraped_month": target_date,
            "score": game_score,  # NEW: Add score field
        })

    # Click back button
    await self.click_back_button()

    return matches
```

#### B. Add debug logging to see table structure:

Add this helper method to understand the popup table:

```python
async def debug_match_table(self):
    """Debug: Log table structure to understand columns"""
    popup = await self.page.query_selector("#multipurpose")
    rows = await popup.query_selector_all("table tr")

    for i, row in enumerate(rows[:3]):  # First 3 rows
        cells = await row.query_selector_all("td")
        cell_texts = []
        for cell in cells:
            text = await cell.text_content()
            cell_texts.append(text.strip())

        log_info(f"Row {i}: {len(cells)} cells - {cell_texts}")
```

Call this in `scrape_matches_for_month` after popup opens:

```python
if not points_span:
    return []

# DEBUG: See table structure
await self.debug_match_table()

# Parse match data...
```

---

## Step 3: Update RankingsScraper to Save Scores

### File: `app/Services/Scraper/RankingsScraper.php`

Update the `saveMatchesToDatabase` method (around line 179):

```php
protected function saveMatchesToDatabase(array $matches): void
{
    foreach ($matches as $match) {
        DB::table('scraped_matches')->insert([
            'scraper_run_id' => $this->run->id,
            'profixio_player_id' => $match['profixio_player_id'],
            'player_name' => $match['player_name'],
            'opponent_name' => $match['opponent_name'],
            'result' => $match['result'],
            'opponent_points' => $match['opponent_points'],
            'match_points' => $match['match_points'],
            'match_date' => $match['match_date'],
            'scraped_month' => $match['scraped_month'],
            'score' => $match['score'] ?? null,  // NEW: Save score
            'is_synced' => false,
            // Legacy fields for backward compatibility
            'source' => 'rankings_popup_python',
            'period' => date('Y-m', strtotime($match['match_date'])),
            'division' => '',
            'series_name' => '',
            'team1_name' => '',
            'team2_name' => '',
            'player1_name' => $match['player_name'],
            'player2_name' => $match['opponent_name'],
            'played_at' => $match['match_date'],
            'winner' => $match['result'] === 'W' ? $match['player_name'] : $match['opponent_name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->run->incrementScraped();
    }
}
```

---

## Step 4: Test the Scraper

### Run a test scrape:

```bash
cd /home/webook/Desktop/Development/iracket/scripts/scraper

# Test with 1 player to see output
python3 rankings_popup_scraper.py --year 2025 --month 12 --gender m --limit 1
```

### Check the output:

```json
{
  "success": true,
  "data": {
    "matches": [
      {
        "profixio_player_id": "14450",
        "player_name": "Andersson, Anna",
        "result": "W",
        "opponent_name": "Svensson, Lisa",
        "opponent_points": 1200,
        "match_points": 23,
        "match_date": "2025-12-15",
        "scraped_month": "2025-12",
        "score": "21-19, 21-15"  ← CHECK IF THIS EXISTS
      }
    ]
  }
}
```

---

## Step 5: Alternative - Extract from Match Detail Popup

If scores aren't in the table but clicking a match opens details:

```python
async def scrape_matches_for_month(self, player: Dict) -> List[Dict]:
    """Enhanced version that clicks into match details"""
    # ... existing code to get to matches table ...

    matches = []
    rows = await popup.query_selector_all("table tr")

    for row in rows:
        cells = await row.query_selector_all("td")
        if len(cells) < 5:
            continue

        result = await cells[0].text_content()
        if result.strip() not in ['W', 'L']:
            continue

        # Extract basic info
        opponent_name = await cells[1].text_content()
        opponent_points = await cells[2].text_content()
        match_points = await cells[3].text_content()
        match_date = await cells[4].text_content()

        # NEW: Try to click row to get detailed scores
        game_score = await self.extract_game_score_from_row(row)

        # ... rest of match data ...
        matches.append({
            # ... existing fields ...
            "score": game_score,
        })

    return matches

async def extract_game_score_from_row(self, row) -> str:
    """Try to extract game score by clicking match row"""
    try:
        # Check if row has a clickable element
        clickable = await row.query_selector("a, span[onclick], span[class*='click']")
        if not clickable:
            return ""

        # Click to open details
        await clickable.click()
        await self.page.wait_for_timeout(1000)

        # Look for score in detail popup
        # This depends on profixio's structure - adjust selectors
        score_element = await self.page.query_selector(".match-score, .game-score, .set-score")
        if score_element:
            score = await score_element.text_content()

            # Close detail view
            await self.page.keyboard.press("Escape")
            await self.page.wait_for_timeout(500)

            return score.strip()

    except Exception as e:
        log_error(f"Failed to extract game score: {e}")

    return ""
```

---

## Step 6: Verify Data in Database

After running the scraper:

```bash
php artisan tinker
```

```php
// Check scraped matches
$matches = \App\Models\Scraper\ScrapedMatch::latest()->take(5)->get();

foreach ($matches as $m) {
    echo "{$m->player_name} vs {$m->opponent_name}\n";
    echo "Result: {$m->result}\n";
    echo "Score: {$m->score}\n";  // Should show game scores now
    echo "---\n";
}
```

---

## Step 7: Update UI to Display Scores

### File: `resources/views/livewire/user/players/show.blade.php`

Update the match display (around line 294):

```blade
<div class="block p-3 bg-white dark:bg-zinc-800 rounded-lg">
    <div class="flex items-center justify-between mb-2">
        <span class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ $matchDate ? $matchDate->format('d M Y') : 'N/A' }}
        </span>
        <div class="flex items-center gap-2">
            @if($myMatchPoints)
                <span class="text-xs font-medium px-2 py-0.5 rounded {{ $myMatchPoints > 0 ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-red-500/10 text-red-600 dark:text-red-400' }}">
                    {{ $myMatchPoints > 0 ? '+' : '' }}{{ $myMatchPoints }} pts
                </span>
            @endif
        </div>
    </div>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                    {{ substr($opponentName, 0, 2) }}
                </span>
            </div>
            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $opponentName }}</span>
        </div>
        <div class="flex flex-col items-end">
            {{-- Show result (W/L) --}}
            <span class="text-sm font-bold {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                {{ $score }}
                <span class="text-xs ml-1">{{ $won ? 'W' : 'L' }}</span>
            </span>

            {{-- NEW: Show game scores if available --}}
            @if($isScrapedMatch && $match->score)
                <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ $match->score }}
                </span>
            @elseif(!$isScrapedMatch && isset($match->sets) && !empty($match->sets))
                <span class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ implode(', ', $match->sets) }}
                </span>
            @endif
        </div>
    </div>
</div>
```

---

## Step 8: Handle Sets JSON Field

If you want to store individual game scores:

### Update Python scraper:

```python
def parse_game_scores(score_string: str) -> List[str]:
    """Parse '21-19, 21-15, 18-21' into list"""
    if not score_string:
        return []

    # Split by comma
    games = [g.strip() for g in score_string.split(',')]
    return [g for g in games if g]

# In scrape_matches_for_month:
matches.append({
    # ... existing fields ...
    "score": game_score,
    "sets": parse_game_scores(game_score),  # Convert to array
})
```

### Update PHP to save as JSON:

```php
protected function saveMatchesToDatabase(array $matches): void
{
    foreach ($matches as $match) {
        DB::table('scraped_matches')->insert([
            // ... existing fields ...
            'score' => $match['score'] ?? null,
            'sets' => json_encode($match['sets'] ?? []),  // Store as JSON
            // ... rest ...
        ]);
    }
}
```

---

## Complete Testing Checklist

- [ ] Run Python scraper with `--limit 1` and check JSON output
- [ ] Verify `score` field has data in output
- [ ] Check database: `select score from scraped_matches where score is not null`
- [ ] Run sync: `php artisan scraper:start 2025-12 --limit-players=5`
- [ ] Verify GameMatch has player1_sets/player2_sets populated
- [ ] Check UI displays game scores
- [ ] Test with different score formats

---

## Troubleshooting

### Score field is empty
- Add debug logging to see table structure
- Check if scores are in a different column
- Try clicking match row to see if details popup has scores

### Score parsing fails
- Log the raw score string
- Update parseScore() regex patterns
- Handle Swedish/Norwegian number formats

### Scores not syncing to GameMatch
- Check MatchSyncService parseScore() method
- Verify ScrapedMatch has score populated
- Add logging in syncMatch() method

---

## Example Score Formats to Handle

```php
"21-19, 21-15"              → 2-0 (Player 1 wins)
"21-19, 18-21, 21-15"       → 2-1 (Player 1 wins)
"19-21, 15-21"              → 0-2 (Player 2 wins)
"2-1"                       → 2-1 (Just sets, no game scores)
"W (2-1)"                   → Extract "2-1"
```

Update `parseScore()` to handle all these formats!

---

## Next Steps

1. Run scraper with debug logging
2. Identify where scores are in profixio.com
3. Update Python scraper accordingly
4. Test thoroughly
5. Update UI to display scores beautifully
