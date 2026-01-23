<?php

namespace App\Livewire\Admin\Staff;

use App\Models\User;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasSearchableQueries;

    public string $search = '';

    protected $queryString = ['search' => ['except' => '']];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleVisibility($id)
    {
        $user = User::findOrFail($id);
        $user->update(['visible_in_players' => !$user->visible_in_players]);
        session()->flash('message', 'Player visibility updated successfully.');
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user->delete();
        session()->flash('message', 'Staff member deleted successfully.');
    }

    public function render()
    {
        $staff = User::query()
            ->role(['Admin', 'Manager'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $this->applySearch($q, $this->search, ['first_name', 'last_name', 'email']);
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.admin.staff.index', [
            'staff' => $staff,
        ])->layout('components.layouts.admin');
    }
}
