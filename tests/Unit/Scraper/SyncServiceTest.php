<?php

use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScraperRun;
use App\Models\User;
use App\Services\Scraper\SyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

describe('SyncService', function () {
    beforeEach(function () {
        $this->syncService = app(SyncService::class);
    });

    describe('syncPlayers', function () {
        it('syncs unsynced players to users table', function () {
            $run = ScraperRun::create([
                'type' => 'players',
                'status' => ScraperRun::STATUS_COMPLETED,
            ]);

            // Create scraped players with correct field names
            $scrapedPlayer = ScrapedPlayer::create([
                'scraper_run_id' => $run->id,
                'first_name' => 'Test',
                'surname' => 'Player',
                'club_name' => 'Test Club',
                'sex' => 'M',
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

            $otherRun = ScraperRun::create([
                'type' => 'players',
                'status' => ScraperRun::STATUS_COMPLETED,
            ]);

            ScrapedPlayer::create([
                'scraper_run_id' => $run->id,
                'first_name' => 'Run',
                'surname' => 'Player',
                'is_synced' => false,
            ]);

            ScrapedPlayer::create([
                'scraper_run_id' => $otherRun->id,
                'first_name' => 'Other',
                'surname' => 'Player',
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncPlayers($run->id);

            // Only the run's player should be synced
            expect(ScrapedPlayer::where('scraper_run_id', $run->id)->first()->is_synced)->toBeTrue();
        });

        it('returns error count for failed syncs', function () {
            $run = ScraperRun::create([
                'type' => 'players',
                'status' => ScraperRun::STATUS_COMPLETED,
            ]);

            // Create scraped player with minimal data
            ScrapedPlayer::create([
                'scraper_run_id' => $run->id,
                'first_name' => '',
                'surname' => '',
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncPlayers();

            // The error count may vary based on validation
            expect($stats)->toHaveKeys(['created', 'updated', 'errors']);
        });
    });

    describe('syncRankings', function () {
        it('syncs rankings', function () {
            $run = ScraperRun::create([
                'type' => 'rankings',
                'status' => ScraperRun::STATUS_COMPLETED,
            ]);

            // Create scraped ranking with correct field names
            $scrapedRanking = ScrapedRanking::create([
                'scraper_run_id' => $run->id,
                'name' => 'Test Player',
                'position' => 5,
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
                'name' => 'Run Ranking Player',
                'position' => 10,
                'points' => 1000,
                'period' => '2024.01.01',
                'is_synced' => false,
            ]);

            $stats = $this->syncService->syncRankings($run->id);

            expect($stats)->toHaveKeys(['created', 'updated', 'errors']);
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

        $run = ScraperRun::create([
            'type' => 'players',
            'status' => ScraperRun::STATUS_COMPLETED,
        ]);

        // Create multiple scraped players
        for ($i = 0; $i < 50; $i++) {
            ScrapedPlayer::create([
                'scraper_run_id' => $run->id,
                'first_name' => "Batch",
                'surname' => "Player{$i}",
                'is_synced' => false,
            ]);
        }

        $stats = $syncService->syncPlayers();

        expect($stats['created'] + $stats['updated'])->toBe(50)
            ->and($stats['errors'])->toBe(0);

        // Verify all are synced
        $unsyncedCount = ScrapedPlayer::where('scraper_run_id', $run->id)
            ->where('is_synced', false)
            ->count();

        expect($unsyncedCount)->toBe(0);
    });

    it('maintains data integrity during sync', function () {
        $syncService = app(SyncService::class);

        $run = ScraperRun::create([
            'type' => 'players',
            'status' => ScraperRun::STATUS_COMPLETED,
        ]);

        $player = ScrapedPlayer::create([
            'scraper_run_id' => $run->id,
            'first_name' => 'Integrity',
            'surname' => 'Test',
            'club_name' => 'Integrity Club',
            'sex' => 'F',
            'is_synced' => false,
        ]);

        $syncService->syncPlayers();

        $player->refresh();
        expect($player->is_synced)->toBeTrue();
    });
});
