<?php

namespace App\Console\Commands;

use App\Jobs\Scraper\ScrapeDistrictsJob;
use App\Services\Scraper\DistrictScraper;
use App\Services\Scraper\DistrictSyncService;
use Illuminate\Console\Command;

class ScrapeDistrictsCommand extends Command
{
    protected $signature = 'scraper:districts
                            {--gender=both : Gender to scrape (m=male, k=female, both=default)}
                            {--limit-districts= : Limit number of districts scraped (for testing)}
                            {--limit-players= : Limit players per district (for testing)}
                            {--sync : Automatically sync district data to users after scraping}
                            {--sync-only : Only sync existing unsynced records (skip scraping)}
                            {--queue : Dispatch as a queue job instead of running synchronously}';

    protected $description = 'Scrape player–district associations from profixio.com and sync to users';

    public function handle(): int
    {
        if ($this->option('sync-only')) {
            return $this->runSync();
        }

        $parameters = [
            'gender' => $this->option('gender'),
        ];

        if ($this->option('limit-districts')) {
            $parameters['limit_districts'] = (int) $this->option('limit-districts');
        }

        if ($this->option('limit-players')) {
            $parameters['limit_players'] = (int) $this->option('limit-players');
        }

        if ($this->option('queue')) {
            ScrapeDistrictsJob::dispatch($parameters);
            $this->info('District scraper job queued successfully.');
            return self::SUCCESS;
        }

        $this->info('Starting district scraper...');
        $this->newLine();

        try {
            $scraper = app(DistrictScraper::class);
            $scraper->setConsoleOutput($this);
            $run = $scraper->scrape($parameters);

            $this->newLine();
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Run ID',          $run->id],
                    ['Status',          $run->status],
                    ['Players Scraped', $run->items_scraped],
                    ['Failed',          $run->items_failed],
                    ['Duration',        $run->duration],
                ]
            );

            if ($this->option('sync')) {
                $this->newLine();
                return $this->runSync($run->id);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('District scraper failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function runSync(?int $runId = null): int
    {
        $this->info('Syncing district data to users...');

        $stats = app(DistrictSyncService::class)->sync($runId);

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Users matched to district', $stats['matched']],
                ['Players not yet registered', $stats['unmatched']],
                ['Errors',                     $stats['errors']],
            ]
        );

        return self::SUCCESS;
    }
}
