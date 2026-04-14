<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_rankings', function (Blueprint $table) {
            $table->date('ranking_date')->nullable()->after('month');
        });

        // Backfill ranking_date from scraped_rankings using the same
        // "latest ranking_date per user+period" logic that SyncService uses.
        DB::statement("
            UPDATE monthly_rankings mr
            INNER JOIN (
                SELECT
                    synced_user_id,
                    YEAR(ranking_date) AS yr,
                    MONTH(ranking_date) AS mo,
                    MAX(ranking_date) AS latest_date
                FROM scraped_rankings
                WHERE is_synced = 1
                  AND synced_user_id IS NOT NULL
                  AND ranking_date IS NOT NULL
                GROUP BY synced_user_id, YEAR(ranking_date), MONTH(ranking_date)
            ) sr ON mr.user_id = sr.synced_user_id
                 AND mr.year = sr.yr
                 AND mr.month = sr.mo
            SET mr.ranking_date = sr.latest_date
        ");
    }

    public function down(): void
    {
        Schema::table('monthly_rankings', function (Blueprint $table) {
            $table->dropColumn('ranking_date');
        });
    }
};
