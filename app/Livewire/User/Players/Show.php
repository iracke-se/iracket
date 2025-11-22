<?php

namespace App\Livewire\User\Players;

use App\Models\User;
use Livewire\Component;

class Show extends Component
{
    public User $player;

    public function mount(User $user)
    {
        $this->player = $user;
    }

    public function render()
    {
        $currentRanking = $this->player->currentMonthRanking();

        $rankingsHistory = $this->player->monthlyRankings()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        return view('livewire.user.players.show', [
            'currentRanking' => $currentRanking,
            'rankingsHistory' => $rankingsHistory,
        ])->layout('components.layouts.app');
    }
}
