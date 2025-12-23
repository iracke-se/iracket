<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use App\Services\Scraper\MatchSyncService;
use App\Services\Scraper\SyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

class ScraperStartCommand extends Command
{
    protected $signature = 'scraper:start
                            {month : The month to scrape (e.g., 2025-09, 2025-10)}
                            {--all : Scrape all data without month filter}
                            {--no-backup : Skip automatic backup before starting}';

    protected $description = 'Scrape and sync all data for a specific month with visual progress';

    protected array $stats = [];
    protected int $totalSteps = 9; // Added backup step
    protected int $currentStep = 0;
    protected ?string $backupFile = null;
    protected array $executionLog = [];
    protected ?string $failedStep = null;
    protected ?\Exception $failureException = null;
    protected ?int $latestRunId = null;
    protected ?ScraperRun $parentRun = null;

    public function handle(SyncService $syncService, MatchSyncService $matchSyncService): int
    {
        $month = $this->argument('month');
        $scrapeAll = $this->option('all');
        $skipBackup = $this->option('no-backup');

        if (!$scrapeAll && !$this->validateMonth($month)) {
            $this->error("Invalid month format. Use YYYY-MM format (e.g., 2025-09)");
            return self::FAILURE;
        }

        // Check if another scraper is already running
        $runningScrapers = ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->get();

        if ($runningScrapers->isNotEmpty()) {
            $this->newLine();
            $this->error("╔════════════════════════════════════════════════════════╗");
            $this->error("║       SCRAPER ALREADY RUNNING                          ║");
            $this->error("╚════════════════════════════════════════════════════════╝");
            $this->newLine();

            $this->warn("Cannot start a new scraper while another is running.");
            $this->newLine();

            $this->info("Currently running scrapers:");
            foreach ($runningScrapers as $run) {
                $duration = $run->started_at ? $run->started_at->diffForHumans() : 'just now';
                $this->line("  • Run #{$run->id} - {$run->type} (started {$duration})");
                $this->line("    Items scraped: {$run->items_scraped}, Failed: {$run->items_failed}");
            }

            $this->newLine();
            $this->info("💡 To monitor progress:");
            $this->line("  • CLI: Check the running terminal");
            $this->line("  • Web: https://iracket.ddev.site/admin/scraper");
            $this->newLine();

            if ($this->confirm("Do you want to wait for the current scraper to finish?", false)) {
                $this->info("Waiting for scraper to complete...");
                $this->newLine();

                // Wait for all running scrapers to finish
                while (ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->exists()) {
                    sleep(2);
                    $this->output->write('.');
                }

                $this->newLine();
                $this->newLine();
                $this->info("✓ All scrapers have finished. Starting new scraper...");
                $this->newLine();
            } else {
                $this->warn("Scraper start cancelled.");
                return self::FAILURE;
            }
        }

        $this->displayHeader($month, $scrapeAll);

        // Create parent scraper run to track the entire process
        $this->parentRun = ScraperRun::create([
            'type' => ScraperRun::TYPE_FULL_SCRAPE,
            'status' => ScraperRun::STATUS_RUNNING,
            'parameters' => [
                'month' => $scrapeAll ? 'all' : $month,
                'all' => $scrapeAll,
                'no_backup' => $skipBackup,
            ],
            'started_at' => now(),
        ]);

        $this->line("  <fg=cyan>Scraper Run ID: #{$this->parentRun->id}</>");
        $this->newLine();

        try {
            // Step 0: Create backup (unless skipped)
            if (!$skipBackup) {
                $this->runStep('Creating Database Backup', function () use ($month) {
                    return $this->createBackup($month);
                });
            } else {
                $this->warn("⚠️  Backup skipped - no rollback available if scrape fails");
                $this->newLine();
            }

            // Step 1: Scrape Players
            $result = $this->runStep('Scraping Players', function () use ($month, $scrapeAll) {
                return $this->scrapePlayers($month, $scrapeAll);
            });
            $this->latestRunId = $result['run_id'] ?? null;

            // Step 2: Sync Players
            $this->runStep('Syncing Players → Users & Clubs', function () use ($syncService) {
                return $this->syncData($syncService, 'players');
            });

            // Step 3: Scrape Rankings (Male)
            $result = $this->runStep('Scraping Rankings (Male)', function () use ($month, $scrapeAll) {
                return $this->scrapeRankings('male', $month, $scrapeAll);
            });
            $this->latestRunId = $result['run_id'] ?? $this->latestRunId;

            // Step 4: Scrape Rankings (Female)
            $result = $this->runStep('Scraping Rankings (Female)', function () use ($month, $scrapeAll) {
                return $this->scrapeRankings('female', $month, $scrapeAll);
            });
            $this->latestRunId = $result['run_id'] ?? $this->latestRunId;

            // Step 5: Sync Rankings
            $this->runStep('Syncing All Rankings', function () use ($syncService) {
                return $this->syncData($syncService, 'rankings');
            });

            // Step 6: Scrape Matches (LiveCenter)
            $result = $this->runStep('Scraping Matches (LiveCenter)', function () use ($month, $scrapeAll) {
                return $this->scrapeMatches($month, $scrapeAll);
            });
            $this->latestRunId = $result['run_id'] ?? $this->latestRunId;

            // Step 7: Sync Matches
            $this->runStep('Syncing Matches', function () use ($matchSyncService) {
                return $this->syncMatches($matchSyncService);
            });

            // Step 8: Verify Data
            $this->runStep('Verifying Data Integrity', function () {
                return $this->verifyData();
            });

            // Display final summary
            $this->displaySummary();

            // Mark parent run as completed
            if ($this->parentRun) {
                $this->parentRun->markAsCompleted();
                $this->parentRun->log('info', 'Full scrape completed successfully');
            }

            // Cleanup backup on success
            if ($this->backupFile && !$skipBackup) {
                $this->cleanupBackup();
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->failureException = $e;

            // Mark parent run as failed
            if ($this->parentRun) {
                $this->parentRun->markAsFailed($e->getMessage());
            }

            $this->handleFailure();
            return self::FAILURE;
        }
    }

    protected function createBackup(string $month): array
    {
        $timestamp = now()->format('YmdHis');
        $this->backupFile = "backup-scraper-{$month}-{$timestamp}.sql.gz";

        $this->line("  📦 Creating backup: <fg=yellow>{$this->backupFile}</>");

        $result = Process::run("ddev export-db --file={$this->backupFile}");

        if (!$result->successful()) {
            throw new \Exception("Failed to create backup: " . $result->errorOutput());
        }

        $this->line("  ✓ Backup created successfully");

        return ['backup_file' => $this->backupFile];
    }

    protected function cleanupBackup(): void
    {
        if (file_exists($this->backupFile)) {
            $this->newLine();
            if ($this->confirm("Scrape completed successfully. Delete backup file {$this->backupFile}?", true)) {
                unlink($this->backupFile);
                $this->info("✓ Backup file deleted");
            } else {
                $this->info("✓ Backup file kept: {$this->backupFile}");
            }
        }
    }

    protected function handleFailure(): void
    {
        $this->newLine(2);
        $this->error("╔════════════════════════════════════════════════════════╗");
        $this->error("║                  ❌ SCRAPER FAILED                      ║");
        $this->error("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Display failure context
        $this->displayFailureDetails();

        // Display execution log
        $this->displayExecutionLog();

        // Offer rollback if backup exists
        if ($this->backupFile && file_exists($this->backupFile)) {
            $this->offerRollback();
        } else {
            $this->newLine();
            $this->warn("⚠️  No backup available - database changes cannot be automatically reverted");
            $this->warn("    You may need to manually restore from backup-clean.sql.gz");
        }
    }

    protected function displayFailureDetails(): void
    {
        $this->error("Failed Step: {$this->failedStep}");
        $this->error("Step Number: {$this->currentStep}/{$this->totalSteps}");
        $this->newLine();

        $this->error("Error Message:");
        $this->line("  " . $this->failureException->getMessage());
        $this->newLine();

        $this->error("Error Type:");
        $this->line("  " . get_class($this->failureException));
        $this->newLine();

        $this->error("Error Location:");
        $this->line("  File: " . $this->failureException->getFile());
        $this->line("  Line: " . $this->failureException->getLine());
        $this->newLine();

        // Provide context-specific suggestions
        $this->displayErrorSuggestions();

        if ($this->option('verbose') || $this->confirm("Show full stack trace?", false)) {
            $this->newLine();
            $this->warn("Full Stack Trace:");
            $this->line($this->failureException->getTraceAsString());
        }
    }

    protected function displayErrorSuggestions(): void
    {
        $message = strtolower($this->failureException->getMessage());

        $this->warn("💡 Possible Solutions:");

        if (str_contains($message, 'timeout') || str_contains($message, 'connection')) {
            $this->line("  • Check network connectivity");
            $this->line("  • Verify profixio.com is accessible");
            $this->line("  • Try again in a few minutes");
        } elseif (str_contains($message, 'chromium') || str_contains($message, 'browser')) {
            $this->line("  • Ensure Chromium is installed: ddev exec which chromium");
            $this->line("  • Check Browsershot configuration");
            $this->line("  • Try: ddev restart");
        } elseif (str_contains($message, 'database') || str_contains($message, 'sql')) {
            $this->line("  • Check database connection");
            $this->line("  • Verify database schema is up to date: php artisan migrate");
            $this->line("  • Check for foreign key constraint violations");
        } elseif (str_contains($message, 'duplicate') || str_contains($message, 'unique')) {
            $this->line("  • Data may have already been scraped");
            $this->line("  • Check scraped_* tables for is_synced = 0");
            $this->line("  • Consider cleaning scraped data before retrying");
        } elseif (str_contains($message, 'memory') || str_contains($message, 'allowed memory size')) {
            $this->line("  • Increase PHP memory limit in php.ini");
            $this->line("  • Try scraping in smaller batches");
            $this->line("  • Check for memory leaks in scraper code");
        } else {
            $this->line("  • Review error details above");
            $this->line("  • Check Laravel logs: storage/logs/laravel.log");
            $this->line("  • Check scraper logs: storage/scraper_logs/");
            $this->line("  • Try running with --verbose for more details");
        }

        $this->newLine();
    }

    protected function displayExecutionLog(): void
    {
        if (empty($this->executionLog)) {
            return;
        }

        $this->newLine();
        $this->info("Execution Log:");
        $this->line(str_repeat('─', 60));

        foreach ($this->executionLog as $log) {
            $status = $log['success'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $duration = number_format($log['duration'], 2);

            $this->line(sprintf(
                "  %s Step %d: %s (%ss)",
                $status,
                $log['step'],
                $log['description'],
                $duration
            ));

            if (isset($log['details']) && !empty($log['details'])) {
                foreach ($log['details'] as $key => $value) {
                    $this->line("      • {$key}: {$value}");
                }
            }
        }

        $this->line(str_repeat('─', 60));
        $this->newLine();
    }

    protected function offerRollback(): void
    {
        $this->newLine();
        $this->warn("╔════════════════════════════════════════════════════════╗");
        $this->warn("║              ROLLBACK AVAILABLE                        ║");
        $this->warn("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->info("A backup was created before scraping started:");
        $this->line("  📦 Backup file: <fg=yellow>{$this->backupFile}</>");
        $this->newLine();

        if ($this->confirm("Do you want to rollback the database to before the scrape?", true)) {
            $this->performRollback();
        } else {
            $this->info("Rollback skipped - you can manually restore later with:");
            $this->line("  ddev import-db --file={$this->backupFile}");
        }
    }

    protected function performRollback(): void
    {
        $this->newLine();
        $this->warn("🔄 Rolling back database...");

        try {
            $result = Process::run("ddev import-db --file={$this->backupFile}");

            if (!$result->successful()) {
                throw new \Exception("Rollback failed: " . $result->errorOutput());
            }

            $this->newLine();
            $this->info("✅ Database successfully rolled back to pre-scrape state");
            $this->newLine();

            // Verify rollback
            $userCount = DB::table('users')->count();
            $this->line("  Current user count: {$userCount}");

            if ($this->confirm("Keep backup file {$this->backupFile}?", true)) {
                $this->info("✓ Backup file kept for future reference");
            } else {
                unlink($this->backupFile);
                $this->info("✓ Backup file deleted");
            }

        } catch (\Exception $e) {
            $this->newLine();
            $this->error("❌ Rollback failed: " . $e->getMessage());
            $this->warn("You'll need to manually restore from: {$this->backupFile}");
            $this->warn("Command: ddev import-db --file={$this->backupFile}");
        }
    }

    protected function validateMonth(string $month): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}$/', $month);
    }

    protected function displayHeader(string $month, bool $scrapeAll): void
    {
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║           iRACKET DATA SCRAPER & SYNC TOOL             ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        if ($scrapeAll) {
            $this->line("  📅 Scraping: <fg=yellow>ALL AVAILABLE DATA</>");
        } else {
            $this->line("  📅 Target Month: <fg=yellow>{$month}</>");
        }

        $this->line("  📊 Total Steps: <fg=cyan>{$this->totalSteps}</>");
        $this->line("  🕐 Started: <fg=gray>" . now()->format('Y-m-d H:i:s') . "</>");
        $this->newLine();
    }

    protected function runStep(string $description, callable $callback): mixed
    {
        $this->currentStep++;
        $this->failedStep = $description;

        // Update parent run with current step
        if ($this->parentRun) {
            $this->parentRun->updateCurrentStep("Step {$this->currentStep}/{$this->totalSteps}: {$description}");
            $this->parentRun->log('info', "Starting: {$description}");
        }

        $this->line(str_repeat('─', 60));
        $this->info("Step {$this->currentStep}/{$this->totalSteps}: {$description}");
        $this->line(str_repeat('─', 60));
        $this->newLine();

        $startTime = microtime(true);
        $logEntry = [
            'step' => $this->currentStep,
            'description' => $description,
            'started_at' => now()->toDateTimeString(),
        ];

        try {
            $result = $callback();

            $duration = microtime(true) - $startTime;

            $logEntry['success'] = true;
            $logEntry['duration'] = $duration;
            $logEntry['completed_at'] = now()->toDateTimeString();

            if (is_array($result) && !empty($result)) {
                $logEntry['details'] = $this->extractRelevantDetails($result);
            }

            $this->executionLog[] = $logEntry;

            // Update parent run with step completion data
            if ($this->parentRun) {
                $this->parentRun->updateStepData("step_{$this->currentStep}", [
                    'description' => $description,
                    'duration' => $duration,
                    'success' => true,
                    'details' => $logEntry['details'] ?? [],
                    'completed_at' => now()->toDateTimeString(),
                ]);
                $this->parentRun->log('info', "Completed: {$description} (in " . number_format($duration, 2) . "s)");
            }

            $this->newLine();
            $this->line("  ✅ <fg=green>Completed</> in <fg=yellow>" . number_format($duration, 2) . "s</>");
            $this->newLine();

            return $result;

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;

            $logEntry['success'] = false;
            $logEntry['duration'] = $duration;
            $logEntry['failed_at'] = now()->toDateTimeString();
            $logEntry['error'] = $e->getMessage();

            $this->executionLog[] = $logEntry;

            // Update parent run with step failure data
            if ($this->parentRun) {
                $this->parentRun->updateStepData("step_{$this->currentStep}", [
                    'description' => $description,
                    'duration' => $duration,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toDateTimeString(),
                ]);
                $this->parentRun->log('error', "Failed: {$description} - {$e->getMessage()}");
            }

            $this->newLine();
            $this->error("  ❌ Failed after " . number_format($duration, 2) . "s");
            $this->newLine();

            throw $e;
        }
    }

    protected function extractRelevantDetails(array $result): array
    {
        $details = [];

        if (isset($result['created'])) $details['Created'] = $result['created'];
        if (isset($result['updated'])) $details['Updated'] = $result['updated'];
        if (isset($result['errors'])) $details['Errors'] = $result['errors'];
        if (isset($result['items_scraped'])) $details['Items Scraped'] = $result['items_scraped'];
        if (isset($result['backup_file'])) $details['Backup File'] = $result['backup_file'];

        return $details;
    }

    /**
     * Run scraper command with real-time progress monitoring
     */
    protected function runScraperWithProgress(string $command, string $label): array
    {
        // Start the scraper command in the background
        $process = Process::start($command);

        // Wait a moment for the scraper run to be created in the database
        sleep(1);

        // Get the latest scraper run
        $run = ScraperRun::latest('id')->first();

        if (!$run) {
            throw new \Exception("Failed to find scraper run in database");
        }

        $this->line("  <fg=cyan>Scraper Run ID: #{$run->id}</>");
        $this->newLine();

        $lastItemCount = 0;
        $lastLogId = 0;
        $dots = 0;

        // Poll the database for progress
        while ($process->running()) {
            // Refresh the run to get latest data
            $run->refresh();

            // Show item progress if count changed
            if ($run->items_scraped > $lastItemCount) {
                $this->line("  <fg=green>✓</> Scraped: {$run->items_scraped} items" .
                    ($run->items_failed > 0 ? " (<fg=red>{$run->items_failed} failed</>)" : ""));
                $lastItemCount = $run->items_scraped;
                $dots = 0;
            }

            // Show latest log messages
            $newLogs = $run->logs()
                ->where('id', '>', $lastLogId)
                ->orderBy('id', 'asc')
                ->get();

            foreach ($newLogs as $log) {
                $icon = match($log->level) {
                    'error' => '<fg=red>✗</>',
                    'warning' => '<fg=yellow>⚠</>',
                    default => '<fg=blue>ℹ</>',
                };
                $this->line("  {$icon} {$log->message}");
                $lastLogId = $log->id;
                $dots = 0;
            }

            // Show activity dots if nothing new
            if ($dots < 3 && $newLogs->isEmpty() && $run->items_scraped === $lastItemCount) {
                $this->output->write('.');
                $dots++;
            }

            // Check if scraper finished
            if (in_array($run->status, [ScraperRun::STATUS_COMPLETED, ScraperRun::STATUS_FAILED])) {
                break;
            }

            sleep(1);
        }

        // Make sure process has finished
        $result = $process->wait();

        // Final refresh
        $run->refresh();

        $this->newLine();

        // Show final status
        if ($run->status === ScraperRun::STATUS_COMPLETED) {
            $this->line("  <fg=green>✓</> {$label} scraping completed!");
            $this->line("  <fg=yellow>Final count:</> {$run->items_scraped} items scraped" .
                ($run->items_failed > 0 ? ", {$run->items_failed} failed" : ""));
        } else {
            $this->line("  <fg=red>✗</> {$label} scraping failed!");
            if ($run->error_message) {
                $this->line("  <fg=red>Error:</> {$run->error_message}");
            }
        }

        return [
            'run_id' => $run->id,
            'status' => $run->status,
            'items_scraped' => $run->items_scraped,
            'items_failed' => $run->items_failed,
            'duration' => $run->duration,
        ];
    }

    protected function scrapePlayers(string $month, bool $scrapeAll): array
    {
        $command = 'php artisan scraper:run players';

        if (!$scrapeAll) {
            $command .= " --period=" . escapeshellarg($month . '-01');
            $command .= " --direction=gte";
        }

        return $this->runScraperWithProgress($command, 'Players');
    }

    protected function scrapeRankings(string $gender, string $month, bool $scrapeAll): array
    {
        $command = "php artisan scraper:run rankings --gender={$gender}";

        if (!$scrapeAll) {
            $command .= " --period=" . escapeshellarg($month . '-01');
            $command .= " --direction=gte";
        }

        return $this->runScraperWithProgress($command, "Rankings ({$gender})");
    }

    protected function scrapeMatches(string $month, bool $scrapeAll): array
    {
        $command = 'php artisan scraper:run live_center';

        if (!$scrapeAll) {
            $command .= " --period=" . escapeshellarg($month . '-01');
            $command .= " --direction=gte";
        }

        return $this->runScraperWithProgress($command, 'Matches');
    }

    protected function syncData(SyncService $syncService, string $type): array
    {
        $this->line("  🔄 Syncing {$type}...");
        $this->newLine();

        // Use parent run for logging
        $run = $this->parentRun;

        $lastLogId = $run ? $run->logs()->max('id') ?? 0 : 0;

        // Start sync in a way that allows us to poll for logs
        if ($type === 'players') {
            // Poll for new logs while syncing
            $this->syncWithProgress(function() use ($syncService, $run) {
                return $syncService->syncPlayers(null, $run);
            }, $run, $lastLogId);

            $stats = $syncService->getStats();
        } elseif ($type === 'rankings') {
            $this->syncWithProgress(function() use ($syncService, $run) {
                return $syncService->syncRankings(null, $run);
            }, $run, $lastLogId);

            $stats = $syncService->getStats();
        } else {
            throw new \Exception("Unknown sync type: {$type}");
        }

        $this->displaySyncStats($stats);
        $this->stats[$type] = $stats;

        return $stats;
    }

    protected function syncMatches(MatchSyncService $matchSyncService): array
    {
        $this->line("  🔄 Syncing matches...");
        $this->newLine();

        // Use parent run for logging
        $run = $this->parentRun;

        $lastLogId = $run ? $run->logs()->max('id') ?? 0 : 0;

        $this->syncWithProgress(function() use ($matchSyncService, $run) {
            return $matchSyncService->syncMatches(null, $run);
        }, $run, $lastLogId);

        $stats = $matchSyncService->getStats();

        $this->displayMatchSyncStats($stats);
        $this->stats['matches'] = $stats;

        return $stats;
    }

    /**
     * Execute sync operation and display progress logs in real-time
     */
    protected function syncWithProgress(callable $syncCallback, ?ScraperRun $run, int $lastLogId): void
    {
        if (!$run) {
            // If no run, just execute synchronously
            $syncCallback();
            return;
        }

        // Since sync operations are fast, we'll just display logs after completion
        // In the future, this could be threaded for true real-time progress
        $syncCallback();

        // Display all new logs that were created during sync
        $newLogs = $run->logs()
            ->where('id', '>', $lastLogId)
            ->orderBy('id', 'asc')
            ->get();

        foreach ($newLogs as $log) {
            $icon = match($log->level) {
                'error' => '<fg=red>✗</>',
                'warning' => '<fg=yellow>⚠</>',
                default => '<fg=blue>ℹ</>',
            };
            $this->line("  {$icon} {$log->message}");
        }

        if ($newLogs->isNotEmpty()) {
            $this->newLine();
        }
    }

    protected function displaySyncStats(array $stats): void
    {
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', "<fg=green>{$stats['created']}</>"],
                ['Updated', "<fg=yellow>{$stats['updated']}</>"],
                ['Skipped', "<fg=gray>{$stats['skipped']}</>"],
                ['Errors', $stats['errors'] > 0 ? "<fg=red>{$stats['errors']}</>" : "<fg=green>0</>"],
            ]
        );
    }

