<?php

namespace App\Models\Scraper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveMatchDetail extends Model
{
    protected $fillable = [
        'scraper_run_id',
        'division',
        'team1_name',
        'team2_name',
        'team1_score',
        'team2_score',
        'played_at',
        'profixio_match_id',
        'status',
        'is_synced',
    ];

    protected $casts = [
        'played_at' => 'date',
        'is_synced' => 'boolean',
    ];

    public function scraperRun(): BelongsTo
    {
        return $this->belongsTo(ScraperRun::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(LiveMatchGame::class);
    }
}
