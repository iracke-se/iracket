<?php

namespace App\Livewire\User\Information;

use Livewire\Component;

class Index extends Component
{
    public string $locale;

    public function mount()
    {
        $this->locale = app()->getLocale();
    }

    public function switchLanguage(string $locale)
    {
        session(['locale' => $locale]);
        $this->locale = $locale;
        app()->setLocale($locale);

        $this->dispatch('language-switched');
    }

    public function render()
    {
        return view('livewire.user.information.index')
            ->layout('components.layouts.app');
    }
}
