<?php

namespace App\Livewire\Admin\Scraper;

use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $month = '';
    public string $typeFilter = '';
    public string $statusFilter = '';
    public int $perPage = 10;
    public bool $isScraperRunning = false;

    protected $queryString = [
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Set default month to current month
        $this->month = now()->format('Y-m');

        // Check if there's a running scraper
        $this->checkRunningStatus();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function checkRunningStatus(): void
    {
        $this->isScraperRunning = ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->exists();
    }

    /**
     * Start scraper for the specified month
     */
    public function startScraper(): void
    {
        if (!$this->month) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select a month',
            ]);
            return;
        }

        // Validate month format
        if (!preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Invalid month format. Use YYYY-MM',
            ]);
            return;
        }

        // Check if scraper is already running
        if ($this->isScraperRunning) {
            $runningRun = ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->first();
            $runInfo = $runningRun ? " (Run #{$runningRun->id} - {$runningRun->type})" : "";

            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "A scraper is already running{$runInfo}. Please wait for it to complete or view progress below.",
            ]);
            return;
        }

        try {
            // Start scraper command in background
            Process::start("ddev exec php artisan scraper:start {$this->month}");

            $this->isScraperRunning = true;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scraper started for ' . $this->month,
            ]);

            // Start polling for progress
            $this->dispatch('scraper-started');

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to start scraper: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Refresh data when polling
     */
    #[On('refresh-scraper-data')]
    public function refreshData(): void
    {
        $this->checkRunningStatus();

        // Reset pagination to show latest runs
        $this->resetPage();
    }

    public function stopRun(int $runId): void
    {
        $run = ScraperRun::find($runId);

        if ($run && $run->status === ScraperRun::STATUS_RUNNING) {
            $run->markAsFailed('Stopped by user via admin panel');

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scraper run #' . $runId . ' stopped successfully',
            ]);

            $this->checkRunningStatus();
        }
    }

    public function stopAllRunning(): void
    {
        $stoppedCount = 0;

        ScraperRun::where('status', ScraperRun::STATUS_RUNNING)
            ->get()
            ->each(function ($run) use (&$stoppedCount) {
                $run->markAsFailed('Stopped by user via admin panel (bulk stop)');
                $stoppedCount++;
            });

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "Stopped {$stoppedCount} running scraper(s)",
        ]);

        $this->checkRunningStatus();
    }

    public function deleteRun(int $runId): void
    {
        $run = ScraperRun::find($runId);

        if ($run && $run->status !== ScraperRun::STATUS_RUNNING) {
            $run->delete();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scrape run deleted',
            ]);
        }
    }

    public function getStatsProperty(): array
    {
        return [
            'total_runs' => ScraperRun::count(),
            'running' => ScraperRun::where('status', ScraperRun::STATUS_RUNNING)->count(),
            'completed' => ScraperRun::where('status', ScraperRun::STATUS_COMPLETED)->count(),
            'failed' => ScraperRun::where('status', ScraperRun::STATUS_FAILED)->count(),
            'unsynced_players' => ScrapedPlayer::where('is_synced', false)->count(),
            'unsynced_rankings' => ScrapedRanking::where('is_synced', false)->count(),
            'unsynced_matches' => ScrapedMatch::where('is_synced', false)->count(),
            'latest_scrape' => ScraperRun::where('status', ScraperRun::STATUS_COMPLETED)
                ->latest('completed_at')
                ->first()?->completed_at?->diffForHumans(),
        ];
    }

    public function render()
    {
        $runs = ScraperRun::query()
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
        ])->layout('components.layouts.admin');
    }
}
