<?php

use App\Models\Scraper\ScraperRun;
use App\Models\Scraper\ScraperLog;

describe('ScraperRun Model', function () {
    it('can create a scraper run', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_PENDING,
            'parameters' => ['gender' => 'male'],
        ]);

        expect($run)->toBeInstanceOf(ScraperRun::class)
            ->and($run->type)->toBe('rankings')
            ->and($run->status)->toBe(ScraperRun::STATUS_PENDING)
            ->and($run->parameters)->toBe(['gender' => 'male']);
    });

    it('has correct status constants', function () {
        expect(ScraperRun::STATUS_PENDING)->toBe('pending')
            ->and(ScraperRun::STATUS_RUNNING)->toBe('running')
            ->and(ScraperRun::STATUS_COMPLETED)->toBe('completed')
            ->and(ScraperRun::STATUS_FAILED)->toBe('failed');
    });

    it('can mark run as running', function () {
        $run = ScraperRun::create([
            'type' => 'players',
            'status' => ScraperRun::STATUS_PENDING,
        ]);

        $run->markAsRunning();

        expect($run->status)->toBe(ScraperRun::STATUS_RUNNING)
            ->and($run->started_at)->not->toBeNull();
    });

    it('can mark run as completed', function () {
        $run = ScraperRun::create([
            'type' => 'players',
            'status' => ScraperRun::STATUS_RUNNING,
            'started_at' => now()->subMinutes(5),
        ]);

        $run->markAsCompleted();

        expect($run->status)->toBe(ScraperRun::STATUS_COMPLETED)
            ->and($run->completed_at)->not->toBeNull();
    });

    it('can mark run as failed with error message', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $run->markAsFailed('Connection timeout');

        expect($run->status)->toBe(ScraperRun::STATUS_FAILED)
            ->and($run->error_message)->toBe('Connection timeout')
            ->and($run->completed_at)->not->toBeNull();
    });

    it('can increment items scraped', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
            'items_scraped' => 0,
        ]);

        $run->incrementScraped(10);

        expect($run->items_scraped)->toBe(10);

        $run->incrementScraped(5);

        expect($run->items_scraped)->toBe(15);
    });

    it('can increment items failed', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
            'items_failed' => 0,
        ]);

        $run->incrementFailed(3);

        expect($run->items_failed)->toBe(3);
    });

    it('can log messages', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
        ]);

        $run->log('info', 'Started scraping', ['page' => 1]);

        $log = $run->logs()->first();

        expect($log)->not->toBeNull()
            ->and($log->level)->toBe('info')
            ->and($log->message)->toBe('Started scraping')
            ->and($log->context)->toBe(['page' => 1]);
    });

    it('has relationship with logs', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
        ]);

        $run->log('info', 'Test message 1');
        $run->log('warning', 'Test message 2');
        $run->log('error', 'Test message 3');

        expect($run->logs)->toHaveCount(3);
    });

    it('calculates duration correctly', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_COMPLETED,
            'started_at' => now()->subMinutes(5)->subSeconds(30),
            'completed_at' => now(),
        ]);

        expect($run->duration)->toContain('5')
            ->and($run->duration)->toContain('30');
    });

    it('returns null duration when not started', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_PENDING,
        ]);

        expect($run->duration)->toBeNull();
    });

    it('casts parameters to array', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_PENDING,
            'parameters' => ['gender' => 'female', 'period' => '2024.01.01'],
        ]);

        $run->refresh();

        expect($run->parameters)->toBeArray()
            ->and($run->parameters['gender'])->toBe('female')
            ->and($run->parameters['period'])->toBe('2024.01.01');
    });

    it('casts dates correctly', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
            'started_at' => '2024-01-15 10:30:00',
        ]);

        expect($run->started_at)->toBeInstanceOf(\Carbon\Carbon::class)
            ->and($run->created_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('can filter by status', function () {
        ScraperRun::create(['type' => 'rankings', 'status' => ScraperRun::STATUS_PENDING]);
        ScraperRun::create(['type' => 'players', 'status' => ScraperRun::STATUS_RUNNING]);
        ScraperRun::create(['type' => 'series', 'status' => ScraperRun::STATUS_COMPLETED]);

        $pendingRuns = ScraperRun::where('status', ScraperRun::STATUS_PENDING)->get();
        $runningRuns = ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->get();

        expect($pendingRuns)->toHaveCount(1)
            ->and($runningRuns)->toHaveCount(1);
    });

    it('can filter by type', function () {
        ScraperRun::create(['type' => 'rankings', 'status' => ScraperRun::STATUS_PENDING]);
        ScraperRun::create(['type' => 'rankings', 'status' => ScraperRun::STATUS_COMPLETED]);
        ScraperRun::create(['type' => 'players', 'status' => ScraperRun::STATUS_PENDING]);

        $rankingsRuns = ScraperRun::where('type', 'rankings')->get();

        expect($rankingsRuns)->toHaveCount(2);
    });
});

describe('ScraperLog Model', function () {
    it('belongs to a scraper run', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
        ]);

        $log = ScraperLog::create([
            'scraper_run_id' => $run->id,
            'level' => 'info',
            'message' => 'Test message',
        ]);

        expect($log->run)->toBeInstanceOf(ScraperRun::class)
            ->and($log->run->id)->toBe($run->id);
    });

    it('casts context to array', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
        ]);

        $log = ScraperLog::create([
            'scraper_run_id' => $run->id,
            'level' => 'error',
            'message' => 'Failed to scrape',
            'context' => ['url' => 'https://example.com', 'error' => 'Timeout'],
        ]);

        $log->refresh();

        expect($log->context)->toBeArray()
            ->and($log->context['url'])->toBe('https://example.com');
    });
});
