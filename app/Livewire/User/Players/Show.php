<?php

namespace App\Livewire\User\Players;

use App\Models\GameMatch;
use App\Models\User;
use Livewire\Component;

class Show extends Component
{
    public User $player;

    public function mount(User $user)
    {
        $this->player = $user;
    }

    public function toggleMonitor()
    {
        auth()->user()->toggleMonitoring($this->player);
    }

    public function render()
    {
        $currentRanking = $this->player->currentMonthRanking();

        $rankingsHistory = $this->player->monthlyRankings()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        $isOwnProfile = auth()->id() === $this->player->id;
        $isMonitoring = auth()->user()->isMonitoring($this->player);

        // Data for own profile
        $topMonitoredPlayers = collect();
        $monitoredPlayersMatches = collect();

        if ($isOwnProfile) {
            // Get top monitored players (those with highest points)
            $topMonitoredPlayers = auth()->user()
                ->monitoring()
                ->with(['monthlyRankings' => function ($q) {
                    $q->where('year', now()->year)
                      ->where('month', now()->month);
                }])
                ->get()
                ->sortByDesc(function ($player) {
                    return $player->monthlyRankings->first()?->points ?? 0;
                })
                ->take(10);

            // Get last 10 matches from monitored players
            $monitoredPlayerIds = auth()->user()->monitoring()->pluck('users.id');

            if ($monitoredPlayerIds->isNotEmpty()) {
                $monitoredPlayersMatches = GameMatch::query()
                    ->where(function ($query) use ($monitoredPlayerIds) {
                        $query->whereIn('player1_id', $monitoredPlayerIds)
                              ->orWhereIn('player2_id', $monitoredPlayerIds);
                    })
                    ->with(['player1', 'player2', 'winner'])
                    ->orderBy('played_at', 'desc')
                    ->take(10)
                    ->get();
            }
        }

        // Get player's ranking position in their gender category
        $rankingPosition = $this->player->getCurrentRankingPosition();
        $rankingCategory = $this->player->getRankingCategory();

        // Get club transitions
        $clubTransitions = $this->player->clubTransitions()
            ->with(['fromClub', 'toClub'])
            ->orderByDesc('completion_date')
            ->get();

        return view('livewire.user.players.show', [
            'currentRanking' => $currentRanking,
            'rankingsHistory' => $rankingsHistory,
            'isOwnProfile' => $isOwnProfile,
            'isMonitoring' => $isMonitoring,
            'topMonitoredPlayers' => $topMonitoredPlayers,
            'monitoredPlayersMatches' => $monitoredPlayersMatches,
            'rankingPosition' => $rankingPosition,
            'rankingCategory' => $rankingCategory,
            'clubTransitions' => $clubTransitions,
        ])->layout('components.layouts.app');
    }
}
