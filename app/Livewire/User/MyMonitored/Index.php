<?php

namespace App\Livewire\User\MyMonitored;

use App\Models\User;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasSearchableQueries;

    public string $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleMonitor(int $userId)
    {
        $user = User::findOrFail($userId);
        auth()->user()->toggleMonitoring($user);
    }

    public function render()
    {
        $monitoredPlayers = auth()->user()
            ->monitoring()
            ->with(['club', 'monthlyRankings' => function ($q) {
                $q->where('year', now()->year)
                  ->where('month', now()->month);
            }])
            ->when($this->search, function ($query) {
                $this->applySearch($query, $this->search, ['first_name', 'last_name']);
            })
            ->paginate(20);

        return view('livewire.user.my-monitored.index', [
            'monitoredPlayers' => $monitoredPlayers,
        ])->layout('components.layouts.app');
    }
}
