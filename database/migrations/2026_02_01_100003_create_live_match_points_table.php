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
        Schema::create('live_match_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_match_set_id')->constrained('live_match_sets')->onDelete('cascade');
            $table->integer('point_number')->nullable();
            $table->integer('player1_points')->nullable();
            $table->integer('player2_points')->nullable();
            $table->string('serve')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['live_match_set_id', 'point_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_match_points');
    }
};
