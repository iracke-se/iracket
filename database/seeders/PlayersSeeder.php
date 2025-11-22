<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\MonthlyRanking;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlayersSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = Club::all();

        if ($clubs->isEmpty()) {
            $this->command->info('No clubs found. Please run ClubsSeeder first.');
            return;
        }

        // Swedish first names
        $maleFirstNames = [
            'Erik', 'Lars', 'Anders', 'Johan', 'Per', 'Karl', 'Peter', 'Mikael', 'Jan', 'Nils',
            'Magnus', 'Jonas', 'Stefan', 'Fredrik', 'Mattias', 'Henrik', 'Daniel', 'Björn', 'Martin', 'Patrik',
            'Oscar', 'Alexander', 'David', 'Marcus', 'Niklas'
        ];

        $femaleFirstNames = [
            'Anna', 'Maria', 'Eva', 'Karin', 'Sara', 'Christina', 'Emma', 'Lena', 'Kristina', 'Ingrid',
            'Johanna', 'Linnea', 'Sofia', 'Elin', 'Maja', 'Hanna', 'Amanda', 'Frida', 'Ida', 'Lisa',
            'Jessica', 'Viktoria', 'Matilda', 'Ebba', 'Wilma'
        ];

        $lastNames = [
            'Andersson', 'Johansson', 'Karlsson', 'Nilsson', 'Eriksson', 'Larsson', 'Olsson', 'Persson',
            'Svensson', 'Gustafsson', 'Pettersson', 'Jonsson', 'Jansson', 'Hansson', 'Bengtsson', 'Jönsson',
            'Lindberg', 'Lindqvist', 'Lindgren', 'Axelsson', 'Berg', 'Bergström', 'Lundberg', 'Lindström',
            'Lundgren', 'Lund', 'Lundqvist', 'Mattsson', 'Berglund', 'Fredriksson'
        ];

        $players = [];

        // Create 25 male players
        for ($i = 0; $i < 25; $i++) {
            $firstName = $maleFirstNames[array_rand($maleFirstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName . '.' . $lastName . $i . '@example.com');

            $players[] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'gender' => 'male',
                'age' => rand(18, 55),
                'club_id' => $clubs->random()->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'visible_in_players' => true,
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ];
        }

        // Create 25 female players
        for ($i = 0; $i < 25; $i++) {
            $firstName = $femaleFirstNames[array_rand($femaleFirstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = strtolower($firstName . '.' . $lastName . $i . '@example.com');

            $players[] = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'gender' => 'female',
                'age' => rand(18, 55),
                'club_id' => $clubs->random()->id,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'visible_in_players' => true,
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ];
        }

        // Insert players
        foreach ($players as $playerData) {
            $user = User::create($playerData);

            // Create monthly rankings for each player (current month)
            $points = rand(800, 2500);
            $rank = 0; // Will be calculated later

            MonthlyRanking::create([
                'user_id' => $user->id,
                'year' => now()->year,
                'month' => now()->month,
                'rank' => $rank,
                'points' => $points,
                'points_change' => rand(-100, 200),
            ]);

            // Also create rankings for previous months
            for ($month = 1; $month < now()->month; $month++) {
                MonthlyRanking::create([
                    'user_id' => $user->id,
                    'year' => now()->year,
                    'month' => $month,
                    'rank' => rand(1, 50),
                    'points' => rand(700, 2400),
                    'points_change' => rand(-150, 250),
                ]);
            }
        }

        // Update ranks based on points for current month
        $this->updateCurrentMonthRanks();

        $this->command->info('Created 50 players with rankings.');
    }

    protected function updateCurrentMonthRanks(): void
    {
        // Update male rankings
        $maleRankings = MonthlyRanking::whereHas('user', function ($q) {
            $q->where('gender', 'male');
        })
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->orderBy('points', 'desc')
            ->get();

        $rank = 1;
        foreach ($maleRankings as $ranking) {
            $ranking->update(['rank' => $rank++]);
        }

        // Update female rankings
        $femaleRankings = MonthlyRanking::whereHas('user', function ($q) {
            $q->where('gender', 'female');
        })
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->orderBy('points', 'desc')
            ->get();

        $rank = 1;
        foreach ($femaleRankings as $ranking) {
            $ranking->update(['rank' => $rank++]);
        }
    }
}
