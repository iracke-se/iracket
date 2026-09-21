<?php

namespace App\Console\Commands;

use App\Services\Scraper\CloudflareClearanceService;
use Illuminate\Console\Command;

class ScraperCfClearanceCommand extends Command
{
    protected $signature = 'scraper:cf-clearance
                            {--refresh : Discard the cached clearance and obtain a new one}
                            {--forget : Discard the cached clearance without obtaining a new one}';

    protected $description = 'Show, refresh or forget the Cloudflare clearance the profixio scrapers use';

    public function handle(CloudflareClearanceService $clearance): int
    {
        if ($this->option('forget')) {
            $clearance->forget();
            $this->info('Cloudflare clearance forgotten.');

            return self::SUCCESS;
        }

        if (!$clearance->enabled()) {
            $this->warn('Cloudflare clearance is disabled (SCRAPER_CF_CLEARANCE=false).');

            return self::SUCCESS;
        }

        if (!$this->option('refresh') && ($cached = $clearance->cached())) {
            $this->line('<fg=green>Cached clearance</> (obtained ' . $cached['obtained_at'] . ')');
            $this->describe($cached);

            return self::SUCCESS;
        }

        $this->line('Obtaining Cloudflare clearance on DISPLAY=' . config('scraper.browser.display') . ' ...');

        try {
            $result = $clearance->get(refresh: true);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Clearance obtained in ' . ($result['cleared_in'] ?? '?') . 's');
        $this->describe($result);

        return self::SUCCESS;
    }

    protected function describe(array $clearance): void
    {
        $this->line('  User-agent: ' . $clearance['user_agent']);
        foreach ($clearance['cookies'] as $cookie) {
            $expires = ($cookie['expires'] ?? -1) > 0
                ? date('Y-m-d H:i', (int) $cookie['expires'])
                : 'session';
            $this->line("  Cookie: {$cookie['name']} (expires {$expires})");
        }
    }
}
