# Smart-Scrape Command - Implementation Plan

## Overview

Create a new `scraper:smart-scrape` command that intelligently scrapes only Rankings and Series data, skipping the time-consuming Players, Transitions, LiveCenter, and Matches scrapers.

## Why "Smart-Scrape"?

- **Rankings**: Already supports month-level filtering (YYYY.MM.DD format) - efficient and precise
- **Series**: Scrapes club standings and series matches - valuable for competition tracking
- **Skip**: Players, Transitions, LiveCenter - these require full year scraping and take significantly longer

## Performance Comparison

| Scraper Type | Full Scrape | Smart Scrape |
|--------------|-------------|--------------|
| Steps | 12 | 4 |
| Duration | ~2-3 hours | ~20-30 minutes |
| Data Points | ~50,000+ | ~15,000 |
| Month Filter | Partial | Full |

## Implementation Steps

### Step 1: Create SmartScrapeCommand.php

**File**: `app/Console/Commands/SmartScrapeCommand.php`

**Features**:
- Scrape only Rankings (male + female)
- Scrape only Series (club standings + matches)
- Support month filtering for Rankings
- Support year filtering for Series (best available)
- Parallel processing for Rankings (like scraper:start)
- Visual progress indicator
- Automatic sync after scraping
- Bubbler recalculation

**Signature**:
```php
protected $signature = 'scraper:smart-scrape
                        {month : The month to scrape (e.g., 2024-12)}
                        {--skip-rankings : Skip rankings scraping}
                        {--skip-series : Skip series scraping}
                        {--skip-sync : Skip automatic sync}
                        {--skip-bubbler : Skip Bubbler recalculation}
                        {--no-backup : Skip automatic backup}
                        {--limit-periods= : Limit periods for testing}
                        {--limit-divisions= : Limit divisions for testing}';
```

### Step 2: Pipeline Steps

The smart-scrape will execute these steps:

1. **Backup Database** (if not --no-backup)
   - Create SQLite backup before starting

2. **Rankings (Male) - Parallel** (if not --skip-rankings)
   - Split into 3 parallel jobs (like scraper:start)
   - Month-level filtering: `--period=2024-12-01 --direction=gte`

3. **Rankings (Female) - Parallel** (if not --skip-rankings)
   - Split into 3 parallel jobs
   - Month-level filtering: `--period=2024-12-01 --direction=gte`

4. **Series Standings** (if not --skip-series)
   - Year-level filtering: `--season=2024-12-01 --direction=gte`

5. **Series Matches** (if not --skip-series)
   - Year-level filtering: `--season=2024-12-01 --direction=gte`

6. **Sync All** (if not --skip-sync)
   - Sync scraped rankings to production tables
   - Sync scraped series to production tables

7. **Bubbler Calculation** (if not --skip-bubbler)
   - Recalculate monthly rankings
   - Recalculate club monthly rankings

### Step 3: Code Structure

```php
<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use App\Services\Scraper\SyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class SmartScrapeCommand extends Command
{
    protected $signature = 'scraper:smart-scrape
                            {month : The month to scrape (e.g., 2024-12)}
                            {--skip-rankings : Skip rankings scraping}
                            {--skip-series : Skip series scraping}
                            {--skip-sync : Skip automatic sync}
                            {--skip-bubbler : Skip Bubbler recalculation}
                            {--no-backup : Skip automatic backup}
                            {--limit-periods= : Limit periods for testing}
                            {--limit-divisions= : Limit divisions for testing}';

    protected $description = 'Smart scrape: Only Rankings and Series (fast, month-aware)';

    protected array $stats = [];
    protected int $totalSteps = 7; // Less steps than full scrape
    protected int $currentStep = 0;
    protected ?string $backupFile = null;
    protected array $executionLog = [];

    public function handle(SyncService $syncService): int
    {
        $month = $this->argument('month');
        $skipRankings = $this->option('skip-rankings');
        $skipSeries = $this->option('skip-series');
        $skipSync = $this->option('skip-sync');
        $skipBubbler = $this->option('skip-bubbler');
        $skipBackup = $this->option('no-backup');

        // Validate month format
        if (!$this->validateMonth($month)) {
            $this->error("Invalid month format. Use YYYY-MM (e.g., 2024-12)");
            return self::FAILURE;
        }

        $this->displayHeader($month);

        // Check for running scrapers
        if ($this->checkRunningScrapers()) {
            return self::FAILURE;
        }

        // Step 1: Backup
        if (!$skipBackup) {
            $this->runStep('Creating Database Backup', fn() => $this->createBackup());
        }

        // Step 2-3: Rankings (parallel)
        if (!$skipRankings) {
            $this->runStep('Scraping Rankings (Male) - 3 Parallel Jobs',
                fn() => $this->scrapeRankingsParallel('male', $month));

            $this->runStep('Scraping Rankings (Female) - 3 Parallel Jobs',
                fn() => $this->scrapeRankingsParallel('female', $month));
        }

        // Step 4-5: Series
        if (!$skipSeries) {
            $this->runStep('Scraping Series Standings',
                fn() => $this->scrapeSeries($month));

            $this->runStep('Scraping Series Matches',
                fn() => $this->scrapeSeriesMatches($month));
        }

        // Step 6: Sync
        if (!$skipSync) {
            $this->runStep('Syncing Scraped Data to Production',
                fn() => $this->syncData($syncService));
        }

        // Step 7: Bubbler
        if (!$skipBubbler) {
            $this->runStep('Recalculating Bubbler Rankings',
                fn() => $this->calculateBubbler($month));
        }

        $this->displaySummary();

        return self::SUCCESS;
    }

    protected function scrapeRankingsParallel(string $gender, string $month): array
    {
        // Similar to ScraperStartCommand::scrapeRankingsParallel()
        // Split periods into 3 chunks, run parallel
    }

    protected function scrapeSeries(string $month): array
    {
        // Run: php artisan scraper:run series --season={$month}-01 --direction=gte
    }

    protected function scrapeSeriesMatches(string $month): array
    {
        // Run: php artisan scraper:run series-matches --season={$month}-01 --direction=gte
    }

    // ... other methods from ScraperStartCommand (reusable)
}
```

