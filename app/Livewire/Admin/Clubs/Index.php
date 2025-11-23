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
    public array $selectedClubs = [];
    public bool $selectAll = false;

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

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedClubs = $this->getFilteredClubIds();
        } else {
            $this->selectedClubs = [];
        }
    }

    public function getFilteredClubIds(): array
    {
        return Club::query()
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
            ->pluck('id')
            ->toArray();
    }

    public function sendNotification()
    {
        if (empty($this->selectedClubs)) {
            session()->flash('error', 'Please select at least one club.');
            return;
        }

        // Get all user IDs from selected clubs
        $userIds = User::whereIn('club_id', $this->selectedClubs)
            ->pluck('id')
            ->toArray();

        if (empty($userIds)) {
            session()->flash('error', 'Selected clubs have no members.');
            return;
        }

        return redirect()->route('admin.notifications.send', ['users' => implode(',', $userIds)]);
    }

    public function clearSelection()
    {
        $this->selectedClubs = [];
        $this->selectAll = false;
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
