<?php

namespace App\Livewire\User\Players;

use App\Models\GameMatch;
use App\Models\User;
use Livewire\Component;

class Show extends Component
{
    public User $player;
    public ?int $expandedRankingId = null;

    public function mount(User $user)
    {
        $this->player = $user;
    }

    public function toggleMonitor()
    {
        auth()->user()->toggleMonitoring($this->player);
    }

    public function toggleRanking($rankingId)
    {
        $this->expandedRankingId = $this->expandedRankingId === $rankingId ? null : $rankingId;
    }

    public function getMatchesForRanking($year, $month)
    {
        // First try to get synced matches from GameMatch
        $matches = GameMatch::where(function ($query) {
            $query->where('player1_id', $this->player->id)
                  ->orWhere('player2_id', $this->player->id);
        })
        ->whereYear('played_at', $year)
        ->whereMonth('played_at', $month)
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
        ->get();

        // If no matches found, check scraped matches that might not be synced yet
        if ($matches->isEmpty()) {
            $playerFullName = $this->player->last_name . ', ' . $this->player->first_name;
            $scrapedMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

            $scrapedMatches = \App\Models\Scraper\ScrapedMatch::where(function ($query) use ($playerFullName) {
                $query->where('player_name', $playerFullName)
                      ->orWhere('opponent_name', $playerFullName);
            })
            ->where('scraped_month', $scrapedMonth)
            ->orderByRaw('COALESCE(match_date, played_at) DESC')
            ->get();

            return $scrapedMatches;
        }

        return $matches;
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
                    $q->orderBy('year', 'desc')
                      ->orderBy('month', 'desc');
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

        // Get matches for expanded ranking
        $expandedRankingMatches = collect();
        if ($this->expandedRankingId) {
            $expandedRanking = $rankingsHistory->firstWhere('id', $this->expandedRankingId);
            if ($expandedRanking) {
                $expandedRankingMatches = $this->getMatchesForRanking(
                    $expandedRanking->year,
                    $expandedRanking->month
                );
            }
        }

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
            'expandedRankingMatches' => $expandedRankingMatches,
        ])->layout('components.layouts.app');
    }
}
