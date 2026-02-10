<?php

namespace App\Models\Scraper;

use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiveMatchGame extends Model
{
    protected $fillable = [
        'live_match_detail_id',
        'game_number',
        'game_type',
        'player1_name',
        'player2_name',
        'player1_partner_name',
        'player2_partner_name',
        'player1_sets',
        'player2_sets',
        'winner_name',
        'profixio_game_id',
        'is_synced',
        'synced_match_id',
    ];

    protected $casts = [
        'is_synced' => 'boolean',
    ];

    public function detail(): BelongsTo
    {
        return $this->belongsTo(LiveMatchDetail::class, 'live_match_detail_id');
    }

    public function sets(): HasMany
    {
        return $this->hasMany(LiveMatchSet::class);
    }

    public function syncedMatch(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'synced_match_id');
    }
}
