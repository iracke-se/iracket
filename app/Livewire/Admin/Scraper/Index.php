<?php

namespace App\Livewire\Admin\Scraper;

use App\Models\Scraper\ScraperRun;
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

    public function getStatsProperty(): array
    {
        return [
            'total' => ScraperRun::count(),
            'running' => ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->count(),
            'completed' => ScraperRun::where('status', ScraperRun::STATUS_COMPLETED)->count(),
            'failed' => ScraperRun::where('status', ScraperRun::STATUS_FAILED)->count(),
        ];
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
        ]);
    }
}
