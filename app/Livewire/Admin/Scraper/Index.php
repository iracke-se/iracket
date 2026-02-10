<?php

namespace App\Livewire\Admin\Scraper;

use App\Jobs\Scraper\RunScraperJob;
use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScraperRun;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // Filters
    public string $typeFilter = '';
    public string $statusFilter = '';
    public int $perPage = 10;
    public bool $isScraperRunning = false;

    // Scraper options
    public string $scraperType = 'start';
    public string $month = '';
    public bool $scrapeAll = false;
    public string $gender = '';
    public ?int $limitPeriods = null;
    public ?int $limitDivisions = null;
    public bool $skipSync = false;
    public bool $skipBubbler = false;
    public bool $noBackup = false;
    public string $liveCenterDate = '';
    public bool $skipPoints = false;
    public ?int $limitMatches = null;

    protected $queryString = [
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        // Set default month to current month
        $this->month = now()->format('Y-m');
        $this->liveCenterDate = now()->format('Y-m-d');

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
     * Start scraper with configured options
     */
    public function startScraper(): void
    {
        // Validate live center date
        if ($this->scraperType === 'live-center' && !$this->liveCenterDate) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select a date for Live Center scraper',
            ]);
            return;
        }

        // Validate required fields
        if ($this->scraperType !== 'live-center' && !$this->scrapeAll && !$this->month) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select a month or enable "Scrape All"',
            ]);
            return;
        }

        // Validate month format if provided
        if ($this->month && !preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Invalid month format. Use YYYY-MM',
            ]);
            return;
        }

        // Validate gender for rankings scraper
        if ($this->scraperType === 'rankings' && !$this->gender) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Please select gender for rankings scraper',
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
            // Build command and parameters
            $command = match ($this->scraperType) {
                'start' => 'scraper:start',
                'smart-scrape' => 'scraper:smart-scrape',
                'rankings', 'players', 'transitions', 'series', 'series-matches', 'live-center' => 'scraper:run',
                default => 'scraper:start',
            };

            $parameters = [];
            $options = [];

            // Add month parameter for start/smart-scrape commands
            if (in_array($command, ['scraper:start', 'scraper:smart-scrape'])) {
                if ($this->month && !$this->scrapeAll) {
                    $parameters['month'] = $this->month;
                }
            } elseif ($command === 'scraper:run') {
                // Add type for scraper:run command
                $parameters['type'] = $this->scraperType;
            }

            // Build options array
            if ($this->scrapeAll) {
                $options['--all'] = true;
            }
            if ($this->gender) {
                $options['--gender'] = $this->gender;
            }
            if ($this->limitPeriods) {
                $options['--limit-periods'] = $this->limitPeriods;
            }
            if ($this->limitDivisions) {
                $options['--limit-divisions'] = $this->limitDivisions;
            }
            if ($this->skipSync) {
                $options['--skip-sync'] = true;
            }
            if ($this->skipBubbler) {
                $options['--skip-bubbler'] = true;
            }
            if ($this->noBackup) {
                $options['--no-backup'] = true;
            }

            // Live center specific options
            if ($this->scraperType === 'live-center') {
                if ($this->liveCenterDate) {
                    $options['--date'] = $this->liveCenterDate;
                }
                if ($this->skipPoints) {
                    $options['--skip-points'] = true;
                }
                if ($this->limitMatches) {
                    $options['--limit-matches'] = $this->limitMatches;
                }
            }

            // Dispatch job to queue
            RunScraperJob::dispatch($command, $parameters, $options);

            $this->isScraperRunning = true;

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Scraper dispatched to queue successfully!',
            ]);

            // Start polling for progress
            $this->dispatch('scraper-started');

            // Close modal
            $this->dispatch('close-modal', 'scraper-options');

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
            'scraperTypes' => [
                'start' => 'Full Scrape (All 12 Steps)',
                'smart-scrape' => 'Smart Scrape (Rankings + Series)',
                'rankings' => 'Rankings Only',
                'players' => 'Players Only',
                'transitions' => 'Transitions Only',
                'series' => 'Series Only',
                'series-matches' => 'Series Matches Only',
                'live-center' => 'Live Center Only',
            ],
            'runTypes' => [
                'rankings' => 'Rankings',
                'players' => 'Players',
                'transitions' => 'Transitions',
                'series' => 'Series',
                'live_center' => 'Live Center',
                'full_scrape' => 'Full Scrape',
                'smart_scrape' => 'Smart Scrape',
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
