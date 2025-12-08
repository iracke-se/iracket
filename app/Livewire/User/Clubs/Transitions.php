<?php

namespace App\Livewire\User\Clubs;

use App\Models\Club;
use Livewire\Component;
use Livewire\WithPagination;

class Transitions extends Component
{
    use WithPagination;

    public Club $club;
    public string $filter = 'all'; // all, incoming, outgoing

    public function mount(Club $club)
    {
        $this->club = $club;
    }

    public function render()
    {
        $query = match ($this->filter) {
            'incoming' => $this->club->incomingTransitions(),
            'outgoing' => $this->club->outgoingTransitions(),
            default => $this->club->incomingTransitions()
                ->union($this->club->outgoingTransitions()->toBase()),
        };

        $transitions = $this->club->incomingTransitions()
            ->when($this->filter === 'outgoing', fn($q) => $this->club->outgoingTransitions())
            ->when($this->filter === 'all', function () {
                // Get both incoming and outgoing
                return $this->club->incomingTransitions()
                    ->get()
                    ->merge($this->club->outgoingTransitions()->get())
                    ->sortByDesc('completion_date');
            })
            ->when($this->filter !== 'all', fn($q) => $q->orderByDesc('completion_date')->get());

        // Simplified approach - get all and filter
        if ($this->filter === 'all') {
            $incoming = $this->club->incomingTransitions()
                ->with(['user', 'fromClub', 'toClub'])
                ->get()
                ->map(fn($t) => $t->setAttribute('direction', 'incoming'));

            $outgoing = $this->club->outgoingTransitions()
                ->with(['user', 'fromClub', 'toClub'])
                ->get()
                ->map(fn($t) => $t->setAttribute('direction', 'outgoing'));

            $transitions = $incoming->merge($outgoing)->sortByDesc('completion_date');
        } elseif ($this->filter === 'incoming') {
            $transitions = $this->club->incomingTransitions()
                ->with(['user', 'fromClub', 'toClub'])
                ->orderByDesc('completion_date')
                ->get()
                ->map(fn($t) => $t->setAttribute('direction', 'incoming'));
        } else {
            $transitions = $this->club->outgoingTransitions()
                ->with(['user', 'fromClub', 'toClub'])
                ->orderByDesc('completion_date')
                ->get()
                ->map(fn($t) => $t->setAttribute('direction', 'outgoing'));
        }

        return view('livewire.user.clubs.transitions', [
            'transitions' => $transitions,
        ])->layout('components.layouts.app');
    }
}
