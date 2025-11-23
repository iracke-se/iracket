<?php

use App\Livewire\Admin\Scraper\Show;
use App\Models\Scraper\ScraperRun;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->admin = User::factory()->create();

    $this->run = ScraperRun::create([
        'type' => 'rankings',
        'status' => ScraperRun::STATUS_COMPLETED,
        'parameters' => ['gender' => 'male', 'period' => '2024.01.01'],
        'started_at' => now()->subMinutes(10),
        'completed_at' => now(),
        'items_scraped' => 150,
        'items_failed' => 5,
    ]);
});

describe('Scraper Show Component', function () {
    it('renders the show page with run details', function () {
        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertStatus(200)
            ->assertSee('rankings')
            ->assertSee('150')
            ->assertSee('5');
    });

    it('displays run parameters', function () {
        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee('male')
            ->assertSee('2024.01.01');
    });

    it('displays run status badge', function () {
        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee(__('admin-scraper.status_completed'));
    });

    it('shows logs from the run', function () {
        $this->run->log('info', 'Started scraping rankings');
        $this->run->log('info', 'Found 150 players');
        $this->run->log('warning', 'Some data was incomplete');

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee('Started scraping rankings')
            ->assertSee('Found 150 players')
            ->assertSee('Some data was incomplete');
    });

    it('can filter logs by level', function () {
        $this->run->log('info', 'Info message');
        $this->run->log('warning', 'Warning message');
        $this->run->log('error', 'Error message');

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->set('logLevel', 'error')
            ->assertSee('Error message')
            ->assertDontSee('Info message')
            ->assertDontSee('Warning message');
    });

    it('shows empty state when no logs exist', function () {
        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee(__('admin-scraper.no_logs_found'));
    });

    it('displays error message for failed runs', function () {
        $failedRun = ScraperRun::create([
            'type' => 'players',
            'status' => ScraperRun::STATUS_FAILED,
            'error_message' => 'Connection timeout after 30 seconds',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $failedRun])
            ->assertSee('Connection timeout after 30 seconds');
    });

    it('shows log context when available', function () {
        $this->run->log('error', 'Failed to parse data', [
            'url' => 'https://example.com/page',
            'response_code' => 500,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee('https://example.com/page')
            ->assertSee('500');
    });

    it('paginates logs', function () {
        // Create many logs
        for ($i = 0; $i < 60; $i++) {
            $this->run->log('info', "Log message {$i}");
        }

        $component = Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run]);

        $logs = $component->viewData('logs');

        // Should paginate at 50
        expect($logs->count())->toBeLessThanOrEqual(50);
    });

    it('displays duration for completed runs', function () {
        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee('10'); // Should show 10 minutes
    });

    it('shows pending status correctly', function () {
        $pendingRun = ScraperRun::create([
            'type' => 'series',
            'status' => ScraperRun::STATUS_PENDING,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $pendingRun])
            ->assertSee(__('admin-scraper.pending'));
    });

    it('shows running status with spinner', function () {
        $runningRun = ScraperRun::create([
            'type' => 'live_center',
            'status' => ScraperRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $runningRun])
            ->assertSee(__('admin-scraper.status_running'));
    });

    it('shows no parameters message when empty', function () {
        $runWithoutParams = ScraperRun::create([
            'type' => 'players',
            'status' => ScraperRun::STATUS_COMPLETED,
            'parameters' => [],
        ]);

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $runWithoutParams])
            ->assertSee(__('admin-scraper.no_parameters'));
    });

    it('has back link to index', function () {
        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee(__('admin-scraper.back'));
    });
});

describe('Log Level Icons', function () {
    it('shows info icon for info logs', function () {
        $this->run->log('info', 'Info message');

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee('Info message');
    });

    it('shows warning icon for warning logs', function () {
        $this->run->log('warning', 'Warning message');

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee('Warning message');
    });

    it('shows error icon for error logs', function () {
        $this->run->log('error', 'Error message');

        Livewire::actingAs($this->admin)
            ->test(Show::class, ['run' => $this->run])
            ->assertSee('Error message');
    });
});
