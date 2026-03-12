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
        $this->player = $user->load('district');
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
        // Rankings for month X reflect matches played in month X-1
        $prevMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();

        // Live Center matches — played in the previous month (have set scores)
        $gameMatches = GameMatch::where(function ($query) {
            $query->where('player1_id', $this->player->id)
                  ->orWhere('player2_id', $this->player->id);
        })
        ->whereYear('played_at', $prevMonth->year)
        ->whereMonth('played_at', $prevMonth->month)
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

        // Rankings popup matches — these are the matches that *contributed* to this
        // month's ranking (typically played the previous month, stored under scraped_month)
        $playerFullName = $this->player->last_name . ', ' . $this->player->first_name;
        $scrapedMonth = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);

        $scrapedMatches = \App\Models\Scraper\ScrapedMatch::where(function ($query) use ($playerFullName) {
            $query->where('player_name', $playerFullName)
                  ->orWhere('opponent_name', $playerFullName);
        })
        ->where('scraped_month', $scrapedMonth)
        ->orderByRaw('COALESCE(match_date, played_at) DESC')
        ->get()
        ->unique(function ($m) use ($playerFullName) {
            $displayedOpponent = ($m->player_name === $playerFullName) ? $m->opponent_name : $m->player_name;
            return $displayedOpponent . '|' . ($m->match_date ?? $m->played_at);
        })
        ->values();

        // Cross-reference: attach match_points from ScrapedMatch onto its GameMatch counterpart
        // (Live Center has scores but no ranking points; rankings popup has points but no scores)
        $usedScrapedIds = [];

        foreach ($gameMatches as $gm) {
            $isPlayer1 = $gm->player1_id === $this->player->id;
            $opponentUser = $isPlayer1 ? $gm->player2 : $gm->player1;
            if (!$opponentUser) continue;

            $gmDate = \Carbon\Carbon::parse($gm->played_at)->format('Y-m-d');

            $matchingSm = $scrapedMatches->first(function ($sm) use ($opponentUser, $gmDate, $playerFullName) {
                $smDate = \Carbon\Carbon::parse($sm->match_date ?? $sm->played_at)->format('Y-m-d');
                if ($smDate !== $gmDate) return false;

                $isPlayerMatch = ($sm->player_name === $playerFullName);
                $rawOpponent   = $isPlayerMatch ? $sm->opponent_name : $sm->player_name;

                // Normalize opponent name — handles both "Surname, Firstname" and "Firstname Lastname"
                $parts = array_map('trim', explode(',', $rawOpponent, 2));
                if (count($parts) === 2) {
                    $smLast  = strtolower($parts[0]);
                    $smFirst = strtolower($parts[1]);
                } else {
                    $words   = explode(' ', trim($rawOpponent));
                    $smLast  = strtolower(array_pop($words));
                    $smFirst = strtolower(implode(' ', $words));
                }

                return strtolower($opponentUser->last_name)  === $smLast
                    && strtolower($opponentUser->first_name) === $smFirst;
            });

            if ($matchingSm) {
                $isPlayerMatch = ($matchingSm->player_name === $playerFullName);
                // Attach ranking points as virtual attribute on the GameMatch object
                $gm->match_points_scraped = $isPlayerMatch ? $matchingSm->match_points : null;
                $usedScrapedIds[] = $matchingSm->getKey();
            }
        }

        // Keep only ScrapedMatches that have no GameMatch counterpart
        $remainingScraped = $scrapedMatches->whereNotIn('id', $usedScrapedIds)->values();

        return $gameMatches->concat($remainingScraped);
    }

    public function render()
    {
        $currentRanking = $this->player->currentMonthRanking();

        $rankingsHistory = $this->player->monthlyRankings()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
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
