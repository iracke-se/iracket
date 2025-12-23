<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use Illuminate\Console\Command;

class ScraperCleanupCommand extends Command
{
    protected $signature = 'scraper:cleanup
                            {--force : Force cleanup without confirmation}
                            {--older-than=30 : Clean up scrapers running for more than X minutes (default: 30)}';

    protected $description = 'Clean up stuck or orphaned scraper runs';

    public function handle(): int
    {
        $force = $this->option('force');
        $olderThanMinutes = (int) $this->option('older-than');

        // Find running scrapers that have been running for too long
        $stuckRuns = ScraperRun::where('status', ScraperRun::STATUS_RUNNING)
            ->where('started_at', '<', now()->subMinutes($olderThanMinutes))
            ->get();

        if ($stuckRuns->isEmpty()) {
            $this->info("✓ No stuck scrapers found.");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn("Found {$stuckRuns->count()} potentially stuck scraper(s):");
        $this->newLine();

        foreach ($stuckRuns as $run) {
            $duration = $run->started_at->diffForHumans();
            $this->line("  • Run #{$run->id} - {$run->type}");
            $this->line("    Started: {$duration}");
            $this->line("    Items scraped: {$run->items_scraped}, Failed: {$run->items_failed}");
            $this->newLine();
        }

        if (!$force && !$this->confirm("Mark these scrapers as failed?", true)) {
            $this->info("Cleanup cancelled.");
            return self::SUCCESS;
        }

        foreach ($stuckRuns as $run) {
            $run->markAsFailed('Cleaned up - marked as stuck/orphaned');
            $this->info("✓ Marked run #{$run->id} as failed");
        }

        $this->newLine();
        $this->info("✓ Cleanup completed successfully!");

        return self::SUCCESS;
    }
}
