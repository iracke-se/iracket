<?php

namespace App\Models\Scraper;

use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedMatch extends Model
{
    protected $table = 'scraped_matches';

    protected $fillable = [
        'scraper_run_id',
        'profixio_player_id',
        'player_name',
        'opponent_name',
        'result',
        'opponent_points',
        'match_points',
        'match_date',
        'scraped_month',
        'source',
        'period',
        'division',
        'series_name',
        'team1_name',
        'team2_name',
        'player1_name',
        'player2_name',
        'score',
        'sets',
        'played_at',
        'winner',
        'is_synced',
        'synced_match_id',
    ];

    protected $casts = [
        'sets' => 'array',
        'is_synced' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ScraperRun::class, 'scraper_run_id');
    }

    public function syncedMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'synced_match_id');
    }

    public function getPlayer1SetsAttribute(): ?int
    {
        if (!$this->score) {
            return null;
        }

        $parts = explode('-', $this->score);
        return (int) ($parts[0] ?? 0);
    }

    public function getPlayer2SetsAttribute(): ?int
    {
        if (!$this->score) {
            return null;
        }

        $parts = explode('-', $this->score);
        return (int) ($parts[1] ?? 0);
    }
}
