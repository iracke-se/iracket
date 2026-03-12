<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraped_district_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->constrained('scraper_runs')->cascadeOnDelete();
            $table->unsignedInteger('profixio_district_id');
            $table->string('district_name');
            $table->char('gender', 1); // 'm' or 'k'
            $table->string('profixio_player_id')->nullable();
            $table->string('surname');
            $table->string('first_name');
            $table->string('birth_year', 4)->nullable();
            $table->string('club_name')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->boolean('is_synced')->default(false);
            $table->foreignId('synced_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Regular indexes
            $table->index(['surname', 'first_name', 'birth_year']);
            $table->index('profixio_player_id');
            $table->index('profixio_district_id');
            $table->index('is_synced');

            // Unique constraint for deduplication on re-runs
            // NULL profixio_player_id values are treated as distinct by MariaDB,
            // so layer-2 (pre-delete unsynced nulls) handles that edge case.
            $table->unique(['profixio_district_id', 'gender', 'profixio_player_id'], 'sdp_district_gender_player_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_district_players');
    }
};
