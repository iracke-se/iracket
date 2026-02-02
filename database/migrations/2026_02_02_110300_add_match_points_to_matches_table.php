<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->integer('player1_match_points')->nullable()->after('player2_sets');
            $table->integer('player2_match_points')->nullable()->after('player1_match_points');
            $table->integer('player1_opponent_rating')->nullable()->after('player2_match_points');
            $table->integer('player2_opponent_rating')->nullable()->after('player1_opponent_rating');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn([
                'player1_match_points',
                'player2_match_points',
                'player1_opponent_rating',
                'player2_opponent_rating',
            ]);
        });
    }
};
