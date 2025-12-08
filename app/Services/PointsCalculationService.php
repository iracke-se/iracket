<?php

namespace App\Services;

class PointsCalculationService
{
    /**
     * Get points awarded based on ranking point difference
     * Returns [higherRankedWins, lowerRankedWins]
     */
    public function getPointsForDifference(int $difference): array
    {
        return match (true) {
            $difference <= 25 => [10, 10],
            $difference <= 50 => [9, 11],
            $difference <= 75 => [8, 12],
            $difference <= 100 => [7, 13],
            $difference <= 125 => [6, 15],
            $difference <= 150 => [6, 16],
            $difference <= 200 => [5, 17],
            $difference <= 250 => [4, 18],
            $difference <= 300 => [3, 19],
            $difference <= 400 => [2, 20],
            $difference <= 500 => [2, 30],
            default => [2, 40],
        };
    }

    /**
     * Calculate match result and return point changes for both players
     */
    public function calculateMatchPoints(
        int $player1Points,
        int $player2Points,
        int $winnerId,
        int $player1Id,
        int $player2Id
    ): array {
        $difference = abs($player1Points - $player2Points);
        $pointsTable = $this->getPointsForDifference($difference);

        // Determine who is higher ranked
        $higherRankedPlayerId = $player1Points >= $player2Points ? $player1Id : $player2Id;
        $winnerIsHigherRanked = $winnerId === $higherRankedPlayerId;

        // Get points based on whether higher or lower ranked player won
        $pointsChange = $winnerIsHigherRanked ? $pointsTable[0] : $pointsTable[1];

        // Calculate individual player changes
        $player1Change = $winnerId === $player1Id ? $pointsChange : -$pointsChange;
        $player2Change = $winnerId === $player2Id ? $pointsChange : -$pointsChange;

        return [
            'points_awarded' => $pointsChange,
            'player1_change' => $player1Change,
            'player2_change' => $player2Change,
            'player1_new_points' => $player1Points + $player1Change,
            'player2_new_points' => $player2Points + $player2Change,
        ];
    }

    /**
     * Get a preview of what points would be awarded for a potential match
     */
    public function previewMatchPoints(
        int $player1Points,
        int $player2Points
    ): array {
        $difference = abs($player1Points - $player2Points);
        $pointsTable = $this->getPointsForDifference($difference);

        return [
            'difference' => $difference,
            'higher_ranked_wins' => $pointsTable[0],
            'lower_ranked_wins' => $pointsTable[1],
        ];
    }
}
