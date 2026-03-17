<?php

namespace App\Livewire\User\Districts;

use App\Models\District;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showRaw = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleRaw(): void
    {
        $this->showRaw = !$this->showRaw;
    }

    public function render()
    {
        $query = District::query()->withCount('users');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->showRaw) {
            $districts = $query->orderBy('name')->get();
        } else {
            $districts = $query->orderBy('name')->paginate(20);
        }

        return view('livewire.user.districts.index', [
            'districts' => $districts,
        ])->layout('components.layouts.app');
    }
}
