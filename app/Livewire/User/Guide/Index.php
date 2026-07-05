<?php

namespace App\Livewire\User\Guide;

use App\Models\GuideSection;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $sections = GuideSection::activeOrdered()->get();

        return view('livewire.user.guide.index', [
            'sections' => $sections,
        ])->layout('components.layouts.app');
    }
}
