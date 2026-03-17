<?php

namespace App\Livewire\User\Districts;

use App\Models\District;
use Livewire\Component;

class Index extends Component
{
    public bool $showRaw = false;

    public function toggleRaw(): void
    {
        $this->showRaw = !$this->showRaw;
    }

    public function render()
    {
        $districts = District::withCount('users')->orderBy('name')->get();

        return view('livewire.user.districts.index', [
            'districts' => $districts,
        ])->layout('components.layouts.app');
    }
}
