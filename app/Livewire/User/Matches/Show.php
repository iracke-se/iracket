<?php

namespace App\Livewire\User\Matches;

use App\Models\GameMatch;
use Livewire\Component;

class Show extends Component
{
    public GameMatch $match;

    public function mount(GameMatch $match)
    {
        $this->match = $match;
    }

    public function render()
    {
        // Get other matches between these two players
        $otherMatches = GameMatch::query()
            ->where('id', '!=', $this->match->id)
            ->where(function ($q) {
                $q->where(function ($subQ) {
                    $subQ->where('player1_id', $this->match->player1_id)
                        ->where('player2_id', $this->match->player2_id);
                })->orWhere(function ($subQ) {
                    $subQ->where('player1_id', $this->match->player2_id)
                        ->where('player2_id', $this->match->player1_id);
                });
            })
            ->with(['player1', 'player2', 'winner', 'scrapedMatches'])
            ->orderBy('played_at', 'desc')
            ->limit(10)
            ->get();

        return view('livewire.user.matches.show', [
            'otherMatches' => $otherMatches,
        ])->layout('components.layouts.app');
    }
}
