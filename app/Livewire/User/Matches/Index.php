<?php

namespace App\Livewire\User\Matches;

use App\Models\GameMatch;
use Livewire\Component;

class Index extends Component
{
    public int $selectedYear;

    public function mount()
    {
        $this->selectedYear = now()->year;
    }

    public function render()
    {
        $user = auth()->user();

        $matches = GameMatch::where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)
                  ->orWhere('player2_id', $user->id);
        })
        ->whereYear('played_at', $this->selectedYear)
        ->with(['player1.club', 'player2.club'])
        ->orderBy('played_at', 'desc')
        ->get()
        ->groupBy(function ($match) {
            return $match->played_at->format('F Y');
        });

        // Get available years
        $years = GameMatch::where(function ($query) use ($user) {
            $query->where('player1_id', $user->id)
                  ->orWhere('player2_id', $user->id);
        })
        ->selectRaw('YEAR(played_at) as year')
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year')
        ->toArray();

        if (empty($years)) {
            $years = [now()->year];
        }

        return view('livewire.user.matches.index', [
            'matchesByMonth' => $matches,
            'years' => $years,
        ])->layout('components.layouts.app');
    }
}
