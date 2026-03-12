<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@iracket.se'],
            [
                'first_name' => 'Admin',
                'last_name' => 'iRacket',
                'password' => Hash::make('iracket2026!@'),
                'email_verified_at' => now(),
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ]
        );

        $admin->assignRole('Admin');
    }
}
