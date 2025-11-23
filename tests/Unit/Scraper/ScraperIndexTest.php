<?php

use App\Livewire\Admin\Scraper\Index;
use App\Models\Scraper\ScraperRun;
use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // Create a user for authentication
    $this->admin = User::factory()->create();
});

describe('Scraper Index Component', function () {
    it('renders the scraper index page', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->assertStatus(200)
            ->assertSee(__('admin-scraper.scraper_management'));
    });

    it('displays scraper runs in table', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_COMPLETED,
            'items_scraped' => 100,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->assertSee('rankings')
            ->assertSee('100');
    });

    it('can filter runs by type', function () {
        ScraperRun::create(['type' => 'rankings', 'status' => ScraperRun::STATUS_COMPLETED]);
        ScraperRun::create(['type' => 'players', 'status' => ScraperRun::STATUS_COMPLETED]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('typeFilter', 'rankings')
            ->assertSee('rankings')
            ->assertDontSee('players');
    });

    it('can filter runs by status', function () {
        ScraperRun::create(['type' => 'rankings', 'status' => ScraperRun::STATUS_COMPLETED]);
        ScraperRun::create(['type' => 'players', 'status' => ScraperRun::STATUS_FAILED]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('statusFilter', 'completed')
            ->assertSee('rankings');
    });

    it('can search runs', function () {
        ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_FAILED,
            'error_message' => 'Connection timeout error',
        ]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('search', 'timeout')
            ->assertSee('rankings');
    });

    it('displays stats correctly', function () {
        ScraperRun::create(['type' => 'rankings', 'status' => ScraperRun::STATUS_PENDING]);
        ScraperRun::create(['type' => 'players', 'status' => ScraperRun::STATUS_RUNNING]);
        ScraperRun::create(['type' => 'series', 'status' => ScraperRun::STATUS_COMPLETED]);
        ScraperRun::create(['type' => 'transitions', 'status' => ScraperRun::STATUS_FAILED]);

        $component = Livewire::actingAs($this->admin)->test(Index::class);

        $stats = $component->viewData('stats');

        expect($stats['total'])->toBeGreaterThanOrEqual(4)
            ->and($stats['running'])->toBeGreaterThanOrEqual(1)
            ->and($stats['completed'])->toBeGreaterThanOrEqual(1)
            ->and($stats['failed'])->toBeGreaterThanOrEqual(1);
    });

    it('can trigger a single scrape', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('scrapeType', 'players')
            ->call('triggerScrape')
            ->assertDispatched('notify');

        // Check that a run was created
        expect(ScraperRun::where('type', 'players')->exists())->toBeTrue();
    });

    it('validates scrape type is required', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('scrapeType', '')
            ->call('triggerScrape')
            ->assertDispatched('notify', function ($name, $data) {
                return $data['type'] === 'error';
            });
    });

    it('can trigger rankings scrape with gender', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('scrapeType', 'rankings')
            ->set('scrapeGender', 'female')
            ->call('triggerScrape')
            ->assertDispatched('notify');

        $run = ScraperRun::where('type', 'rankings')->latest()->first();

        expect($run->parameters['gender'])->toBe('female');
    });

    it('can trigger batch scrape', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('selectedTypes', ['rankings', 'players'])
            ->set('selectedGenders', ['male', 'female'])
            ->call('triggerFullScrape')
            ->assertDispatched('notify');

        // Should create 3 runs: rankings(male), rankings(female), players
        $rankingsRuns = ScraperRun::where('type', 'rankings')
            ->where('status', ScraperRun::STATUS_PENDING)
            ->count();
        $playersRuns = ScraperRun::where('type', 'players')
            ->where('status', ScraperRun::STATUS_PENDING)
            ->count();

        expect($rankingsRuns)->toBeGreaterThanOrEqual(2)
            ->and($playersRuns)->toBeGreaterThanOrEqual(1);
    });

    it('validates at least one type selected for batch scrape', function () {
        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('selectedTypes', [])
            ->call('triggerFullScrape')
            ->assertDispatched('notify', function ($name, $data) {
                return $data['type'] === 'error';
            });
    });

    it('can toggle all types', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('selectedTypes', ['rankings', 'players', 'transitions', 'series', 'live_center'])
            ->call('toggleAllTypes');

        expect($component->get('selectedTypes'))->toBe([]);

        $component->call('toggleAllTypes');

        expect($component->get('selectedTypes'))->toBe(['rankings', 'players', 'transitions', 'series', 'live_center']);
    });

    it('can toggle all genders', function () {
        $component = Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('selectedGenders', ['male', 'female'])
            ->call('toggleAllGenders');

        expect($component->get('selectedGenders'))->toBe([]);

        $component->call('toggleAllGenders');

        expect($component->get('selectedGenders'))->toBe(['male', 'female']);
    });

    it('can cancel a running scrape', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->call('cancelRun', $run->id)
            ->assertDispatched('notify');

        $run->refresh();

        expect($run->status)->toBe(ScraperRun::STATUS_FAILED)
            ->and($run->error_message)->toBe('Cancelled by user');
    });

    it('can delete a scraper run', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_COMPLETED,
        ]);

        $runId = $run->id;

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->call('deleteRun', $runId)
            ->assertDispatched('notify');

        expect(ScraperRun::find($runId))->toBeNull();
    });

    it('can retry a failed scrape', function () {
        $run = ScraperRun::create([
            'type' => 'rankings',
            'status' => ScraperRun::STATUS_FAILED,
            'parameters' => ['gender' => 'male'],
        ]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->call('retryRun', $run->id)
            ->assertDispatched('notify');

        // Check that a new pending run was created with same parameters
        $newRun = ScraperRun::where('type', 'rankings')
            ->where('status', ScraperRun::STATUS_PENDING)
            ->latest()
            ->first();

        expect($newRun)->not->toBeNull()
            ->and($newRun->parameters['gender'])->toBe('male');
    });

    it('generates periods correctly', function () {
        $component = Livewire::actingAs($this->admin)->test(Index::class);

        $periods = $component->viewData('periods');

        expect($periods)->toBeArray()
            ->and(count($periods))->toBe(36);

        // Check that first period is current month
        $firstPeriod = array_key_first($periods);
        expect($firstPeriod)->toBe(now()->format('Y.m.01'));
    });

    it('resets page when search changes', function () {
        // Create enough runs to paginate
        for ($i = 0; $i < 20; $i++) {
            ScraperRun::create([
                'type' => 'rankings',
                'status' => ScraperRun::STATUS_COMPLETED,
            ]);
        }

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->set('search', 'test')
            ->assertSet('page', 1);
    });

    it('shows sync section when unsynced data exists', function () {
        $run = ScraperRun::create([
            'type' => 'players',
            'status' => ScraperRun::STATUS_COMPLETED,
        ]);

        ScrapedPlayer::create([
            'scraper_run_id' => $run->id,
            'first_name' => 'Test',
            'surname' => 'Player',
            'is_synced' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test(Index::class)
            ->assertSee(__('admin-scraper.sync_scraped_data'));
    });
});
