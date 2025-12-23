<?php

namespace App\Livewire\Admin\Scraper;

use App\Models\Scraper\ScraperRun;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public ScraperRun $run;
    public string $logLevel = '';
    public bool $isRunning = false;

    public function mount(ScraperRun $run): void
    {
        $this->run = $run;
        $this->checkRunningStatus();
    }

    public function checkRunningStatus(): void
    {
        $this->run->refresh();
        $this->isRunning = $this->run->status === ScraperRun::STATUS_RUNNING;
    }

    /**
     * Refresh data when polling
     */
    #[On('refresh-run-data')]
    public function refreshData(): void
    {
        $this->checkRunningStatus();
    }

    public function render()
    {
        // Get all logs ordered chronologically
        $logs = $this->run->logs()
            ->when($this->logLevel, function ($query) {
                $query->where('level', $this->logLevel);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.admin.scraper.show', [
            'logs' => $logs,
        ])->layout('components.layouts.admin');
    }
}
