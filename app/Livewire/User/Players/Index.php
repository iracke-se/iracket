<?php

namespace App\Livewire\User\Players;

use App\Models\District;
use App\Models\MonthlyRanking;
use App\Models\User;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasSearchableQueries;

    public string $search = '';
    public string $gender = '';
    public bool $showFilters = false;

    // Advanced filters
    public string $sortBy = 'points_desc';
    public string $filterDistrict = '';
    public ?int $rankingFrom = null;
    public ?int $rankingTo = null;
    public ?int $ageFrom = null;
    public ?int $ageTo = null;
    public ?string $selectedMonth = null;

    protected $queryString = [
        'search'         => ['except' => ''],
        'gender'         => ['except' => ''],
        'filterDistrict' => ['except' => ''],
    ];

    public function mount()
    {
        // Don't set a default month - show all players with their latest rankings
        $this->selectedMonth = null;
    }

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
        $this->sortBy        = 'points_desc';
        $this->filterDistrict = '';
        $this->rankingFrom   = null;
        $this->rankingTo     = null;
        $this->ageFrom       = null;
        $this->ageTo         = null;
        $this->selectedMonth = null;
    }

    public function applyFilters()
    {
        $this->showFilters = false;
        $this->resetPage();
    }

    public function render()
    {
        // Parse selected month for filtering
        $selectedYear = null;
        $selectedMonth = null;
        if ($this->selectedMonth) {
            $parts = explode('-', $this->selectedMonth);
            if (count($parts) === 2) {
                $selectedYear = (int) $parts[0];
                $selectedMonth = (int) $parts[1];
            }
        }

        $query = User::query()
            ->where('visible_in_players', true)
            ->with('districtModel')
            ->with(['club.monthlyRankings' => function ($q) use ($selectedYear, $selectedMonth) {
                if ($selectedYear && $selectedMonth) {
                    $q->where('year', $selectedYear)
                      ->where('month', $selectedMonth);
                } else {
                    $q->orderBy('year', 'desc')
                      ->orderBy('month', 'desc')
                      ->limit(1);
                }
            }])
            ->with(['monthlyRankings' => function ($q) use ($selectedYear, $selectedMonth) {
                if ($selectedYear && $selectedMonth) {
                    $q->where('year', $selectedYear)
                      ->where('month', $selectedMonth);
                } else {
                    $q->where('year', now()->year)
                      ->where('month', now()->month);
                }
            }]);

        // Search by name or email (with Nordic character support)
        if ($this->search) {
            $search = trim($this->search);

            // If search contains space, split and search first/last name parts
            if (str_contains($search, ' ')) {
                $parts = explode(' ', $search, 2);
                $firstName = trim($parts[0]);
                $lastName = trim($parts[1] ?? '');

                if ($firstName && $lastName) {
                    // Apply search for first name
                    $this->applySearch($query, $firstName, ['first_name']);
                    // Apply search for last name (both must match)
                    $this->applySearch($query, $lastName, ['last_name']);
                } else {
                    // If only first part exists, search normally
                    $this->applySearch($query, $firstName ?: $lastName, ['first_name', 'last_name', 'email']);
                }
            } else {
                // Single word search - search in both first_name and last_name separately
                $this->applySearch($query, $search, ['first_name', 'last_name', 'email']);
            }
        }

        // Filter by gender
        if ($this->gender) {
            $query->where('gender', $this->gender);
        }

        // Filter by district
        if ($this->filterDistrict !== '') {
            $query->where('district_id', (int) $this->filterDistrict);
        }

        // Filter by age range
        if ($this->ageFrom) {
            $query->where('age', '>=', $this->ageFrom);
        }

        if ($this->ageTo) {
            $query->where('age', '<=', $this->ageTo);
        }

        // Filter by ranking range (using most recent ranking or selected month)
        if ($this->rankingFrom || $this->rankingTo || ($selectedYear && $selectedMonth)) {
            $query->whereHas('monthlyRankings', function ($q) use ($selectedYear, $selectedMonth) {
                if ($selectedYear && $selectedMonth) {
                    // Filter by selected month
                    $q->where('year', $selectedYear)
                      ->where('month', $selectedMonth);
                } else {
                    // Get the most recent year/month combination for filtering
                    $q->whereRaw('(year, month) = (
                        SELECT year, month
                        FROM monthly_rankings mr2
                        WHERE mr2.user_id = monthly_rankings.user_id
                        ORDER BY year DESC, month DESC
                        LIMIT 1
                    )');
                }

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
                if ($selectedYear && $selectedMonth) {
                    // Use selected month for sorting
                    $query->leftJoin(\DB::raw("(
                        SELECT *
                        FROM monthly_rankings
                        WHERE year = {$selectedYear} AND month = {$selectedMonth}
                    ) as latest_rankings"), 'users.id', '=', 'latest_rankings.user_id')
                    ->orderByRaw('latest_rankings.points IS NULL, latest_rankings.points DESC')
                    ->select('users.*');
                } else {
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
                }
                break;
            case 'points_asc':
                if ($selectedYear && $selectedMonth) {
                    // Use selected month for sorting
                    $query->leftJoin(\DB::raw("(
                        SELECT *
                        FROM monthly_rankings
                        WHERE year = {$selectedYear} AND month = {$selectedMonth}
                    ) as latest_rankings"), 'users.id', '=', 'latest_rankings.user_id')
                    ->orderByRaw('latest_rankings.points IS NULL DESC, latest_rankings.points ASC')
                    ->select('users.*');
                } else {
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
                }
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

        // Load manual points delta for this month (same logic as show page)
        $playerIds = $players->pluck('id')->toArray();
        $manualPointsMap = [];
        if (!$selectedYear && !$selectedMonth && !empty($playerIds)) {
            \App\Models\GameMatch::where(function ($q) use ($playerIds) {
                    $q->whereIn('player1_id', $playerIds)
                      ->orWhereIn('player2_id', $playerIds);
                })
                ->where('is_manual', true)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->get()
                ->each(function ($match) use (&$manualPointsMap, $playerIds) {
                    if (in_array($match->player1_id, $playerIds)) {
                        $manualPointsMap[$match->player1_id] = ($manualPointsMap[$match->player1_id] ?? 0) + $match->player1_points_change;
                    }
                    if (in_array($match->player2_id, $playerIds)) {
                        $manualPointsMap[$match->player2_id] = ($manualPointsMap[$match->player2_id] ?? 0) + $match->player2_points_change;
                    }
                });
        }

        // Get top 3 positions for men and women (from selected month or most recent rankings)
        if ($selectedYear && $selectedMonth) {
            // Use selected month
            $rankingYear = $selectedYear;
            $rankingMonth = $selectedMonth;
        } else {
            // Get most recent rankings
            $latestRanking = MonthlyRanking::orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();
            $rankingYear = $latestRanking?->year;
            $rankingMonth = $latestRanking?->month;
        }

        $topMen = [];
        $topWomen = [];

        if ($rankingYear && $rankingMonth) {
            $topMen = MonthlyRanking::where('year', $rankingYear)
                ->where('month', $rankingMonth)
                ->whereHas('user', function ($q) {
                    $q->where('gender', 'male')
                      ->where('is_active_player', true)
                      ->where('visible_in_players', true);
                })
                ->orderByDesc('points')
                ->limit(3)
                ->pluck('user_id')
                ->toArray();

            $topWomen = MonthlyRanking::where('year', $rankingYear)
                ->where('month', $rankingMonth)
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
            'players'            => $players,
            'rankingPositions'   => $rankingPositions,
            'availableDistricts' => District::orderBy('name')->get(['id', 'name']),
            'manualPointsMap'    => $manualPointsMap,
        ])->layout('components.layouts.app');
    }
}
