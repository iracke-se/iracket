<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with everything required to run
     * in production. All seeders below are idempotent — safe to re-run.
     *
     * Order matters: roles must exist before AdminUserSeeder assigns one.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
            TermsSeeder::class,
            AboutTermsSeeder::class,
            BubblerTermsSeeder::class,
            MatchesTermsSeeder::class,
            PlayerDistrictSeeder::class,
        ]);
    }
}
