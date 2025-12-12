<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class RestoreBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore {backup? : The backup filename to restore} {--latest : Restore the latest backup} {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore database from a backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $backupPath = storage_path('app/private/iRacket');

        if (!File::exists($backupPath)) {
            $this->error('No backups found!');
            $this->line("Looking in: {$backupPath}");
            return Command::FAILURE;
        }

        // Get all backup files
        $backups = collect(File::files($backupPath))
            ->filter(fn($file) => $file->getExtension() === 'zip')
            ->sortByDesc(fn($file) => $file->getMTime())
            ->values();

        if ($backups->isEmpty()) {
            $this->error('No backup files found!');
            return Command::FAILURE;
        }

        // Determine which backup to restore
        $selectedBackup = null;

        if ($this->option('latest')) {
            $selectedBackup = $backups->first();
            $this->info('Selected latest backup: ' . $selectedBackup->getFilename());
        } elseif ($this->argument('backup')) {
            $filename = $this->argument('backup');
            $selectedBackup = $backups->first(fn($file) => $file->getFilename() === $filename);

            if (!$selectedBackup) {
                $this->error("Backup file not found: {$filename}");
                return Command::FAILURE;
            }
        } else {
            // Interactive selection
            $this->info('Available backups:');
            $this->newLine();

            $choices = $backups->map(function ($file, $index) {
                $size = $this->formatBytes($file->getSize());
                $date = date('Y-m-d H:i:s', $file->getMTime());
                return sprintf('[%d] %s (%s) - %s', $index + 1, $file->getFilename(), $size, $date);
            })->toArray();

            foreach ($choices as $choice) {
                $this->line($choice);
            }

            $this->newLine();
            $selection = $this->ask('Which backup would you like to restore? (number)');

            if (!is_numeric($selection) || $selection < 1 || $selection > $backups->count()) {
                $this->error('Invalid selection!');
                return Command::FAILURE;
            }

            $selectedBackup = $backups[$selection - 1];
        }

        // Confirm restoration
        if (!$this->option('force')) {
            $this->newLine();
            $this->warn('⚠️  WARNING: This will replace ALL data in your current database!');
            $this->line('Backup: ' . $selectedBackup->getFilename());
            $this->line('Database: ' . config('database.connections.'.config('database.default').'.database'));
            $this->newLine();

            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Restoration cancelled.');
                return Command::SUCCESS;
            }
        }

        // Extract backup
        $this->info('Extracting backup...');
        $extractPath = storage_path('app/backup-temp/restore-' . time());
        File::makeDirectory($extractPath, 0755, true);

        $zip = new ZipArchive;
        if ($zip->open($selectedBackup->getPathname()) !== true) {
            $this->error('Failed to open backup file!');
            return Command::FAILURE;
        }

        $zip->extractTo($extractPath);
        $zip->close();

        // Find SQL file
        $sqlFiles = File::glob($extractPath . '/db-dumps/*.sql');

        if (empty($sqlFiles)) {
            $this->error('No SQL dump found in backup!');
            File::deleteDirectory($extractPath);
            return Command::FAILURE;
        }

        $sqlFile = $sqlFiles[0];
        $this->info('Found database dump: ' . basename($sqlFile));

        // Get database connection info
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];

        // Import SQL dump
        $this->info('Restoring database...');
        $this->newLine();

        try {
            // Drop all tables first
            $this->line('Dropping existing tables...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            $tables = DB::select('SHOW TABLES');
            $dbName = 'Tables_in_' . $database;
            foreach ($tables as $table) {
                $tableName = $table->$dbName;
                DB::statement("DROP TABLE `{$tableName}`");
                $this->line("  Dropped: {$tableName}");
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Import SQL file
            $this->newLine();
            $this->line('Importing SQL dump...');

            $command = sprintf(
                'mysql -h%s -P%s -u%s %s %s < %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                $password ? '-p' . escapeshellarg($password) : '',
                escapeshellarg($database),
                escapeshellarg($sqlFile)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->error('Failed to import database!');
                $this->line('Error output: ' . implode("\n", $output));
                return Command::FAILURE;
            }

            // Cleanup
            File::deleteDirectory($extractPath);

            $this->newLine();
            $this->info('✓ Database restored successfully!');
            $this->newLine();
            $this->line('Restored from: ' . $selectedBackup->getFilename());
            $this->line('Database: ' . $database);

            // Clear caches
            $this->call('cache:clear');
            $this->call('config:clear');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Restoration failed: ' . $e->getMessage());
            File::deleteDirectory($extractPath);
            return Command::FAILURE;
        }
    }

    /**
     * Format bytes to human readable size
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
