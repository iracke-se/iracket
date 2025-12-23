<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            // Track if match is from scraper or manual
            $table->enum('source', ['player_added', 'scraped'])
                  ->default('player_added')
                  ->after('id');

            // Mark matches that don't match official data
            $table->boolean('is_unofficial')
                  ->default(false)
                  ->after('source')
                  ->index();

            // Link replaced manual match to official scraped match
            $table->foreignId('replaced_by_match_id')
                  ->nullable()
                  ->constrained('matches')
                  ->onDelete('set null')
                  ->after('is_unofficial');

            // Soft delete for replaced matches
            $table->softDeletes();

            // Add index for efficient duplicate detection
            $table->index(['player1_id', 'player2_id', 'played_at', 'deleted_at'], 'match_lookup');
        });

        // Mark all existing matches as player_added
        DB::table('matches')
            ->whereNull('source')
            ->update(['source' => 'player_added']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropIndex('match_lookup');
            $table->dropSoftDeletes();
            $table->dropForeign(['replaced_by_match_id']);
            $table->dropColumn(['source', 'is_unofficial', 'replaced_by_match_id']);
        });
    }
};
