<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = [
            [
                'name' => 'Summer Tournament 2025',
                'image' => 'assets/images/landing/lastsect.png',
                'position' => 'top_sticky',
                'views' => 1250,
                'clicks' => 89,
                'locations' => ['home', 'matches'],
                'link' => 'https://example.com/summer-tournament',
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
                'status' => 'active',
            ],
            [
                'name' => 'Premium Membership',
                'image' => 'assets/images/landing/lefthero-deskt.png',
                'position' => 'bottom_sticky',
                'views' => 3420,
                'clicks' => 156,
                'locations' => ['players', 'bubbler'],
                'link' => 'https://example.com/premium',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'status' => 'active',
            ],
            [
                'name' => 'New Racket Collection',
                'image' => 'assets/images/landing/pingpong.png',
                'position' => 'top',
                'views' => 890,
                'clicks' => 45,
                'locations' => ['home', 'clubs'],
                'link' => 'https://example.com/rackets',
                'start_date' => now(),
                'end_date' => now()->addDays(14),
                'status' => 'active',
            ],
            [
                'name' => 'Winter League Registration',
                'image' => 'assets/images/landing/righthero-deskt.png',
                'position' => 'popup',
                'views' => 2100,
                'clicks' => 312,
                'locations' => null,
                'link' => 'https://example.com/winter-league',
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(45),
                'status' => 'scheduled',
            ],
            [
                'name' => 'Club Sponsorship',
                'image' => 'assets/images/landing/iPhone12left.png',
                'position' => 'within_page',
                'views' => 567,
                'clicks' => 23,
                'locations' => ['clubs'],
                'link' => 'https://example.com/sponsors',
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'status' => 'active',
            ],
            [
                'name' => 'Training Sessions',
                'image' => 'assets/images/landing/iPhone12right2.png',
                'position' => 'bottom',
                'views' => 1890,
                'clicks' => 78,
                'locations' => ['matches', 'players'],
                'link' => 'https://example.com/training',
                'start_date' => null,
                'end_date' => null,
                'status' => 'active',
            ],
            [
                'name' => 'Old Campaign',
                'image' => 'assets/images/landing/badge-appStore.png',
                'position' => 'random',
                'views' => 4500,
                'clicks' => 234,
                'locations' => ['home'],
                'link' => 'https://example.com/old-campaign',
                'start_date' => now()->subDays(60),
                'end_date' => now()->subDays(30),
                'status' => 'inactive',
            ],
            [
                'name' => 'App Download',
                'image' => 'assets/images/landing/badge-PlayStore.png',
                'position' => 'bottom_sticky',
                'views' => 780,
                'clicks' => 92,
                'locations' => ['settings', 'profile'],
                'link' => 'https://example.com/app',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(45),
                'status' => 'active',
            ],
        ];

        foreach ($banners as $banner) {
            Banner::create($banner);
        }
    }
}
