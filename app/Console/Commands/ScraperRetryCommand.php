<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use App\Services\Scraper\RetryFailedScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-scrapes players that previously FAILED during a rankings scrape (popup
 * timed out) — the ones that show up in the logs as
 * "Error processing player X: Page.wait_for_selector: Timeout ...".
 *
 * It is a standalone recovery tool: it reads the failures straight out of
 * scraper_logs, figures out each player's profixio id + rough rank from data we
 * ALREADY have (those players were scraped fine in other months), then drives
 * the SEPARATE RetryFailedScraper / rankings_retry_scraper.py to fetch only
 * those players for only those months — patiently and one at a time.
 *
 * It does NOT modify or call the existing rankings scraper. After scraping it
 * runs the normal sync so recovered rows land in production tables.
 *
 *   php artisan scraper:retry-failed --year=2023
 *   php artisan scraper:retry-failed --all-years --dry-run
 *   php artisan scraper:retry-failed --year=2024 --gender=m
 */
class ScraperRetryCommand extends Command
{
    protected $signature = 'scraper:retry-failed
                            {--year= : Only retry failures from this year (YYYY)}
                            {--all-years : Retry failures across every scraped year}
                            {--gender= : Only retry one gender (m/k/male/female)}
                            {--dry-run : Show what would be retried, scrape nothing}
                            {--no-sync : Skip the sync step after scraping}';

    protected $description = 'Find players that failed during rankings scrapes and re-scrape only them, for only the months they failed.';

    public function handle(): int
    {
        $year = $this->option('year');
        $allYears = (bool) $this->option('all-years');

        if (!$year && !$allYears) {
            $this->error('Specify --year=YYYY or --all-years.');
            return self::FAILURE;
        }

        $genderFilter = $this->normalizeGender($this->option('gender'));

        // 1. Collect failed player names grouped by (year, month, gender).
        $groups = $this->collectFailures($year, $genderFilter);

        if (empty($groups)) {
            $this->info('No failed players found for the given filter. Nothing to do.');
            return self::SUCCESS;
        }

        // 2. Resolve each failure to a scrapeable target (id + estimated rank),
        //    skipping ones we have already recovered.
        $resolvedGroups = [];   // key => ['year','month','gender','targets'=>[]]
        $skipped = 0;
        $unresolvable = [];     // ["2023-06 m: Name", ...]
        $totalTargets = 0;

        foreach ($groups as $key => $group) {
            [$gYear, $gMonth, $gGender] = [$group['year'], $group['month'], $group['gender']];
            $targets = [];

            foreach ($group['names'] as $name) {
                if ($this->alreadyRecovered($name, $gYear, $gMonth)) {
                    $skipped++;
                    continue;
                }

                $ref = $this->resolvePlayer($name, $gYear, $gMonth, $gGender);
                if ($ref === null) {
                    $unresolvable[] = "{$gYear}-{$gMonth} {$gGender}: {$name}";
                    continue;
                }

                $targets[] = [
                    'name' => $name,
                    'profixio_id' => $ref['profixio_id'],
                    'est_position' => $ref['est_position'],
                ];
            }

            if (!empty($targets)) {
                $resolvedGroups[$key] = [
                    'year' => $gYear,
                    'month' => $gMonth,
                    'gender' => $gGender,
                    'targets' => $targets,
                ];
                $totalTargets += count($targets);
            }
        }

        $this->printPlan($resolvedGroups, $totalTargets, $skipped, $unresolvable);

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('Dry run — nothing scraped. Re-run without --dry-run to recover.');
            return self::SUCCESS;
        }

        if (empty($resolvedGroups)) {
            $this->info('Nothing left to scrape (all already recovered or unresolvable).');
            return self::SUCCESS;
        }

        // 3. Scrape each group with the dedicated retry service.
        $recovered = 0;
        $stillFailed = 0;

        foreach ($resolvedGroups as $group) {
            $label = "{$group['year']}-{$group['month']} {$group['gender']}";
            $this->section("Retrying {$label} — " . count($group['targets']) . ' player(s)');

            try {
                $scraper = app(RetryFailedScraper::class);
                $scraper->setConsoleOutput($this);
                $run = $scraper->scrape([
                    'year' => $group['year'],
                    'month' => $group['month'],
                    'gender' => $group['gender'],
                    'targets' => $group['targets'],
                ]);

                $recovered += $run->items_scraped;
                $stillFailed += $run->items_failed;
                $this->line("  ✓ Run #{$run->id}: {$run->items_scraped} rows scraped, {$run->items_failed} still failing");
            } catch (\Throwable $e) {
                $this->error("  ✗ {$label} failed: {$e->getMessage()}");
            }
        }

        // 4. Sync recovered rows into production tables.
        if (!$this->option('no-sync') && $recovered > 0) {
            $this->section('Syncing recovered data');
            for ($pass = 1; $pass <= 2; $pass++) {
                $this->line("  ↻ Sync pass {$pass}/2...");
                if ($this->call('scraper:sync', ['type' => 'all']) !== self::SUCCESS) {
                    $this->warn('  ⚠ Sync pass failed — recovered rows remain in scraped_* tables (is_synced=false).');
                    break;
                }
            }
        }

