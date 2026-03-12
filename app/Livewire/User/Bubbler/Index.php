<?php

namespace App\Livewire\User\Bubbler;

use App\Models\Club;
use App\Models\ClubTransition;
use App\Models\District;
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
    public string $filterPointsFrom = '';
    public string $filterPointsTo   = '';
    public string $filterAgeFrom    = '';
    public string $filterAgeTo      = '';
    public string $sortPoints       = 'desc'; // 'asc' | 'desc'

    // Club filter
    public string $sortClubsBy = 'points_gained'; // 'points_gained' | 'new_members' | 'bubblare'

    // SBTF standard point ranges (descending)
    protected static array $pointRanges = [
        ['label' => '2000+',       'min' => 2000, 'max' => null],
        ['label' => '1500 - 1999', 'min' => 1500, 'max' => 1999],
        ['label' => '750 - 1499',  'min' => 750,  'max' => 1499],
        ['label' => '500 - 749',   'min' => 500,  'max' => 749],
        ['label' => '0 - 499',     'min' => 0,    'max' => 499],
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
        $this->filterDistrict   = '';
        $this->filterPointsFrom = '';
        $this->filterPointsTo   = '';
        $this->filterAgeFrom    = '';
        $this->filterAgeTo      = '';
        $this->sortPoints       = 'desc';
        $this->sortClubsBy      = 'points_gained';
    }

    public function updatedSelectedYear(): void  { $this->validatePeriod(); }
    public function updatedSelectedMonth(): void { $this->validatePeriod(); }

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
        if ($this->filterDistrict !== '')   $count++;
        if ($this->filterPointsFrom !== '') $count++;
        if ($this->filterPointsTo !== '')   $count++;
        if ($this->filterAgeFrom !== '')    $count++;
        if ($this->filterAgeTo !== '')      $count++;
        return $count;
    }

    protected function groupByPointRanges(Collection $rankings, string $pointsField = 'points'): array
    {
        $ranges = $this->sortPoints === 'asc'
            ? array_reverse(static::$pointRanges)
            : static::$pointRanges;

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

        if ($this->filterPointsFrom !== '') {
            $query->where('points', '>=', (int) $this->filterPointsFrom);
        }
        if ($this->filterPointsTo !== '') {
            $query->where('points', '<=', (int) $this->filterPointsTo);
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
        $allRankings = MonthlyRanking::where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth)
            ->whereHas('user', fn ($q) => $q->whereNotNull('club_id'))
            ->with('user:id,club_id')
            ->get(['user_id', 'points_change', 'points']);

        if ($allRankings->isEmpty()) {
            return collect();
        }

        // Group by club_id
        $byClub = $allRankings->groupBy(fn ($r) => $r->user->club_id);

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
                    'club_id'            => $clubId,
                    'total_points_gained' => $totalPointsGained,
                    'new_members'        => $newMembers,
                    'bubblare_count'     => $bubblerCount,
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
        $ladiesQuery = MonthlyRanking::where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth);
        $this->applyPlayerFilters($ladiesQuery, 'female');
        $ladiesRankings = $ladiesQuery->with('user.club')->orderBy('points', 'desc')->get()
            ->filter(fn ($r) => $r->user !== null);

        $menQuery = MonthlyRanking::where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth);
        $this->applyPlayerFilters($menQuery, 'male');
        $menRankings = $menQuery->with('user.club')->orderBy('points', 'desc')->get()
            ->filter(fn ($r) => $r->user !== null);

        return view('livewire.user.bubbler.index', [
            'ladiesGrouped'      => $this->groupByPointRanges($ladiesRankings),
            'menGrouped'         => $this->groupByPointRanges($menRankings),
            'clubBubblerRankings' => $this->computeClubBubblerRankings(),
            'availableDistricts' => $this->availableDistricts,
            'activeFilterCount'  => $this->activeFilterCount,
        ])->layout('components.layouts.app');
    }
}
