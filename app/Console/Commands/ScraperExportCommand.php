<?php

namespace App\Console\Commands;

use App\Services\Scraper\ScraperExporter;
use Illuminate\Console\Command;

class ScraperExportCommand extends Command
{
    protected $signature = 'scraper:export
        {--limit-rankings=0 : Limit rankings periods}
        {--limit-players=0 : Limit player periods}
        {--limit-clubs=0 : Limit clubs}
        {--limit-transitions=0 : Limit transition periods}
        {--limit-divisions=0 : Limit live center divisions}
        {--limit-periods=0 : Limit live center periods}
        {--limit-seasons=0 : Limit series seasons}
        {--limit-series=0 : Limit series per season}';

    protected $description = 'Run all scrapers and export results to JSON';

    public function handle(): int
    {
        $this->info('Starting full scraper export...');

        $exporter = new ScraperExporter();

        $parameters = [
            'rankings' => array_filter([
                'limit_periods' => (int) $this->option('limit-rankings'),
            ]),
            'players' => array_filter([
                'limit_periods' => (int) $this->option('limit-players'),
                'limit_clubs' => (int) $this->option('limit-clubs'),
            ]),
            'transitions' => array_filter([
                'limit_periods' => (int) $this->option('limit-transitions'),
            ]),
            'live_center' => array_filter([
                'limit_divisions' => (int) $this->option('limit-divisions'),
                'limit_periods' => (int) $this->option('limit-periods'),
            ]),
            'series' => array_filter([
                'limit_seasons' => (int) $this->option('limit-seasons'),
                'limit_series' => (int) $this->option('limit-series'),
            ]),
        ];

        try {
            $filepath = $exporter->runAllAndExport($parameters);

            $this->info('Export completed successfully!');
            $this->info("File saved to: {$filepath}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Export failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
