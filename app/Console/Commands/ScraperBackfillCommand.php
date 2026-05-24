<?php

namespace App\Console\Commands;

use App\Jobs\Scraper\ScrapeLiveCenterJob;
use App\Jobs\Scraper\ScrapePlayersJob;
use App\Jobs\Scraper\ScrapeRankingsJob;
use App\Jobs\Scraper\ScrapeSeriesJob;
use App\Jobs\Scraper\ScrapeTransitionsJob;
use App\Models\Scraper\ScraperRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Backfill historical scraper data across one or more domains.
 *
 * Default range covers the last five years through the most recently
 * completed month. Atoms (the smallest scrape units) are dispatched
 * to the `scraper` queue by default; pass --sync to run them in the
 * foreground.
 */
class ScraperBackfillCommand extends Command
{
    protected $signature = 'scraper:backfill
        {--from= : Start month (YYYY-MM). Defaults to 5 years before --to.}
        {--to= : End month (YYYY-MM). Defaults to last completed month.}
        {--domains= : Comma-separated domains (rankings,players,transitions,series,live_center). Defaults to all.}
        {--genders=m,k : Comma-separated genders for the rankings scraper.}
        {--dry-run : Print the plan and exit.}
        {--resume : Continue the most recent unfinished backfill ScraperRun, skipping already-completed atoms.}
        {--sync : Run atoms synchronously in the foreground instead of dispatching to the queue.}
        {--allow-name-collisions : Bypass the duplicate-name pre-flight check on the users table.}
        {--skip-final-sync : Do not run "scraper:sync all" after raw scrapes complete.}
        {--rate-limit-seconds=2 : Seconds to sleep between dispatches.}
        {--force : Skip the interactive confirmation prompt.}';

    protected $description = 'Backfill historical scraped data across rankings, players, transitions, series, and live center.';

    protected ScraperRun $parentRun;

    protected array $domainsAll = ['rankings', 'players', 'transitions', 'series', 'live_center'];

