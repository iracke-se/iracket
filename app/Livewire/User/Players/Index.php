<?php

namespace App\Livewire\User\Players;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $gender = '';
    public bool $showFilters = false;

    // Advanced filters
    public string $sortBy = 'points_desc';
    public string $location = '';
    public ?int $rankingFrom = null;
    public ?int $rankingTo = null;
    public ?int $ageFrom = null;
    public ?int $ageTo = null;
    public ?string $selectedDate = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'gender' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingGender()
    {
        $this->resetPage();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->sortBy = 'points_desc';
        $this->location = '';
        $this->rankingFrom = null;
        $this->rankingTo = null;
        $this->ageFrom = null;
        $this->ageTo = null;
        $this->selectedDate = null;
    }

    public function applyFilters()
    {
        $this->showFilters = false;
        $this->resetPage();
    }

    public function render()
    {
        $query = User::query()
            ->where('visible_in_players', true);

        // Search by name or email
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by gender
        if ($this->gender) {
            $query->where('gender', $this->gender);
        }

        // Filter by age range
        if ($this->ageFrom) {
            $query->where('age', '>=', $this->ageFrom);
        }

        if ($this->ageTo) {
            $query->where('age', '<=', $this->ageTo);
        }

        // For now, just order by name since points aren't implemented yet
        $query->orderBy('first_name');

        $players = $query->paginate(20);

        return view('livewire.user.players.index', [
            'players' => $players,
        ])->layout('components.layouts.app');
    }
}
