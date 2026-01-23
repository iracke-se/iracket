<?php

namespace App\Livewire\User\Clubs;

use App\Models\Club;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasSearchableQueries;

    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Club::query()->withCount('members');

        if ($this->search) {
            $this->applySearch($query, $this->search, ['name', 'location']);
        }

        $clubs = $query->orderBy('name')->paginate(20);

        return view('livewire.user.clubs.index', [
            'clubs' => $clubs,
        ])->layout('components.layouts.app');
    }
}
