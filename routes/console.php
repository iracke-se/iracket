<?php

use App\Jobs\Scraper\ScrapeRankingsJob;
use App\Jobs\Scraper\ScrapePlayersJob;
use App\Jobs\Scraper\ScrapeTransitionsJob;
use App\Jobs\Scraper\ScrapeSeriesJob;
use App\Jobs\Scraper\ScrapeLiveCenterJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scraper Schedules
|--------------------------------------------------------------------------
|
| Configure automated scraping schedules based on config/scraper.php
|
*/

// Rankings - Weekly on Sundays at 2:00 AM
if (config('scraper.schedule.rankings.enabled')) {
    Schedule::job(new ScrapeRankingsJob(['gender' => 'male']))
        ->weekly()
        ->sundays()
        ->at(config('scraper.schedule.rankings.time', '02:00'))
        ->withoutOverlapping()
        ->onOneServer();

    Schedule::job(new ScrapeRankingsJob(['gender' => 'female']))
        ->weekly()
        ->sundays()
        ->at('02:30')
        ->withoutOverlapping()
        ->onOneServer();
}

// Players - Monthly on the 1st at 3:00 AM
if (config('scraper.schedule.players.enabled')) {
    Schedule::job(new ScrapePlayersJob())
        ->monthlyOn(config('scraper.schedule.players.day', 1), config('scraper.schedule.players.time', '03:00'))
        ->withoutOverlapping()
        ->onOneServer();
}

// Series - Weekly on Mondays at 4:00 AM
if (config('scraper.schedule.series.enabled')) {
    Schedule::job(new ScrapeSeriesJob())
        ->weekly()
        ->mondays()
        ->at(config('scraper.schedule.series.time', '04:00'))
        ->withoutOverlapping()
        ->onOneServer();
}

// Live Center - Daily at 5:00 AM
if (config('scraper.schedule.live_center.enabled')) {
    Schedule::job(new ScrapeLiveCenterJob())
        ->daily()
        ->at(config('scraper.schedule.live_center.time', '05:00'))
        ->withoutOverlapping()
        ->onOneServer();
}
