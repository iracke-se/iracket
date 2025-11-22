<?php

namespace App\Livewire\Admin\Scraper;

use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScraperRun;
use App\Services\Scraper\SyncService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $typeFilter = '';
    public string $statusFilter = '';
    public int $perPage = 15;

    // For triggering new scrapes
    public string $scrapeType = '';
    public string $scrapeGender = 'male';
    public string $scrapePeriod = '';

    // For batch/full scrape
    public string $fullScrapePeriod = '';
    public array $selectedTypes = ['rankings', 'players', 'transitions', 'series', 'live_center'];
    public array $selectedGenders = ['male', 'female'];

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function triggerScrape(): void
    {
        if (!$this->scrapeType) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select a scrape type',
            ]);
            return;
        }

        $parameters = [];

        if ($this->scrapeType === 'rankings') {
            $parameters['gender'] = $this->scrapeGender;
            if ($this->scrapePeriod) {
                $parameters['period'] = $this->scrapePeriod;
            }
        }

        // Dispatch the job based on type
        $jobClass = match ($this->scrapeType) {
            'rankings' => \App\Jobs\Scraper\ScrapeRankingsJob::class,
            'players' => \App\Jobs\Scraper\ScrapePlayersJob::class,
            'transitions' => \App\Jobs\Scraper\ScrapeTransitionsJob::class,
            'series' => \App\Jobs\Scraper\ScrapeSeriesJob::class,
            'live_center' => \App\Jobs\Scraper\ScrapeLiveCenterJob::class,
            default => null,
        };

        if ($jobClass && class_exists($jobClass)) {
            $jobClass::dispatch($parameters);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scrape job queued successfully',
            ]);
        } else {
            // Create a pending run for now (jobs not implemented yet)
            ScraperRun::create([
                'type' => $this->scrapeType,
                'status' => ScraperRun::STATUS_PENDING,
                'parameters' => $parameters,
            ]);

            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'Scrape run created (job not yet implemented)',
            ]);
        }

        $this->reset(['scrapeType', 'scrapeGender', 'scrapePeriod']);
    }

    public function triggerFullScrape(): void
    {
        if (empty($this->selectedTypes)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select at least one scrape type',
            ]);
            return;
        }

        $jobsQueued = 0;

        foreach ($this->selectedTypes as $type) {
            $jobClass = match ($type) {
                'rankings' => \App\Jobs\Scraper\ScrapeRankingsJob::class,
                'players' => \App\Jobs\Scraper\ScrapePlayersJob::class,
                'transitions' => \App\Jobs\Scraper\ScrapeTransitionsJob::class,
                'series' => \App\Jobs\Scraper\ScrapeSeriesJob::class,
                'live_center' => \App\Jobs\Scraper\ScrapeLiveCenterJob::class,
                default => null,
            };

            if ($type === 'rankings') {
                // Queue rankings for each selected gender
                foreach ($this->selectedGenders as $gender) {
                    $parameters = ['gender' => $gender];
                    if ($this->fullScrapePeriod) {
                        $parameters['period'] = $this->fullScrapePeriod;
                    }

                    if ($jobClass && class_exists($jobClass)) {
                        $jobClass::dispatch($parameters);
                        $jobsQueued++;
                    } else {
                        ScraperRun::create([
                            'type' => $type,
                            'status' => ScraperRun::STATUS_PENDING,
                            'parameters' => $parameters,
                        ]);
                        $jobsQueued++;
                    }
                }
            } else {
                $parameters = [];
                if ($this->fullScrapePeriod) {
                    $parameters['period'] = $this->fullScrapePeriod;
                }

                if ($jobClass && class_exists($jobClass)) {
                    $jobClass::dispatch($parameters);
                    $jobsQueued++;
                } else {
                    ScraperRun::create([
                        'type' => $type,
                        'status' => ScraperRun::STATUS_PENDING,
                        'parameters' => $parameters,
                    ]);
                    $jobsQueued++;
                }
            }
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "{$jobsQueued} scrape job(s) queued successfully",
        ]);

        $this->reset(['fullScrapePeriod']);
    }

    public function toggleAllTypes(): void
    {
        $allTypes = ['rankings', 'players', 'transitions', 'series', 'live_center'];

        if (count($this->selectedTypes) === count($allTypes)) {
            $this->selectedTypes = [];
        } else {
            $this->selectedTypes = $allTypes;
        }
    }

    public function toggleAllGenders(): void
    {
        $allGenders = ['male', 'female'];

        if (count($this->selectedGenders) === count($allGenders)) {
            $this->selectedGenders = [];
        } else {
            $this->selectedGenders = $allGenders;
        }
    }

    public function cancelRun(int $runId): void
    {
        $run = ScraperRun::find($runId);

        if ($run && $run->status === ScraperRun::STATUS_RUNNING) {
            $run->markAsFailed('Cancelled by user');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scrape run cancelled',
            ]);
        }
    }

    public function deleteRun(int $runId): void
    {
        $run = ScraperRun::find($runId);

        if ($run) {
            $run->delete();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scrape run deleted',
            ]);
        }
    }

    public function retryRun(int $runId): void
    {
        $run = ScraperRun::find($runId);

        if ($run && $run->status === ScraperRun::STATUS_FAILED) {
            // Create a new run with the same parameters
            ScraperRun::create([
                'type' => $run->type,
                'status' => ScraperRun::STATUS_PENDING,
                'parameters' => $run->parameters,
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scrape run queued for retry',
            ]);
        }
    }

    public function syncRun(int $runId): void
    {
        $run = ScraperRun::find($runId);

        if (!$run || $run->status !== ScraperRun::STATUS_COMPLETED) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Can only sync completed runs',
            ]);
            return;
        }

        $syncService = app(SyncService::class);

        if ($run->type === 'players') {
            $stats = $syncService->syncPlayers($runId);
        } elseif ($run->type === 'rankings') {
            $stats = $syncService->syncRankings($runId);
        } else {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Sync not available for this type yet',
            ]);
            return;
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Sync completed: {$stats['created']} created, {$stats['updated']} updated, {$stats['errors']} errors",
        ]);
    }

    public function syncAllPlayers(): void
    {
        $syncService = app(SyncService::class);
        $stats = $syncService->syncPlayers();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Players sync completed: {$stats['created']} created, {$stats['updated']} updated",
        ]);
    }

    public function syncAllRankings(): void
    {
        $syncService = app(SyncService::class);
        $stats = $syncService->syncRankings();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Rankings sync completed: {$stats['created']} created, {$stats['updated']} updated",
        ]);
    }

    public function getStatsProperty(): array
    {
        return [
            'total' => ScraperRun::count(),
            'running' => ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->count(),
            'completed' => ScraperRun::where('status', ScraperRun::STATUS_COMPLETED)->count(),
            'failed' => ScraperRun::where('status', ScraperRun::STATUS_FAILED)->count(),
            'unsynced_players' => ScrapedPlayer::where('is_synced', false)->count(),
            'unsynced_rankings' => ScrapedRanking::where('is_synced', false)->count(),
        ];
    }

    public function getPeriodsProperty(): array
    {
        $periods = [];
        $currentDate = now();

        // Generate periods for the last 3 years (monthly)
        for ($i = 0; $i < 36; $i++) {
            $date = $currentDate->copy()->subMonths($i);
            $value = $date->format('Y.m.01');
            $label = $date->format('F Y');
            $periods[$value] = $label;
        }

        return $periods;
    }

    public function render()
    {
        $runs = ScraperRun::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('type', 'like', "%{$this->search}%")
                        ->orWhere('error_message', 'like', "%{$this->search}%");
                });
            })
            ->when($this->typeFilter, function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.scraper.index', [
            'runs' => $runs,
            'stats' => $this->stats,
            'periods' => $this->periods,
            'types' => [
                'rankings' => 'Rankings',
                'players' => 'Players',
                'transitions' => 'Transitions',
                'series' => 'Series',
                'live_center' => 'Live Center',
            ],
            'statuses' => [
                'pending' => 'Pending',
                'running' => 'Running',
                'completed' => 'Completed',
                'failed' => 'Failed',
            ],
        ])->layout('components.layouts.admin');
    }
}
