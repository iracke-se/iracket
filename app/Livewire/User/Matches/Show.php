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
        return view('livewire.user.matches.show')
            ->layout('components.layouts.app');
    }
}
