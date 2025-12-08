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

        // Get pending incoming and outgoing players
        $incomingPlayers = $this->club->pendingIncomingPlayers()
            ->with('user')
            ->get();

        $outgoingPlayers = $this->club->pendingOutgoingPlayers()
            ->with('user')
            ->get();

        // Get all club transitions for the link
        $totalTransitions = $this->club->incomingTransitions()->count()
            + $this->club->outgoingTransitions()->count();

        return view('livewire.user.clubs.show', [
            'members' => $members,
            'clubRankings' => $clubRankings,
            'incomingPlayers' => $incomingPlayers,
            'outgoingPlayers' => $outgoingPlayers,
            'totalTransitions' => $totalTransitions,
        ])->layout('components.layouts.app');
    }
}
