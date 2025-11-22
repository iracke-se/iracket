<?php

namespace Database\Seeders;

use App\Models\Club;
use Illuminate\Database\Seeder;

class ClubsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clubs = [
            [
                'name' => 'Stockholm Racket Club',
                'slug' => 'stockholm-racket-club',
                'description' => 'Premier racket sports club in Stockholm with world-class facilities.',
                'location' => 'Stockholm',
                'email' => 'info@stockholmracketclub.se',
                'phone' => '+46 8 123 4567',
                'website' => 'https://stockholmracketclub.se',
            ],
            [
                'name' => 'Göteborg Tennis & Padel',
                'slug' => 'goteborg-tennis-padel',
                'description' => 'Leading tennis and padel club on the west coast.',
                'location' => 'Gothenburg',
                'email' => 'kontakt@gtpadel.se',
                'phone' => '+46 31 234 5678',
                'website' => 'https://gtpadel.se',
            ],
            [
                'name' => 'Malmö Badminton Club',
                'slug' => 'malmo-badminton-club',
                'description' => 'Southern Sweden\'s premier badminton facility.',
                'location' => 'Malmö',
                'email' => 'info@malmobadminton.se',
                'phone' => '+46 40 345 6789',
            ],
            [
                'name' => 'Uppsala Squash Center',
                'slug' => 'uppsala-squash-center',
                'description' => 'Modern squash center with 8 courts.',
                'location' => 'Uppsala',
                'email' => 'bokning@uppsalasquash.se',
                'phone' => '+46 18 456 7890',
            ],
            [
                'name' => 'Linköping Racket Arena',
                'slug' => 'linkoping-racket-arena',
                'description' => 'Multi-sport racket arena in Östergötland.',
                'location' => 'Linköping',
                'email' => 'info@lkpgarena.se',
                'phone' => '+46 13 567 8901',
            ],
            [
                'name' => 'Västerås Padel Club',
                'slug' => 'vasteras-padel-club',
                'description' => 'Dedicated padel club with indoor and outdoor courts.',
                'location' => 'Västerås',
                'email' => 'hej@vasteraspadel.se',
                'phone' => '+46 21 678 9012',
            ],
            [
                'name' => 'Örebro Tennis Society',
                'slug' => 'orebro-tennis-society',
                'description' => 'Historic tennis club founded in 1920.',
                'location' => 'Örebro',
                'email' => 'styrelsen@orebrotennis.se',
                'phone' => '+46 19 789 0123',
            ],
            [
                'name' => 'Helsingborg Racket & Fitness',
                'slug' => 'helsingborg-racket-fitness',
                'description' => 'Combined racket sports and fitness center.',
                'location' => 'Helsingborg',
                'email' => 'kontakt@hbgracket.se',
                'phone' => '+46 42 890 1234',
            ],
        ];

        foreach ($clubs as $club) {
            Club::updateOrCreate(
                ['slug' => $club['slug']],
                $club
            );
        }
    }
}
