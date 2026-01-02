<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MonthlyRanking;
use App\Models\Scraper\ScraperRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BubblerService
{
    protected array $stats = [
        'matches_processed' => 0,
        'points_calculated' => 0,
        'rankings_updated' => 0,
        'errors' => 0,
    ];

    /**
     * Bubbler points table based on ranking difference
     */
    protected array $pointsTable = [
        ['min' => 0, 'max' => 25, 'higher' => 10, 'lower' => 10],
        ['min' => 26, 'max' => 50, 'higher' => 9, 'lower' => 11],
        ['min' => 51, 'max' => 75, 'higher' => 8, 'lower' => 12],
        ['min' => 76, 'max' => 100, 'higher' => 7, 'lower' => 13],
        ['min' => 101, 'max' => 125, 'higher' => 6, 'lower' => 15],
        ['min' => 126, 'max' => 150, 'higher' => 6, 'lower' => 16],
        ['min' => 151, 'max' => 200, 'higher' => 5, 'lower' => 17],
        ['min' => 201, 'max' => 250, 'higher' => 4, 'lower' => 18],
        ['min' => 251, 'max' => 300, 'higher' => 3, 'lower' => 19],
        ['min' => 301, 'max' => 400, 'higher' => 2, 'lower' => 20],
        ['min' => 401, 'max' => 500, 'higher' => 2, 'lower' => 30],
        ['min' => 501, 'max' => PHP_INT_MAX, 'higher' => 2, 'lower' => 40],
    ];

    /**
     * Calculate Bubbler points for all matches in a period
     */
    public function calculateMatchPoints(?Carbon $period = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();

        $period = $period ?? now();
        $year = $period->year;
        $month = $period->month;

        if ($run) {
            $run->log('info', "Starting Bubbler points calculation for {$year}-{$month}");
        }

        // Get all official matches from the current period that haven't had points calculated
        $matches = GameMatch::where('source', 'scraped')
            ->whereYear('played_at', $year)
            ->whereMonth('played_at', $month)
            ->whereNull('deleted_at')
            ->whereNotNull('winner_id')
            ->orderBy('played_at')
            ->get();

        if ($run) {
            $run->log('info', "Found {$matches->count()} matches to process");
        }

        foreach ($matches as $match) {
            try {
                $this->processMatch($match, $year, $month, $run);
                $this->stats['matches_processed']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error("Failed to calculate Bubbler points for match {$match->id}", [
                    'error' => $e->getMessage(),
                ]);
                if ($run) {
                    $run->log('error', "Failed to calculate points for match {$match->id}: {$e->getMessage()}");
                }
            }
        }

        if ($run) {
            $run->log('info', "Bubbler calculation completed: {$this->stats['matches_processed']} matches processed, {$this->stats['rankings_updated']} rankings updated");
        }

        return $this->stats;
    }

    /**
     * Process a single match and calculate points
     */
    protected function processMatch(GameMatch $match, int $year, int $month, ?ScraperRun $run): void
    {
        // Get current rankings for both players
        $player1Ranking = $this->getOrCreateRanking($match->player1_id, $year, $month);
        $player2Ranking = $this->getOrCreateRanking($match->player2_id, $year, $month);

        // Calculate point difference
        $pointDifference = abs($player1Ranking->points - $player2Ranking->points);

        // Determine who has higher ranking
        $player1IsHigher = $player1Ranking->points > $player2Ranking->points;

        // Determine winner and apply points
        $isPlayer1Winner = $match->winner_id === $match->player1_id;

        // Calculate points to award
        $points = $this->calculatePoints($pointDifference, $player1IsHigher, $isPlayer1Winner);

        // Update rankings
        DB::transaction(function () use ($player1Ranking, $player2Ranking, $points, $isPlayer1Winner) {
            if ($isPlayer1Winner) {
                // Player 1 wins
                $player1Ranking->points += $points;
                $player2Ranking->points -= $points;
            } else {
                // Player 2 wins
                $player1Ranking->points -= $points;
                $player2Ranking->points += $points;
            }

            // Ensure points don't go negative
            $player1Ranking->points = max(0, $player1Ranking->points);
            $player2Ranking->points = max(0, $player2Ranking->points);

            $player1Ranking->save();
            $player2Ranking->save();
        });

        $this->stats['points_calculated'] += $points * 2; // Winner gets +, loser gets -
        $this->stats['rankings_updated'] += 2;

        if ($run && $this->stats['matches_processed'] % 100 === 0) {
            $run->log('info', "Processed {$this->stats['matches_processed']} matches, calculated {$this->stats['points_calculated']} total point changes");
        }
    }

    /**
     * Calculate points based on Bubbler formula
     */
    protected function calculatePoints(int $pointDifference, bool $player1IsHigher, bool $isPlayer1Winner): int
    {
        // Find the appropriate row in points table
        foreach ($this->pointsTable as $row) {
            if ($pointDifference >= $row['min'] && $pointDifference <= $row['max']) {
                // Determine if higher ranked player won
                $higherRankedWon = ($player1IsHigher && $isPlayer1Winner) || (!$player1IsHigher && !$isPlayer1Winner);

                return $higherRankedWon ? $row['higher'] : $row['lower'];
            }
        }

        // Fallback (should never happen)
        return 10;
    }

    /**
     * Get or create monthly ranking for a player
     */
    protected function getOrCreateRanking(int $userId, int $year, int $month): MonthlyRanking
    {
        return MonthlyRanking::firstOrCreate(
            [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
            ],
            [
                'rank' => 0,
                'points' => 1000, // Default starting points
            ]
        );
    }

    protected function resetStats(): void
    {
        $this->stats = [
            'matches_processed' => 0,
            'points_calculated' => 0,
            'rankings_updated' => 0,
            'errors' => 0,
        ];
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    /**
     * Assign proper rank numbers to all players for a given period
     * Handles ties: Same points = same rank, next rank skips
     * Example: 1050pts=#1, 1050pts=#1, 1040pts=#3
     */
    public function assignPlayerRanks(int $year, int $month, ?ScraperRun $run = null): array
    {
        $stats = [
            'male_players_ranked' => 0,
            'female_players_ranked' => 0,
            'total_ties' => 0,
        ];

        if ($run) {
            $run->log('info', "Starting rank assignment for {$year}-{$month}");
        }

        // Process male and female rankings separately
        $genders = ['male', 'female'];

        foreach ($genders as $gender) {
            // Get all rankings for this gender, sorted by points descending
            $rankings = MonthlyRanking::where('year', $year)
                ->where('month', $month)
                ->whereHas('user', function ($query) use ($gender) {
                    $query->where('gender', $gender);
                })
                ->with('user')
                ->orderByDesc('points')
                ->orderBy('user_id') // Stable sort for same points
                ->get();

            if ($rankings->isEmpty()) {
                continue;
            }

            // Assign ranks with tie handling
            $currentRank = 1;
            $previousPoints = null;
            $playersAtCurrentRank = 0;

            foreach ($rankings as $ranking) {
                if ($previousPoints === null || $ranking->points < $previousPoints) {
                    // New points value - assign new rank
                    if ($playersAtCurrentRank > 1) {
                        $stats['total_ties'] += $playersAtCurrentRank;
                    }
                    $currentRank += $playersAtCurrentRank;
                    $playersAtCurrentRank = 0;
                }

                // Assign rank
                $ranking->rank = $currentRank;
                $ranking->save();

                $previousPoints = $ranking->points;
                $playersAtCurrentRank++;
                $stats[$gender . '_players_ranked']++;
            }

            // Account for final tie if exists
            if ($playersAtCurrentRank > 1) {
                $stats['total_ties'] += $playersAtCurrentRank;
            }
        }

        if ($run) {
            $run->log('info', "Rank assignment completed: {$stats['male_players_ranked']} male, {$stats['female_players_ranked']} female players ranked ({$stats['total_ties']} players in ties)");
        }

        return $stats;
    }
}
