<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupScraperLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:cleanup-logs
                            {--days=7 : Archive logs older than this many days}
                            {--delete-archived=30 : Delete archived logs older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive old scraper logs and clean up archived logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $scraperLogPath = storage_path('logs/scraper');
        $archivePath = storage_path('logs/scraper/archive');

        // Ensure directories exist
        if (!File::isDirectory($scraperLogPath)) {
            File::makeDirectory($scraperLogPath, 0755, true);
            $this->info('Created scraper log directory');
        }

        if (!File::isDirectory($archivePath)) {
            File::makeDirectory($archivePath, 0755, true);
            $this->info('Created archive directory');
        }

        $daysToKeep = (int) $this->option('days');
        $daysToDeleteArchived = (int) $this->option('delete-archived');

        // Archive old logs
        $archivedCount = $this->archiveOldLogs($scraperLogPath, $archivePath, $daysToKeep);

        // Delete very old archived logs
        $deletedCount = $this->deleteOldArchivedLogs($archivePath, $daysToDeleteArchived);

        $this->info("Scraper log cleanup completed:");
        $this->info("  - Archived: {$archivedCount} log files");
        $this->info("  - Deleted from archive: {$deletedCount} old files");

        return Command::SUCCESS;
    }

    /**
     * Archive logs older than specified days
     */
    private function archiveOldLogs(string $logPath, string $archivePath, int $days): int
    {
        $cutoffDate = now()->subDays($days);
        $archivedCount = 0;

        $files = File::glob($logPath . '/scraper-*.log');

        foreach ($files as $file) {
            $filename = basename($file);

            // Extract date from filename (format: scraper-YYYY-MM-DD.log)
            if (preg_match('/scraper-(\d{4}-\d{2}-\d{2})\.log/', $filename, $matches)) {
                $fileDate = \Carbon\Carbon::parse($matches[1]);

                if ($fileDate->lt($cutoffDate)) {
                    // Compress and move to archive
                    $archiveFilename = $filename . '.gz';
                    $archiveFullPath = $archivePath . '/' . $archiveFilename;

                    // Compress the file
                    $content = File::get($file);
                    $compressed = gzencode($content, 9);

                    File::put($archiveFullPath, $compressed);
                    File::delete($file);

                    $archivedCount++;
                    $this->line("  Archived: {$filename}");
                }
            }
        }

        return $archivedCount;
    }

    /**
     * Delete archived logs older than specified days
     */
    private function deleteOldArchivedLogs(string $archivePath, int $days): int
    {
        $cutoffDate = now()->subDays($days);
        $deletedCount = 0;

        $files = File::glob($archivePath . '/scraper-*.log.gz');

        foreach ($files as $file) {
            $filename = basename($file);

            // Extract date from filename
            if (preg_match('/scraper-(\d{4}-\d{2}-\d{2})\.log\.gz/', $filename, $matches)) {
                $fileDate = \Carbon\Carbon::parse($matches[1]);

                if ($fileDate->lt($cutoffDate)) {
                    File::delete($file);
                    $deletedCount++;
                    $this->line("  Deleted archived: {$filename}");
                }
            }
        }

        return $deletedCount;
    }
}
