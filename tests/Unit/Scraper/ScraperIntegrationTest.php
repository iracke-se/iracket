<?php

use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScrapedTransition;
use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScrapedStanding;
use App\Models\Scraper\ScraperRun;
use App\Services\Scraper\RankingsScraper;
use App\Services\Scraper\PlayerListScraper;
use App\Services\Scraper\TransitionsScraper;
use App\Services\Scraper\SeriesScraper;
use App\Services\Scraper\LiveCenterScraper;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;

uses(DatabaseTransactions::class);

/*
|--------------------------------------------------------------------------
| Integration Tests - Actually Scrape Real Content
|--------------------------------------------------------------------------
|
| These tests verify that the scrapers can:
| 1. Connect to the real source (profixio.com)
| 2. Extract data with all expected fields
| 3. Store data correctly in the database
|
| Note: These tests require a working internet connection and may take time.
|
*/

describe('Rankings Scraper Integration', function () {
    it('can scrape rankings and return data with all expected fields', function () {
        $scraper = app(RankingsScraper::class);

        // Scrape with limited scope - just one period and gender
        $parameters = [
            'gender' => 'male',
            'limit_periods' => 1, // Only scrape 1 period for testing
            'limit_divisions' => 1, // Only scrape 1 division
        ];

        try {
            // scrape() returns the ScraperRun
            $run = $scraper->scrape($parameters);

            // Get scraped rankings
            $rankings = ScrapedRanking::where('scraper_run_id', $run->id)->get();

            // Verify we got some data
            expect($rankings->count())->toBeGreaterThan(0);

            // Verify first ranking has all expected fields
            $firstRanking = $rankings->first();

            // Required fields check
            expect($firstRanking->scraper_run_id)->toBe($run->id)
                ->and($firstRanking->period)->not->toBeEmpty()
                ->and($firstRanking->name)->not->toBeEmpty()
                ->and($firstRanking->position)->toBeInt()
                ->and($firstRanking->position)->toBeGreaterThan(0)
                ->and($firstRanking->points)->toBeInt()
                ->and($firstRanking->is_synced)->toBeFalse();

            // Log success
            Log::channel('scraper')->info('Rankings scraper integration test passed', [
                'items_scraped' => $rankings->count(),
                'sample_data' => [
                    'name' => $firstRanking->name,
                    'position' => $firstRanking->position,
                    'points' => $firstRanking->points,
                    'club' => $firstRanking->club,
                ],
            ]);

        } catch (\Exception $e) {
            Log::channel('scraper')->error('Rankings scraper integration test failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }); // 2 minute timeout

    it('validates all ranking fields are properly formatted', function () {
        $scraper = app(RankingsScraper::class);

        $run = $scraper->scrape([
            'gender' => 'male',
            'limit_periods' => 1,
            'limit_divisions' => 1,
        ]);

        $rankings = ScrapedRanking::where('scraper_run_id', $run->id)->get();

        // Validate each ranking
        foreach ($rankings as $ranking) {
            // Position should be numeric and positive
            expect($ranking->position)->toBeInt()->toBeGreaterThan(0);

            // Points should be numeric
            expect($ranking->points)->toBeInt();

            // Name should not be empty
            expect($ranking->name)->not->toBeEmpty();

            // Period should be in format YYYY.MM.DD
            expect($ranking->period)->toMatch('/^\d{4}\.\d{2}\.\d{2}$/');

            // Gender should be male or female
            if ($ranking->gender) {
                expect($ranking->gender)->toBeIn(['male', 'female']);
            }
        }
    });
});

describe('Players Scraper Integration', function () {
    it('can scrape players and return data with all expected fields', function () {
        $scraper = app(PlayerListScraper::class);

        $parameters = [
            'limit_periods' => 1,
            'limit_clubs' => 1, // Only scrape 1 club for testing
        ];

        try {
            $run = $scraper->scrape($parameters);

            $players = ScrapedPlayer::where('scraper_run_id', $run->id)->get();

            expect($players->count())->toBeGreaterThan(0);

            $firstPlayer = $players->first();

            // Required fields check
            expect($firstPlayer->scraper_run_id)->toBe($run->id)
                ->and($firstPlayer->first_name)->not->toBeEmpty()
                ->and($firstPlayer->surname)->not->toBeEmpty()
                ->and($firstPlayer->is_synced)->toBeFalse();

            Log::channel('scraper')->info('Players scraper integration test passed', [
                'items_scraped' => $players->count(),
                'sample_data' => [
                    'first_name' => $firstPlayer->first_name,
                    'surname' => $firstPlayer->surname,
                    'club_name' => $firstPlayer->club_name,
                    'sex' => $firstPlayer->sex,
                ],
            ]);

        } catch (\Exception $e) {
            Log::channel('scraper')->error('Players scraper integration test failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }); // 3 minute timeout

    it('validates all player fields are properly formatted', function () {
        $scraper = app(PlayerListScraper::class);

        $run = $scraper->scrape([
            'limit_periods' => 1,
            'limit_clubs' => 1,
        ]);

        $players = ScrapedPlayer::where('scraper_run_id', $run->id)->get();

        foreach ($players as $player) {
            // Name fields should not be empty
            expect($player->first_name)->not->toBeEmpty();
            expect($player->surname)->not->toBeEmpty();

            // Sex should be a valid value if present
            if ($player->sex) {
                expect($player->sex)->toBeIn(['M', 'F', 'K', 'Male', 'Female', 'Mann', 'Kvinne']);
            }
        }
    });
});

describe('Transitions Scraper Integration', function () {
    it('can scrape transitions and return data with all expected fields', function () {
        $scraper = app(TransitionsScraper::class);

        $parameters = [
            'limit_periods' => 1,
        ];

        try {
            $run = $scraper->scrape($parameters);

            $transitions = ScrapedTransition::where('scraper_run_id', $run->id)->get();

            // Transitions might be empty in some periods
            if ($transitions->count() > 0) {
                $firstTransition = $transitions->first();

                expect($firstTransition->scraper_run_id)->toBe($run->id)
                    ->and($firstTransition->first_name)->not->toBeEmpty()
                    ->and($firstTransition->surname)->not->toBeEmpty()
                    ->and($firstTransition->is_synced)->toBeFalse();

                Log::channel('scraper')->info('Transitions scraper integration test passed', [
                    'items_scraped' => $transitions->count(),
                    'sample_data' => [
                        'first_name' => $firstTransition->first_name,
                        'surname' => $firstTransition->surname,
                        'from_club' => $firstTransition->from_club,
                        'to_club' => $firstTransition->to_club,
                    ],
                ]);
            } else {
                Log::channel('scraper')->info('Transitions scraper ran but no transitions found in period');
            }

        } catch (\Exception $e) {
            Log::channel('scraper')->error('Transitions scraper integration test failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    });
});

describe('Live Center Scraper Integration', function () {
    it('can scrape matches and return data with all expected fields', function () {
        $scraper = app(LiveCenterScraper::class);

        $parameters = [
            'limit_divisions' => 1,
            'limit_periods' => 1,
        ];

        try {
            $run = $scraper->scrape($parameters);

            $matches = ScrapedMatch::where('scraper_run_id', $run->id)->get();

            if ($matches->count() > 0) {
                $firstMatch = $matches->first();

                expect($firstMatch->scraper_run_id)->toBe($run->id)
                    ->and($firstMatch->source)->toBe('live_center')
                    ->and($firstMatch->team1_name)->not->toBeEmpty()
                    ->and($firstMatch->team2_name)->not->toBeEmpty()
                    ->and($firstMatch->is_synced)->toBeFalse();

                Log::channel('scraper')->info('Live Center scraper integration test passed', [
                    'items_scraped' => $matches->count(),
                    'sample_data' => [
                        'team1' => $firstMatch->team1_name,
                        'team2' => $firstMatch->team2_name,
                        'score' => $firstMatch->score,
                    ],
                ]);
            } else {
                Log::channel('scraper')->info('Live Center scraper ran but no matches found');
            }

        } catch (\Exception $e) {
            Log::channel('scraper')->error('Live Center scraper integration test failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    });

    it('validates match scores are properly formatted', function () {
        $scraper = app(LiveCenterScraper::class);

        $run = $scraper->scrape([
            'limit_divisions' => 1,
            'limit_periods' => 1,
        ]);

        $matches = ScrapedMatch::where('scraper_run_id', $run->id)->get();

        foreach ($matches as $match) {
            // Team names should not be empty
            expect($match->team1_name)->not->toBeEmpty();
            expect($match->team2_name)->not->toBeEmpty();

            // Score should be in X-Y format if present
            if ($match->score && $match->score !== '') {
                expect($match->score)->toMatch('/^\d+-\d+$/');
            }

            // Winner should be valid if present
            if ($match->winner) {
                expect($match->winner)->toBeIn([$match->player1_name, $match->player2_name]);
            }
        }
    });
});

describe('Series Scraper Integration', function () {
    it('can scrape standings and return data with all expected fields', function () {
        $scraper = app(SeriesScraper::class);

        $parameters = [
            'limit_seasons' => 1,
        ];

        try {
            $run = $scraper->scrape($parameters);

            $standings = ScrapedStanding::where('scraper_run_id', $run->id)->get();

            if ($standings->count() > 0) {
                $firstStanding = $standings->first();

                expect($firstStanding->scraper_run_id)->toBe($run->id)
                    ->and($firstStanding->team_name)->not->toBeEmpty()
                    ->and($firstStanding->position)->toBeInt()
                    ->and($firstStanding->position)->toBeGreaterThan(0);

                Log::channel('scraper')->info('Series scraper integration test passed', [
                    'items_scraped' => $standings->count(),
                    'sample_data' => [
                        'team_name' => $firstStanding->team_name,
                        'position' => $firstStanding->position,
                        'points' => $firstStanding->points,
                    ],
                ]);
            } else {
                Log::channel('scraper')->info('Series scraper ran but no standings found');
            }

        } catch (\Exception $e) {
            Log::channel('scraper')->error('Series scraper integration test failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    });
});

/*
|--------------------------------------------------------------------------
| Field Structure Validation Tests
|--------------------------------------------------------------------------
|
| These tests verify the expected field structure for each model.
|
*/

describe('Expected Field Structure', function () {
    it('ScrapedRanking has all required fillable fields', function () {
        $expectedFields = [
            'scraper_run_id',
            'period',
            'division',
            'gender',
            'position',
            'position_change',
            'name',
            'born',
            'club',
            'points',
            'points_change',
            'is_synced',
            'synced_user_id',
        ];

        $model = new ScrapedRanking();
        $fillable = $model->getFillable();

        foreach ($expectedFields as $field) {
            expect($fillable)->toContain($field);
        }
    });

    it('ScrapedPlayer has all required fillable fields', function () {
        $expectedFields = [
            'scraper_run_id',
            'period',
            'club_name',
            'surname',
            'first_name',
            'sex',
            'date_of_birth',
            'license_type',
            'player_class',
            'is_synced',
            'synced_user_id',
        ];

        $model = new ScrapedPlayer();
        $fillable = $model->getFillable();

        foreach ($expectedFields as $field) {
            expect($fillable)->toContain($field);
        }
    });

    it('ScrapedTransition has all required fillable fields', function () {
        $expectedFields = [
            'scraper_run_id',
            'period',
            'surname',
            'first_name',
            'born',
            'from_club',
            'to_club',
            'completion_date',
            'is_synced',
        ];

        $model = new ScrapedTransition();
        $fillable = $model->getFillable();

        foreach ($expectedFields as $field) {
            expect($fillable)->toContain($field);
        }
    });

    it('ScrapedMatch has all required fillable fields', function () {
        $expectedFields = [
            'scraper_run_id',
            'source',
            'period',
            'division',
            'series_name',
            'team1_name',
            'team2_name',
            'player1_name',
            'player2_name',
            'score',
            'sets',
            'played_at',
            'winner',
            'is_synced',
            'synced_match_id',
        ];

        $model = new ScrapedMatch();
        $fillable = $model->getFillable();

        foreach ($expectedFields as $field) {
            expect($fillable)->toContain($field);
        }
    });

    it('ScrapedStanding has all required fillable fields', function () {
        $expectedFields = [
            'scraper_run_id',
            'period',
            'series_name',
            'session_name',
            'position',
            'team_name',
            'matches_played',
            'wins',
            'losses',
            'draws',
            'points',
            'goal_difference',
        ];

        $model = new ScrapedStanding();
        $fillable = $model->getFillable();

        foreach ($expectedFields as $field) {
            expect($fillable)->toContain($field);
        }
    });
});

/*
|--------------------------------------------------------------------------
| Scraper Run Logging Tests
|--------------------------------------------------------------------------
*/

describe('Scraper Run Logging', function () {
    it('creates log entries during scraping', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_PENDING,
        ]);

        // Log some test entries
        $run->log('info', 'Test info message', ['key' => 'value']);
        $run->log('warning', 'Test warning message');
        $run->log('error', 'Test error message', ['error_code' => 500]);

        $logs = $run->logs()->get();

        expect($logs->count())->toBe(3);
        expect($logs->where('level', 'info')->first()->message)->toBe('Test info message');
        expect($logs->where('level', 'warning')->first()->message)->toBe('Test warning message');
        expect($logs->where('level', 'error')->first()->message)->toBe('Test error message');
    });

    it('tracks scraper run status correctly', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_PENDING,
        ]);

        expect($run->status)->toBe(ScraperRun::STATUS_PENDING);

        $run->update(['status' => ScraperRun::STATUS_RUNNING, 'started_at' => now()]);
        expect($run->fresh()->status)->toBe(ScraperRun::STATUS_RUNNING);

        $run->update([
            'status' => ScraperRun::STATUS_COMPLETED,
            'completed_at' => now(),
            'items_scraped' => 100,
        ]);
        expect($run->fresh()->status)->toBe(ScraperRun::STATUS_COMPLETED);
        expect($run->fresh()->items_scraped)->toBe(100);
    });
});
