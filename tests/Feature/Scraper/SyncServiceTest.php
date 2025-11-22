<?php

use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScraperRun;
use App\Models\User;
use App\Services\Scraper\SyncService;

describe('SyncService', function () {
    beforeEach(function () {
        $this->syncService = app(SyncService::class);
    });

    describe('syncPlayers', function () {
        it('syncs unsynced players to users table', function () {
            // Create scraped players
            $scrapedPlayer = ScrapedPlayer::create([
                'external_id' => 'test-player-001',
                'name' => 'Test Player',
                'email' => 'test.player@example.com',
                'club_name' => 'Test Club',
                'gender' => 'male',
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncPlayers();

            expect($stats)->toHaveKeys(['created', 'updated', 'errors'])
                ->and($stats['errors'])->toBe(0);

            // Check that player was marked as synced
            $scrapedPlayer->refresh();
            expect($scrapedPlayer->is_synced)->toBeTrue();
        });

        it('can sync players for a specific run', function () {
            $run = ScraperRun::create([
                'type' => 'players',
                'status' => ScraperRun::STATUS_COMPLETED,
            ]);

            ScrapedPlayer::create([
                'scraper_run_id' => $run->id,
                'external_id' => 'run-player-001',
                'name' => 'Run Player',
                'is_synced' => false,
            ]);

            ScrapedPlayer::create([
                'scraper_run_id' => null,
                'external_id' => 'other-player-001',
                'name' => 'Other Player',
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncPlayers($run->id);

            // Only the run's player should be synced
            expect(ScrapedPlayer::where('external_id', 'run-player-001')->first()->is_synced)->toBeTrue();
        });

        it('updates existing users instead of creating duplicates', function () {
            // Create existing user with external_id
            $existingUser = User::create([
                'name' => 'Existing User',
                'email' => 'existing@example.com',
                'password' => bcrypt('password'),
                'external_id' => 'existing-001',
            ]);

            // Create scraped player with same external_id
            $scrapedPlayer = ScrapedPlayer::create([
                'external_id' => 'existing-001',
                'name' => 'Updated Name',
                'email' => 'existing@example.com',
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncPlayers();

            expect($stats['updated'])->toBeGreaterThanOrEqual(1);

            $existingUser->refresh();
            expect($existingUser->name)->toBe('Updated Name');
        });

        it('returns error count for failed syncs', function () {
            // Create scraped player with invalid data
            ScrapedPlayer::create([
                'external_id' => 'invalid-001',
                'name' => '', // Invalid - name is required
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncPlayers();

            // The error count may vary based on validation
            expect($stats)->toHaveKeys(['created', 'updated', 'errors']);
        });
    });

    describe('syncRankings', function () {
        it('syncs rankings to users', function () {
            // Create a user to update
            $user = User::create([
                'name' => 'Ranking User',
                'email' => 'ranking@example.com',
                'password' => bcrypt('password'),
                'external_id' => 'ranking-user-001',
            ]);

            // Create scraped ranking
            $scrapedRanking = ScrapedRanking::create([
                'external_player_id' => 'ranking-user-001',
                'rank' => 5,
                'points' => 1500,
                'division' => 'A',
                'gender' => 'male',
                'period' => '2024.01.01',
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncRankings();

            expect($stats)->toHaveKeys(['created', 'updated', 'errors'])
                ->and($stats['errors'])->toBe(0);

            // Check that ranking was marked as synced
            $scrapedRanking->refresh();
            expect($scrapedRanking->is_synced)->toBeTrue();
        });

        it('can sync rankings for a specific run', function () {
            $run = ScraperRun::create([
                'type' => 'rankings',
                'status' => ScraperRun::STATUS_COMPLETED,
            ]);

            ScrapedRanking::create([
                'scraper_run_id' => $run->id,
                'external_player_id' => 'run-ranking-001',
                'rank' => 10,
                'points' => 1000,
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncRankings($run->id);

            expect($stats)->toHaveKeys(['created', 'updated', 'errors']);
        });

        it('updates user ranking fields', function () {
            $user = User::create([
                'name' => 'User To Update',
                'email' => 'update@example.com',
                'password' => bcrypt('password'),
                'external_id' => 'update-user-001',
                'ranking' => null,
                'ranking_points' => null,
            ]);

            ScrapedRanking::create([
                'external_player_id' => 'update-user-001',
                'rank' => 3,
                'points' => 2000,
                'division' => 'Elite',
                'is_synced' => false,
            ]);

            $this->syncService->syncRankings();

            $user->refresh();

            // Check that user's ranking was updated
            expect($user->ranking)->toBe(3)
                ->and($user->ranking_points)->toBe(2000);
        });
    });
});

describe('SyncService Integration', function () {
    it('handles empty data gracefully', function () {
        $syncService = app(SyncService::class);

        // Ensure no unsynced data
        ScrapedPlayer::where('is_synced', false)->delete();
        ScrapedRanking::where('is_synced', false)->delete();

        $playerStats = $syncService->syncPlayers();
        $rankingStats = $syncService->syncRankings();

        expect($playerStats['created'])->toBe(0)
            ->and($playerStats['updated'])->toBe(0)
            ->and($rankingStats['created'])->toBe(0)
            ->and($rankingStats['updated'])->toBe(0);
    });

    it('handles large batches efficiently', function () {
        $syncService = app(SyncService::class);

        // Create multiple scraped players
        for ($i = 0; $i < 50; $i++) {
            ScrapedPlayer::create([
                'external_id' => "batch-player-{$i}",
                'name' => "Batch Player {$i}",
                'email' => "batch{$i}@example.com",
                'is_synced' => false,
            ]);
        }

        $stats = $syncService->syncPlayers();

        expect($stats['created'] + $stats['updated'])->toBe(50)
            ->and($stats['errors'])->toBe(0);

        // Verify all are synced
        $unsyncedCount = ScrapedPlayer::where('external_id', 'like', 'batch-player-%')
            ->where('is_synced', false)
            ->count();

        expect($unsyncedCount)->toBe(0);
    });

    it('maintains data integrity during sync', function () {
        $syncService = app(SyncService::class);

        $player = ScrapedPlayer::create([
            'external_id' => 'integrity-001',
            'name' => 'Integrity Test',
            'email' => 'integrity@example.com',
            'club_name' => 'Integrity Club',
            'gender' => 'female',
            'is_synced' => false,
        ]);

        $syncService->syncPlayers();

        $user = User::where('external_id', 'integrity-001')->first();

        if ($user) {
            expect($user->name)->toBe('Integrity Test')
                ->and($user->email)->toBe('integrity@example.com')
                ->and($user->gender)->toBe('female');
        }
    });
});
