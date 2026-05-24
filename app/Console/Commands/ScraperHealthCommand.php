<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class ScraperHealthCommand extends Command
{
    protected $signature = 'scraper:health
                            {--json : Output structured JSON suitable for monitoring/alerting}
                            {--stuck-threshold=120 : Minutes a run may be in "running" state before flagged stuck}
                            {--failure-window=7 : Days to look back when computing recent failure rate}';

    protected $description = 'Operational scraper health check (scheduler, queue, recent runs, profixio reachability, disk).';

    protected array $results = [];

    protected int $passed = 0;

    protected int $failed = 0;

    protected int $warnings = 0;

    protected bool $json = false;

    public function handle(): int
    {
        $this->json = (bool) $this->option('json');

        if (! $this->json) {
            $this->displayHeader();
        }

        $this->section('Environment');
        $this->checkPython();
        $this->checkPythonScripts();

        $this->section('Database');
        $this->checkScraperTables();
        $this->checkScraperSettings();

        $this->section('Queue & Scheduler');
        $this->checkQueueDriver();
        $this->checkScraperWorker();
        $this->checkSchedulerHeartbeat();
        $this->checkQueuedJobs();
        $this->checkFailedJobs();
        $this->checkRedisIfApplicable();

        $this->section('Recent Activity');
        $this->checkStuckRuns();
        $this->checkLastRunsByDomain();
        $this->checkRecentFailureRate();

        $this->section('External');
        $this->checkProfixioReachable();

        $this->section('Storage');
        $this->checkDiskSpace();

        if ($this->json) {
            $status = $this->failed > 0 ? 'unhealthy' : ($this->warnings > 0 ? 'degraded' : 'healthy');
            $this->line(json_encode([
                'status' => $status,
                'passed' => $this->passed,
                'failed' => $this->failed,
                'warnings' => $this->warnings,
                'checks' => $this->results,
                'generated_at' => now()->toIso8601String(),
            ], JSON_PRETTY_PRINT));
        } else {
            $this->displaySummary();
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function checkPython(): void
    {
        $binary = config('scraper.python.binary', env('SCRAPER_PYTHON_BINARY', 'python3'));

        try {
            $result = Process::timeout(5)->run("{$binary} --version 2>&1");
            if ($result->successful()) {
                $this->checkPass('Python', trim($result->output()), ['binary' => $binary]);
                return;
            }
            $this->checkFail('Python', "Not runnable at {$binary}");
        } catch (\Throwable $e) {
            $this->checkFail('Python', "Error invoking {$binary}: ".$e->getMessage());
        }
    }

    protected function checkPythonScripts(): void
    {
        $scripts = [
            'rankings_popup_scraper.py' => base_path('scripts/scraper/rankings_popup_scraper.py'),
            'livecenter_scraper.py' => base_path('scripts/scraper/livecenter_scraper.py'),
        ];

        $missing = [];
        foreach ($scripts as $name => $path) {
            if (! file_exists($path)) {
                $missing[] = $name;
            }
        }

        if (empty($missing)) {
            $this->checkPass('Python scripts', 'All scraper scripts present', ['scripts' => array_keys($scripts)]);
        } else {
            $this->checkFail('Python scripts', 'Missing: '.implode(', ', $missing));
        }
    }

    protected function checkScraperTables(): void
    {
        $required = [
            'scraper_runs', 'scraper_logs', 'scraper_settings',
            'scraped_players', 'scraped_rankings', 'scraped_matches',
            'scraped_transitions', 'scraped_standings',
            'live_match_details', 'live_match_games', 'live_match_sets', 'live_match_points',
        ];

        $missing = array_values(array_filter($required, fn ($t) => ! Schema::hasTable($t)));

        if (empty($missing)) {
            $this->checkPass('Scraper tables', count($required).' tables present');
        } else {
            $this->checkFail('Scraper tables', 'Missing: '.implode(', ', $missing));
        }
    }

    protected function checkScraperSettings(): void
    {
        if (! Schema::hasTable('scraper_settings')) {
            $this->checkFail('Scraper settings', 'scraper_settings table missing — schedules will not register');
            return;
        }

        $count = DB::table('scraper_settings')->count();
        if ($count === 0) {
            $this->checkWarn('Scraper settings', 'Table exists but empty — schedules use defaults from .env');
        } else {
            $this->checkPass('Scraper settings', "{$count} settings rows");
        }
    }

    protected function checkQueueDriver(): void
    {
        $connection = config('scraper.queue.connection', config('queue.default'));
        $driver = config("queue.connections.{$connection}.driver");

        if ($driver === 'sync') {
            $this->checkFail('Queue driver', "'sync' driver — jobs will block scraper runs", ['connection' => $connection]);
        } elseif ($driver === null) {
            $this->checkFail('Queue driver', "Connection '{$connection}' has no driver configured");
        } else {
            $this->checkPass('Queue driver', "{$driver} ({$connection})");
        }
    }

    protected function checkScraperWorker(): void
    {
        $queueName = config('scraper.queue.queue_name', env('SCRAPER_QUEUE_NAME', 'scraper'));

        try {
            $result = Process::timeout(5)->run("ps aux | grep -E 'queue:work.*{$queueName}|queue:listen.*{$queueName}' | grep -v grep");
            $output = trim($result->output());

            if ($output !== '') {
                $workerCount = count(array_filter(explode("\n", $output)));
                $this->checkPass('Scraper queue worker', "{$workerCount} worker process(es) on '{$queueName}' queue");
            } else {
                $this->checkFail('Scraper queue worker', "No worker process found for '{$queueName}' queue");
            }
        } catch (\Throwable $e) {
            $this->checkWarn('Scraper queue worker', 'Could not inspect process list: '.$e->getMessage());
        }
    }

    protected function checkSchedulerHeartbeat(): void
    {
        if (! config('heartbeat.scheduler.enabled')) {
            $this->checkWarn('Scheduler heartbeat', 'Heartbeat monitoring disabled — cannot verify scheduler is firing');
            return;
        }

        $cacheKey = config('heartbeat.cache_prefix').config('heartbeat.scheduler.cache_key');
        $heartbeat = Cache::get($cacheKey);

        if (! $heartbeat) {
            $this->checkFail('Scheduler heartbeat', 'No heartbeat in cache — scheduler may not be running');
            return;
        }

        $timestamp = $heartbeat['unix_timestamp'] ?? null;
        if (! $timestamp) {
            $this->checkWarn('Scheduler heartbeat', 'Heartbeat exists but malformed');
            return;
        }

        $minutesAgo = (int) round((time() - $timestamp) / 60);
        $threshold = (int) config('heartbeat.timeout', 5);

        if ($minutesAgo > $threshold) {
            $this->checkFail('Scheduler heartbeat', "Stale — last beat {$minutesAgo}m ago (threshold {$threshold}m)");
        } else {
            $this->checkPass('Scheduler heartbeat', "Fresh — last beat {$minutesAgo}m ago");
        }
    }

    protected function checkQueuedJobs(): void
    {
        $connection = config('scraper.queue.connection', config('queue.default'));
        $queueName = config('scraper.queue.queue_name', 'scraper');

        if ($connection === 'database' && Schema::hasTable('jobs')) {
            $count = DB::table('jobs')->where('queue', $queueName)->count();
            $oldest = DB::table('jobs')->where('queue', $queueName)->min('created_at');

            if ($count === 0) {
                $this->checkPass('Queued jobs', "0 pending on '{$queueName}' queue");
            } elseif ($count < 10) {
                $this->checkPass('Queued jobs', "{$count} pending on '{$queueName}' queue");
            } else {
                $this->checkWarn('Queued jobs', "{$count} pending on '{$queueName}' queue — backlog forming"
                    .($oldest ? " (oldest: {$oldest})" : ''));
            }
        } else {
            $this->checkWarn('Queued jobs', "Cannot inspect — connection '{$connection}' is not database-backed");
        }
    }

    protected function checkFailedJobs(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            $this->checkWarn('Failed jobs', 'failed_jobs table missing');
            return;
        }

        $total = DB::table('failed_jobs')->count();
        $recent = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDays(7))->count();

        if ($recent === 0 && $total === 0) {
            $this->checkPass('Failed jobs', '0 ever');
        } elseif ($recent === 0) {
            $this->checkPass('Failed jobs', "{$total} total, none in the last 7 days");
        } else {
            $this->checkWarn('Failed jobs', "{$recent} in the last 7 days ({$total} all-time)");
        }
    }

    protected function checkRedisIfApplicable(): void
    {
        $connection = config('scraper.queue.connection', config('queue.default'));
        $driver = config("queue.connections.{$connection}.driver");

        if ($driver !== 'redis') {
            return;
        }

        try {
            $redisConn = config("queue.connections.{$connection}.connection", 'default');
            $pong = Redis::connection($redisConn)->command('ping');
            if ($pong === true || $pong === 'PONG') {
                $this->checkPass('Redis', "Reachable (connection: {$redisConn})");
            } else {
                $this->checkWarn('Redis', 'PING returned unexpected response: '.json_encode($pong));
            }
        } catch (\Throwable $e) {
            $this->checkFail('Redis', 'Connection failed: '.$e->getMessage());
        }
    }

    protected function checkStuckRuns(): void
    {
        if (! Schema::hasTable('scraper_runs')) {
            return;
        }

        $threshold = (int) $this->option('stuck-threshold');
        $cutoff = now()->subMinutes($threshold);

        $stuck = ScraperRun::where('status', 'running')
            ->where('started_at', '<', $cutoff)
            ->get(['id', 'type', 'current_step', 'started_at']);

        if ($stuck->isEmpty()) {
            $this->checkPass('Stuck runs', "None running longer than {$threshold}m");
            return;
        }

        $details = $stuck->map(fn ($r) => "#{$r->id} ({$r->type}, step: ".($r->current_step ?: 'n/a').", started {$r->started_at})")
            ->all();

        $this->checkFail('Stuck runs', $stuck->count().' run(s) running longer than '.$threshold.'m', ['stuck' => $details]);
    }

    protected function checkLastRunsByDomain(): void
    {
        if (! Schema::hasTable('scraper_runs')) {
            return;
        }

        $domains = ['rankings', 'players', 'transitions', 'series', 'series_matches', 'live_center', 'full_scrape'];
        $rows = [];

        foreach ($domains as $domain) {
            $last = ScraperRun::where('type', $domain)
                ->where('status', 'completed')
                ->orderByDesc('completed_at')
                ->first(['id', 'completed_at', 'items_scraped']);

            if (! $last) {
                $rows[$domain] = ['status' => 'never', 'last' => null];
                continue;
            }

            $daysAgo = $last->completed_at ? Carbon::parse($last->completed_at)->diffInDays(now()) : null;
            $rows[$domain] = [
                'last_run_id' => $last->id,
                'completed_at' => (string) $last->completed_at,
                'items_scraped' => $last->items_scraped,
                'days_ago' => $daysAgo,
            ];
        }

        // Surface as a single check — warn if rankings/players are stale (>40d) or never
        $stale = [];
        foreach (['rankings', 'players', 'full_scrape'] as $criticalDomain) {
            $info = $rows[$criticalDomain];
            if (($info['status'] ?? null) === 'never') {
                $stale[] = "{$criticalDomain}: never";
            } elseif (($info['days_ago'] ?? 0) > 40) {
                $stale[] = "{$criticalDomain}: {$info['days_ago']}d ago";
            }
        }

        if (empty($stale)) {
            $this->checkPass('Last successful runs', 'All critical domains scraped within 40d', ['by_domain' => $rows]);
        } else {
            $this->checkWarn('Last successful runs', 'Stale: '.implode('; ', $stale), ['by_domain' => $rows]);
        }
    }

    protected function checkRecentFailureRate(): void
    {
        if (! Schema::hasTable('scraper_runs')) {
            return;
        }

        $window = (int) $this->option('failure-window');
        $since = now()->subDays($window);

        $total = ScraperRun::where('started_at', '>=', $since)->count();
        $failed = ScraperRun::where('started_at', '>=', $since)->where('status', 'failed')->count();

        if ($total === 0) {
            $this->checkWarn('Recent run activity', "No runs in the last {$window} days — scheduler may be silent");
            return;
        }

        $rate = (int) round(($failed / $total) * 100);

        if ($rate === 0) {
            $this->checkPass('Recent failure rate', "0% ({$failed}/{$total} in last {$window}d)");
        } elseif ($rate < 20) {
            $this->checkPass('Recent failure rate', "{$rate}% ({$failed}/{$total} in last {$window}d)");
        } elseif ($rate < 50) {
            $this->checkWarn('Recent failure rate', "{$rate}% ({$failed}/{$total} in last {$window}d)");
        } else {
            $this->checkFail('Recent failure rate', "{$rate}% ({$failed}/{$total} in last {$window}d) — investigate");
        }
    }

    protected function checkProfixioReachable(): void
    {
        $url = config('scraper.main_url', env('SCRAPER_MAIN_URL', 'https://www.profixio.com/fx/sbtf/'));

        try {
            $started = microtime(true);
            $response = Http::timeout(5)->withHeaders(['User-Agent' => 'iRacket-health-check/1.0'])->head($url);
            $ms = (int) round((microtime(true) - $started) * 1000);

            if ($response->successful() || $response->status() === 302 || $response->status() === 301) {
                $this->checkPass('Profixio reachability', "HTTP {$response->status()} in {$ms}ms", ['url' => $url]);
            } else {
                $this->checkWarn('Profixio reachability', "HTTP {$response->status()} from {$url}");
            }
        } catch (\Throwable $e) {
            $this->checkFail('Profixio reachability', 'Unreachable: '.$e->getMessage());
        }
    }

    protected function checkDiskSpace(): void
    {
        $path = storage_path();

        try {
            $free = @disk_free_space($path);
            $total = @disk_total_space($path);

            if ($free === false || $total === false || $total === 0) {
                $this->checkWarn('Disk space', 'Could not read disk stats for '.$path);
                return;
            }

            $freeGb = round($free / 1024 / 1024 / 1024, 1);
            $usedPct = (int) round((1 - ($free / $total)) * 100);

            if ($freeGb < 2) {
                $this->checkFail('Disk space', "{$freeGb}GB free ({$usedPct}% used) — critically low");
            } elseif ($freeGb < 10 || $usedPct > 90) {
                $this->checkWarn('Disk space', "{$freeGb}GB free ({$usedPct}% used)");
            } else {
                $this->checkPass('Disk space', "{$freeGb}GB free ({$usedPct}% used)");
            }
        } catch (\Throwable $e) {
            $this->checkWarn('Disk space', 'Error: '.$e->getMessage());
        }
    }

    // ---- output helpers ----

    protected function checkPass(string $name, string $message, array $context = []): void
    {
        $this->record('pass', $name, $message, $context);
        if (! $this->json) {
            $this->line("  <fg=green>✓</> <fg=white>{$name}:</> {$message}");
        }
        $this->passed++;
    }

    protected function checkFail(string $name, string $message, array $context = []): void
    {
        $this->record('fail', $name, $message, $context);
        if (! $this->json) {
            $this->line("  <fg=red>✗</> <fg=white>{$name}:</> {$message}");
        }
        $this->failed++;
    }

    protected function checkWarn(string $name, string $message, array $context = []): void
    {
        $this->record('warn', $name, $message, $context);
        if (! $this->json) {
            $this->line("  <fg=yellow>⚠</> <fg=white>{$name}:</> {$message}");
        }
        $this->warnings++;
    }

    protected function record(string $level, string $name, string $message, array $context): void
    {
        $this->results[] = [
            'level' => $level,
            'name' => $name,
            'message' => $message,
            'context' => $context,
        ];
    }

    protected function section(string $title): void
    {
        if ($this->json) {
            return;
        }
        $this->newLine();
        $this->line("<fg=cyan>── {$title} ──</>");
    }

    protected function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║              SCRAPER HEALTH CHECK                      ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║                     SUMMARY                            ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->line("  Passed:   <fg=green>{$this->passed}</>");
        $this->line("  Failed:   <fg=red>{$this->failed}</>");
        $this->line("  Warnings: <fg=yellow>{$this->warnings}</>");
        $this->newLine();

        if ($this->failed > 0) {
            $this->error('⚠  Scraper is UNHEALTHY. Investigate the failures above.');
        } elseif ($this->warnings > 0) {
            $this->line("<fg=yellow>⚠  Scraper is functional but degraded. Address warnings when possible.</>");
        } else {
            $this->info('✓  All checks passed. Scraper is healthy.');
        }
        $this->newLine();
        $this->line('For environment/install validation, also run: <fg=cyan>php artisan scraper:check</>');
        $this->newLine();
    }
}