    public function handle(): int
    {
        $opts = $this->resolveOptions();
        if ($opts === null) {
            return self::FAILURE;
        }

        $plan = $this->buildPlan($opts);

        $this->renderPlan($plan, $opts);

        if ($this->option('dry-run')) {
            $this->info('Dry run — exiting without dispatching anything.');
            return self::SUCCESS;
        }

        // ---- safety pre-flight ----
        if (! $this->checkConcurrencyLock()) {
            return self::FAILURE;
        }

        if (! $this->checkNameCollisions()) {
            return self::FAILURE;
        }

        if (! $this->checkDiskSpace($plan)) {
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Proceed with this backfill?', false)) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        // ---- resume or create parent run ----
        $resume = $this->option('resume');
        $completedAtoms = [];

        if ($resume) {
            $existing = ScraperRun::where('type', ScraperRun::TYPE_BACKFILL)
                ->whereIn('status', [ScraperRun::STATUS_RUNNING, ScraperRun::STATUS_FAILED])
                ->orderByDesc('id')
                ->first();

            if (! $existing) {
                $this->error('--resume passed but no resumable backfill ScraperRun found.');
                return self::FAILURE;
            }

            $this->parentRun = $existing;
            $completedAtoms = collect($existing->steps_data ?? [])
                ->filter(fn ($v) => ($v['status'] ?? null) === 'done')
                ->keys()
                ->all();

            $this->info("Resuming backfill ScraperRun #{$existing->id} — {$existing->items_scraped} atoms previously completed.");
        } else {
            $this->parentRun = ScraperRun::create([
                'type' => ScraperRun::TYPE_BACKFILL,
                'status' => ScraperRun::STATUS_RUNNING,
                'parameters' => [
                    'from' => $opts['from'],
                    'to' => $opts['to'],
                    'domains' => $opts['domains'],
                    'genders' => $opts['genders'],
                    'sync' => $opts['sync'],
                    'plan' => $plan,
                ],
                'steps_data' => [],
                'items_scraped' => 0,
                'items_failed' => 0,
                'started_at' => now(),
            ]);
            $this->parentRun->log('info', "Backfill started — {$this->countAtoms($plan)} atoms planned");
        }

        // ---- dispatch atoms ----
        $rateLimit = max(0, (int) $this->option('rate-limit-seconds'));
        $dispatched = 0;
        $skipped = 0;

        foreach ($this->flattenAtoms($plan) as $atom) {
            if (in_array($atom['key'], $completedAtoms, true)) {
                $skipped++;
                continue;
            }

            try {
                $this->dispatchAtom($atom, $opts['sync']);
                $this->parentRun->updateStepData($atom['key'], [
                    'status' => 'done',
                    'domain' => $atom['domain'],
                    'params' => $atom['params'],
                    'dispatched_at' => now()->toIso8601String(),
                ]);
                $this->parentRun->increment('items_scraped');
                $dispatched++;
                $this->line("  <fg=green>✓</> {$atom['domain']} {$atom['label']}");
            } catch (\Throwable $e) {
                $this->parentRun->updateStepData($atom['key'], [
                    'status' => 'failed',
                    'domain' => $atom['domain'],
                    'params' => $atom['params'],
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                ]);
                $this->parentRun->increment('items_failed');
                $this->line("  <fg=red>✗</> {$atom['domain']} {$atom['label']}: {$e->getMessage()}");
            }

            if ($rateLimit > 0) {
                sleep($rateLimit);
            }
        }

        $this->newLine();
        $this->info("Dispatched {$dispatched} atoms (skipped {$skipped} previously completed).");

        // ---- final sync pass ----
        if (! $this->option('skip-final-sync') && $opts['sync']) {
            $this->newLine();
            $this->info('Running scraper:sync all to promote scraped rows to production tables...');
            Artisan::call('scraper:sync', ['type' => 'all'], $this->getOutput());
        } elseif (! $this->option('skip-final-sync')) {
            $this->newLine();
            $this->warn('Atoms were queued — not running sync automatically. After the queue drains, run:');
            $this->line('  <fg=cyan>php artisan scraper:sync all</>');
        }

        $this->parentRun->markAsCompleted();
        $this->parentRun->log('info', "Backfill completed — {$dispatched} atoms dispatched, {$skipped} resumed-skipped");

        return self::SUCCESS;
    }

    // ---- option resolution ----

    protected function resolveOptions(): ?array
    {
        $to = $this->option('to') ?: now()->subMonth()->format('Y-m');
        $from = $this->option('from') ?: Carbon::createFromFormat('Y-m', $to)->copy()->subYears(5)->format('Y-m');

        if (! $this->validateMonth($from) || ! $this->validateMonth($to)) {
            $this->error('Invalid --from or --to format. Use YYYY-MM.');
            return null;
        }

        if (Carbon::createFromFormat('Y-m', $from)->greaterThan(Carbon::createFromFormat('Y-m', $to))) {
            $this->error("--from ({$from}) must be on or before --to ({$to}).");
            return null;
        }

        $domains = $this->option('domains')
            ? array_map('trim', explode(',', $this->option('domains')))
            : $this->domainsAll;

        $invalidDomains = array_diff($domains, $this->domainsAll);
        if (! empty($invalidDomains)) {
            $this->error('Unknown domain(s): '.implode(', ', $invalidDomains));
            $this->line('Valid: '.implode(', ', $this->domainsAll));
            return null;
        }

        $genders = array_map('trim', explode(',', $this->option('genders')));
        $invalidGenders = array_diff($genders, ['m', 'k']);
        if (! empty($invalidGenders)) {
            $this->error('Unknown gender(s): '.implode(', ', $invalidGenders).". Use 'm' and/or 'k'.");
            return null;
        }

        return [
            'from' => $from,
            'to' => $to,
            'domains' => $domains,
            'genders' => $genders,
            'sync' => (bool) $this->option('sync'),
        ];
    }

    protected function validateMonth(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value);
    }

