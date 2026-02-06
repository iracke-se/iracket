<?php

namespace App\Console\Commands;

use App\Jobs\Scraper\RunScraperJob;
use App\Models\Scraper\ScraperRun;
use Illuminate\Console\Command;

class ScraperQueueCommand extends Command
{
    protected $signature = 'scraper:queue
                            {type : The scraper type (start, smart-scrape, rankings, players, etc.)}
                            {month? : The month to scrape (e.g., 2024-12)}
                            {--all : Scrape all data without month filter}
                            {--gender= : Gender for rankings (male/female)}
                            {--limit-periods= : Limit periods for testing}
                            {--limit-divisions= : Limit divisions for testing}
                            {--skip-sync : Skip automatic sync}
                            {--skip-bubbler : Skip Bubbler recalculation}
                            {--no-backup : Skip automatic backup}
                            {--only-existing-dates : Scrape only dates that already have matches in database (live-center only)}
                            {--date= : Date for live_center scraper (YYYY-MM-DD)}
                            {--date-from= : Start date for live_center date range (YYYY-MM-DD)}
                            {--date-to= : End date for live_center date range (YYYY-MM-DD)}
                            {--limit= : Limit number of matches per date for live_center scraper}';

    protected $description = 'Dispatch a scraper job to the queue (production-ready)';

    public function handle(): int
    {
        $type = $this->argument('type');
        $month = $this->argument('month');

        // Check if another scraper is already running
        $runningScrapers = ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->get();

        if ($runningScrapers->isNotEmpty()) {
            $this->newLine();
            $this->error("╔════════════════════════════════════════════════════════╗");
            $this->error("║       SCRAPER ALREADY RUNNING                          ║");
            $this->error("╚════════════════════════════════════════════════════════╝");
            $this->newLine();

            $this->table(
                ['ID', 'Type', 'Status', 'Started', 'Items'],
                $runningScrapers->map(fn($run) => [
                    $run->id,
                    $run->type,
                    $run->status,
                    $run->started_at?->diffForHumans(),
                    $run->items_scraped,
                ])->toArray()
            );

            $this->newLine();
            $this->warn("Wait for the running scraper to finish or cancel it first.");
            $this->line("To view running scraper: <fg=cyan>php artisan scraper:show {$runningScrapers->first()->id}</>");
            $this->newLine();

            return self::FAILURE;
        }

        // Determine command to run
        $command = match ($type) {
            'start' => 'scraper:start',
            'smart', 'smart-scrape' => 'scraper:smart-scrape',
            'rankings', 'players', 'transitions', 'series', 'series-matches', 'live-center' => 'scraper:run',
            default => null,
        };

        if (!$command) {
            $this->error("Invalid scraper type: {$type}");
            $this->line("Valid types: start, smart-scrape, rankings, players, transitions, series, series-matches, live-center");
            return self::FAILURE;
        }

        // Build parameters
        $parameters = [];
        $options = [];

        if ($command === 'scraper:start' || $command === 'scraper:smart-scrape') {
            if (!$month && !$this->option('all')) {
                $this->error("Month is required (e.g., 2024-12) or use --all flag");
                return self::FAILURE;
            }

            if ($month) {
                $parameters['month'] = $month;
            }
        } elseif ($command === 'scraper:run') {
            $parameters['type'] = $type;

            if ($this->option('gender')) {
                $options['--gender'] = $this->option('gender');
            }

            // Live-center specific options
            if ($type === 'live-center') {
                if ($this->option('only-existing-dates')) {
                    $options['--use-existing-dates'] = true;
                }
                if ($this->option('date')) {
                    $options['--date'] = $this->option('date');
                }
                if ($this->option('date-from')) {
                    $options['--date-from'] = $this->option('date-from');
                }
                if ($this->option('date-to')) {
                    $options['--date-to'] = $this->option('date-to');
                }
                if ($this->option('limit')) {
                    $options['--limit'] = $this->option('limit');
                }
            }
        }

        // Add common options
        if ($this->option('all')) {
            $options['--all'] = true;
        }
        if ($this->option('limit-periods')) {
            $options['--limit-periods'] = $this->option('limit-periods');
        }
        if ($this->option('limit-divisions')) {
            $options['--limit-divisions'] = $this->option('limit-divisions');
        }
        if ($this->option('skip-sync')) {
            $options['--skip-sync'] = true;
        }
        if ($this->option('skip-bubbler')) {
            $options['--skip-bubbler'] = true;
        }
        if ($this->option('no-backup')) {
            $options['--no-backup'] = true;
        }

        // Dispatch job to queue
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║          DISPATCHING SCRAPER TO QUEUE                  ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Command', $command],
                ['Type', $type],
                ['Month', $month ?? ($this->option('all') ? 'All data' : 'N/A')],
                ['Options', json_encode($options)],
                ['Queue', config('queue.default')],
            ]
        );

        $this->newLine();

        try {
            RunScraperJob::dispatch($command, $parameters, $options);

            $this->info("✓ Scraper job dispatched to queue successfully!");
            $this->newLine();

            $this->line("Monitor queue with:");
            $this->line("  <fg=cyan>php artisan queue:monitor</>");
            $this->line("  <fg=cyan>php artisan queue:work --verbose</>");
            $this->newLine();

            $this->line("View logs:");
            $this->line("  <fg=cyan>tail -f storage/logs/laravel.log</>");
            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to dispatch job: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
