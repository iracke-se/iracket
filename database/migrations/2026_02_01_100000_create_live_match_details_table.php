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
        Schema::create('live_match_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scraper_run_id')->nullable()->constrained('scraper_runs')->onDelete('cascade');
            $table->string('division')->nullable();
            $table->string('team1_name')->nullable();
            $table->string('team2_name')->nullable();
            $table->integer('team1_score')->nullable();
            $table->integer('team2_score')->nullable();
            $table->date('played_at')->nullable();
            $table->string('profixio_match_id')->nullable()->index();
            $table->string('status')->nullable();
            $table->boolean('is_synced')->default(false)->index();
            $table->timestamps();

            $table->index(['played_at', 'division']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_match_details');
    }
};