    // ---- plan ----

    /**
     * Build the plan. Returns: [domain => [atom, atom, ...]]
     * Each atom has: domain, key, label, params, est_minutes.
     */
    protected function buildPlan(array $opts): array
    {
        $plan = [];

        $months = $this->enumerateMonths($opts['from'], $opts['to']);

        if (in_array('rankings', $opts['domains'], true)) {
            $atoms = [];
            // Reverse-chronological by default (recent first → quick feedback)
            foreach (array_reverse($months) as [$year, $month]) {
                foreach ($opts['genders'] as $gender) {
                    $atoms[] = [
                        'domain' => 'rankings',
                        'key' => "rankings:{$year}-{$month}:{$gender}",
                        'label' => "{$year}-{$month} ({$gender})",
                        'params' => ['year' => (int) $year, 'month' => (int) $month, 'gender' => $gender],
                        'est_minutes' => 30,
                    ];
                }
            }
            $plan['rankings'] = $atoms;
        }

        if (in_array('players', $opts['domains'], true)) {
            // Single atom — the players scraper iterates periods from the dropdown.
            $startPeriod = str_replace('-', '.', $opts['from']).'.01';
            $plan['players'] = [[
                'domain' => 'players',
                'key' => 'players:all-from-'.$opts['from'],
                'label' => "all periods from {$opts['from']}",
                'params' => ['period' => $startPeriod, 'direction' => 'gte'],
                'est_minutes' => 45,
            ]];
        }

        if (in_array('transitions', $opts['domains'], true)) {
            $plan['transitions'] = [[
                'domain' => 'transitions',
                'key' => 'transitions:all',
                'label' => 'all available periods',
                'params' => [],
                'est_minutes' => 15,
            ]];
        }

        if (in_array('series', $opts['domains'], true)) {
            $plan['series'] = [[
                'domain' => 'series',
                'key' => 'series:all',
                'label' => 'all discovered seasons',
                'params' => [],
                'est_minutes' => 60,
            ]];
        }

        if (in_array('live_center', $opts['domains'], true)) {
            $atoms = [];
            $years = $this->enumerateYears($opts['from'], $opts['to']);
            foreach (array_reverse($years) as $year) {
                $atoms[] = [
                    'domain' => 'live_center',
                    'key' => "live_center:{$year}",
                    'label' => "year {$year}",
                    'params' => ['year' => $year],
                    'est_minutes' => 30,
                ];
            }
            $plan['live_center'] = $atoms;
        }

        return $plan;
    }

    protected function flattenAtoms(array $plan): array
    {
        $flat = [];
        foreach ($plan as $atoms) {
            foreach ($atoms as $atom) {
                $flat[] = $atom;
            }
        }
        return $flat;
    }

    protected function countAtoms(array $plan): int
    {
        return array_sum(array_map('count', $plan));
    }

