<?php

namespace App\Livewire\Admin\Clubs;

use App\Models\Club;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $hasMembers = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'hasMembers' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingHasMembers()
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
            ->when($this->hasMembers !== '', function ($query) {
                if ($this->hasMembers === '1') {
                    $query->has('members');
                } else {
                    $query->doesntHave('members');
                }
            })
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Stats
        $totalClubs = Club::count();
        $totalMembers = User::whereNotNull('club_id')->count();
        $clubsWithMembers = Club::has('members')->count();
        $clubsThisMonth = Club::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return view('livewire.admin.clubs.index', [
            'clubs' => $clubs,
            'totalClubs' => $totalClubs,
            'totalMembers' => $totalMembers,
            'clubsWithMembers' => $clubsWithMembers,
            'clubsThisMonth' => $clubsThisMonth,
        ])->layout('components.layouts.admin');
    }
}