### Step 4: Reusable Code

Extract common functionality from `ScraperStartCommand` into a trait:

**File**: `app/Console/Commands/Concerns/ScraperCommandHelpers.php`

```php
<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\DB;

trait ScraperCommandHelpers
{
    protected function validateMonth(string $month): bool
    {
        return preg_match('/^\d{4}-\d{2}$/', $month);
    }

    protected function createBackup(): array
    {
        // Backup logic
    }

    protected function displayHeader(string $month): void
    {
        // Header display logic
    }

    protected function runStep(string $description, callable $callback): void
    {
        // Step execution with progress
    }

    protected function displaySummary(): void
    {
        // Summary display logic
    }

    protected function checkRunningScrapers(): bool
    {
        // Check for already running scrapers
    }
}
```

### Step 5: Update Both Commands to Use Trait

**ScraperStartCommand.php**:
```php
class ScraperStartCommand extends Command
{
    use Concerns\ScraperCommandHelpers;

    // ... rest of code
}
```

**SmartScrapeCommand.php**:
```php
class SmartScrapeCommand extends Command
{
    use Concerns\ScraperCommandHelpers;

    // ... rest of code
}
```

### Step 6: Testing Plan

```bash
# Test with small limits
php artisan scraper:smart-scrape 2024-12 \
  --limit-periods=2 \
  --limit-divisions=2 \
  --no-backup

# Test with only rankings
php artisan scraper:smart-scrape 2024-12 \
  --skip-series \
  --no-backup

# Test with only series
php artisan scraper:smart-scrape 2024-12 \
  --skip-rankings \
  --no-backup

# Full test
php artisan scraper:smart-scrape 2024-12

# Background execution
nohup php artisan scraper:smart-scrape 2024-12 > storage/logs/smart-scrape.log 2>&1 &
```

### Step 7: Documentation

Update [CLAUDE.md](../CLAUDE.md):

```markdown
### Scraper Commands
\`\`\`bash
# Full scrape (all 12 steps, ~2-3 hours)
php artisan scraper:start 2024-12

# Smart scrape (Rankings + Series only, ~20-30 minutes)
php artisan scraper:smart-scrape 2024-12

# Individual scrapers
php artisan scraper:run rankings --gender=male --period=2024-12-01
php artisan scraper:run series --season=2024-12-01
\`\`\`
```

## Files to Create/Modify

### New Files
1. `app/Console/Commands/SmartScrapeCommand.php` - Main smart-scrape command
2. `app/Console/Commands/Concerns/ScraperCommandHelpers.php` - Shared helper trait
3. `docs/SMART_SCRAPE_PLAN.md` - This file (implementation plan)

### Files to Modify
1. `app/Console/Commands/ScraperStartCommand.php` - Extract common code to trait
2. `docs/CLAUDE.md` - Add smart-scrape documentation
3. `README.md` - Add smart-scrape to quick start guide (if exists)

## Expected Benefits

### Performance
- **Time Savings**: 75% faster than full scrape (30 min vs 2-3 hours)
- **Resource Usage**: Lower CPU/memory usage (fewer parallel jobs)
- **Network Requests**: ~90% fewer HTTP requests

### Data Quality
- **Month-Precise**: Rankings scraped at exact month level
- **Fresh Series Data**: Club standings and match results
- **Skip Stale Data**: Players/Transitions change infrequently, skip them

### Use Cases
1. **Daily Updates**: Quick refresh of rankings and recent matches
2. **Competition Tracking**: Monitor series progression during season
3. **Testing**: Fast iteration during development
4. **Production**: Lower server load for frequent updates

## Implementation Checklist

- [ ] Create `SmartScrapeCommand.php` with full logic
- [ ] Extract shared code to `ScraperCommandHelpers` trait
- [ ] Refactor `ScraperStartCommand` to use trait
- [ ] Add parallel rankings processing (reuse from ScraperStartCommand)
- [ ] Implement series scraping with year filtering
- [ ] Add progress indicators and stats tracking
- [ ] Test with various options (--skip-*, --limit-*)
- [ ] Update documentation (CLAUDE.md, README.md)
- [ ] Add command to `Kernel.php` schedule (if needed)
- [ ] Test in production environment

## Next Steps

1. Review this plan and approve approach
2. Implement `ScraperCommandHelpers` trait first (extract from ScraperStartCommand)
3. Create `SmartScrapeCommand` using the trait
4. Test with small limits
5. Run full smart-scrape test
6. Document usage

## Questions to Resolve

1. Should smart-scrape also support `--all` flag (scrape all years)?
2. Should we add `--parallel` option to control number of parallel jobs?
3. Should we create separate commands like `scraper:quick-rankings` and `scraper:quick-series`?
4. Should we add a `--dry-run` option to preview what will be scraped?
