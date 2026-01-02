<?php

namespace App\Livewire\User\Clubs;

use App\Models\Club;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Club::query()->withCount('members');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('location', 'like', '%' . $this->search . '%');
            });
        }

        $clubs = $query->orderBy('name')->paginate(20);

        return view('livewire.user.clubs.index', [
            'clubs' => $clubs,
        ])->layout('components.layouts.app');
    }
}
