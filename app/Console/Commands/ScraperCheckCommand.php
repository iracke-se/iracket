<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Spatie\Browsershot\Browsershot;

class ScraperCheckCommand extends Command
{
    protected $signature = 'scraper:check
                            {--fix : Attempt to fix issues automatically}
                            {--verbose : Show detailed diagnostic information}';

    protected $description = 'Check if scraper has all required tools and configuration';

    protected array $checks = [];
    protected int $passed = 0;
    protected int $failed = 0;
    protected int $warnings = 0;

    public function handle(): int
    {
        $this->displayHeader();

        // Run all checks
        $this->checkPhpVersion();
        $this->checkPhpExtensions();
        $this->checkNodeJs();
        $this->checkNpm();
        $this->checkChrome();
        $this->checkBrowsershot();
        $this->checkDatabase();
        $this->checkStoragePermissions();
        $this->checkEnvironmentVariables();
        $this->checkQueueConfiguration();
        $this->checkScraperTables();
        $this->checkTestConnection();

        // Display results
        $this->displaySummary();

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function displayHeader(): void
    {
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║          SCRAPER ENVIRONMENT CHECK                     ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();
    }

    protected function checkPhpVersion(): void
    {
        $version = PHP_VERSION;
        $required = '8.2.0';

        if (version_compare($version, $required, '>=')) {
            $this->checkPass("PHP Version: {$version}");
        } else {
            $this->checkFail("PHP Version: {$version} (requires >= {$required})");
        }
    }

    protected function checkPhpExtensions(): void
    {
        $required = ['pdo_sqlite', 'sqlite3', 'mbstring', 'xml', 'curl', 'zip', 'pcntl'];
        $missing = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if (empty($missing)) {
            $this->checkPass("PHP Extensions: All required extensions loaded");
        } else {
            $this->checkFail("PHP Extensions: Missing - " . implode(', ', $missing));
            $this->line("   Install with: sudo apt-get install php-" . implode(' php-', $missing));
        }
    }

    protected function checkNodeJs(): void
    {
        $nodePath = config('scraper.browser.node_binary', 'node');

        try {
            $result = Process::run("{$nodePath} --version");

            if ($result->successful()) {
                $version = trim($result->output());
                $this->checkPass("Node.js: {$version} at {$nodePath}");

                if ($this->option('verbose')) {
                    $this->line("   Path: {$nodePath}");
                }
            } else {
                $this->checkFail("Node.js: Not found at {$nodePath}");
                $this->suggestNodeInstall();
            }
        } catch (\Exception $e) {
            $this->checkFail("Node.js: Error - {$e->getMessage()}");
            $this->suggestNodeInstall();
        }
    }

    protected function checkNpm(): void
    {
        $npmPath = config('scraper.browser.npm_binary', 'npm');

        try {
            $result = Process::run("{$npmPath} --version");

            if ($result->successful()) {
                $version = trim($result->output());
                $this->checkPass("npm: {$version} at {$npmPath}");

                if ($this->option('verbose')) {
                    $this->line("   Path: {$npmPath}");
                }
            } else {
                $this->checkFail("npm: Not found at {$npmPath}");
            }
        } catch (\Exception $e) {
            $this->checkFail("npm: Error - {$e->getMessage()}");
        }
    }

    protected function checkChrome(): void
    {
        $chromePath = config('scraper.browser.chrome_path');

        if (!$chromePath) {
            $this->checkWarn("Chrome: Path not configured in SCRAPER_CHROME_PATH");
            $this->line("   Set SCRAPER_CHROME_PATH in .env file");
            return;
        }

        if (!file_exists($chromePath)) {
            $this->checkFail("Chrome: Not found at {$chromePath}");
            $this->suggestChromeInstall();
            return;
        }

        if (!is_executable($chromePath)) {
            $this->checkFail("Chrome: Not executable at {$chromePath}");
            $this->line("   Run: chmod +x {$chromePath}");
            return;
        }

        // Try to get Chrome version
        try {
            $result = Process::timeout(5)->run("\"{$chromePath}\" --version");

            if ($result->successful()) {
                $version = trim($result->output());
                $this->checkPass("Chrome: {$version}");

                if ($this->option('verbose')) {
                    $this->line("   Path: {$chromePath}");
                }
            } else {
                $this->checkWarn("Chrome: Found but cannot determine version");
            }
        } catch (\Exception $e) {
            $this->checkWarn("Chrome: Found but cannot execute (this is normal for headless environments)");

            if ($this->option('verbose')) {
                $this->line("   Error: {$e->getMessage()}");
            }
        }
    }

    protected function checkBrowsershot(): void
    {
        if (!class_exists(Browsershot::class)) {
            $this->checkFail("Browsershot: Package not installed");
            $this->line("   Run: composer require spatie/browsershot");
            return;
        }

        // Check if Puppeteer is installed
        $npmPath = config('scraper.browser.npm_binary', 'npm');
        $result = Process::run("cd " . base_path() . " && {$npmPath} list puppeteer 2>&1");

        if (str_contains($result->output(), 'puppeteer@')) {
            preg_match('/puppeteer@([^\s]+)/', $result->output(), $matches);
            $version = $matches[1] ?? 'unknown';
            $this->checkPass("Browsershot: Package installed, Puppeteer {$version}");
        } else {
            $this->checkFail("Browsershot: Puppeteer not installed");
            $this->line("   Run: npm install puppeteer");
        }
    }

    protected function checkDatabase(): void
    {
        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            DB::connection()->getPdo();

            $this->checkPass("Database: Connected ({$driver})");

            if ($this->option('verbose')) {
                $dbPath = config("database.connections.{$connection}.database");
                $this->line("   Path: {$dbPath}");
            }
        } catch (\Exception $e) {
            $this->checkFail("Database: Connection failed - {$e->getMessage()}");
        }
    }

