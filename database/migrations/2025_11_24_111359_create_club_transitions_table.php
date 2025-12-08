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
        Schema::create('club_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('period'); // e.g., "Licens 2025-26"
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('from_club_id')->nullable()->constrained('clubs')->nullOnDelete();
            $table->foreignId('to_club_id')->nullable()->constrained('clubs')->nullOnDelete();

            // Store original scraped data for matching
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->date('born')->nullable();
            $table->string('from_club_name')->nullable();
            $table->string('to_club_name')->nullable();

            $table->date('completion_date');
            $table->boolean('is_synced')->default(false);
            $table->timestamps();

            $table->index(['to_club_id', 'completion_date']);
            $table->index(['from_club_id', 'completion_date']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_transitions');
    }
};
