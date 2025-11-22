<?php

namespace Database\Seeders;

use App\Models\GameMatch;
use App\Models\User;
use Illuminate\Database\Seeder;

class MatchesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->count() < 2) {
            return;
        }

        $comments = [
            'Good backhand',
            'Strong forehand',
            'Fast serve',
            'Great footwork',
            'Excellent net play',
            'Good sportsmanship',
            'Consistent player',
        ];

        // Create matches for the past 3 months
        for ($i = 0; $i < 20; $i++) {
            $player1 = $users->random();
            $player2 = $users->where('id', '!=', $player1->id)->random();

            $player1Sets = rand(0, 3);
            $player2Sets = rand(0, 3);

            // Ensure there's a winner (no ties)
            if ($player1Sets === $player2Sets) {
                if (rand(0, 1)) {
                    $player1Sets++;
                } else {
                    $player2Sets++;
                }
            }

            $winnerId = $player1Sets > $player2Sets ? $player1->id : $player2->id;

            // Random date in past 3 months
            $daysAgo = rand(1, 90);
            $playedAt = now()->subDays($daysAgo);

            // Random comments
            $numComments1 = rand(0, 3);
            $numComments2 = rand(0, 3);

            $player1Comments = [];
            $player2Comments = [];

            if ($numComments1 > 0) {
                $keys = array_rand($comments, min($numComments1, count($comments)));
                $player1Comments = is_array($keys) ? array_map(fn($k) => $comments[$k], $keys) : [$comments[$keys]];
            }

            if ($numComments2 > 0) {
                $keys = array_rand($comments, min($numComments2, count($comments)));
                $player2Comments = is_array($keys) ? array_map(fn($k) => $comments[$k], $keys) : [$comments[$keys]];
            }

            GameMatch::create([
                'player1_id' => $player1->id,
                'player2_id' => $player2->id,
                'played_at' => $playedAt,
                'player1_sets' => $player1Sets,
                'player2_sets' => $player2Sets,
                'winner_id' => $winnerId,
                'player1_comments' => $player1Comments,
                'player2_comments' => $player2Comments,
                'description' => rand(0, 1) ? 'Great match with lots of rallies.' : null,
                'status' => 'confirmed',
                'created_by' => $player1->id,
            ]);
        }
    }
}
