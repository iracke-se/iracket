<?php

namespace App\Livewire\User\Information;

use App\Models\Notification;
use Livewire\Component;

class Index extends Component
{
    public string $locale;
    public int $unreadNotificationsCount = 0;

    public function mount()
    {
        $this->locale = app()->getLocale();
        $this->unreadNotificationsCount = Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->count();
    }

    public function switchLanguage(string $locale)
    {
        $user = auth()->user();
        if ($user) {
            $user->update(['locale' => $locale]);
        }

        session(['locale' => $locale]);
        $this->locale = $locale;
        app()->setLocale($locale);

        $this->dispatch('language-switched');

        return $this->redirect(route('information'), navigate: true);
    }

    public function render()
    {
        return view('livewire.user.information.index')
            ->layout('components.layouts.app');
    }
}
