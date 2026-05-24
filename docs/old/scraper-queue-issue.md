# Scraper Queue Issue: Missing Sub-Runs

## Issue Summary

When the scraper is executed via the queue system, only the parent "full_scrape" run is created in the database. Individual scraper runs (players, rankings, matches, etc.) are not being created, making it impossible to track detailed progress in the admin panel.

## Expected Behavior

When `scraper:start` is executed, it should create multiple scraper runs:
- **Parent Run**: `full_scrape` (tracks the overall operation)
- **Sub-Runs**: `players`, `rankings` (male), `rankings` (female), `series`, `series_matches`, etc.

### Example from Direct CLI Execution

```bash
$ php artisan scraper:start 2025-12 --limit-periods=1

# Creates:
Run #5: full_scrape (parent)
Run #6: players
Run #7: rankings_male
Run #8: rankings_female
Run #9: series_matches
Run #10: series
...
```

## Actual Behavior (Queue Execution)

When executed via queue:

```bash
$ php artisan scraper:queue start 2025-12

# Only creates:
Run #7: full_scrape
```

**No sub-runs are created**, so the admin panel only displays "full_scrape" entries.

## Root Cause

The issue occurs because of how the command is executed in different contexts:

### Direct CLI Execution (Works ✅)
```
Terminal → php artisan scraper:start
  └─> Process::start("php artisan scraper:run players")  ← Creates separate process
        └─> Creates Run #6 (players)
```

### Queue Execution (Broken ❌)
```
Supervisor Queue Worker → RunScraperJob
  └─> Artisan::call('scraper:start', [...])  ← In-process call
        └─> Process::start("php artisan scraper:run players")  ← Process fails silently
              └─> No run created
```

### Technical Details

**File**: `app/Console/Commands/ScraperStartCommand.php`

The `scraper:start` command uses `Process::start()` to launch sub-commands:

```php
// Line 706-718
protected function scrapePlayers(string $month, bool $scrapeAll): array
{
    $command = 'php artisan scraper:run players';

    if (!$scrapeAll) {
        $command .= " --period=" . escapeshellarg($month . '-01');
        $command .= " --direction=gte";
    }

    $command .= $this->buildLimitOptions();

    return $this->runScraperWithProgress($command, 'Players');
}

// Line 611-614
protected function runScraperWithProgress(string $command, string $label): array
{
    // Start the scraper command in the background
    $process = Process::start($command);
    // ...
}
```

**Problem**: When `Artisan::call()` is used by the queue worker, the `Process::start()` calls don't create separate processes properly. The processes either:
1. Fail to start
2. Start but don't have access to the database connection
3. Complete but don't persist runs to the database

## Impact

### Admin Panel
- Users only see "full_scrape" entries
- Cannot view detailed progress for individual scraper types
- No way to see which specific scraper step failed
- Cannot monitor parallel scraping operations (male/female rankings)

### Monitoring & Debugging
- Difficult to identify which scraper component is slow
- Cannot see item counts per scraper type
- Logs are associated with the parent run only

## Potential Solutions

### Option 1: Use Artisan::call() Instead of Process::start()

Modify `runScraperWithProgress()` to use `Artisan::call()` when running from queue:

```php
protected function runScraperWithProgress(string $command, string $label): array
{
    if (app()->runningInConsole() && !$this->option('queue')) {
        // CLI execution - use Process
        $process = Process::start($command);
    } else {
        // Queue execution - use Artisan::call()
        [$artisanCommand, $args] = $this->parseCommand($command);
        $exitCode = Artisan::call($artisanCommand, $args);
    }
    // ...
}
```

**Pros**:
- Should work in both contexts
- Keeps existing architecture

**Cons**:
- Adds complexity
- May lose process isolation benefits

### Option 2: Dispatch Individual Jobs

Instead of one `RunScraperJob` that calls `scraper:start`, dispatch separate jobs for each scraper type:

```php
// In scraper:queue command
RunScraperJob::dispatch('scraper:run', ['type' => 'players'], $options);
RunScraperJob::dispatch('scraper:run', ['type' => 'rankings'], ['--gender' => 'male'] + $options);
RunScraperJob::dispatch('scraper:run', ['type' => 'rankings'], ['--gender' => 'female'] + $options);
// etc.
```

**Pros**:
- Each scraper type gets its own job and run
- Better parallelization
- Clearer separation of concerns

**Cons**:
- Loses the orchestration provided by `scraper:start`
- Need to handle dependencies between steps
- No single "parent" run to track overall progress

### Option 3: Create Runs Programmatically

Have `scraper:start` create all runs programmatically before launching sub-commands:

```php
public function handle(): int
{
    // Create parent run
    $this->parentRun = ScraperRun::create([...]);

    // Pre-create child runs
    $playerRun = ScraperRun::create(['type' => 'players', ...]);
    $rankingsMaleRun = ScraperRun::create(['type' => 'rankings', ...]);
    // etc.

    // Then execute and update existing runs
    $this->runStep('Scraping Players', function() use ($playerRun) {
        // Update $playerRun status as it progresses
    });
}
```

**Pros**:
- Runs appear immediately in admin panel
- Works with any execution method

**Cons**:
- Significant refactoring required
- Tight coupling between parent and child runs

## Workaround (Current)

For now, use direct CLI execution with `screen` or `tmux` for production scraping:

```bash
# Start a screen session
screen -S scraper

# Run the scraper directly
php artisan scraper:start 2025-12

# Detach from screen: Ctrl+A, then D
# Reattach later: screen -r scraper
```

This ensures all sub-runs are created and visible in the admin panel.

## Related Files

- `app/Console/Commands/ScraperStartCommand.php` - Parent command
- `app/Console/Commands/ScraperCommand.php` - Sub-command (`scraper:run`)
- `app/Jobs/Scraper/RunScraperJob.php` - Queue job wrapper
- `app/Livewire/Admin/Scraper/Index.php` - Admin panel
- `routes/web.php` (line 176-177) - Scraper admin routes

## Testing

To verify the issue:

```bash
# 1. Clear database
php artisan tinker --execute="DB::table('scraper_runs')->delete();"

# 2. Run via queue
php artisan scraper:queue start 2025-12 --limit-periods=1

# 3. Wait for completion, then check runs
php artisan tinker --execute="\$runs = DB::table('scraper_runs')->get(['id', 'type']); foreach (\$runs as \$r) { echo \"#{\$r->id}: {\$r->type}\\n\"; }"

# Result: Only shows "full_scrape"

# 4. Compare with direct execution
php artisan scraper:start 2025-12 --limit-periods=1 --force

# Result: Shows "full_scrape", "players", "rankings", etc.
```

## Status

- **Discovered**: 2025-12-31
- **Status**: Open
- **Priority**: Medium (workaround available)
- **Assigned**: Unassigned
