<?php

namespace App\Livewire\User\Players;

use App\Models\GameMatch;
use App\Models\MonthlyRanking;
use App\Models\User;
use Livewire\Component;

class Show extends Component
{
    public User $player;

    // Find Players modal
    public bool $showFindPlayersModal = false;
    public string $findPlayersSearch = '';

    public function mount(User $user)
    {
        $this->player = $user->load('districtModel');
    }

    public function toggleMonitor()
    {
        auth()->user()->toggleMonitoring($this->player);
    }

    public function openFindPlayersModal()
    {
        $this->findPlayersSearch = '';
        $this->showFindPlayersModal = true;
    }

    public function closeFindPlayersModal()
    {
        $this->showFindPlayersModal = false;
        $this->findPlayersSearch = '';
    }

    public function toggleMonitorFor($userId)
    {
        $target = User::find($userId);
        if ($target && $target->id !== auth()->id()) {
            auth()->user()->toggleMonitoring($target);
        }
    }

    public function render()
    {
        $currentRanking = $this->player->currentMonthRanking();

        // Sum points from manual matches this month (not stored in monthly_rankings)
        $manualPointsDelta = GameMatch::where(function ($q) {
            $q->where('player1_id', $this->player->id)
                ->orWhere('player2_id', $this->player->id);
        })
            ->where('is_manual', true)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->get()
            ->sum(
                fn($m) => $m->player1_id === $this->player->id
                ? $m->player1_points_change
                : $m->player2_points_change
            );

        $currentRankingPoints = ($currentRanking?->points ?? 0) + $manualPointsDelta;

        $rankingsHistory = $this->player->monthlyRankings()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Since matches are now shown under their actual played month (not the ranking month),
        // shift points_change one row forward: each month's change reflects the matches played
        // that month (which appear in the NEXT month's ranking). The most recent row gets null.
        $originalChanges = $rankingsHistory->pluck('points_change')->all();
        $rankingsHistory->each(function ($ranking, $index) use ($originalChanges) {
            $ranking->points_change = $index > 0 ? $originalChanges[$index - 1] : null;
        });

        $isOwnProfile = auth()->id() === $this->player->id;
        $isMonitoring = auth()->user()->isMonitoring($this->player);

        // Data for own profile
        $topMonitoredPlayers = collect();
        $playerLatestMatches = collect();

        if ($isOwnProfile) {
            // Get the player's own last 10 matches
            $playerLatestMatches = GameMatch::where(function ($query) {
                $query->where('player1_id', $this->player->id)
                    ->orWhere('player2_id', $this->player->id);
            })
                ->with([
                    'player1',
                    'player2',
                    'winner',
                    'liveMatchGame.sets' => function ($query) {
                        $query->orderBy('set_number');
                    },
                    'liveMatchGame.detail'
                ])
                ->orderBy('played_at', 'desc')
                ->take(10)
                ->get();

            // Get top monitored players (those with highest points)
            $topMonitoredPlayers = auth()->user()
                ->monitoring()
                ->with([
                    'monthlyRankings' => function ($q) {
                        $q->orderBy('year', 'desc')
                            ->orderBy('month', 'desc');
                    }
                ])
                ->get()
                ->sortByDesc(function ($player) {
                    return $player->monthlyRankings->first()?->points ?? 0;
                })
                ->take(10);
        }

        // Get player's ranking position in their gender category
        $rankingPosition = $this->player->getCurrentRankingPosition();
        $rankingCategory = $this->player->getRankingCategory();

        // Get club transitions
        $clubTransitions = $this->player->clubTransitions()
            ->with(['fromClub', 'toClub'])
            ->orderByDesc('completion_date')
            ->get();

        // Find Players modal data — top 10 by latest ranking points, or filtered by search
        $findPlayersResults = collect();
        $monitoringIds = [];
        if ($isOwnProfile && $this->showFindPlayersModal) {
            $monitoringIds = auth()->user()->monitoring()->pluck('users.id')->toArray();

            $latestRanking = MonthlyRanking::orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->first();

            $playersQuery = User::query()
                ->where('visible_in_players', true)
                ->where('users.id', '!=', auth()->id())
                ->with([
                    'monthlyRankings' => function ($q) {
                        $q->orderBy('year', 'desc')->orderBy('month', 'desc');
                    }
                ]);

            $search = trim($this->findPlayersSearch);
            if ($search !== '') {
                $playersQuery->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                        ->orWhereRaw("CONCAT(last_name, ' ', first_name) like ?", ["%{$search}%"]);
                });
                $findPlayersResults = $playersQuery->limit(20)->get();
            } elseif ($latestRanking) {
                // Top 10 by points from the latest ranking month
                $findPlayersResults = $playersQuery
                    ->leftJoin('monthly_rankings', function ($join) use ($latestRanking) {
                        $join->on('users.id', '=', 'monthly_rankings.user_id')
                            ->where('monthly_rankings.year', '=', $latestRanking->year)
                            ->where('monthly_rankings.month', '=', $latestRanking->month);
                    })
                    ->orderByRaw('monthly_rankings.points IS NULL, monthly_rankings.points DESC')
                    ->select('users.*')
                    ->limit(10)
                    ->get();
            } else {
                $findPlayersResults = $playersQuery->orderBy('first_name')->limit(10)->get();
            }
        }

        return view('livewire.user.players.show', [
            'currentRanking' => $currentRanking,
            'currentRankingPoints' => $currentRankingPoints,
            'manualPointsDelta' => $manualPointsDelta,
            'rankingsHistory' => $rankingsHistory,
            'isOwnProfile' => $isOwnProfile,
            'isMonitoring' => $isMonitoring,
            'topMonitoredPlayers' => $topMonitoredPlayers,
            'playerLatestMatches' => $playerLatestMatches,
            'rankingPosition' => $rankingPosition,
            'rankingCategory' => $rankingCategory,
            'clubTransitions' => $clubTransitions,
            'findPlayersResults' => $findPlayersResults,
            'monitoringIds' => $monitoringIds,
        ])->layout('components.layouts.app');
    }
}
