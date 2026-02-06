<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get the min and max dates from matches table
$result = DB::table('matches')
    ->selectRaw('MIN(DATE(played_at)) as date_from, MAX(DATE(played_at)) as date_to, COUNT(*) as total_matches, COUNT(DISTINCT DATE(played_at)) as unique_dates')
    ->whereNotNull('played_at')
    ->first();

if ($result) {
    echo "Match Date Range:\n";
    echo "================\n";
    echo "Date From: " . $result->date_from . "\n";
    echo "Date To: " . $result->date_to . "\n";
    echo "Total Matches: " . $result->total_matches . "\n";
    echo "Unique Dates: " . $result->unique_dates . "\n";
    echo "\n";
    echo "To scrape all these dates, run:\n";
    echo "ddev exec \"php artisan scraper:run live_center --use-existing-dates --date-from={$result->date_from} --date-to={$result->date_to}\"\n";
} else {
    echo "No matches found in database.\n";
}
