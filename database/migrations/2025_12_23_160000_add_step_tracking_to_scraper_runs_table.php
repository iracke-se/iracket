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
        Schema::table('scraper_runs', function (Blueprint $table) {
            $table->string('current_step')->nullable()->after('type');
            $table->json('steps_data')->nullable()->after('parameters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraper_runs', function (Blueprint $table) {
            $table->dropColumn(['current_step', 'steps_data']);
        });
    }
};
