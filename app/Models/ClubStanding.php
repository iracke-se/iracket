<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubStanding extends Model
{
    protected $fillable = [
        'club_id',
        'team_name',
        'series_name',
        'session_name',
        'position',
        'matches_played',
        'wins',
        'losses',
        'draws',
        'points',
        'goal_difference',
        'period',
    ];

    protected $casts = [
        'position' => 'integer',
        'matches_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'draws' => 'integer',
        'points' => 'integer',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }
}