    protected function enumerateMonths(string $from, string $to): array
    {
        $cursor = Carbon::createFromFormat('Y-m', $from)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $to)->startOfMonth();
        $months = [];
        while ($cursor->lessThanOrEqualTo($end)) {
            $months[] = [$cursor->format('Y'), $cursor->format('m')];
            $cursor->addMonth();
        }
        return $months;
    }

    protected function enumerateYears(string $from, string $to): array
    {
        $start = (int) substr($from, 0, 4);
        $end = (int) substr($to, 0, 4);
        return range($start, $end);
    }

    protected function renderPlan(array $plan, array $opts): void
    {
        $this->newLine();
        $this->info("Backfill plan: {$opts['from']} → {$opts['to']}");
        $this->line('Domains: '.implode(', ', $opts['domains']).'  |  Genders: '.implode(', ', $opts['genders']).'  |  Mode: '.($opts['sync'] ? 'sync' : 'queue'));
        $this->newLine();

        $rows = [];
        $totalAtoms = 0;
        $totalMinutes = 0;
        foreach ($plan as $domain => $atoms) {
            $estMinutes = array_sum(array_column($atoms, 'est_minutes'));
            $rows[] = [
                $domain,
                count($atoms),
                $this->formatDuration($estMinutes),
            ];
            $totalAtoms += count($atoms);
            $totalMinutes += $estMinutes;
        }

        $this->table(['Domain', 'Atoms', 'Est. sequential time'], $rows);
        $this->line("Total: <fg=cyan>{$totalAtoms}</> atoms, ~<fg=cyan>".$this->formatDuration($totalMinutes).'</> sequential wall time.');
        $this->newLine();
    }

    protected function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}m";
        }
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return $mins ? "{$hours}h {$mins}m" : "{$hours}h";
    }

    // ---- safety pre-flight ----

    protected function checkConcurrencyLock(): bool
    {
        $busy = ScraperRun::whereIn('type', [ScraperRun::TYPE_BACKFILL, ScraperRun::TYPE_FULL_SCRAPE])
            ->where('status', ScraperRun::STATUS_RUNNING)
            ->exists();

        if ($busy) {
            $this->error('Another backfill or full_scrape ScraperRun is currently running. Refusing to start.');
            $this->line('Use --resume to continue, or wait for it to finish.');
            return false;
        }

        return true;
    }

    protected function checkNameCollisions(): bool
    {
        if ($this->option('allow-name-collisions')) {
            return true;
        }

        $collisions = DB::table('users')
            ->select('first_name', 'last_name', DB::raw('COUNT(*) as c'))
            ->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->groupBy('first_name', 'last_name')
            ->havingRaw('COUNT(*) > 1')
            ->limit(10)
            ->get();

        if ($collisions->isEmpty()) {
            return true;
        }

        $this->error('Duplicate-name pre-flight failed — these users share a (first_name, last_name) pair:');
        foreach ($collisions as $c) {
            $this->line("  • {$c->first_name} {$c->last_name} (x{$c->c})");
        }
        $this->newLine();
        $this->line('Scraped rankings/matches resolve users by name only. With duplicates, sync will silently merge data into one of them.');
        $this->line('Either deduplicate the users table first, or pass <fg=cyan>--allow-name-collisions</> to proceed anyway.');
        return false;
    }

    protected function checkDiskSpace(array $plan): bool
    {
        $rankingsAtoms = count($plan['rankings'] ?? []);
        // Conservative: ~5000 rankings rows × ~600 bytes × number of months (×2 genders).
        // Plus matches and a few other tables. Round up to ~120MB per (month, gender).
        $estimatedGb = (int) ceil(($rankingsAtoms * 120) / 1024);

        $free = @disk_free_space(storage_path());
        if ($free === false) {
            $this->warn('Could not determine free disk space. Proceeding without disk check.');
            return true;
        }

        $freeGb = round($free / 1024 / 1024 / 1024, 1);
        $threshold = max(5, $estimatedGb * 2);

        if ($freeGb < $threshold) {
            $this->error("Insufficient disk space: {$freeGb}GB free, recommend at least {$threshold}GB for this backfill (estimated raw growth ~{$estimatedGb}GB).");
            return false;
        }

        $this->line("Disk: <fg=green>{$freeGb}GB</> free (estimated growth ~{$estimatedGb}GB).");
        return true;
    }

    // ---- atom dispatch ----

    protected function dispatchAtom(array $atom, bool $sync): void
    {
        $jobClass = match ($atom['domain']) {
            'rankings' => ScrapeRankingsJob::class,
            'players' => ScrapePlayersJob::class,
            'transitions' => ScrapeTransitionsJob::class,
            'series' => ScrapeSeriesJob::class,
            'live_center' => ScrapeLiveCenterJob::class,
            default => throw new \InvalidArgumentException("Unknown domain: {$atom['domain']}"),
        };

        $job = new $jobClass($atom['params']);

        if ($sync) {
            // Synchronous — block until the scraper finishes
            dispatch_sync($job);
        } else {
            // Queue — push and move on
            dispatch($job);
        }
    }
}
