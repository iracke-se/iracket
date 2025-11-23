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
        Schema::table('users', function (Blueprint $table) {
            // External SBTF player ID (for future use if we find unique IDs)
            $table->string('sbtf_player_id')->nullable()->after('apple_id');

            // Birth year for matching with SBTF rankings data
            $table->integer('birth_year')->nullable()->after('age');

            // Flag to indicate if user has been synced with SBTF data
            $table->boolean('sbtf_synced')->default(false)->after('sbtf_player_id');

            // Last sync timestamp
            $table->timestamp('sbtf_synced_at')->nullable()->after('sbtf_synced');

            // Index for efficient player matching
            $table->index(['last_name', 'first_name', 'birth_year'], 'users_player_match_index');
            $table->index('sbtf_player_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_player_match_index');
            $table->dropIndex(['sbtf_player_id']);
            $table->dropColumn([
                'sbtf_player_id',
                'birth_year',
                'sbtf_synced',
                'sbtf_synced_at',
            ]);
        });
    }
};
