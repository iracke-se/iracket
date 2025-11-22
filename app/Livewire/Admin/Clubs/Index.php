<?php

namespace App\Livewire\Admin\Clubs;

use App\Models\Club;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        Club::findOrFail($id)->delete();
        session()->flash('message', 'Club deleted successfully.');
    }

    public function render()
    {
        $clubs = Club::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('location', 'like', '%' . $this->search . '%');
            })
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.clubs.index', [
            'clubs' => $clubs,
        ])->layout('components.layouts.admin');
    }
}