        $this->newLine();
        $this->info("✓ Done. Rows scraped: {$recovered}. Still failing: {$stillFailed}. Skipped (already recovered): {$skipped}.");
        if (!empty($unresolvable)) {
            $this->warn('  Unresolvable (no profixio id in existing data — handle manually): ' . count($unresolvable));
        }

        return self::SUCCESS;
    }

    /**
     * Read failures from scraper_logs and group unique player names by
     * (year, month, gender). Handles the double-logged retry lines.
     *
     * @return array<string, array{year:string,month:string,gender:string,names:array<string>}>
     */
    protected function collectFailures(?string $year, ?string $genderFilter): array
    {
        $runs = ScraperRun::where('type', ScraperRun::TYPE_RANKINGS)->get();

        $groups = [];

        foreach ($runs as $run) {
            $params = $run->parameters ?? [];
            $rYear = (string) ($params['year'] ?? '');
            $rMonth = $this->normalizeMonth($params['month'] ?? null);
            $rGender = $this->normalizeGender($params['gender'] ?? null);

            if ($rYear === '' || $rMonth === null || $rGender === null) {
                continue;
            }
            if ($year !== null && $rYear !== (string) $year) {
                continue;
            }
            if ($genderFilter !== null && $rGender !== $genderFilter) {
                continue;
            }

            $logs = $run->logs()
                ->where('level', 'warning')
                ->where('message', 'like', 'Error processing player%')
                ->pluck('message');

            foreach ($logs as $message) {
                if (!preg_match('/^Error processing player (.+?): /', $message, $m)) {
                    continue;
                }
                $name = trim($m[1]);
                $key = "{$rYear}-{$rMonth}-{$rGender}";

                if (!isset($groups[$key])) {
                    $groups[$key] = ['year' => $rYear, 'month' => $rMonth, 'gender' => $rGender, 'names' => []];
                }
                $groups[$key]['names'][$name] = true; // set semantics → dedupe
            }
        }

        // Flatten the name sets to lists.
        foreach ($groups as $key => $g) {
            $groups[$key]['names'] = array_keys($g['names']);
        }

        ksort($groups);
        return $groups;
    }

    /**
     * True if this player already has a ranking row for the target period
     * (recovered by an earlier retry, a re-run, or never actually missing).
     */
    protected function alreadyRecovered(string $name, string $year, string $month): bool
    {
        $period = "{$year}-{$month}";
        return DB::table('scraped_rankings')
            ->where('name', $name)
            ->where('period', $period)
            ->exists();
    }

    /**
     * Resolve a failed player to {profixio_id, est_position} using existing
     * scraped_rankings rows from OTHER months (same player, different period).
     * est_position = the rank from the period closest to the target month.
     */
    protected function resolvePlayer(string $name, string $year, string $month, string $gender): ?array
    {
        $rows = DB::table('scraped_rankings')
            ->where('name', $name)
            ->whereNotNull('profixio_player_id')
            ->where('profixio_player_id', '!=', '')
            ->select('profixio_player_id', 'position', 'period')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $targetIndex = ((int) $year) * 12 + (int) $month;

        $best = null;
        $bestDistance = PHP_INT_MAX;
        foreach ($rows as $row) {
            // period is 'YYYY-MM'
            if (!preg_match('/^(\d{4})-(\d{2})/', (string) $row->period, $pm)) {
                continue;
            }
            $idx = ((int) $pm[1]) * 12 + (int) $pm[2];
            $distance = abs($idx - $targetIndex);
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $row;
            }
        }

        $best = $best ?? $rows->first();

        return [
            'profixio_id' => (string) $best->profixio_player_id,
            'est_position' => $best->position !== null ? (int) $best->position : null,
        ];
    }

    protected function printPlan(array $resolvedGroups, int $totalTargets, int $skipped, array $unresolvable): void
    {
        $this->newLine();
        $this->info("Retry plan: {$totalTargets} player(s) to re-scrape across " . count($resolvedGroups) . ' month/gender group(s).');
        if ($skipped > 0) {
            $this->line("  Skipped (already recovered): {$skipped}");
        }

        foreach ($resolvedGroups as $group) {
            $names = array_map(fn ($t) => $t['name'], $group['targets']);
            $this->line("  {$group['year']}-{$group['month']} {$group['gender']} (" . count($names) . '): ' . implode(' / ', $names));
        }

        if (!empty($unresolvable)) {
            $this->newLine();
            $this->warn('  Unresolvable — no profixio id found in existing data (need manual handling):');
            foreach ($unresolvable as $u) {
                $this->line("    - {$u}");
            }
        }
    }

    protected function section(string $title): void
    {
        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->line("  {$title}");
        $this->line(str_repeat('─', 60));
    }

    protected function normalizeGender($gender): ?string
    {
        if ($gender === null || $gender === '') {
            return null;
        }
        $g = strtolower((string) $gender);
        return match ($g) {
            'm', 'male', 'man', 'men' => 'm',
            'k', 'female', 'woman', 'women', 'f' => 'k',
            default => null,
        };
    }

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
}
