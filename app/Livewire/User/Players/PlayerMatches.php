<?php

namespace App\Livewire\User\Players;

use App\Models\GameMatch;
use App\Models\User;
use Livewire\Component;

class PlayerMatches extends Component
{
    public User $player;
    public $selectedYear;

    public function mount(User $user)
    {
        $this->player = $user;

        // Get the most recent year with matches, or default to current year
        $mostRecentYear = GameMatch::where(function ($query) {
            $query->where('player1_id', $this->player->id)
                  ->orWhere('player2_id', $this->player->id);
        })
        ->selectRaw('YEAR(played_at) as year')
        ->orderBy('year', 'desc')
        ->value('year');

        $this->selectedYear = $mostRecentYear ?? now()->year;
    }

    public function render()
    {
        $matchesQuery = GameMatch::where(function ($query) {
            $query->where('player1_id', $this->player->id)
                  ->orWhere('player2_id', $this->player->id);
        })
        ->whereYear('played_at', $this->selectedYear)
        ->with(['player1.club', 'player2.club', 'winner', 'scrapedMatches']);

        $matches = $matchesQuery->orderBy('played_at', 'desc')
            ->get()
            ->groupBy(function ($match) {
                return $match->played_at->format('F Y');
            });

        // Get available years
        $years = GameMatch::where(function ($query) {
            $query->where('player1_id', $this->player->id)
                  ->orWhere('player2_id', $this->player->id);
        })
        ->selectRaw('YEAR(played_at) as year')
        ->distinct()
        ->orderBy('year', 'desc')
        ->pluck('year')
        ->toArray();

        if (empty($years)) {
            $years = [now()->year];
        }

        // Calculate stats
        $totalMatches = GameMatch::where(function ($query) {
            $query->where('player1_id', $this->player->id)
                  ->orWhere('player2_id', $this->player->id);
        })->whereYear('played_at', $this->selectedYear)->count();

        $wins = GameMatch::where('winner_id', $this->player->id)
            ->whereYear('played_at', $this->selectedYear)
            ->count();

        $losses = $totalMatches - $wins;

        return view('livewire.user.players.player-matches', [
            'matchesByMonth' => $matches,
            'years' => $years,
            'totalMatches' => $totalMatches,
            'wins' => $wins,
            'losses' => $losses,
        ])->layout('components.layouts.app');
    }
}
