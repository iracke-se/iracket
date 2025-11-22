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
