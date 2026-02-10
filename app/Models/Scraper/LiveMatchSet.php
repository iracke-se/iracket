<?php

namespace App\Models\Scraper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveMatchSet extends Model
{
    protected $fillable = [
        'live_match_game_id',
        'set_number',
        'player1_points',
        'player2_points',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(LiveMatchGame::class, 'live_match_game_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(LiveMatchPoint::class);
    }
}
