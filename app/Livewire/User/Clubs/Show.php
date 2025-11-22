<?php

namespace App\Livewire\User\Clubs;

use App\Models\Club;
use Livewire\Component;

class Show extends Component
{
    public Club $club;

    public function mount(Club $club)
    {
        $this->club = $club;
    }

    public function render()
    {
        $members = $this->club->members()
            ->with('monthlyRankings')
            ->get()
            ->map(function ($member) {
                $member->current_ranking = $member->currentMonthRanking();
                return $member;
            })
            ->sortBy(function ($member) {
                return $member->current_ranking ? $member->current_ranking->rank : PHP_INT_MAX;
            });

        $clubRankings = $this->club->monthlyRankings()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(6)
            ->get();

        return view('livewire.user.clubs.show', [
            'members' => $members,
            'clubRankings' => $clubRankings,
        ])->layout('components.layouts.app');
    }
}
