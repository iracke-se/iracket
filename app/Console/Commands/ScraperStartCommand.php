<?php

namespace App\Console\Commands;

use App\Models\Scraper\ScraperRun;
use App\Services\Scraper\SyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ScraperStartCommand extends Command
{
    protected $signature = 'scraper:start
                            {month? : The month to scrape (e.g., 2025-09). Defaults to previous month when omitted.}
                            {--all : Scrape all data without month filter}
                            {--no-backup : Skip automatic backup before starting}
                            {--skip-sync : Skip automatic sync to production tables}
                            {--skip-bubbler : Skip Bubbler recalculation}
                            {--force : Force execution without confirmation (for queue jobs)}
                            {--limit-periods= : Limit number of periods to scrape (for testing)}
                            {--limit-divisions= : Limit number of divisions to scrape (for testing)}
                            {--limit-clubs= : Limit number of clubs to scrape (for testing)}
                            {--limit-seasons= : Limit number of seasons to scrape (for testing)}
                            {--limit-players= : Limit number of players to scrape (for testing)}
                            {--skip-live-center : Skip Live Center scraping and syncing}
                            {--skip-series : Skip Series Standings scraping}
                            {--skip-transitions : Skip Club Transitions scraping and syncing}';

    protected $description = 'Scrape and sync all data for a specific month with visual progress';

    protected array $stats = [];
    protected int $totalSteps = 15; // + Scrape Transitions, Sync Transitions, Sync Standings (added 2026-05-24)
    protected int $currentStep = 0;
    protected ?string $backupFile = null;
    protected array $executionLog = [];
    protected ?string $failedStep = null;
    protected ?\Exception $failureException = null;
    protected ?int $latestRunId = null;
    protected ?ScraperRun $parentRun = null;

    public function handle(SyncService $syncService): int
    {
        $month = $this->argument('month') ?: now()->subMonth()->format('Y-m');
        $scrapeAll = $this->option('all');
        $skipBackup = $this->option('no-backup');

        if (!$scrapeAll && !$this->validateMonth($month)) {
            $this->error("Invalid month format. Use YYYY-MM format (e.g., 2025-09)");
            return self::FAILURE;
        }

        // Check if another scraper is already running
        $runningScrapers = ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->get();

        if ($runningScrapers->isNotEmpty()) {
            // If --force flag is used (e.g., from queue), wait automatically without confirmation
            if ($this->option('force')) {
                $this->info("Waiting for running scraper to complete (forced mode)...");

                // Wait for all running scrapers to finish
                while (ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->exists()) {
                    sleep(2);
                }

                $this->info("✓ Running scraper completed. Starting new scraper...");
                $this->newLine();
            } else {
                // Interactive mode - show details and ask for confirmation
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
        }

        // Count steps dynamically based on options
        // Base: Backup + Rankings M+F (parallel) + Players + Sync Players
        //     + Sync Rankings + Monthly Rankings + Sync Matches + Series
        //     + Live Center + Sync Live Center + Verify = 11
        $this->totalSteps = 11;
        if ($skipBackup) {
            $this->totalSteps--;
        }
        if ($this->option('limit-players')) {
            $this->totalSteps -= 2; // Skip "Scraping Players" and "Syncing Players"
        }
        if ($this->option('skip-live-center')) {
            $this->totalSteps -= 2; // Remove Live Center scrape + sync steps
        }
        if ($this->option('skip-series')) {
            $this->totalSteps--; // Remove Series Standings step
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

            // Step 1: Scrape Rankings with Popup Interaction (Male + Female in parallel)
            $result = $this->runStep('Scraping Rankings & Matches (Male + Female parallel)', function () use ($month, $scrapeAll) {
                return $this->scrapeRankingsParallel($month, $scrapeAll);
            });
            $this->latestRunId = $result['run_id'] ?? null;

            // Step 2: Scrape Players
            // Skip this step if --limit-players is set, since players will be created from rankings
            if (!$this->option('limit-players')) {
                $result = $this->runStep('Scraping Players', function () use ($month, $scrapeAll) {
                    return $this->scrapePlayers($month, $scrapeAll);
                });
                $this->latestRunId = $result['run_id'] ?? $this->latestRunId;
            } else {
                $this->line("  ⏭️  Skipping player list scrape (using --limit-players)");
                $this->newLine();
            }

            // Step 3: Sync Players
            // Skip this step if --limit-players is set, since players will be synced from rankings
            if (!$this->option('limit-players')) {
                $this->runStep('Syncing Players → Users & Clubs', function () use ($syncService) {
                    return $this->syncData($syncService, 'players');
                });
            } else {
                $this->line("  ⏭️  Skipping player sync (players will be synced from rankings)");
                $this->newLine();
            }

            // Step 4: Sync Rankings
            $this->runStep('Syncing Rankings', function () use ($syncService) {
                return $this->syncData($syncService, 'rankings');
            });

            // Step 5: Create Monthly Rankings
            $this->runStep('Creating Monthly Rankings', function () use ($syncService) {
                return $this->createMonthlyRankings($syncService);
            });

            // Step 6: Sync Matches
            $this->runStep('Syncing Matches', function () {
                return $this->syncMatches();
            });

            // Step 7: Scrape Club Transitions
            if (!$this->option('skip-transitions')) {
                $result = $this->runStep('Scraping Club Transitions', function () use ($month, $scrapeAll) {
                    return $this->scrapeTransitions($month, $scrapeAll);
                });
                $this->latestRunId = $result['run_id'] ?? $this->latestRunId;
            } else {
                $this->line('  ⏭  Skipping Club Transitions scrape (--skip-transitions)');
                $this->newLine();
            }

            // Step 8: Sync Club Transitions
            if (!$this->option('skip-transitions')) {
                $this->runStep('Syncing Club Transitions', function () {
                    return $this->syncTransitions();
                });
            } else {
                $this->line('  ⏭  Skipping Club Transitions sync (--skip-transitions)');
                $this->newLine();
            }

            // Step 9: Scrape Series Standings
            if (!$this->option('skip-series')) {
                $result = $this->runStep('Scraping Series Standings', function () use ($month, $scrapeAll) {
                    return $this->scrapeSeries($month, $scrapeAll);
                });
                $this->latestRunId = $result['run_id'] ?? $this->latestRunId;

                // Step 10: Sync Series Standings
                $this->runStep('Syncing Series Standings', function () {
                    return $this->syncStandings();
                });
            } else {
                $this->info('  ⏭  Skipping Series Standings (--skip-series)');
            }

            // Step 8: Scrape Live Center
            if (!$this->option('skip-live-center')) {
                $result = $this->runStep('Scraping Live Center Match Details', function () use ($month, $scrapeAll) {
                    return $this->scrapeLiveCenter($month, $scrapeAll);
                });
                $this->latestRunId = $result['run_id'] ?? $this->latestRunId;
            } else {
                $this->line("  ⏭️  Skipping Live Center scrape (--skip-live-center)");
                $this->newLine();
            }

            // Step 9: Sync Live Center
            if (!$this->option('skip-live-center')) {
                $this->runStep('Syncing Live Center → Matches', function () {
                    return $this->syncLiveCenter();
                });
            } else {
                $this->line("  ⏭️  Skipping Live Center sync (--skip-live-center)");
                $this->newLine();
            }

            // Step 10: Verify Data
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
        $backupName = "scraper-{$month}-{$timestamp}";

        $this->line("  📦 Creating database backup: <fg=yellow>{$backupName}</>");

        try {
            // Run Spatie backup for database only
            $exitCode = \Illuminate\Support\Facades\Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);

            if ($exitCode !== 0) {
                $output = \Illuminate\Support\Facades\Artisan::output();
                throw new \Exception("Backup command failed: " . $output);
            }

            // Get the latest backup file from configured backup destination
            $backupDestination = config('backup.backup.destination.disks')[0] ?? 'local';
            $disk = \Storage::disk($backupDestination);

            // Get backup directory path
            $backupName = config('backup.backup.name');
            $files = collect($disk->files($backupName))
                ->filter(fn($file) => str_ends_with($file, '.zip'))
                ->sortByDesc(fn($file) => $disk->lastModified($file))
                ->first();

            if (!$files) {
                throw new \Exception("No backup file found after backup:run completed");
            }

            $this->backupFile = $disk->path($files);

            $this->line("  ✓ Backup created successfully");
            $this->line("  📍 Location: <fg=cyan>{$this->backupFile}</>");

            return [
                'backup_file' => $this->backupFile,
                'backup_name' => $backupName,
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to create backup: " . $e->getMessage());
        }
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

        if ($this->option('verbose') || ($this->input->isInteractive() && $this->confirm("Show full stack trace?", false))) {
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

    protected function buildLimitOptions(): string
    {
        $options = '';

        if ($this->option('limit-periods')) {
            $options .= ' --limit-periods=' . escapeshellarg($this->option('limit-periods'));
        }

        if ($this->option('limit-divisions')) {
            $options .= ' --limit-divisions=' . escapeshellarg($this->option('limit-divisions'));
        }

        if ($this->option('limit-clubs')) {
            $options .= ' --limit-clubs=' . escapeshellarg($this->option('limit-clubs'));
        }

        if ($this->option('limit-seasons')) {
            $options .= ' --limit-seasons=' . escapeshellarg($this->option('limit-seasons'));
        }

        if ($this->option('limit-players')) {
            $options .= ' --limit-players=' . escapeshellarg($this->option('limit-players'));
        }

        return $options;
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
        // Detect execution context: use Artisan::call() for queue, Process::start() for CLI
        $isQueueContext = $this->option('force');

        if ($isQueueContext) {
            // Queue execution - run via Artisan::call() to avoid process issues
            [$artisanCommand, $args] = $this->parseCommand($command);

            // Run synchronously in same process
            \Illuminate\Support\Facades\Artisan::call($artisanCommand, $args);

            // Get the latest scraper run that was just created
            sleep(1);
            $run = ScraperRun::latest('id')->first();

            if (!$run) {
                throw new \Exception("Failed to find scraper run in database");
            }

            $this->line("  <fg=cyan>Scraper Run ID: #{$run->id}</>");
            $this->newLine();

            // For queue context, the command runs synchronously so we can just return the results
            $run->refresh();

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

        // Run via Artisan::call() — synchronous, reliable, no race conditions
        [$artisanCommand, $args] = $this->parseCommand($command);

        $this->line("  <fg=cyan>Running:</> {$command}");
        $this->newLine();

        \Illuminate\Support\Facades\Artisan::call($artisanCommand, $args, $this->output);

        // Find the run that was just created by looking for the latest run
        // that isn't the parent run and was created after we started
        $run = ScraperRun::where('id', '!=', $this->parentRun?->id)
            ->latest('id')
            ->first();

        if (!$run) {
            throw new \Exception("Failed to find scraper run after executing: {$command}");
        }

        $this->line("  <fg=cyan>Scraper Run ID: #{$run->id}</>");
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

    /**
     * Parse a shell command string into Artisan command name and arguments
     *
     * Example: "php artisan scraper:run players --period=2025-12-01 --direction=gte"
     * Returns: ['scraper:run', ['type' => 'players', '--period' => '2025-12-01', '--direction' => 'gte']]
     */
    protected function parseCommand(string $command): array
    {
        // Remove "php artisan " prefix if present
        $command = preg_replace('/^php\s+artisan\s+/', '', $command);

        // Split into parts
        $parts = preg_split('/\s+/', trim($command));

        // First part is the command name
        $commandName = array_shift($parts);

        // Parse remaining parts as arguments
        $args = [];
        $positionalIndex = 0;

        foreach ($parts as $part) {
            if (str_starts_with($part, '--')) {
                // Long option: --name=value or --flag
                if (str_contains($part, '=')) {
                    [$key, $value] = explode('=', $part, 2);
                    // Remove quotes from value if present
                    $value = trim($value, '"\'');
                    $args[$key] = $value;
                } else {
                    // Boolean flag
                    $args[$part] = true;
                }
            } elseif (str_starts_with($part, '-') && strlen($part) === 2) {
                // Short option: -v
                $args[$part] = true;
            } else {
                // Positional argument
                // For scraper:run, first positional is the 'type'
                if ($commandName === 'scraper:run' && $positionalIndex === 0) {
                    $args['type'] = $part;
                } else {
                    $args[$positionalIndex] = $part;
                }
                $positionalIndex++;
            }
        }

        return [$commandName, $args];
    }

    /**
     * Scrape rankings for both genders in parallel using separate OS processes
     */
    protected function scrapeRankingsParallel(string $month, bool $scrapeAll): array
    {
        [$year, $monthNum] = explode('-', $month);

        $buildArgs = function (string $gender) use ($year, $monthNum): array {
            $args = [
                PHP_BINARY,
                base_path('artisan'),
                'scraper:run',
                'rankings',
                "--year={$year}",
                "--month={$monthNum}",
                "--gender={$gender}",
            ];

            if ($this->option('limit-players')) {
                $args[] = '--limit-players=' . $this->option('limit-players');
            }

            return $args;
        };

        $startId = ScraperRun::max('id') ?? 0;

        // Wrap with setsid so child processes get their own session and survive terminal SIGHUP
        $wrap = fn(array $args) => array_merge(['setsid'], $args);
        $mProcess = new \Symfony\Component\Process\Process($wrap($buildArgs('m')));
        $fProcess = new \Symfony\Component\Process\Process($wrap($buildArgs('k')));
        $mProcess->setTimeout(3600);
        $fProcess->setTimeout(3600);

        $this->line("  <fg=cyan>Starting Male rankings process...</>");
        $mProcess->start();
        $this->line("  <fg=cyan>Starting Female rankings process...</>");
        $fProcess->start();
        $this->newLine();

        // Poll both processes, streaming output until both finish
        $mBuffer = '';
        $fBuffer = '';
        $maxWait = 7200; // 2 hours hard cap
        $elapsed = 0;
        while (!$mProcess->isTerminated() || !$fProcess->isTerminated()) {
            try {
                $mProcess->checkTimeout();
                $fProcess->checkTimeout();
            } catch (\Symfony\Component\Process\Exception\ProcessTimedOutException $e) {
                break;
            }

            // Flush incremental output from male process
            $mNew = $mProcess->getIncrementalOutput() . $mProcess->getIncrementalErrorOutput();
            foreach (explode("\n", $mBuffer . $mNew) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $this->line("  <fg=blue>[M]</> {$line}");
                }
            }
            $mBuffer = '';

            // Flush incremental output from female process
            $fNew = $fProcess->getIncrementalOutput() . $fProcess->getIncrementalErrorOutput();
            foreach (explode("\n", $fBuffer . $fNew) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $this->line("  <fg=magenta>[F]</> {$line}");
                }
            }
            $fBuffer = '';

            usleep(500000); // 0.5s
            $elapsed++;

            // Fallback: if DB shows both runs completed/failed, don't wait for the process object
            if ($elapsed % 20 === 0) {
                $pending = ScraperRun::where('id', '>', $startId)
                    ->where('type', ScraperRun::TYPE_RANKINGS)
                    ->where('status', 'running')
                    ->count();
                $done = ScraperRun::where('id', '>', $startId)
                    ->where('type', ScraperRun::TYPE_RANKINGS)
                    ->whereIn('status', ['completed', 'failed'])
                    ->count();
                if ($pending === 0 && $done >= 2) {
                    $this->line("  <fg=yellow>Both ranking runs finished in DB — continuing.</>");
                    break;
                }
            }

            // Hard cap
            if ($elapsed * 0.5 >= $maxWait) {
                $this->warn("  Hard timeout reached — continuing.");
                break;
            }
        }

        // Check exit codes
        $errors = [];
        if (!$mProcess->isSuccessful()) {
            $errors[] = "Male rankings failed (exit {$mProcess->getExitCode()}): " . $mProcess->getErrorOutput();
        }
        if (!$fProcess->isSuccessful()) {
            $errors[] = "Female rankings failed (exit {$fProcess->getExitCode()}): " . $fProcess->getErrorOutput();
        }
        if (!empty($errors)) {
            throw new \Exception(implode("\n", $errors));
        }

        // Find the two new ScraperRun records created by the sub-processes
        $runs = ScraperRun::where('id', '>', $startId)
            ->where('type', ScraperRun::TYPE_RANKINGS)
            ->get();

        foreach ($runs as $run) {
            $gender = ($run->parameters['gender'] ?? '') === 'k' ? 'Female' : 'Male';
            $this->line("  <fg=green>✓</> {$gender} Run #{$run->id}: {$run->items_scraped} items scraped");
        }

        return [
            'run_id' => $runs->last()?->id,
            'runs' => $runs->pluck('id')->toArray(),
            'items_scraped' => $runs->sum('items_scraped'),
            'items_failed' => $runs->sum('items_failed'),
        ];
    }

    /**
     * Scrape rankings with popup interaction (includes matches)
     */
    protected function scrapeRankingsWithPopup(string $gender, string $month, bool $scrapeAll): array
    {
        // Parse month into year and month components
        [$year, $monthNum] = explode('-', $month);

        $command = "php artisan scraper:run rankings --year={$year} --month={$monthNum} --gender={$gender}";

        // Add limit options (including limit-players)
        $command .= $this->buildLimitOptions();

        $genderLabel = $gender === 'm' ? 'Male' : 'Female';
        return $this->runScraperWithProgress($command, "Rankings & Matches ({$genderLabel})");
    }

    protected function scrapePlayers(string $month, bool $scrapeAll): array
    {
        $command = 'php artisan scraper:run players';

        if (!$scrapeAll) {
            $command .= " --period=" . escapeshellarg($month . '-01');
            $command .= " --direction=gte";
        }

        $command .= $this->buildLimitOptions();

        return $this->runScraperWithProgress($command, 'Players');
    }

    protected function scrapeTransitions(string $month, bool $scrapeAll): array
    {
        $command = 'php artisan scraper:run transitions';
        $command .= $this->buildLimitOptions();

        return $this->runScraperWithProgress($command, 'Club Transitions');
    }

    protected function syncTransitions(): array
    {
        $this->line('  🔄 Syncing club transitions...');
        $this->newLine();

        $run = $this->parentRun;
        $lastLogId = $run ? $run->logs()->max('id') ?? 0 : 0;

        $service = app(\App\Services\Scraper\TransitionSyncService::class);

        $this->syncWithProgress(function () use ($service, $run) {
            return $service->syncTransitions(null, $run);
        }, $run, $lastLogId);

        $stats = $service->getStats();

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', number_format($stats['created'])],
                ['Updated', number_format($stats['updated'])],
                ['Skipped', number_format($stats['skipped'])],
                ['Errors', number_format($stats['errors'])],
            ]
        );

        $this->stats['transitions'] = $stats;

        return $stats;
    }

    protected function syncStandings(): array
    {
        $this->line('  🔄 Syncing series standings...');
        $this->newLine();

        $run = $this->parentRun;
        $lastLogId = $run ? $run->logs()->max('id') ?? 0 : 0;

        $service = app(\App\Services\Scraper\StandingsSyncService::class);

        $this->syncWithProgress(function () use ($service, $run) {
            return $service->syncStandings(null, $run);
        }, $run, $lastLogId);

        $stats = $service->getStats();

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', number_format($stats['created'])],
                ['Updated', number_format($stats['updated'])],
                ['Skipped', number_format($stats['skipped'])],
                ['Errors', number_format($stats['errors'])],
            ]
        );

        $this->stats['standings'] = $stats;

        return $stats;
    }

    protected function scrapeSeries(string $month, bool $scrapeAll): array
    {
        $command = 'php artisan scraper:run series';

        if (!$scrapeAll) {
            $command .= " --period=" . escapeshellarg($month . '-01');
            $command .= " --direction=gte";
        }

        $command .= $this->buildLimitOptions();

        return $this->runScraperWithProgress($command, 'Series Standings');
    }

    protected function scrapeLiveCenter(string $month, bool $scrapeAll): array
    {
        $command = 'php artisan scraper:run live_center';

        if (!$scrapeAll) {
            $command .= " --month={$month}";
        } else {
            $command .= " --from-matches";
        }

        return $this->runScraperWithProgress($command, 'Live Center');
    }

    protected function syncLiveCenter(): array
    {
        $this->line("  🔄 Syncing Live Center games → matches...");
        $this->newLine();

        $run = $this->parentRun;
        $lastLogId = $run ? $run->logs()->max('id') ?? 0 : 0;

        $liveCenterSyncService = app(\App\Services\Scraper\LiveCenterSyncService::class);

        $this->syncWithProgress(function () use ($liveCenterSyncService, $run) {
            return $liveCenterSyncService->syncMatches(null, $run);
        }, $run, $lastLogId);

        $stats = $liveCenterSyncService->getStats();

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Games synced', number_format($stats['games_synced'])],
                ['Matches created', number_format($stats['matches_created'])],
                ['Matches linked', number_format($stats['matches_linked'])],
                ['Skipped', number_format($stats['skipped'])],
                ['Errors', number_format($stats['errors'])],
            ]
        );

        $this->stats['live_center'] = $stats;

        return $stats;
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

    protected function createMonthlyRankings(SyncService $syncService): array
    {
        $this->line("  📊 Creating monthly rankings from scraped data...");
        $this->newLine();

        // Use parent run for logging
        $run = $this->parentRun;

        $lastLogId = $run ? $run->logs()->max('id') ?? 0 : 0;

        // Create monthly rankings with progress
        $this->syncWithProgress(function() use ($syncService, $run) {
            return $syncService->createMonthlyRankings(null, $run);
        }, $run, $lastLogId);

        $stats = $syncService->getStats();

        // Display results
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created/Updated', number_format($stats['created'])],
                ['Errors', number_format($stats['errors'])],
            ]
        );

        $this->stats['monthly_rankings'] = $stats;

        return $stats;
    }

    protected function syncMatches(): array
    {
        $this->line("  🔄 Syncing matches...");
        $this->newLine();

        // Use parent run for logging
        $run = $this->parentRun;

        $lastLogId = $run ? $run->logs()->max('id') ?? 0 : 0;

        // Create MatchSyncService instance
        $matchSyncService = app(\App\Services\Scraper\MatchSyncService::class);

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
            'club_rankings' => DB::table('club_monthly_rankings')->count(),
            'scraped_players' => DB::table('scraped_players')->where('is_synced', true)->count(),
            'scraped_rankings' => DB::table('scraped_rankings')->where('is_synced', true)->count(),
            'scraped_matches' => DB::table('scraped_matches')->where('is_synced', true)->count(),
            'scraped_standings' => DB::table('scraped_standings')->count(),
        ];

        $this->table(
            ['Entity', 'Count'],
            [
                ['Total Users', "<fg=cyan>{$counts['users']}</>"],
                ['Total Clubs', "<fg=cyan>{$counts['clubs']}</>"],
                ['Total Matches', "<fg=cyan>{$counts['matches']}</>"],
                ['Official Matches', "<fg=green>{$counts['official_matches']}</>"],
                ['Monthly Rankings', "<fg=cyan>{$counts['rankings']}</>"],
                ['Club Rankings', "<fg=cyan>{$counts['club_rankings']}</>"],
                ['Synced Players', "<fg=green>{$counts['scraped_players']}</>"],
                ['Synced Rankings', "<fg=green>{$counts['scraped_rankings']}</>"],
                ['Synced Matches', "<fg=green>{$counts['scraped_matches']}</>"],
                ['Scraped Standings', "<fg=yellow>{$counts['scraped_standings']}</>"],
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
