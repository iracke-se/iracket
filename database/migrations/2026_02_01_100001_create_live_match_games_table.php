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
        Schema::create('live_match_games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_match_detail_id')->constrained('live_match_details')->onDelete('cascade');
            $table->string('game_number')->nullable();
            $table->string('game_type')->nullable();
            $table->string('player1_name')->nullable();
            $table->string('player2_name')->nullable();
            $table->string('player1_partner_name')->nullable();
            $table->string('player2_partner_name')->nullable();
            $table->integer('player1_sets')->nullable();
            $table->integer('player2_sets')->nullable();
            $table->string('winner_name')->nullable();
            $table->string('profixio_game_id')->nullable()->index();
            $table->boolean('is_synced')->default(false)->index();
            $table->foreignId('synced_match_id')->nullable()->constrained('matches')->onDelete('set null');
            $table->timestamps();

            $table->index(['live_match_detail_id', 'game_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_match_games');
    }
};
