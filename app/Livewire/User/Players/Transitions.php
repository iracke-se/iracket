<?php

namespace App\Livewire\User\Players;

use App\Models\User;
use Livewire\Component;

class Transitions extends Component
{
    public User $player;

    public function mount(User $user)
    {
        $this->player = $user;
    }

    public function render()
    {
        $transitions = $this->player->clubTransitions()
            ->with(['fromClub', 'toClub'])
            ->orderByDesc('completion_date')
            ->get();

        return view('livewire.user.players.transitions', [
            'transitions' => $transitions,
        ])->layout('components.layouts.app');
    }
}
