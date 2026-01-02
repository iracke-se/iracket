<?php

namespace App\Livewire\User\Players;

use App\Models\MonthlyRanking;
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
            ->where('visible_in_players', true)
            ->with(['club.monthlyRankings' => function ($q) {
                $q->orderBy('year', 'desc')
                  ->orderBy('month', 'desc')
                  ->limit(1);
            }])
            ->with(['monthlyRankings' => function ($q) {
                $q->orderBy('year', 'desc')
                  ->orderBy('month', 'desc')
                  ->limit(1);
            }]);

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

        // Filter by ranking range (using most recent ranking)
        if ($this->rankingFrom || $this->rankingTo) {
            $query->whereHas('monthlyRankings', function ($q) {
                // Get the most recent year/month combination for filtering
                $q->whereRaw('(year, month) = (
                    SELECT year, month
                    FROM monthly_rankings mr2
                    WHERE mr2.user_id = monthly_rankings.user_id
                    ORDER BY year DESC, month DESC
                    LIMIT 1
                )');

                if ($this->rankingFrom) {
                    $q->where('points', '>=', $this->rankingFrom);
                }
                if ($this->rankingTo) {
                    $q->where('points', '<=', $this->rankingTo);
                }
            });
        }

        // Sort by points or name
        switch ($this->sortBy) {
            case 'points_desc':
                // Use a derived table to get latest rankings efficiently
                $query->leftJoin(\DB::raw('(
                    SELECT mr1.*
                    FROM monthly_rankings mr1
                    INNER JOIN (
                        SELECT user_id, MAX(year * 100 + month) as max_period
                        FROM monthly_rankings
                        GROUP BY user_id
                    ) mr2 ON mr1.user_id = mr2.user_id
                        AND (mr1.year * 100 + mr1.month) = mr2.max_period
                ) as latest_rankings'), 'users.id', '=', 'latest_rankings.user_id')
                ->orderByRaw('latest_rankings.points IS NULL, latest_rankings.points DESC')
                ->select('users.*');
                break;
            case 'points_asc':
                // Use a derived table to get latest rankings efficiently
                $query->leftJoin(\DB::raw('(
                    SELECT mr1.*
                    FROM monthly_rankings mr1
                    INNER JOIN (
                        SELECT user_id, MAX(year * 100 + month) as max_period
                        FROM monthly_rankings
                        GROUP BY user_id
                    ) mr2 ON mr1.user_id = mr2.user_id
                        AND (mr1.year * 100 + mr1.month) = mr2.max_period
                ) as latest_rankings'), 'users.id', '=', 'latest_rankings.user_id')
                ->orderByRaw('latest_rankings.points IS NULL DESC, latest_rankings.points ASC')
                ->select('users.*');
                break;
            case 'name_asc':
                $query->orderBy('first_name');
                break;
            case 'name_desc':
                $query->orderByDesc('first_name');
                break;
            default:
                $query->orderBy('first_name');
        }

        $players = $query->paginate(20);

        // Get top 3 positions for men and women (from most recent rankings)
        $latestRanking = MonthlyRanking::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        $topMen = [];
        $topWomen = [];

        if ($latestRanking) {
            $topMen = MonthlyRanking::where('year', $latestRanking->year)
                ->where('month', $latestRanking->month)
                ->whereHas('user', function ($q) {
                    $q->where('gender', 'male')
                      ->where('is_active_player', true)
                      ->where('visible_in_players', true);
                })
                ->orderByDesc('points')
                ->limit(3)
                ->pluck('user_id')
                ->toArray();

            $topWomen = MonthlyRanking::where('year', $latestRanking->year)
                ->where('month', $latestRanking->month)
                ->whereHas('user', function ($q) {
                    $q->where('gender', 'female')
                      ->where('is_active_player', true)
                      ->where('visible_in_players', true);
                })
                ->orderByDesc('points')
                ->limit(3)
                ->pluck('user_id')
                ->toArray();
        }

        // Create position maps
        $rankingPositions = [];
        foreach ($topMen as $index => $userId) {
            $rankingPositions[$userId] = ['position' => $index + 1, 'category' => 'men'];
        }
        foreach ($topWomen as $index => $userId) {
            $rankingPositions[$userId] = ['position' => $index + 1, 'category' => 'women'];
        }

        return view('livewire.user.players.index', [
            'players' => $players,
            'rankingPositions' => $rankingPositions,
        ])->layout('components.layouts.app');
    }
}
