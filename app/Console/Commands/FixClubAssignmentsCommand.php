<?php

namespace App\Console\Commands;

use App\Models\Club;
use App\Models\User;
use App\Models\Scraper\ScrapedPlayer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixClubAssignmentsCommand extends Command
{
    protected $signature = 'fx:fix-clubs
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Fix club assignments for users based on scraped player data';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════╗');
        $this->info('║           FIX CLUB ASSIGNMENTS FROM SCRAPED DATA       ║');
        $this->info('╚════════════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $stats = [
            'users_checked' => 0,
            'users_fixed' => 0,
            'users_no_scraped_data' => 0,
            'users_no_club_in_scraped' => 0,
            'clubs_created' => 0,
        ];

        // Get all users without club_id
        $this->info('Finding users without club assignments...');
        $users = User::whereNull('club_id')->get();
        $stats['users_checked'] = $users->count();

        $this->line("Found {$users->count()} users without clubs");
        $this->newLine();

        if ($users->isEmpty()) {
            $this->info('No users found without club assignments!');
            return self::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            // Find most recent scraped player data for this user
            $scrapedPlayer = ScrapedPlayer::where('synced_user_id', $user->id)
                ->whereNotNull('club_name')
                ->latest()
                ->first();

            if (!$scrapedPlayer) {
                $stats['users_no_scraped_data']++;
                $progressBar->advance();
                continue;
            }

            if (empty($scrapedPlayer->club_name)) {
                $stats['users_no_club_in_scraped']++;
                $progressBar->advance();
                continue;
            }

            // Normalize club name
            $clubName = trim($scrapedPlayer->club_name);
            $clubName = rtrim($clubName, '*');
            $clubName = trim($clubName);
            $slug = Str::slug($clubName);

            // Find or create club
            $club = Club::where('slug', $slug)->first();

            if (!$club) {
                if (!$dryRun) {
                    $club = Club::create([
                        'name' => $clubName,
                        'slug' => $slug,
                        'description' => null,
                    ]);
                    $stats['clubs_created']++;
                }
            }

            // Assign club to user
            if ($club && !$dryRun) {
                $user->update(['club_id' => $club->id]);
            }

            $stats['users_fixed']++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display results
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✓ CLUB ASSIGNMENT FIX COMPLETED');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Users checked', $stats['users_checked']],
                ['Users fixed', $stats['users_fixed']],
                ['Clubs created', $stats['clubs_created']],
                ['Users without scraped data', $stats['users_no_scraped_data']],
                ['Users without club in scraped data', $stats['users_no_club_in_scraped']],
            ]
        );

        $this->newLine();

        if ($dryRun) {
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->info('Club assignments have been fixed!');
        }

        $this->newLine();

        return self::SUCCESS;
    }
}
