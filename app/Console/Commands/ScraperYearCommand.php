<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Standalone full-year scraper.
 *
 * Scrapes a whole year month-by-month (rankings + matches), then Live Center for
 * the year, syncing after each month. It is a thin wrapper that only *calls* the
 * existing `scraper:run` and `scraper:sync` commands — it does not modify any
 * existing scraper code or services.
 *
 * NOTE: Rankings can only be scraped one month at a time (the underlying scraper
 * has no "whole year" mode), so this loops months 1..12. Live Center DOES support
 * a year natively, so it runs once for the whole year at the end.
 *
 * Excludes Club Transitions and Series Standings by design (run those separately).
 */
class ScraperYearCommand extends Command
{
    /**
     * Built-in retry policy for scrape failures (e.g. profixio rate-limit /
     * HTTP error). On each failure we wait WAIT_MINUTES and try again, up to
     * MAX_RETRIES extra attempts. So the total is 1 initial attempt + N retries.
     */
    private const MAX_RETRIES = 3;
    private const WAIT_MINUTES = 30;

    protected $signature = 'scraper:year
                            {year : The year to scrape (YYYY, e.g. 2026)}
                            {--from= : First month to scrape (1-12). Default 1.}
                            {--to= : Last month to scrape (1-12). Default 12, or current month for the current year.}
                            {--resume : Skip months that already have completed rankings runs for BOTH genders}
                            {--parallel : Scrape male + female rankings at the same time (scraper:run --gender=both)}
                            {--skip-live-center : Skip the year-wide Live Center scrape + its sync}';

    protected $description = 'Scrape a full year month-by-month (rankings + matches), then Live Center for the year, syncing as it goes. Standalone wrapper around scraper:run / scraper:sync.';

    public function handle(): int
    {
        $rawYear = $this->argument('year');
        if (!ctype_digit((string) $rawYear) || (int) $rawYear < 2000 || (int) $rawYear > 2100) {
            $this->error("Invalid year '{$rawYear}'. Use a 4-digit year, e.g. 2026.");
            return self::FAILURE;
        }
        $year = (int) $rawYear;

        $now = Carbon::now();
        if ($year > (int) $now->year) {
            $this->error("Year {$year} is in the future — nothing to scrape.");
            return self::FAILURE;
        }

        $isCurrentYear = $year === (int) $now->year;
        $defaultTo = $isCurrentYear ? (int) $now->month : 12;

        $from = $this->option('from') !== null ? (int) $this->option('from') : 1;
        $to   = $this->option('to') !== null ? (int) $this->option('to') : $defaultTo;

        $from = max(1, min(12, $from));
        $to   = max(1, min(12, $to));

        if ($isCurrentYear && $to > (int) $now->month) {
            $this->warn("  {$year} is the current year — capping last month to " . $now->format('m') . " (no data for future months).");
            $to = (int) $now->month;
        }

        if ($from > $to) {
            $this->error("--from ({$from}) is after --to ({$to}). Nothing to do.");
            return self::FAILURE;
        }

        $months = range($from, $to);

        $this->displayHeader($year, $months);

        $parallel = (bool) $this->option('parallel');
        $resume   = (bool) $this->option('resume');

        $scraped = [];
        $skipped = [];
        $failed  = null;

        foreach ($months as $index => $m) {
            $mm = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $label = "{$year}-{$mm}";

            $this->section('Month ' . ($index + 1) . '/' . count($months) . ": {$label}");

            if ($resume && $this->monthAlreadyComplete($year, $mm)) {
                $this->line("  ⏭  Skipping {$label} — already has completed rankings runs for both genders (--resume).");
                $skipped[] = $label;
                continue;
            }

            // --- Rankings (+ matches) for this month --- (retries on profixio failure)
            if ($parallel) {
                if (!$this->callScrapeWithRetry([
                    'type' => 'rankings',
                    '--year' => (string) $year,
                    '--month' => $mm,
                    '--gender' => 'both',
                ], "{$label} rankings (both)")) {
                    $failed = "{$label} rankings (both genders)";
                    break;
                }
            } else {
                if (!$this->callScrapeWithRetry([
                    'type' => 'rankings',
                    '--year' => (string) $year,
                    '--month' => $mm,
                    '--gender' => 'm',
                ], "{$label} rankings (male)")) {
                    $failed = "{$label} rankings (male)";
                    break;
                }

                if (!$this->callScrapeWithRetry([
                    'type' => 'rankings',
                    '--year' => (string) $year,
                    '--month' => $mm,
                    '--gender' => 'k',
                ], "{$label} rankings (female)")) {
                    $failed = "{$label} rankings (female)";
                    break;
                }
            }

            // --- Sync this month before moving on (twice — locks in progress) ---
            if (!$this->syncAllTwice()) {
                $failed = "{$label} sync";
                break;
            }

            $scraped[] = $label;
        }

        if ($failed !== null) {
            $this->newLine();
            $this->error("✗ Stopped at: {$failed}");
            $this->warn('  Months completed this run: ' . (empty($scraped) ? 'none' : implode(', ', $scraped)));
            $this->line('  To continue where it stopped, re-run with --resume:');
            $this->line("    php artisan scraper:year {$year} --resume");
            return self::FAILURE;
        }

        // --- Live Center for the whole year ---
        // Uses --from-matches: it reads the play dates from the matches we already
        // synced above and scrapes Live Center for exactly those dates (no month
        // shift, so it never hits the "No dates found for X" failure). This is the
        // same approach the full `scraper:start --all` run uses.
        //
        // NOTE: Live Center coverage is partial — some dates legitimately have no
        // data. Like the full scraper, we treat a Live Center failure as NON-FATAL:
        // warn and continue. Rankings & matches are already synced per month.
        if (!$this->option('skip-live-center')) {
            $this->section("Live Center: full year {$year} (from matches)");

            if ($this->callScrapeWithRetry([
                'type' => 'live_center',
                '--from-matches' => true,
                '--year' => (string) $year,
            ], "Live Center {$year}")) {
                if (!$this->syncAllTwice()) {
                    $this->warn('  ⚠ Live Center sync failed — rankings & matches were already synced per month.');
                }
            } else {
                $this->warn('  ⚠ Live Center had no data / failed for ' . $year . ' — skipping (non-fatal). Rankings & matches are already synced.');
            }
        } else {
            $this->section('Live Center');
            $this->line('  ⏭  Skipping Live Center (--skip-live-center).');
        }

        // --- Summary ---
        $this->newLine();
        $this->info("✓ Year {$year} done.");
        $this->line('  Months scraped: ' . (empty($scraped) ? 'none' : implode(', ', $scraped)));
        if (!empty($skipped)) {
            $this->line('  Months skipped (already complete): ' . implode(', ', $skipped));
        }
        $this->newLine();
        $this->line('  Not included: Club Transitions and Series Standings.');
        $this->line('  Run transitions separately when ready:');
        $this->line('    php artisan scraper:run transitions && php artisan scraper:sync transitions');

        return self::SUCCESS;
    }

