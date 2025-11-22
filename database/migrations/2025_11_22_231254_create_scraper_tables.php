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
        // Scraper runs - tracks each scrape execution
        Schema::create('scraper_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // rankings, players, transitions, series, live_center
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->json('parameters')->nullable(); // gender, period, etc.
            $table->integer('items_scraped')->default(0);
            $table->integer('items_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });

        // Scraper logs - detailed logs for each run
        Schema::create('scraper_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->constrained()->onDelete('cascade');
            $table->string('level')->default('info'); // info, warning, error
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['scraper_run_id', 'level']);
        });

        // Scraped players - raw player data from profixio
        Schema::create('scraped_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->constrained()->onDelete('cascade');
            $table->string('period'); // e.g., "2023/2024"
            $table->string('club_name');
            $table->string('surname');
            $table->string('first_name');
            $table->string('sex')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('license_type')->nullable();
            $table->string('player_class')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->foreignId('synced_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['surname', 'first_name']);
            $table->index('club_name');
            $table->index('is_synced');
        });

        // Scraped transitions - player club transfers
        Schema::create('scraped_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->constrained()->onDelete('cascade');
            $table->string('period');
            $table->string('surname');
            $table->string('first_name');
            $table->string('born')->nullable();
            $table->string('from_club');
            $table->string('to_club');
            $table->string('completion_date')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->timestamps();

            $table->index(['surname', 'first_name']);
        });

        // Scraped rankings - player rankings
        Schema::create('scraped_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->constrained()->onDelete('cascade');
            $table->string('period');
            $table->string('division');
            $table->string('gender'); // male, female
            $table->integer('position');
            $table->string('position_change')->nullable(); // +2, -1, etc.
            $table->string('name');
            $table->string('born')->nullable();
            $table->string('club')->nullable();
            $table->integer('points')->default(0);
            $table->string('points_change')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->foreignId('synced_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['period', 'gender', 'division']);
            $table->index('name');
            $table->index('is_synced');
        });

        // Scraped matches - match data from live center/series
        Schema::create('scraped_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->constrained()->onDelete('cascade');
            $table->string('source'); // live_center, series
            $table->string('period');
            $table->string('division')->nullable();
            $table->string('series_name')->nullable();
            $table->string('team1_name')->nullable();
            $table->string('team2_name')->nullable();
            $table->string('player1_name');
            $table->string('player2_name');
            $table->string('score')->nullable(); // e.g., "3-1"
            $table->json('sets')->nullable(); // detailed set scores
            $table->string('played_at')->nullable();
            $table->string('winner')->nullable();
            $table->boolean('is_synced')->default(false);
            $table->foreignId('synced_match_id')->nullable()->constrained('matches')->onDelete('set null');
            $table->timestamps();

            $table->index(['player1_name', 'player2_name']);
            $table->index(['period', 'division']);
            $table->index('is_synced');
        });

        // Scraped series standings
        Schema::create('scraped_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->constrained()->onDelete('cascade');
            $table->string('period');
            $table->string('series_name');
            $table->string('session_name')->nullable();
            $table->integer('position');
            $table->string('team_name');
            $table->integer('matches_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('points')->default(0);
            $table->string('goal_difference')->nullable();
            $table->timestamps();

            $table->index(['period', 'series_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraped_standings');
        Schema::dropIfExists('scraped_matches');
        Schema::dropIfExists('scraped_rankings');
        Schema::dropIfExists('scraped_transitions');
        Schema::dropIfExists('scraped_players');
        Schema::dropIfExists('scraper_logs');
        Schema::dropIfExists('scraper_runs');
    }
};
