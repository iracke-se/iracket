<?php

namespace App\Livewire\User\Players;

use App\Models\GameMatch;
use App\Models\User;
use Livewire\Component;

class RankingMatchesPanel extends Component
{
    public User $player;
    public int $year;
    public int $month;

    public function placeholder()
    {
        return <<<'HTML'
        <div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4">
            <div class="animate-pulse h-20 bg-zinc-100 dark:bg-zinc-800 rounded-xl"></div>
        </div>
        HTML;
    }

    protected function getMatches()
    {
        // Live Center matches — played in the same month as the accordion
        $gameMatches = GameMatch::where(function ($query) {
            $query->where('player1_id', $this->player->id)
                ->orWhere('player2_id', $this->player->id);
        })
            ->whereYear('played_at', $this->year)
            ->whereMonth('played_at', $this->month)
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

        // Scraped matches — filter by actual match date month, not scraped_month
        $playerFullName = $this->player->last_name . ', ' . $this->player->first_name;

        $scrapedMatches = \App\Models\Scraper\ScrapedMatch::where(function ($query) use ($playerFullName) {
            $query->where('player_name', $playerFullName)
                ->orWhere('opponent_name', $playerFullName);
        })
            ->whereRaw('YEAR(COALESCE(match_date, played_at)) = ?', [$this->year])
            ->whereRaw('MONTH(COALESCE(match_date, played_at)) = ?', [$this->month])
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

            $gmDate = \Carbon\Carbon::parse($gm->played_at)->format('Y-m-d');

            $matchingSm = $scrapedMatches->first(function ($sm) use ($opponentUser, $gmDate, $playerFullName) {
                $smDate = \Carbon\Carbon::parse($sm->match_date ?? $sm->played_at)->format('Y-m-d');
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

        return $gameMatches->concat($remainingScraped);
    }

    public function render()
    {
        return view('livewire.user.players.ranking-matches-panel', [
            'matches' => $this->getMatches(),
        ]);
    }
}
