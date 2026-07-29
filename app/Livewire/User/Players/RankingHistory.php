<?php

namespace App\Livewire\User\Players;

use App\Models\GameMatch;
use App\Models\Scraper\ScrapedMatch;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class RankingHistory extends Component
{
    public User $player;

    public function placeholder()
    {
        return <<<'HTML'
        <div class="mb-6">
            <div class="h-6 w-36 bg-zinc-100 dark:bg-zinc-800 rounded animate-pulse mb-3"></div>
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 space-y-3">
                <div class="animate-pulse h-8 bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
                <div class="animate-pulse h-8 bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
                <div class="animate-pulse h-8 bg-zinc-200 dark:bg-zinc-700 rounded-lg"></div>
            </div>
        </div>
        HTML;
    }

    /**
     * Load the player's full match history once (2 queries) and group it by
     * month, instead of querying per accordion row.
     */
    protected function getMatchesByMonth()
    {
        $gameMatches = GameMatch::where(function ($query) {
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
            ->get();

        $playerFullName = $this->player->last_name . ', ' . $this->player->first_name;

        $scrapedMatches = ScrapedMatch::where(function ($query) use ($playerFullName) {
            $query->where('player_name', $playerFullName)
                ->orWhere('opponent_name', $playerFullName);
        })
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
            if (!$opponentUser)
                continue;

            $gmDate = $gm->played_at->format('Y-m-d');

            $matchingSm = $scrapedMatches->first(function ($sm) use ($opponentUser, $gmDate, $playerFullName) {
                $smDate = $this->scrapedMatchDate($sm)?->format('Y-m-d');
                if ($smDate !== $gmDate)
                    return false;

                $isPlayerMatch = ($sm->player_name === $playerFullName);
                $rawOpponent = $isPlayerMatch ? $sm->opponent_name : $sm->player_name;

                // Normalize opponent name — handles both "Surname, Firstname" and "Firstname Lastname"
                $parts = array_map('trim', explode(',', $rawOpponent, 2));
                if (count($parts) === 2) {
                    $smLast = strtolower($parts[0]);
                    $smFirst = strtolower($parts[1]);
                } else {
                    $words = explode(' ', trim($rawOpponent));
                    $smLast = strtolower(array_pop($words));
                    $smFirst = strtolower(implode(' ', $words));
                }

                return strtolower($opponentUser->last_name) === $smLast
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

        return $gameMatches->concat($remainingScraped)
            ->groupBy(function ($match) {
                $date = $match instanceof ScrapedMatch
                    ? $this->scrapedMatchDate($match)
                    : $match->played_at;
                return $date?->format('Y-n') ?? 'unknown';
            })
            ->map(function ($group) {
                return $group->sortByDesc(function ($match) {
                    return $match instanceof ScrapedMatch
                        ? $this->scrapedMatchDate($match)
                        : $match->played_at;
                })->values();
            });
    }

    protected function scrapedMatchDate(ScrapedMatch $match): ?Carbon
    {
        $raw = $match->match_date ?? $match->played_at;
        if (!$raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public function render()
    {
        $rankingsHistory = $this->player->monthlyRankings()
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        // Since matches are shown under their actual played month (not the ranking month),
        // shift points_change one row forward: each month's change reflects the matches played
        // that month (which appear in the NEXT month's ranking). The most recent row gets null.
        $originalChanges = $rankingsHistory->pluck('points_change')->all();
        $rankingsHistory->each(function ($ranking, $index) use ($originalChanges) {
            $ranking->points_change = $index > 0 ? $originalChanges[$index - 1] : null;
        });

        return view('livewire.user.players.ranking-history', [
            'rankingsHistory' => $rankingsHistory,
            'matchesByMonth' => $rankingsHistory->isEmpty() ? collect() : $this->getMatchesByMonth(),
        ]);
    }
}
