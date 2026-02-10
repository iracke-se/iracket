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
        Schema::create('live_match_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_match_game_id')->constrained('live_match_games')->onDelete('cascade');
            $table->integer('set_number')->nullable();
            $table->integer('player1_points')->nullable();
            $table->integer('player2_points')->nullable();
            $table->timestamps();

            $table->index(['live_match_game_id', 'set_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_match_sets');
    }
};
