<?php

namespace App\Livewire\Admin\Users;

use App\Models\Club;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $gender = '';
    public string $club = '';
    public string $verified = '';
    public array $selectedUsers = [];
    public bool $selectAll = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'gender' => ['except' => ''],
        'club' => ['except' => ''],
        'verified' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingGender()
    {
        $this->resetPage();
    }

    public function updatingClub()
    {
        $this->resetPage();
    }

    public function updatingVerified()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedUsers = $this->getFilteredUserIds();
        } else {
            $this->selectedUsers = [];
        }
    }

    public function getFilteredUserIds(): array
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->gender, function ($query) {
                $query->where('gender', $this->gender);
            })
            ->when($this->club, function ($query) {
                $query->where('club_id', $this->club);
            })
            ->when($this->verified !== '', function ($query) {
                if ($this->verified === '1') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            })
            ->pluck('id')
            ->toArray();
    }

    public function sendNotification()
    {
        if (empty($this->selectedUsers)) {
            session()->flash('error', 'Please select at least one user.');
            return;
        }

        return redirect()->route('admin.notifications.send', ['users' => implode(',', $this->selectedUsers)]);
    }

    public function clearSelection()
    {
        $this->selectedUsers = [];
        $this->selectAll = false;
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete your own account.');
            return;
        }

        $user->delete();
        session()->flash('message', 'User deleted successfully.');
    }

    public function render()
    {
        $users = User::query()
            ->with('club')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->gender, function ($query) {
                $query->where('gender', $this->gender);
            })
            ->when($this->club, function ($query) {
                $query->where('club_id', $this->club);
            })
            ->when($this->verified !== '', function ($query) {
                if ($this->verified === '1') {
                    $query->whereNotNull('email_verified_at');
                } else {
                    $query->whereNull('email_verified_at');
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Stats
        $totalUsers = User::count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $unverifiedUsers = User::whereNull('email_verified_at')->count();
        $usersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Get clubs for filter
        $clubs = Club::orderBy('name')->get();

        return view('livewire.admin.users.index', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'verifiedUsers' => $verifiedUsers,
            'unverifiedUsers' => $unverifiedUsers,
            'usersThisMonth' => $usersThisMonth,
            'clubs' => $clubs,
        ])->layout('components.layouts.admin');
    }
}
