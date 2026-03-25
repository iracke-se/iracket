<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use Illuminate\Console\Command;

class ScraperCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scraper:run
                            {type : The type of scrape (rankings, players, transitions, series, series_matches, live_center)}
                            {--year= : Year (YYYY) for rankings scraper or live_center (e.g., 2026)}
                            {--month= : Month (MM or YYYY-MM) for rankings scraper or live_center (e.g., 01 or 2025-12)}
                            {--gender=male : Gender for rankings (male/female, or m/k for popup scraper)}
                            {--period= : Period filter (e.g., 2024.01.01)}
                            {--direction=gte : Direction for period filter (gte/lte)}
                            {--period-skip= : Skip first N periods (for parallel processing)}
                            {--period-take= : Take only N periods after skip (for parallel processing)}
                            {--limit-periods= : Limit number of periods to scrape (for testing)}
                            {--limit-clubs= : Limit number of clubs to scrape (for testing, players only)}
                            {--limit-divisions= : Limit number of divisions to scrape (for testing, rankings/live_center)}
                            {--limit-seasons= : Limit number of seasons to scrape (for testing, series/series_matches)}
                            {--limit-series= : Limit number of series per season to scrape (for testing, series/series_matches)}
                            {--limit-matches= : Limit number of matches to scrape (for testing, series_matches/live_center)}
                            {--limit-players= : Limit number of players to scrape (for testing, rankings popup scraper)}
                            {--concurrency= : Number of parallel browser tabs for rankings scraper (default: 10)}
                            {--date= : Date to scrape (YYYY-MM-DD format, for live_center)}
                            {--from-matches : Scrape dates from existing matches table (for live_center)}
                            {--skip-points : Skip point-by-point data (for live_center)}
                            {--queue : Queue the job instead of running synchronously}';

    /**
     * The console command description.
     */
    protected $description = 'Run the profixio.com scraper';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->argument('type');
        $validTypes = ['rankings', 'players', 'transitions', 'series', 'series_matches', 'live_center'];

        if (!in_array($type, $validTypes)) {
            $this->error("Invalid type. Must be one of: " . implode(', ', $validTypes));
            return self::FAILURE;
        }

        $parameters = [];

        // Build parameters based on type
        if ($type === 'rankings') {
            // New popup-based scraper uses year/month/gender parameters
            if ($this->option('year')) {
                $parameters['year'] = $this->option('year');
            }
            if ($this->option('month')) {
                $parameters['month'] = $this->option('month');
            }
            $parameters['gender'] = $this->option('gender');

            // Legacy parameters for backward compatibility
            if ($this->option('period')) {
                $parameters['period'] = $this->option('period');
                $parameters['direction'] = $this->option('direction');
            }
            if ($this->option('period-skip')) {
                $parameters['period_skip'] = (int) $this->option('period-skip');
            }
            if ($this->option('period-take')) {
                $parameters['period_take'] = (int) $this->option('period-take');
            }
            if ($this->option('limit-periods')) {
                $parameters['limit_periods'] = (int) $this->option('limit-periods');
            }
            if ($this->option('limit-divisions')) {
                $parameters['limit_divisions'] = (int) $this->option('limit-divisions');
            }
            if ($this->option('limit-players')) {
                $parameters['limit_players'] = (int) $this->option('limit-players');
            }
            if ($this->option('concurrency')) {
                $parameters['concurrency'] = (int) $this->option('concurrency');
            }
        }

        if ($type === 'players') {
            if ($this->option('period')) {
                $parameters['period'] = $this->option('period');
                $parameters['direction'] = $this->option('direction');
            }
            if ($this->option('limit-periods')) {
                $parameters['limit_periods'] = (int) $this->option('limit-periods');
            }
            if ($this->option('limit-clubs')) {
                $parameters['limit_clubs'] = (int) $this->option('limit-clubs');
            }
        }

        if ($type === 'transitions') {
            if ($this->option('limit-periods')) {
                $parameters['limit_periods'] = (int) $this->option('limit-periods');
            }
        }

        if ($type === 'live_center') {
            if ($this->option('from-matches')) {
                $parameters['from_matches'] = true;
                // Optional: filter by month or year when using from-matches
                if ($this->option('month')) {
                    $parameters['month'] = $this->option('month');
                } elseif ($this->option('year')) {
                    $parameters['year'] = $this->option('year');
                }
            } elseif ($this->option('date')) {
                $parameters['date'] = $this->option('date');
            } elseif ($this->option('month')) {
                $parameters['month'] = $this->option('month');
            } elseif ($this->option('year')) {
                $parameters['year'] = $this->option('year');
            } else {
                // Default to today's date
                $parameters['date'] = now()->format('Y-m-d');
            }
            if ($this->option('limit-matches')) {
                $parameters['limit_matches'] = (int) $this->option('limit-matches');
            }
            if ($this->option('skip-points')) {
                $parameters['skip_points'] = true;
            }
        }

        if ($type === 'series') {
            if ($this->option('period')) {
                $parameters['period'] = $this->option('period');
                $parameters['direction'] = $this->option('direction');
            }
            if ($this->option('limit-seasons')) {
                $parameters['limit_seasons'] = (int) $this->option('limit-seasons');
            }
            if ($this->option('limit-series')) {
                $parameters['limit_series'] = (int) $this->option('limit-series');
            }
        }

        if ($type === 'series_matches') {
            if ($this->option('period')) {
                $parameters['period'] = $this->option('period');
                $parameters['direction'] = $this->option('direction');
            }
            if ($this->option('limit-seasons')) {
                $parameters['limit_seasons'] = (int) $this->option('limit-seasons');
            }
            if ($this->option('limit-series')) {
                $parameters['limit_series'] = (int) $this->option('limit-series');
            }
            if ($this->option('limit-matches')) {
                $parameters['limit_matches'] = (int) $this->option('limit-matches');
            }
        }

        if ($this->option('queue')) {
            // If both genders requested, queue two separate jobs
            if ($type === 'rankings' && in_array($parameters['gender'], ['both', 'male+female', 'all'])) {
                $this->queueJob($type, array_merge($parameters, ['gender' => 'm']));
                $this->queueJob($type, array_merge($parameters, ['gender' => 'k']));
                return self::SUCCESS;
            }

            return $this->queueJob($type, $parameters);
        }

        // If both genders requested, run in parallel (same as scraper:start)
        if ($type === 'rankings' && in_array($parameters['gender'], ['both', 'male+female', 'all'])) {
            return $this->runRankingsParallel($parameters);
        }

        return $this->runSynchronously($type, $parameters);
    }

    /**
     * Queue the scraper job
     */
    protected function queueJob(string $type, array $parameters): int
    {
        $jobClass = match ($type) {
            'rankings' => \App\Jobs\Scraper\ScrapeRankingsJob::class,
            'players' => \App\Jobs\Scraper\ScrapePlayersJob::class,
            'transitions' => \App\Jobs\Scraper\ScrapeTransitionsJob::class,
            'series' => \App\Jobs\Scraper\ScrapeSeriesJob::class,
            'live_center' => \App\Jobs\Scraper\ScrapeLiveCenterJob::class,
            default => null,
        };

        if (!$jobClass || !class_exists($jobClass)) {
            $this->warn("Job class not implemented yet for type: {$type}");

            // Create a pending run for tracking
            $run = ScraperRun::create([
                'type' => $type,
                'status' => ScraperRun::STATUS_PENDING,
                'parameters' => $parameters,
            ]);

            $this->info("Created pending run #{$run->id} (job not yet implemented)");
            return self::SUCCESS;
        }

        $jobClass::dispatch($parameters);
        $this->info("Job queued successfully for type: {$type}");

        return self::SUCCESS;
    }

    /**
     * Run the scraper synchronously
     */
    protected function runSynchronously(string $type, array $parameters): int
    {
        $this->info("Starting {$type} scraper...");
        $this->newLine();

        $scraperClass = match ($type) {
            'rankings' => \App\Services\Scraper\RankingsScraper::class,
            'players' => \App\Services\Scraper\PlayerListScraper::class,
            'transitions' => \App\Services\Scraper\TransitionsScraper::class,
            'series' => \App\Services\Scraper\SeriesScraper::class,
            'series_matches' => \App\Services\Scraper\SeriesMatchScraper::class,
            'live_center' => \App\Services\Scraper\LiveCenterDetailsScraper::class,
            default => null,
        };

        if (!$scraperClass || !class_exists($scraperClass)) {
            $this->warn("Scraper service not implemented yet for type: {$type}");

            // Create a pending run for tracking
            $run = ScraperRun::create([
                'type' => $type,
                'status' => ScraperRun::STATUS_PENDING,
                'parameters' => $parameters,
            ]);

            $this->info("Created pending run #{$run->id} (scraper not yet implemented)");
            return self::SUCCESS;
        }

        try {
            $scraper = app($scraperClass);
            $scraper->setConsoleOutput($this);
            $run = $scraper->scrape($parameters);

            $this->newLine();
            $this->info("Scrape completed!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Run ID', $run->id],
                    ['Status', $run->status],
                    ['Items Scraped', $run->items_scraped],
                    ['Items Failed', $run->items_failed],
                    ['Duration', $run->duration],
                ]
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Scrape failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Run male and female rankings in parallel as separate OS processes (mirrors scraper:start behaviour)
     */
    protected function runRankingsParallel(array $parameters): int
    {
        $year  = $parameters['year']  ?? date('Y');
        $month = $parameters['month'] ?? date('m');

        $buildArgs = function (string $gender) use ($year, $month, $parameters): array {
            $args = [
                PHP_BINARY,
                base_path('artisan'),
                'scraper:run',
                'rankings',
                "--year={$year}",
                "--month={$month}",
                "--gender={$gender}",
            ];

            if (!empty($parameters['limit_players'])) {
                $args[] = '--limit-players=' . $parameters['limit_players'];
            }

            return $args;
        };

        $startId  = \App\Models\Scraper\ScraperRun::max('id') ?? 0;

        $mProcess = new \Symfony\Component\Process\Process($buildArgs('m'));
        $fProcess = new \Symfony\Component\Process\Process($buildArgs('k'));
        $mProcess->setTimeout(3600);
        $fProcess->setTimeout(3600);

        $this->line("  <fg=cyan>Starting Male rankings process...</>");
        $mProcess->start();
        $this->line("  <fg=cyan>Starting Female rankings process...</>");
        $fProcess->start();
        $this->newLine();

        while (!$mProcess->isTerminated() || !$fProcess->isTerminated()) {
            foreach (explode("\n", $mProcess->getIncrementalOutput() . $mProcess->getIncrementalErrorOutput()) as $line) {
                if (trim($line) !== '') $this->line("  <fg=blue>[M]</> " . trim($line));
            }
            foreach (explode("\n", $fProcess->getIncrementalOutput() . $fProcess->getIncrementalErrorOutput()) as $line) {
                if (trim($line) !== '') $this->line("  <fg=magenta>[F]</> " . trim($line));
            }
            usleep(500000);
        }

        $errors = [];
        if (!$mProcess->isSuccessful()) {
            $errors[] = "Male rankings failed (exit {$mProcess->getExitCode()}): " . $mProcess->getErrorOutput();
        }
        if (!$fProcess->isSuccessful()) {
            $errors[] = "Female rankings failed (exit {$fProcess->getExitCode()}): " . $fProcess->getErrorOutput();
        }

        if (!empty($errors)) {
            $this->error(implode("\n", $errors));
            return self::FAILURE;
        }

        $runs = \App\Models\Scraper\ScraperRun::where('id', '>', $startId)
            ->where('type', \App\Models\Scraper\ScraperRun::TYPE_RANKINGS)
            ->get();

        $this->newLine();
        foreach ($runs as $run) {
            $gender = ($run->parameters['gender'] ?? '') === 'k' ? 'Female' : 'Male';
            $this->line("  <fg=green>✓</> {$gender} Run #{$run->id}: {$run->items_scraped} items scraped");
        }

        return self::SUCCESS;
    }
}
