<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Points before match
            $table->integer('player1_points_before')->nullable()->after('player2_sets');
            $table->integer('player2_points_before')->nullable()->after('player1_points_before');

            // Points change from match
            $table->integer('player1_points_change')->nullable()->after('player2_points_before');
            $table->integer('player2_points_change')->nullable()->after('player1_points_change');

            // Track if match was manually entered or synced
            $table->boolean('is_manual')->default(true)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn([
                'player1_points_before',
                'player2_points_before',
                'player1_points_change',
                'player2_points_change',
                'is_manual',
            ]);
        });
    }
};
