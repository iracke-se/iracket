<?php

namespace App\Livewire\User\Rankings;

use App\Models\GameMatch;
use Livewire\Component;

class Index extends Component
{
    public ?int $expandedRankingId = null;

    public function toggleRanking($rankingId)
    {
        $this->expandedRankingId = $this->expandedRankingId === $rankingId ? null : $rankingId;
    }

    public function getMatchesForRanking($year, $month)
    {
        $player = auth()->user();
        $prevMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();

        $gameMatches = GameMatch::where(function ($query) use ($player) {
            $query->where('player1_id', $player->id)
                  ->orWhere('player2_id', $player->id);
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

        $playerFullName = $player->last_name . ', ' . $player->first_name;
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

        $usedScrapedIds = [];

        foreach ($gameMatches as $gm) {
            $isPlayer1 = $gm->player1_id === $player->id;
            $opponentUser = $isPlayer1 ? $gm->player2 : $gm->player1;
            if (!$opponentUser) continue;

            $gmDate = \Carbon\Carbon::parse($gm->played_at)->format('Y-m-d');

            $matchingSm = $scrapedMatches->first(function ($sm) use ($opponentUser, $gmDate, $playerFullName) {
                $smDate = \Carbon\Carbon::parse($sm->match_date ?? $sm->played_at)->format('Y-m-d');
                if ($smDate !== $gmDate) return false;

                $isPlayerMatch = ($sm->player_name === $playerFullName);
                $rawOpponent   = $isPlayerMatch ? $sm->opponent_name : $sm->player_name;

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
                $gm->match_points_scraped = $isPlayerMatch ? $matchingSm->match_points : null;
                $usedScrapedIds[] = $matchingSm->getKey();
            }
        }

        $remainingScraped = $scrapedMatches->whereNotIn('id', $usedScrapedIds)->values();

        return $gameMatches->concat($remainingScraped);
    }

    public function render()
    {
        $player = auth()->user()->load('districtModel');

        $currentRanking = $player->currentMonthRanking();

        $rankingsHistory = $player->monthlyRankings()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $rankingPosition = $player->getCurrentRankingPosition();
        $rankingCategory = $player->getRankingCategory();

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

        return view('livewire.user.rankings.index', [
            'player' => $player,
            'currentRanking' => $currentRanking,
            'rankingsHistory' => $rankingsHistory,
            'rankingPosition' => $rankingPosition,
            'rankingCategory' => $rankingCategory,
            'expandedRankingMatches' => $expandedRankingMatches,
        ])->layout('components.layouts.app');
    }
}
