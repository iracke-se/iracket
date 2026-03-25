<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedTerms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seed:terms {--fresh : Truncate terms table before seeding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed all terms content (Terms & Conditions, Privacy Policy, Bubbler, About, Matches)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Seeding terms...');

        if ($this->option('fresh')) {
            $this->warn('Truncating terms table...');
            \App\Models\Term::truncate();
        }

        $seeders = [
            'TermsSeeder' => 'Terms and Conditions, Privacy Policy',
            'BubblerTermsSeeder' => 'Bubbler',
            'AboutTermsSeeder' => 'About Us',
            'MatchesTermsSeeder' => 'Matches',
        ];

        foreach ($seeders as $seeder => $description) {
            $this->line("  Seeding {$description}...");

            try {
                Artisan::call('db:seed', [
                    '--class' => "Database\\Seeders\\{$seeder}",
                ]);
                $this->info("  ✓ {$description} seeded successfully");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed to seed {$description}: {$e->getMessage()}");
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('All terms have been seeded successfully!');

        $termCount = \App\Models\Term::count();
        $this->line("  Total terms in database: {$termCount}");

        return Command::SUCCESS;
    }
}