    protected function displayMatchSyncStats(array $stats): void
    {
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Official matches created', "<fg=green>{$stats['created']}</>"],
                ['Comments migrated', "<fg=cyan>{$stats['comments_migrated']}</>"],
                ['Manual matches replaced', "<fg=yellow>{$stats['manual_matches_replaced']}</>"],
                ['Manual matches marked unofficial', "<fg=gray>{$stats['manual_matches_marked_unofficial']}</>"],
                ['Errors', $stats['errors'] > 0 ? "<fg=red>{$stats['errors']}</>" : "<fg=green>0</>"],
            ]
        );
    }

    protected function verifyData(): array
    {
        $this->line("  🔍 Running data integrity checks...");
        $this->newLine();

        $counts = [
            'users' => DB::table('users')->count(),
            'clubs' => DB::table('clubs')->count(),
            'matches' => DB::table('matches')->whereNull('deleted_at')->count(),
            'official_matches' => DB::table('matches')
                ->where('source', 'scraped')
                ->whereNull('deleted_at')
                ->count(),
            'rankings' => DB::table('monthly_rankings')->count(),
            'scraped_players' => DB::table('scraped_players')->where('is_synced', true)->count(),
            'scraped_rankings' => DB::table('scraped_rankings')->where('is_synced', true)->count(),
            'scraped_matches' => DB::table('scraped_matches')->where('is_synced', true)->count(),
        ];

        $this->table(
            ['Entity', 'Count'],
            [
                ['Total Users', "<fg=cyan>{$counts['users']}</>"],
                ['Total Clubs', "<fg=cyan>{$counts['clubs']}</>"],
                ['Total Matches', "<fg=cyan>{$counts['matches']}</>"],
                ['Official Matches', "<fg=green>{$counts['official_matches']}</>"],
                ['Monthly Rankings', "<fg=cyan>{$counts['rankings']}</>"],
                ['Synced Players', "<fg=green>{$counts['scraped_players']}</>"],
                ['Synced Rankings', "<fg=green>{$counts['scraped_rankings']}</>"],
                ['Synced Matches', "<fg=green>{$counts['scraped_matches']}</>"],
            ]
        );

        // Check for errors
        $errors = [];

        if ($counts['users'] < 2) {
            $errors[] = "Too few users (expected > 2, got {$counts['users']})";
        }

        if ($counts['clubs'] < 1) {
            $errors[] = "No clubs found";
        }

        if (!empty($errors)) {
            $this->newLine();
            $this->warn("⚠️  Data Quality Warnings:");
            foreach ($errors as $error) {
                $this->warn("  • {$error}");
            }
        }

        return $counts;
    }

    protected function getLatestScraperRun(): array
    {
        return DB::table('scraper_runs')
            ->orderBy('id', 'desc')
            ->first()
            ? (array) DB::table('scraper_runs')->orderBy('id', 'desc')->first()
            : [];
    }

    protected function displaySummary(): void
    {
        $this->newLine(2);
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║                   SCRAPE COMPLETE ✅                    ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Summary table
        $summaryData = [];

        if (isset($this->stats['players'])) {
            $summaryData[] = [
                'Players',
                $this->stats['players']['created'],
                $this->stats['players']['updated'],
                $this->stats['players']['errors']
            ];
        }

        if (isset($this->stats['rankings'])) {
            $summaryData[] = [
                'Rankings',
                $this->stats['rankings']['created'],
                $this->stats['rankings']['updated'],
                $this->stats['rankings']['errors']
            ];
        }

        if (isset($this->stats['matches'])) {
            $summaryData[] = [
                'Matches',
                $this->stats['matches']['created'],
                $this->stats['matches']['comments_migrated'],
                $this->stats['matches']['errors']
            ];
        }

        if (!empty($summaryData)) {
            $this->table(
                ['Type', 'Created', 'Updated/Migrated', 'Errors'],
                $summaryData
            );
        }

        $this->newLine();
        $this->line("  🕐 Finished: <fg=gray>" . now()->format('Y-m-d H:i:s') . "</>");
        $this->newLine();

        $this->info("💡 Next step: Review the data in your application");
        $this->newLine();
    }
}
