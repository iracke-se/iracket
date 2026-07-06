<?php

namespace App\Livewire\User\Guide;

use App\Models\GuideSection;
use Livewire\Component;

class Show extends Component
{
    public GuideSection $section;
    public ?GuideSection $previous = null;
    public ?GuideSection $next = null;

    public function mount(string $slug)
    {
        $this->section = GuideSection::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $sections = GuideSection::activeOrdered()->get();
        $index = $sections->search(fn ($s) => $s->id === $this->section->id);

        if ($index !== false) {
            $this->previous = $index > 0 ? $sections[$index - 1] : null;
            $this->next = $index < $sections->count() - 1 ? $sections[$index + 1] : null;
        }
    }

    public function render()
    {
        return view('livewire.user.guide.show')
            ->layout('components.layouts.app');
    }
}
