<?php

namespace App\Livewire\Admin\Scraper;

use App\Models\Scraper\ScraperRun;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public ScraperRun $run;
    public string $logLevel = '';

    public function mount(ScraperRun $run): void
    {
        $this->run = $run;
    }

    public function render()
    {
        $logs = $this->run->logs()
            ->when($this->logLevel, function ($query) {
                $query->where('level', $this->logLevel);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('livewire.admin.scraper.show', [
            'logs' => $logs,
        ])->layout('components.layouts.admin');
    }
}
