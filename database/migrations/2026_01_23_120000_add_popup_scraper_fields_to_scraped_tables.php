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
        // Add new fields to scraped_rankings for popup-based scraping
        Schema::table('scraped_rankings', function (Blueprint $table) {
            if (!Schema::hasColumn('scraped_rankings', 'profixio_player_id')) {
                $table->string('profixio_player_id')->nullable()->after('scraper_run_id');
            }
            if (!Schema::hasColumn('scraped_rankings', 'ranking_date')) {
                $table->date('ranking_date')->nullable()->after('profixio_player_id');
            }
            if (!Schema::hasColumn('scraped_rankings', 'points_diff')) {
                $table->string('points_diff')->nullable()->after('points');
            }
            if (!Schema::hasColumn('scraped_rankings', 'rmld_id')) {
                $table->string('rmld_id')->nullable()->after('points_diff');
            }
            if (!Schema::hasColumn('scraped_rankings', 'synced_ranking_id')) {
                $table->foreignId('synced_ranking_id')->nullable()->after('is_synced')->constrained('monthly_rankings')->onDelete('set null');
            }
        });

        // Add new fields to scraped_matches for popup-based scraping
        Schema::table('scraped_matches', function (Blueprint $table) {
            if (!Schema::hasColumn('scraped_matches', 'profixio_player_id')) {
                $table->string('profixio_player_id')->nullable()->after('scraper_run_id');
            }
            if (!Schema::hasColumn('scraped_matches', 'player_name')) {
                $table->string('player_name')->nullable()->after('profixio_player_id');
            }
            if (!Schema::hasColumn('scraped_matches', 'opponent_name')) {
                $table->string('opponent_name')->nullable()->after('player_name');
            }
            if (!Schema::hasColumn('scraped_matches', 'result')) {
                $table->string('result', 1)->nullable()->after('opponent_name'); // 'W' or 'L'
            }
            if (!Schema::hasColumn('scraped_matches', 'opponent_points')) {
                $table->integer('opponent_points')->nullable()->after('result');
            }
            if (!Schema::hasColumn('scraped_matches', 'match_points')) {
                $table->integer('match_points')->nullable()->after('opponent_points');
            }
            if (!Schema::hasColumn('scraped_matches', 'match_date')) {
                $table->date('match_date')->nullable()->after('match_points');
            }
            if (!Schema::hasColumn('scraped_matches', 'scraped_month')) {
                $table->string('scraped_month', 7)->nullable()->after('match_date'); // 'YYYY-MM'
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraped_rankings', function (Blueprint $table) {
            $table->dropColumn([
                'profixio_player_id',
                'ranking_date',
                'points_diff',
                'rmld_id',
                'synced_ranking_id',
            ]);
        });

        Schema::table('scraped_matches', function (Blueprint $table) {
            $table->dropColumn([
                'profixio_player_id',
                'player_name',
                'opponent_name',
                'result',
                'opponent_points',
                'match_points',
                'match_date',
                'scraped_month',
            ]);
        });
    }
};
