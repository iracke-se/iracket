<?php

use App\Jobs\Scraper\ScrapeRankingsJob;
use App\Jobs\Scraper\ScrapePlayersJob;
use App\Jobs\Scraper\ScrapeTransitionsJob;
use App\Jobs\Scraper\ScrapeSeriesJob;
use App\Jobs\Scraper\ScrapeLiveCenterJob;
use App\Models\Scraper\ScraperSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scraper Schedules
|--------------------------------------------------------------------------
|
| Configure automated scraping schedules from database settings
|
*/

// Helper function to configure schedule based on frequency
$configureSchedule = function ($schedule, $frequency, $day, $time) {
    switch ($frequency) {
        case 'daily':
            return $schedule->daily()->at($time);
        case 'weekly':
            $dayMethod = strtolower($day) . 's'; // e.g., 'sundays'
            if (method_exists($schedule, $dayMethod)) {
                return $schedule->weekly()->{$dayMethod}()->at($time);
            }
            return $schedule->weekly()->sundays()->at($time);
        case 'monthly':
            $dayNum = is_numeric($day) ? (int) $day : 1;
            return $schedule->monthlyOn($dayNum, $time);
        default:
            return $schedule->daily()->at($time);
    }
};

// Only run schedules if the settings table exists
if (Schema::hasTable('scraper_settings')) {
    // Players
    if (ScraperSetting::get('schedule_players_enabled', true)) {
        $schedule = Schedule::job(new ScrapePlayersJob())
            ->withoutOverlapping()
            ->onOneServer();
        $configureSchedule(
            $schedule,
            ScraperSetting::get('schedule_players_frequency', 'monthly'),
            ScraperSetting::get('schedule_players_day', '1'),
            ScraperSetting::get('schedule_players_time', '03:00')
        );
    }

    // Rankings (runs for both male and female)
    if (ScraperSetting::get('schedule_rankings_enabled', true)) {
        $frequency = ScraperSetting::get('schedule_rankings_frequency', 'weekly');
        $day = ScraperSetting::get('schedule_rankings_day', 'sunday');
        $time = ScraperSetting::get('schedule_rankings_time', '02:00');

        $scheduleMale = Schedule::job(new ScrapeRankingsJob(['gender' => 'male']))
            ->withoutOverlapping()
            ->onOneServer();
        $configureSchedule($scheduleMale, $frequency, $day, $time);

        // Female rankings 30 minutes later
        $timeParts = explode(':', $time);
        $femaleTime = sprintf('%02d:%02d', $timeParts[0], ((int)$timeParts[1] + 30) % 60);
        $scheduleFemale = Schedule::job(new ScrapeRankingsJob(['gender' => 'female']))
            ->withoutOverlapping()
            ->onOneServer();
        $configureSchedule($scheduleFemale, $frequency, $day, $femaleTime);
    }

    // Transitions
    if (ScraperSetting::get('schedule_transitions_enabled', false)) {
        $schedule = Schedule::job(new ScrapeTransitionsJob())
            ->withoutOverlapping()
            ->onOneServer();
        $configureSchedule(
            $schedule,
            ScraperSetting::get('schedule_transitions_frequency', 'weekly'),
            ScraperSetting::get('schedule_transitions_day', 'monday'),
            ScraperSetting::get('schedule_transitions_time', '03:30')
        );
    }

    // Series
    if (ScraperSetting::get('schedule_series_enabled', false)) {
        $schedule = Schedule::job(new ScrapeSeriesJob())
            ->withoutOverlapping()
            ->onOneServer();
        $configureSchedule(
            $schedule,
            ScraperSetting::get('schedule_series_frequency', 'weekly'),
            ScraperSetting::get('schedule_series_day', 'monday'),
            ScraperSetting::get('schedule_series_time', '04:00')
        );
    }

    // Live Center
    if (ScraperSetting::get('schedule_live_center_enabled', false)) {
        $schedule = Schedule::job(new ScrapeLiveCenterJob())
            ->withoutOverlapping()
            ->onOneServer();
        $configureSchedule(
            $schedule,
            ScraperSetting::get('schedule_live_center_frequency', 'daily'),
            ScraperSetting::get('schedule_live_center_day', ''),
            ScraperSetting::get('schedule_live_center_time', '05:00')
        );
    }
}

/*
|--------------------------------------------------------------------------
| Monthly Rankings Scraper with Popup Interaction
|--------------------------------------------------------------------------
|
| Run new popup-based rankings scraper on first Tuesday of each month
| Profixio updates data on first Monday, we scrape on first Tuesday at 2 AM
| This scraper clicks through popups to get both rankings and matches
|
*/

// Rankings scraper: First Tuesday of every month at 2 AM
// Cron: "0 2 1-7 * 2" means 2 AM on days 1-7 of month, only on Tuesday (2)
Schedule::command('scraper:start', [
    date('Y-m') // Current year-month
])
    ->cron('0 2 1-7 * 2')
    ->name('rankings-scraper-monthly-popup')
    ->onOneServer()
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Monthly Full Scraper Export
|--------------------------------------------------------------------------
|
| Run complete scraper export on configured day of each month
| This runs all scrapers and saves results to JSON for archival
|
*/

if (Schema::hasTable('scraper_settings')) {
    if (ScraperSetting::get('schedule_export_enabled', true)) {
        $exportDay = (int) ScraperSetting::get('schedule_export_day', '1');
        $exportTime = ScraperSetting::get('schedule_export_time', '02:00');

        Schedule::command('scraper:export')
            ->monthlyOn($exportDay, $exportTime)
            ->withoutOverlapping()
            ->onOneServer()
            ->appendOutputTo(storage_path('logs/scraper-export.log'));
    }
}

/*
|--------------------------------------------------------------------------
| Scraper Log Cleanup
|--------------------------------------------------------------------------
|
| Archive old scraper logs daily and clean up old archives
|
*/

Schedule::command('scraper:cleanup-logs --days=7 --delete-archived=30')
    ->daily()
    ->at('00:00')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Database Backup
|--------------------------------------------------------------------------
|
| Run database backup daily and cleanup old backups
|
*/

Schedule::command('backup:run --only-db')
    ->daily()
    ->at('01:00')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:clean')
    ->daily()
    ->at('01:30')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Apple Sign In Secret Regeneration
|--------------------------------------------------------------------------
|
| Automatically regenerate Apple client secret every 5 months
| The command checks if regeneration is needed before running
|
*/

Schedule::command('apple:regenerate-secret')
    ->daily()
    ->at('02:30')
    ->withoutOverlapping()
    ->onOneServer();

/*
|--------------------------------------------------------------------------
| Heartbeat Monitoring
|--------------------------------------------------------------------------
|
| Update scheduler heartbeat every minute to track scheduler health
|
*/

Schedule::command('heartbeat:scheduler')
    ->everyMinute()
    ->withoutOverlapping();

// Dispatch queue heartbeat job periodically to monitor queue workers
if (config('heartbeat.queue.enabled')) {
    Schedule::job(new \App\Jobs\QueueHeartbeat())
        ->everyFiveMinutes()
        ->withoutOverlapping();
}
