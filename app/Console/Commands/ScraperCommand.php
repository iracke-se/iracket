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
                            {type : The type of scrape (rankings, players, transitions, series, live_center)}
                            {--gender=male : Gender for rankings (male/female)}
                            {--period= : Period filter (e.g., 2024.01.01)}
                            {--direction=gte : Direction for period filter (gte/lte)}
                            {--limit-periods= : Limit number of periods to scrape (for testing)}
                            {--limit-clubs= : Limit number of clubs to scrape (for testing, players only)}
                            {--limit-divisions= : Limit number of divisions to scrape (for testing, rankings/live_center)}
                            {--limit-seasons= : Limit number of seasons to scrape (for testing, series only)}
                            {--limit-series= : Limit number of series per season to scrape (for testing, series only)}
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
        $validTypes = ['rankings', 'players', 'transitions', 'series', 'live_center'];

        if (!in_array($type, $validTypes)) {
            $this->error("Invalid type. Must be one of: " . implode(', ', $validTypes));
            return self::FAILURE;
        }

        $parameters = [];

        // Build parameters based on type
        if ($type === 'rankings') {
            $parameters['gender'] = $this->option('gender');
            if ($this->option('period')) {
                $parameters['period'] = $this->option('period');
                $parameters['direction'] = $this->option('direction');
            }
            if ($this->option('limit-periods')) {
                $parameters['limit_periods'] = (int) $this->option('limit-periods');
            }
            if ($this->option('limit-divisions')) {
                $parameters['limit_divisions'] = (int) $this->option('limit-divisions');
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
            if ($this->option('period')) {
                $parameters['period'] = $this->option('period');
                $parameters['direction'] = $this->option('direction');
            }
            if ($this->option('limit-periods')) {
                $parameters['limit_periods'] = (int) $this->option('limit-periods');
            }
            if ($this->option('limit-divisions')) {
                $parameters['limit_divisions'] = (int) $this->option('limit-divisions');
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

        if ($this->option('queue')) {
            return $this->queueJob($type, $parameters);
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
            'live_center' => \App\Services\Scraper\LiveCenterScraper::class,
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
}
