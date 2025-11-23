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
        // Schedule settings for each scraper type
        $scheduleSettings = [
            // Players - Monthly
            ['key' => 'schedule_players_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'schedule', 'description' => 'Enable automatic players scraping'],
            ['key' => 'schedule_players_frequency', 'value' => 'monthly', 'type' => 'string', 'group' => 'schedule', 'description' => 'Players scraping frequency'],
            ['key' => 'schedule_players_day', 'value' => '1', 'type' => 'string', 'group' => 'schedule', 'description' => 'Day to run players scrape (1-31 for monthly, sunday-saturday for weekly)'],
            ['key' => 'schedule_players_time', 'value' => '03:00', 'type' => 'string', 'group' => 'schedule', 'description' => 'Time to run players scrape (HH:MM)'],

            // Rankings - Weekly
            ['key' => 'schedule_rankings_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'schedule', 'description' => 'Enable automatic rankings scraping'],
            ['key' => 'schedule_rankings_frequency', 'value' => 'weekly', 'type' => 'string', 'group' => 'schedule', 'description' => 'Rankings scraping frequency'],
            ['key' => 'schedule_rankings_day', 'value' => 'sunday', 'type' => 'string', 'group' => 'schedule', 'description' => 'Day to run rankings scrape'],
            ['key' => 'schedule_rankings_time', 'value' => '02:00', 'type' => 'string', 'group' => 'schedule', 'description' => 'Time to run rankings scrape (HH:MM)'],

            // Transitions - Weekly
            ['key' => 'schedule_transitions_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'schedule', 'description' => 'Enable automatic transitions scraping'],
            ['key' => 'schedule_transitions_frequency', 'value' => 'weekly', 'type' => 'string', 'group' => 'schedule', 'description' => 'Transitions scraping frequency'],
            ['key' => 'schedule_transitions_day', 'value' => 'monday', 'type' => 'string', 'group' => 'schedule', 'description' => 'Day to run transitions scrape'],
            ['key' => 'schedule_transitions_time', 'value' => '03:30', 'type' => 'string', 'group' => 'schedule', 'description' => 'Time to run transitions scrape (HH:MM)'],

            // Series - Weekly
            ['key' => 'schedule_series_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'schedule', 'description' => 'Enable automatic series scraping'],
            ['key' => 'schedule_series_frequency', 'value' => 'weekly', 'type' => 'string', 'group' => 'schedule', 'description' => 'Series scraping frequency'],
            ['key' => 'schedule_series_day', 'value' => 'monday', 'type' => 'string', 'group' => 'schedule', 'description' => 'Day to run series scrape'],
            ['key' => 'schedule_series_time', 'value' => '04:00', 'type' => 'string', 'group' => 'schedule', 'description' => 'Time to run series scrape (HH:MM)'],

            // Live Center - Daily
            ['key' => 'schedule_live_center_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'schedule', 'description' => 'Enable automatic live center scraping'],
            ['key' => 'schedule_live_center_frequency', 'value' => 'daily', 'type' => 'string', 'group' => 'schedule', 'description' => 'Live center scraping frequency'],
            ['key' => 'schedule_live_center_day', 'value' => '', 'type' => 'string', 'group' => 'schedule', 'description' => 'Day to run live center scrape (not used for daily)'],
            ['key' => 'schedule_live_center_time', 'value' => '05:00', 'type' => 'string', 'group' => 'schedule', 'description' => 'Time to run live center scrape (HH:MM)'],
        ];

        foreach ($scheduleSettings as $setting) {
            \DB::table('scraper_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::table('scraper_settings')
            ->where('group', 'schedule')
            ->delete();
    }
};
