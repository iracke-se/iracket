<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Confirm all existing pending matches
        DB::table('matches')
            ->where('status', 'pending')
            ->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('matches')
            ->whereNotNull('confirmed_at')
            ->update([
                'status' => 'pending',
                'confirmed_at' => null,
            ]);
    }
};
