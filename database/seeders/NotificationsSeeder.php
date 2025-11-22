<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user or create one
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@iracket.se',
                'password' => bcrypt('password'),
            ]);
        }

        $notifications = [
            [
                'type' => 'match_result',
                'title' => 'Match Result',
                'message' => 'You won against Erik Lindqvist! Your ranking increased by 15 points.',
                'data' => ['match_id' => 1, 'opponent' => 'Erik Lindqvist', 'points' => 15],
                'read_at' => null,
                'created_at' => now()->subMinutes(5),
            ],
            [
                'type' => 'ranking_update',
                'title' => 'Ranking Update',
                'message' => 'Your monthly ranking has been updated. You are now ranked #42 in your region.',
                'data' => ['rank' => 42, 'region' => 'Stockholm'],
                'read_at' => null,
                'created_at' => now()->subHours(2),
            ],
            [
                'type' => 'achievement',
                'title' => 'Achievement Unlocked!',
                'message' => 'Congratulations! You\'ve won 10 matches this month. Keep it up!',
                'data' => ['achievement' => 'win_streak_10'],
                'read_at' => null,
                'created_at' => now()->subHours(5),
            ],
            [
                'type' => 'match_result',
                'title' => 'Match Result',
                'message' => 'You lost against Anna Svensson. Your ranking decreased by 8 points.',
                'data' => ['match_id' => 2, 'opponent' => 'Anna Svensson', 'points' => -8],
                'read_at' => now()->subHours(1),
                'created_at' => now()->subDay(),
            ],
            [
                'type' => 'system',
                'title' => 'Welcome to iRacket!',
                'message' => 'Welcome to iRacket! Start by recording your first match to see your ranking.',
                'data' => null,
                'read_at' => now()->subDays(2),
                'created_at' => now()->subDays(3),
            ],
            [
                'type' => 'bubbler',
                'title' => 'Bubbler Update',
                'message' => 'You made it to the top 3 in your class last month! Check out the Bubbler rankings.',
                'data' => ['position' => 2, 'class' => 'Class 4'],
                'read_at' => null,
                'created_at' => now()->subHours(8),
            ],
            [
                'type' => 'follow',
                'title' => 'New Follower',
                'message' => 'Marcus Johansson started following you.',
                'data' => ['follower_id' => 5, 'follower_name' => 'Marcus Johansson'],
                'read_at' => now()->subHours(12),
                'created_at' => now()->subDays(2),
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create(array_merge($notification, ['user_id' => $user->id]));
        }
    }
}
