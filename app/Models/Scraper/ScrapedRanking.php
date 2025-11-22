<?php

namespace App\Models\Scraper;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedRanking extends Model
{
    protected $fillable = [
        'scraper_run_id',
        'period',
        'division',
        'gender',
        'position',
        'position_change',
        'name',
        'born',
        'club',
        'points',
        'points_change',
        'is_synced',
        'synced_user_id',
    ];

    protected $casts = [
        'is_synced' => 'boolean',
        'points' => 'integer',
        'position' => 'integer',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ScraperRun::class, 'scraper_run_id');
    }

    public function syncedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'synced_user_id');
    }

    public function getPositionChangeIntAttribute(): ?int
    {
        if (!$this->position_change) {
            return null;
        }

        return (int) str_replace(['+', ' '], '', $this->position_change);
    }

    public function getPointsChangeIntAttribute(): ?int
    {
        if (!$this->points_change) {
            return null;
        }

        return (int) str_replace(['+', ' '], '', $this->points_change);
    }
}
