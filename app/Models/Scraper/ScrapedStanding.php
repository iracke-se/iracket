<?php

namespace App\Models\Scraper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedStanding extends Model
{
    protected $fillable = [
        'scraper_run_id',
        'period',
        'series_name',
        'session_name',
        'position',
        'team_name',
        'matches_played',
        'wins',
        'losses',
        'draws',
        'points',
        'goal_difference',
    ];

    protected $casts = [
        'position' => 'integer',
        'matches_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'draws' => 'integer',
        'points' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ScraperRun::class, 'scraper_run_id');
    }
}
