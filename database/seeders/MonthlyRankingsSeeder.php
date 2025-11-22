<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\ClubMonthlyRanking;
use App\Models\MonthlyRanking;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonthlyRankingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $clubs = Club::all();

        if ($users->isEmpty()) {
            return;
        }

        // Assign users to random clubs
        foreach ($users as $user) {
            if ($clubs->isNotEmpty() && !$user->club_id) {
                $user->update(['club_id' => $clubs->random()->id]);
            }
        }

        // Generate rankings for the past 6 months
        $currentYear = now()->year;
        $currentMonth = now()->month;

        for ($i = 0; $i < 6; $i++) {
            $month = $currentMonth - $i;
            $year = $currentYear;

            if ($month <= 0) {
                $month += 12;
                $year--;
            }

            // Generate user rankings for this month
            $rank = 1;
            $shuffledUsers = $users->shuffle();

            foreach ($shuffledUsers as $user) {
                $basePoints = rand(1000, 2000);
                $pointsChange = rand(-50, 100);

                MonthlyRanking::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'rank' => $rank,
                        'points' => $basePoints,
                        'points_change' => $pointsChange,
                    ]
                );

                $rank++;
            }

            // Generate club rankings for this month
            if ($clubs->isNotEmpty()) {
                $clubRank = 1;
                $shuffledClubs = $clubs->shuffle();

                foreach ($shuffledClubs as $club) {
                    $totalPoints = rand(5000, 15000);

                    ClubMonthlyRanking::updateOrCreate(
                        [
                            'club_id' => $club->id,
                            'year' => $year,
                            'month' => $month,
                        ],
                        [
                            'rank' => $clubRank,
                            'total_points' => $totalPoints,
                        ]
                    );

                    $clubRank++;
                }
            }
        }
    }
}
