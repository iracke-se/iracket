<?php

namespace App\Livewire\User\Districts;

use App\Models\District;
use App\Models\MonthlyRanking;
use App\Models\User;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination, HasSearchableQueries;

    public District $district;
    public string $search = '';
    public string $gender = '';

    public function mount(District $district): void
    {
        $this->district = $district;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGender(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = User::query()
            ->where('visible_in_players', true)
            ->where('district_id', $this->district->id)
            ->with(['club', 'monthlyRankings' => function ($q) {
                $q->where('year', now()->year)
                  ->where('month', now()->month);
            }]);

        if ($this->search) {
            $search = trim($this->search);
            if (str_contains($search, ' ')) {
                $parts = explode(' ', $search, 2);
                $firstName = trim($parts[0]);
                $lastName = trim($parts[1] ?? '');
                if ($firstName && $lastName) {
                    $this->applySearch($query, $firstName, ['first_name']);
                    $this->applySearch($query, $lastName, ['last_name']);
                } else {
                    $this->applySearch($query, $firstName ?: $lastName, ['first_name', 'last_name']);
                }
            } else {
                $this->applySearch($query, $search, ['first_name', 'last_name']);
            }
        }

        if ($this->gender) {
            $query->where('gender', $this->gender);
        }

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

        $players = $query->paginate(20);

        // Manual points delta for current month
        $playerIds = $players->pluck('id')->toArray();
        $manualPointsMap = [];
        if (!empty($playerIds)) {
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

        return view('livewire.user.districts.show', [
            'players'         => $players,
            'manualPointsMap' => $manualPointsMap,
        ])->layout('components.layouts.app');
    }
}