    protected function checkStoragePermissions(): void
    {
        $directories = [
            storage_path('scraper_logs'),
            storage_path('logs'),
            storage_path('app'),
            storage_path('framework/cache'),
        ];

        $issues = [];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0775, true);
            }

            if (!is_writable($dir)) {
                $issues[] = $dir;
            }
        }

        if (empty($issues)) {
            $this->checkPass("Storage Permissions: All directories writable");
        } else {
            $this->checkFail("Storage Permissions: Not writable - " . implode(', ', $issues));
            $this->line("   Run: chmod -R 775 storage/");
        }
    }

    protected function checkEnvironmentVariables(): void
    {
        $required = [
            'SCRAPER_NODE_BINARY',
            'SCRAPER_NPM_BINARY',
            'SCRAPER_CHROME_PATH',
        ];

        $missing = [];

        foreach ($required as $var) {
            if (!config("scraper.browser." . strtolower(str_replace('SCRAPER_', '', $var)))) {
                $missing[] = $var;
            }
        }

        if (empty($missing)) {
            $this->checkPass("Environment Variables: All required variables set");

            if ($this->option('verbose')) {
                $this->line("   NODE_BINARY: " . config('scraper.browser.node_binary'));
                $this->line("   NPM_BINARY: " . config('scraper.browser.npm_binary'));
                $this->line("   CHROME_PATH: " . config('scraper.browser.chrome_path'));
            }
        } else {
            $this->checkFail("Environment Variables: Missing - " . implode(', ', $missing));
            $this->line("   Add to .env file");
        }
    }

    protected function checkQueueConfiguration(): void
    {
        $connection = config('queue.default');
        $driver = config("queue.connections.{$connection}.driver");

        if ($driver === 'sync') {
            $this->checkWarn("Queue: Using 'sync' driver (not recommended for production)");
            $this->line("   Set QUEUE_CONNECTION=database in .env");
        } else {
            $this->checkPass("Queue: Configured with '{$driver}' driver");

            if ($this->option('verbose')) {
                $this->line("   Connection: {$connection}");
            }
        }

        // Check if queue worker is running
        if ($driver !== 'sync') {
            $result = Process::run("ps aux | grep 'queue:work' | grep -v grep");

            if ($result->successful() && !empty(trim($result->output()))) {
                $this->checkPass("Queue Worker: Running");
            } else {
                $this->checkWarn("Queue Worker: Not running");
                $this->line("   Start with: php artisan queue:work");
            }
        }
    }

    protected function checkScraperTables(): void
    {
        try {
            $tables = [
                'scraper_runs',
                'scraper_logs',
                'scraped_rankings',
                'scraped_players',
                'scraped_matches',
                'scraped_transitions',
                'scraped_standings',
            ];

            $missing = [];

            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    $missing[] = $table;
                }
            }

            if (empty($missing)) {
                $this->checkPass("Database Tables: All scraper tables exist");
            } else {
                $this->checkFail("Database Tables: Missing - " . implode(', ', $missing));
                $this->line("   Run: php artisan migrate");
            }
        } catch (\Exception $e) {
            $this->checkWarn("Database Tables: Could not check - {$e->getMessage()}");
        }
    }

    protected function checkTestConnection(): void
    {
        $this->line("   Testing scraper connectivity...");

        try {
            $nodePath = config('scraper.browser.node_binary', 'node');
            $npmPath = config('scraper.browser.npm_binary', 'npm');
            $chromePath = config('scraper.browser.chrome_path');

            if (!$chromePath || !file_exists($chromePath)) {
                $this->checkWarn("Scraper Test: Skipped (Chrome not configured)");
                return;
            }

            $browsershot = Browsershot::url('https://www.google.com')
                ->setNodeBinary($nodePath)
                ->setNpmBinary($npmPath)
                ->setChromePath($chromePath)
                ->timeout(10)
                ->noSandbox();

            $html = $browsershot->bodyHtml();

            if (str_contains($html, 'Google')) {
                $this->checkPass("Scraper Test: Successfully fetched test page");
            } else {
                $this->checkWarn("Scraper Test: Page fetched but content unexpected");
            }
        } catch (\Exception $e) {
            $this->checkFail("Scraper Test: Failed - {$e->getMessage()}");

            if ($this->option('verbose')) {
                $this->line("   " . $e->getTraceAsString());
            }
        }
    }

    protected function checkPass(string $message): void
    {
        $this->line("  <fg=green>✓</> {$message}");
        $this->passed++;
    }

    protected function checkFail(string $message): void
    {
        $this->line("  <fg=red>✗</> {$message}");
        $this->failed++;
    }

    protected function checkWarn(string $message): void
    {
        $this->line("  <fg=yellow>⚠</> {$message}");
        $this->warnings++;
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║                    SUMMARY                             ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->line("  Passed:   <fg=green>{$this->passed}</>");
        $this->line("  Failed:   <fg=red>{$this->failed}</>");
        $this->line("  Warnings: <fg=yellow>{$this->warnings}</>");

        $this->newLine();

        if ($this->failed > 0) {
            $this->error("⚠  Scraper is NOT ready. Please fix the issues above.");
            $this->newLine();
            $this->line("Run with --verbose for detailed information:");
            $this->line("  <fg=cyan>php artisan scraper:check --verbose</>");
        } elseif ($this->warnings > 0) {
            $this->checkWarn("⚠  Scraper is functional but has warnings.");
            $this->checkWarn("   Consider addressing warnings for optimal performance.");
        } else {
            $this->info("✓  All checks passed! Scraper is ready to run.");
        }

        $this->newLine();
    }

    protected function suggestNodeInstall(): void
    {
        $this->line("   Install Node.js:");
        $this->line("   - Using nvm: curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash");
        $this->line("   - Then: nvm install --lts");
        $this->line("   - Update .env: SCRAPER_NODE_BINARY=/path/to/node");
    }

    protected function suggestChromeInstall(): void
    {
        $this->line("   Install Chrome/Chromium:");
        $this->line("   - Ubuntu/Debian: sudo apt-get install chromium-browser");
        $this->line("   - CentOS/RHEL: sudo yum install chromium");
        $this->line("   - macOS: brew install --cask google-chrome");
        $this->line("   - Update .env: SCRAPER_CHROME_PATH=/path/to/chrome");
    }
}
