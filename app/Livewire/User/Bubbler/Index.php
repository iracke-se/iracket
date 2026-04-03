<?php

namespace App\Livewire\User\Bubbler;

use App\Models\Club;
use App\Models\ClubTransition;
use App\Models\District;
use App\Models\GameMatch;
use App\Models\MonthlyRanking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'men';
    public int $selectedYear;
    public int $selectedMonth;

    // Player filters
    public string $filterDistrict   = '';
    public string $filterPointsClass = ''; // encoded as "min:max" or "min:" for no upper bound
    public string $filterAgeFrom    = '';
    public string $filterAgeTo      = '';
    public string $sortPoints       = 'desc'; // 'asc' | 'desc'

    // Club filter
    public string $sortClubsBy = 'points_gained'; // 'points_gained' | 'new_members' | 'bubblare'

    // SBTF standard point class ranges per gender
    protected static array $menClassRanges = [
        ['label' => 'Elite · 2250+',       'min' => 2250, 'max' => null],
        ['label' => 'Class 1 · 2000–2249', 'min' => 2000, 'max' => 2249],
        ['label' => 'Class 2 · 1750–1999', 'min' => 1750, 'max' => 1999],
        ['label' => 'Class 3 · 1500–1749', 'min' => 1500, 'max' => 1749],
        ['label' => 'Class 4 · 1250–1499', 'min' => 1250, 'max' => 1499],
        ['label' => 'Class 5 · 1000–1249', 'min' => 1000, 'max' => 1249],
        ['label' => 'Class 6 · 750–999',   'min' => 750,  'max' => 999],
        ['label' => 'Class 7 · 0–749',     'min' => 0,    'max' => 749],
    ];

    protected static array $womenClassRanges = [
        ['label' => 'Elite · 1750+',       'min' => 1750, 'max' => null],
        ['label' => 'Class 1 · 1500–1749', 'min' => 1500, 'max' => 1749],
        ['label' => 'Class 2 · 1250–1499', 'min' => 1250, 'max' => 1499],
        ['label' => 'Class 3 · 1000–1249', 'min' => 1000, 'max' => 1249],
        ['label' => 'Class 4 · 750–999',   'min' => 750,  'max' => 999],
        ['label' => 'Class 5 · 0–749',     'min' => 0,    'max' => 749],
    ];

    public function mount(): void
    {
        $user = auth()->user();
        if ($user && $user->is_connected && $user->gender === 'female') {
            $this->activeTab = 'ladies';
        }

        if ($user && $user->is_connected && $user->district_id) {
            $this->filterDistrict = (string) $user->district_id;
        }

        $latestRanking = MonthlyRanking::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        if ($latestRanking) {
            $this->selectedYear  = $latestRanking->year;
            $this->selectedMonth = $latestRanking->month;
        } else {
            $this->selectedYear  = now()->year;
            $this->selectedMonth = now()->month;
        }
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function clearFilters(): void
    {
        $this->filterDistrict    = '';
        $this->filterPointsClass = '';
        $this->filterAgeFrom     = '';
        $this->filterAgeTo       = '';
        $this->sortPoints        = 'desc';
        $this->sortClubsBy       = 'points_gained';
    }

    public function updatedSelectedYear(): void
    {
        $this->validatePeriod();
    }

    public function updatedSelectedMonth(): void
    {
        $this->validatePeriod();
    }

    protected function validatePeriod(): void
    {
        $now      = now();
        $selected = Carbon::create($this->selectedYear, $this->selectedMonth);
        if ($selected->isFuture()) {
            $this->selectedYear  = $now->year;
            $this->selectedMonth = $now->month;
        }
    }

    public function getAvailableYearsProperty(): array
    {
        $years = MonthlyRanking::selectRaw('DISTINCT year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (! in_array(now()->year, $years)) {
            $years[] = now()->year;
            rsort($years);
        }

        return $years;
    }

    public function getAvailableMonthsForYearProperty(): array
    {
        $months = MonthlyRanking::selectRaw('DISTINCT month')
            ->where('year', $this->selectedYear)
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->toArray();

        if (empty($months) && $this->selectedYear == now()->year) {
            return [now()->month];
        }

        return $months ?: range(1, 12);
    }

    public function getAvailableDistrictsProperty()
    {
        return District::orderBy('name')->get(['id', 'name']);
    }

    public function getActiveFilterCountProperty(): int
    {
        if ($this->activeTab === 'clubs') {
            return $this->sortClubsBy !== 'points_gained' ? 1 : 0;
        }

        $count = 0;
        if ($this->filterDistrict !== '') {
            $count++;
        }
        if ($this->filterPointsClass !== '') {
            $count++;
        }
        if ($this->filterAgeFrom !== '') {
            $count++;
        }
        if ($this->filterAgeTo !== '') {
            $count++;
        }

        return $count;
    }

    protected function groupByPointRanges(Collection $rankings, string $gender, string $pointsField = 'points'): array
    {
        $baseRanges = $gender === 'female' ? static::$womenClassRanges : static::$menClassRanges;
        $ranges = $this->sortPoints === 'asc' ? array_reverse($baseRanges) : $baseRanges;

        $grouped = [];

        foreach ($ranges as $range) {
            $players = $rankings->filter(function ($r) use ($range, $pointsField) {
                $pts = $r->{$pointsField};

                return $range['max'] === null
                    ? $pts >= $range['min']
                    : $pts >= $range['min'] && $pts <= $range['max'];
            })->values()->take(3);

            if ($players->isNotEmpty()) {
                $grouped[] = ['label' => $range['label'], 'players' => $players];
            }
        }

        return $grouped;
    }

    protected function applyPlayerFilters($query, string $gender): void
    {
        $query->whereHas('user', function ($q) use ($gender) {
            $q->where('gender', $gender);

            if ($this->filterDistrict !== '') {
                $q->where('district_id', (int) $this->filterDistrict);
            }

            if ($this->filterAgeFrom !== '' || $this->filterAgeTo !== '') {
                $q->whereNotNull('birth_year');
                if ($this->filterAgeFrom !== '') {
                    $q->where('birth_year', '<=', now()->year - (int) $this->filterAgeFrom);
                }
                if ($this->filterAgeTo !== '') {
                    $q->where('birth_year', '>=', now()->year - (int) $this->filterAgeTo);
                }
            }
        });

        if ($this->filterPointsClass !== '') {
            [$min, $max] = explode(':', $this->filterPointsClass);
            $query->where('points', '>=', (int) $min);
            if ($max !== '') {
                $query->where('points', '<=', (int) $max);
            }
        }
    }

    /**
     * Compute the Club Bubblare ranking:
     * - For each club take the top 10 players by points_change
     * - Sum their positive points_change → total_points_gained
     * - Count climbers with points_change > 0 → bubblare_count
     * - Count club_transitions.to_club_id this month → new_members
     * - Rank top 10 clubs
     */
    protected function computeClubBubblerRankings(): Collection
    {
        // All monthly rankings for this period that have a club
        // Join users inline to avoid eager-loading full model objects
        $allRankings = MonthlyRanking::where('monthly_rankings.year', $this->selectedYear)
            ->where('monthly_rankings.month', $this->selectedMonth)
            ->join('users', 'users.id', '=', 'monthly_rankings.user_id')
            ->whereNotNull('users.club_id')
            ->select('monthly_rankings.user_id', 'monthly_rankings.points_change', 'monthly_rankings.points', 'users.club_id')
            ->get();

        if ($allRankings->isEmpty()) {
            return collect();
        }

        // Group by club_id
        $byClub = $allRankings->groupBy(fn ($r) => $r->club_id);

        $clubIds = $byClub->keys()->toArray();

        // Batch-fetch new members (via club_transitions) for all clubs at once
        $periodStart = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $periodEnd   = $periodStart->copy()->endOfMonth();

        $newMembersByClub = ClubTransition::whereIn('to_club_id', $clubIds)
            ->whereBetween('completion_date', [$periodStart, $periodEnd])
            ->selectRaw('to_club_id, COUNT(*) as cnt')
            ->groupBy('to_club_id')
            ->pluck('cnt', 'to_club_id');

        // Build scores
        $scores = [];
        foreach ($byClub as $clubId => $rankings) {
            $top10            = $rankings->sortByDesc('points_change')->take(10);
            $positiveClimbers = $top10->where('points_change', '>', 0);

            $totalPointsGained = (int) $positiveClimbers->sum('points_change');
            $bubblerCount      = $positiveClimbers->count();
            $newMembers        = (int) ($newMembersByClub[$clubId] ?? 0);

            if ($totalPointsGained > 0 || $newMembers > 0) {
                $scores[$clubId] = [
                    'club_id'             => $clubId,
                    'total_points_gained' => $totalPointsGained,
                    'new_members'         => $newMembers,
                    'bubblare_count'      => $bubblerCount,
                ];
            }
        }

        // Sort
        $sorted = match ($this->sortClubsBy) {
            'new_members' => collect($scores)->sortByDesc('new_members'),
            'bubblare'    => collect($scores)->sortByDesc('bubblare_count'),
            default       => collect($scores)->sortByDesc('total_points_gained'),
        };

        // Top 10, load club models
        $clubs = Club::whereIn('id', $sorted->keys()->take(10)->toArray())
            ->withCount('members')
            ->get()
            ->keyBy('id');

        return $sorted->take(10)->values()
            ->filter(fn ($data) => isset($clubs[$data['club_id']]))
            ->map(fn ($data, $index) => array_merge($data, [
                'club' => $clubs[$data['club_id']],
                'rank' => $index + 1,
            ]));
    }

    public function render()
    {
        $isCurrentMonth = $this->selectedYear == now()->year && $this->selectedMonth == now()->month;

        // Build manual match deltas for current month
        $deltas = [];
        if ($isCurrentMonth) {
            $manualMatches = GameMatch::where('is_manual', true)
                ->whereYear('created_at', $this->selectedYear)
                ->whereMonth('created_at', $this->selectedMonth)
                ->get(['player1_id', 'player2_id', 'player1_points_change', 'player2_points_change']);

            foreach ($manualMatches as $m) {
                $deltas[$m->player1_id] = ($deltas[$m->player1_id] ?? 0) + $m->player1_points_change;
                $deltas[$m->player2_id] = ($deltas[$m->player2_id] ?? 0) + $m->player2_points_change;
            }
        }

        $ladiesGrouped = $this->buildGrouped('female', $deltas, $isCurrentMonth);
        $menGrouped    = $this->buildGrouped('male', $deltas, $isCurrentMonth);

        return view('livewire.user.bubbler.index', [
            'ladiesGrouped'       => $ladiesGrouped,
            'menGrouped'          => $menGrouped,
            'clubBubblerRankings' => $this->computeClubBubblerRankings(),
            'availableDistricts'  => $this->availableDistricts,
            'activeFilterCount'   => $this->activeFilterCount,
            'menClassRanges'      => static::$menClassRanges,
            'womenClassRanges'    => static::$womenClassRanges,
        ])->layout('components.layouts.app');
    }

    protected function buildGrouped(string $gender, array $deltas, bool $isCurrentMonth): array
    {
        // Load only the columns needed for grouping — no user/club eager load yet
        $query = MonthlyRanking::where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth)
            ->select(['user_id', 'points', 'points_change']);

        $this->applyPlayerFilters($query, $gender);

        $rankings = $query->orderBy('points', 'desc')->get();

        // Apply deltas and re-sort if needed
        foreach ($rankings as $r) {
            $r->effective_points = $r->points + ($deltas[$r->user_id] ?? 0);
        }

        if ($isCurrentMonth) {
            $rankings = $rankings->sortByDesc('effective_points')->values();
        }

        // Group into ranges, taking top 3 per range
        $baseRanges = $gender === 'female' ? static::$womenClassRanges : static::$menClassRanges;
        $ranges     = $this->sortPoints === 'asc' ? array_reverse($baseRanges) : $baseRanges;

        $grouped   = [];
        $neededIds = [];

        foreach ($ranges as $range) {
            $players = $rankings->filter(function ($r) use ($range) {
                $pts = $r->effective_points;

                return $range['max'] === null
                    ? $pts >= $range['min']
                    : $pts >= $range['min'] && $pts <= $range['max'];
            })->values()->take(3);

            if ($players->isNotEmpty()) {
                $grouped[] = ['label' => $range['label'], 'players' => $players];
                $neededIds = array_merge($neededIds, $players->pluck('user_id')->all());
            }
        }

        if (empty($neededIds)) {
            return $grouped;
        }

        // Eager-load user+club only for the players we actually display
        $users = User::whereIn('id', array_unique($neededIds))
            ->with('club:id,name,slug')
            ->get([
                'id',
                'first_name',
                'last_name',
                'user_fullname',
                'club_id',
                'gender',
                'birth_year',
                'uuid',
            ])
            ->keyBy('id');

        foreach ($grouped as &$group) {
            foreach ($group['players'] as $r) {
                $r->setRelation('user', $users[$r->user_id] ?? null);
            }
        }

        return array_filter($grouped, fn ($g) => $g['players']->contains(fn ($r) => $r->user !== null));
    }
}