<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubTransition;
use App\Models\GameMatch;
use App\Models\MonthlyRanking;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // Get existing users and clubs
        $users = User::where('is_active_player', true)->get();
        $clubs = Club::all();

        if ($users->count() < 5) {
            $this->command->warn('Not enough users found. Please run the players seeder first.');
            return;
        }

        if ($clubs->count() < 3) {
            $this->command->warn('Not enough clubs found. Please create some clubs first.');
            return;
        }

        // Create monthly rankings for users who don't have them
        $this->createMonthlyRankings($users);

        // Create matches between players
        $this->createMatches($users);

        // Create club transitions
        $this->createClubTransitions($users, $clubs);

        // Create monitoring relationships
        $this->createMonitoringRelationships($users);

        $this->command->info('Demo data seeded successfully!');
    }

    private function createMonthlyRankings($users): void
    {
        $this->command->info('Creating monthly rankings...');

        $currentMonth = now()->month;
        $currentYear = now()->year;

        foreach ($users as $user) {
            // Check if ranking already exists
            $exists = MonthlyRanking::where('user_id', $user->id)
                ->where('month', $currentMonth)
                ->where('year', $currentYear)
                ->exists();

            if (!$exists) {
                MonthlyRanking::create([
                    'user_id' => $user->id,
                    'month' => $currentMonth,
                    'year' => $currentYear,
                    'points' => rand(500, 2500),
                    'rank' => 0,
                    'points_change' => 0,
                ]);
            }
        }

        // Update ranks based on points
        $rankings = MonthlyRanking::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->orderByDesc('points')
            ->get();

        $rank = 1;
        foreach ($rankings as $ranking) {
            $ranking->update(['rank' => $rank]);
            $rank++;
        }
    }

    private function createMatches($users): void
    {
        $this->command->info('Creating matches...');

        $matchCount = 0;

        // Create 20 random matches
        for ($i = 0; $i < 20; $i++) {
            $player1 = $users->random();
            $player2 = $users->where('id', '!=', $player1->id)->random();

            // Random sets
            $player1Sets = rand(0, 3);
            $player2Sets = rand(0, 3);

            // Ensure there's a winner
            if ($player1Sets === $player2Sets) {
                $player1Sets = rand(0, 2);
                $player2Sets = 3;
            }

            $winnerId = $player1Sets > $player2Sets ? $player1->id : $player2->id;

            // Get current rankings for points calculation
            $player1Ranking = $player1->currentMonthRanking();
            $player2Ranking = $player2->currentMonthRanking();

            $player1Points = $player1Ranking?->points ?? 1000;
            $player2Points = $player2Ranking?->points ?? 1000;

            // Simple points calculation
            $pointsDiff = abs($player1Points - $player2Points);
            $basePoints = $pointsDiff <= 100 ? 10 : ($pointsDiff <= 300 ? 15 : 20);

            $player1Change = $winnerId === $player1->id ? $basePoints : -$basePoints;
            $player2Change = $winnerId === $player2->id ? $basePoints : -$basePoints;

            // Random date in the past 30 days
            $playedAt = now()->subDays(rand(1, 30));

            GameMatch::create([
                'player1_id' => $player1->id,
                'player2_id' => $player2->id,
                'played_at' => $playedAt,
                'player1_sets' => $player1Sets,
                'player2_sets' => $player2Sets,
                'player1_points_before' => $player1Points,
                'player2_points_before' => $player2Points,
                'player1_points_change' => $player1Change,
                'player2_points_change' => $player2Change,
                'winner_id' => $winnerId,
                'player1_comments' => [],
                'player2_comments' => [],
                'description' => null,
                'status' => 'confirmed',
                'is_manual' => true,
                'created_by' => $player1->id,
            ]);

            $matchCount++;
        }

        $this->command->info("Created {$matchCount} matches.");
    }

    private function createClubTransitions($users, $clubs): void
    {
        $this->command->info('Creating club transitions...');

        $swedishNames = [
            ['first_name' => 'Erik', 'surname' => 'Andersson'],
            ['first_name' => 'Anna', 'surname' => 'Johansson'],
            ['first_name' => 'Lars', 'surname' => 'Karlsson'],
            ['first_name' => 'Maria', 'surname' => 'Nilsson'],
            ['first_name' => 'Johan', 'surname' => 'Eriksson'],
            ['first_name' => 'Eva', 'surname' => 'Larsson'],
            ['first_name' => 'Anders', 'surname' => 'Olsson'],
            ['first_name' => 'Karin', 'surname' => 'Persson'],
            ['first_name' => 'Per', 'surname' => 'Svensson'],
            ['first_name' => 'Emma', 'surname' => 'Gustafsson'],
        ];

        $transitionCount = 0;

        // Create 15 transitions
        for ($i = 0; $i < 15; $i++) {
            $fromClub = $clubs->random();
            $toClub = $clubs->where('id', '!=', $fromClub->id)->random();
            $name = $swedishNames[array_rand($swedishNames)];

            // Some in the future (pending), some in the past (completed)
            $isPending = rand(0, 1) === 1;
            $completionDate = $isPending
                ? now()->addDays(rand(1, 60))
                : now()->subDays(rand(1, 90));

            // Sometimes link to a real user
            $user = rand(0, 2) === 0 ? $users->random() : null;

            ClubTransition::create([
                'period' => 'Licens 2025-26',
                'user_id' => $user?->id,
                'from_club_id' => $fromClub->id,
                'to_club_id' => $toClub->id,
                'surname' => $user ? $user->last_name : $name['surname'],
                'first_name' => $user ? $user->first_name : $name['first_name'],
                'born' => now()->subYears(rand(18, 45))->subDays(rand(1, 365)),
                'from_club_name' => $fromClub->name,
                'to_club_name' => $toClub->name,
                'completion_date' => $completionDate,
                'is_synced' => false,
            ]);

            $transitionCount++;
        }

        $this->command->info("Created {$transitionCount} club transitions.");
    }

    private function createMonitoringRelationships($users): void
    {
        $this->command->info('Creating monitoring relationships...');

        $monitoringCount = 0;

        // Each user monitors 2-5 random other users
        foreach ($users->take(10) as $user) {
            $toMonitor = $users->where('id', '!=', $user->id)
                ->random(min(rand(2, 5), $users->count() - 1));

            foreach ($toMonitor as $monitored) {
                // Check if relationship already exists
                $exists = DB::table('user_monitors')
                    ->where('user_id', $user->id)
                    ->where('monitored_user_id', $monitored->id)
                    ->exists();

                if (!$exists) {
                    DB::table('user_monitors')->insert([
                        'user_id' => $user->id,
                        'monitored_user_id' => $monitored->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $monitoringCount++;
                }
            }
        }

        $this->command->info("Created {$monitoringCount} monitoring relationships.");
    }
}
