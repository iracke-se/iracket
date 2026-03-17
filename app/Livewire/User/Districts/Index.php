<?php

namespace App\Livewire\User\Districts;

use App\Models\District;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $districts = District::orderBy('name')->get();

        return view('livewire.user.districts.index', [
            'districts' => $districts,
        ])->layout('components.layouts.app');
    }
}
