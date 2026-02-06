<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/debug/scraped-matches', function() {
    $playerName = "Möregårdh, Truls";
    
    echo "<h2>Scraped Matches for $playerName</h2>";
    
    // Get all scraped matches for this player
    $matches = DB::table('scraped_matches')
        ->where(function($q) use ($playerName) {
            $q->where('player_name', $playerName)
              ->orWhere('opponent_name', $playerName);
        })
        ->orderBy('scraped_month', 'desc')
        ->get();
    
    // Group by scraped_month
    $grouped = $matches->groupBy('scraped_month');
    
    foreach ($grouped as $month => $monthMatches) {
        echo "<h3>$month (" . count($monthMatches) . " matches)</h3>";
        echo "<ul>";
        foreach ($monthMatches as $match) {
            echo "<li>";
            echo "Date: " . ($match->match_date ?? $match->played_at ?? 'N/A');
            echo " | " . $match->player_name . " vs " . $match->opponent_name;
            echo " | Points: " . $match->match_points;
            echo " | Result: " . $match->result;
            echo "</li>";
        }
        echo "</ul>";
    }
    
    if ($matches->isEmpty()) {
        echo "<p>No matches found!</p>";
    }
});
