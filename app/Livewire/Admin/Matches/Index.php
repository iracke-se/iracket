<?php

namespace App\Livewire\Admin\Matches;

use App\Models\GameMatch;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasSearchableQueries;

    public string $search = '';
    public string $status = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        GameMatch::findOrFail($id)->delete();
        session()->flash('message', 'Match deleted successfully.');
    }

    public function render()
    {
        $matches = GameMatch::query()
            ->with(['player1', 'player2', 'winner'])
            ->when($this->search, function ($query) {
                $this->applySearchToMultipleRelations($query, $this->search, [
                    'player1' => ['first_name', 'last_name'],
                    'player2' => ['first_name', 'last_name'],
                ]);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->orderBy('played_at', 'desc')
            ->paginate(10);

        // Stats
        $totalMatches = GameMatch::count();
        $confirmedMatches = GameMatch::where('status', 'confirmed')->count();
        $pendingMatches = GameMatch::where('status', 'pending')->count();
        $matchesThisMonth = GameMatch::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return view('livewire.admin.matches.index', [
            'matches' => $matches,
            'totalMatches' => $totalMatches,
            'confirmedMatches' => $confirmedMatches,
            'pendingMatches' => $pendingMatches,
            'matchesThisMonth' => $matchesThisMonth,
        ])->layout('components.layouts.admin');
    }
}
