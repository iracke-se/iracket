<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('club_standings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->string('team_name');

            $table->string('series_name');
            $table->string('session_name')->nullable();

            $table->unsignedInteger('position');
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->integer('points')->default(0);
            $table->string('goal_difference')->nullable();

            $table->string('period')->nullable();
            $table->timestamps();

            $table->unique(['team_name', 'series_name', 'session_name'], 'club_standings_unique');
            $table->index('club_id');
            $table->index(['series_name', 'session_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_standings');
    }
};
