<?php

namespace App\Models\Scraper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveMatchPoint extends Model
{
    protected $fillable = [
        'live_match_set_id',
        'point_number',
        'player1_points',
        'player2_points',
        'serve',
        'comment',
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(LiveMatchSet::class, 'live_match_set_id');
    }
}