    /**
     * Run a scrape command, retrying on failure with a fixed wait between
     * attempts (for profixio rate-limit / transient HTTP errors). Total
     * attempts = 1 + MAX_RETRIES. Returns true if any attempt succeeds.
     *
     * Only used for scrape calls — sync failures are local and not retried here.
     */
    protected function callScrapeWithRetry(array $args, string $label): bool
    {
        $maxAttempts = 1 + self::MAX_RETRIES;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt > 1) {
                $waitSeconds = self::WAIT_MINUTES * 60;
                $resumeAt = \Illuminate\Support\Carbon::now()->addSeconds($waitSeconds)->format('H:i');
                $this->warn("  ⏳ {$label} failed — profixio may be rate-limiting. Waiting " . self::WAIT_MINUTES . " min before retry {$attempt}/{$maxAttempts} (resume around {$resumeAt})...");
                sleep($waitSeconds);
            }

            $code = $this->call('scraper:run', $args);

            if ($code === self::SUCCESS) {
                if ($attempt > 1) {
                    $this->info("  ✓ {$label} recovered on attempt {$attempt}.");
                }
                return true;
            }

            if ($attempt < $maxAttempts) {
                $this->warn("  ✗ {$label} attempt {$attempt}/{$maxAttempts} failed.");
            } else {
                $this->error("  ✗ {$label} failed after {$maxAttempts} attempts. Giving up.");
            }
        }

        return false;
    }

    /**
     * Run `scraper:sync all` twice. The second pass picks up records that depend
     * on data created during the first pass (e.g. matches/live-center linking to
     * users that rankings created earlier in the same sync). Returns false if any
     * pass fails.
     */
    protected function syncAllTwice(): bool
    {
        for ($pass = 1; $pass <= 2; $pass++) {
            $this->line("  ↻ Sync pass {$pass}/2...");
            if ($this->call('scraper:sync', ['type' => 'all']) !== self::SUCCESS) {
                return false;
            }
        }

        return true;
    }

    /**
     * A month counts as complete when there is at least one COMPLETED rankings run
     * for both a male gender (m/male) and a female gender (k/female).
     */
    protected function monthAlreadyComplete(int $year, string $mm): bool
    {
        $runs = ScraperRun::where('type', ScraperRun::TYPE_RANKINGS)
            ->where('status', ScraperRun::STATUS_COMPLETED)
            ->get();

        $hasMale = false;
        $hasFemale = false;

        foreach ($runs as $run) {
            $params = $run->parameters ?? [];

            if ((string) ($params['year'] ?? '') !== (string) $year) {
                continue;
            }
            if ($this->normalizeMonth($params['month'] ?? null) !== $mm) {
                continue;
            }

            $gender = strtolower((string) ($params['gender'] ?? ''));
            if (in_array($gender, ['m', 'male'], true)) {
                $hasMale = true;
            }
            if (in_array($gender, ['k', 'female'], true)) {
                $hasFemale = true;
            }
        }

        return $hasMale && $hasFemale;
    }

    /**
     * Normalise a stored month value ('5', '05', or '2026-05') to a zero-padded 'MM'.
     */
    protected function normalizeMonth($month): ?string
    {
        if ($month === null || $month === '') {
            return null;
        }
        if (str_contains((string) $month, '-')) {
            $parts = explode('-', (string) $month);
            $month = end($parts);
        }
        return str_pad((string) (int) $month, 2, '0', STR_PAD_LEFT);
    }

    protected function section(string $title): void
    {
        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->line("  {$title}");
        $this->line(str_repeat('─', 60));
    }

    protected function displayHeader(int $year, array $months): void
    {
        $first = str_pad((string) $months[0], 2, '0', STR_PAD_LEFT);
        $last  = str_pad((string) end($months), 2, '0', STR_PAD_LEFT);
        $mode  = $this->option('parallel') ? 'parallel genders' : 'sequential genders';
        $perMonth = $this->option('parallel') ? 51 : 95; // minutes, from observed run times
        $hours = (int) round((count($months) * $perMonth) / 60);

        $this->newLine();
        $this->info("Scrape Year {$year}  ({$year}-{$first} → {$year}-{$last}, " . count($months) . ' months)');
        $this->line("  Mode: {$mode}" . ($this->option('resume') ? ', resume on' : ''));
        $this->warn("  ⚠  Long job: roughly ~{$hours}h for this range (≈{$perMonth} min/month). Rankings sync runs after every month.");
    }
}
