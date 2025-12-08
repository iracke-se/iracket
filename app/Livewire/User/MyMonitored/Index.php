<?php

namespace App\Livewire\User\MyMonitored;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

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
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%');
                });
            })
            ->paginate(20);

        return view('livewire.user.my-monitored.index', [
            'monitoredPlayers' => $monitoredPlayers,
        ])->layout('components.layouts.app');
    }
}
