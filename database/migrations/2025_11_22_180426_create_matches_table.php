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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player1_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('player2_id')->constrained('users')->onDelete('cascade');
            $table->date('played_at');
            $table->unsignedTinyInteger('player1_sets');
            $table->unsignedTinyInteger('player2_sets');
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('player1_comments')->nullable();
            $table->json('player2_comments')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'disputed'])->default('pending');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index(['player1_id', 'played_at']);
            $table->index(['player2_id', 'played_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
