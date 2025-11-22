<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubMonthlyRanking extends Model
{
    protected $fillable = [
        'club_id',
        'year',
        'month',
        'rank',
        'total_points',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->getMonthNameAttribute() . ' ' . $this->year;
    }

    public static function getCurrentMonthRankings()
    {
        return self::where('year', now()->year)
            ->where('month', now()->month)
            ->with('club')
            ->orderBy('rank')
            ->get();
    }
}
