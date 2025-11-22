<?php

namespace App\Livewire\Public\Terms;

use App\Models\Term;
use Livewire\Component;

class Show extends Component
{
    public Term $term;

    public function mount(string $slug)
    {
        $this->term = Term::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function render()
    {
        $layout = auth()->check()
            ? 'components.layouts.app'
            : 'components.layouts.auth.wide';

        return view('livewire.public.terms.show')
            ->layout($layout);
    }
}
