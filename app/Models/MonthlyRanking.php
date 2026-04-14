<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyRanking extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'ranking_date',
        'rank',
        'points',
        'points_change',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->getMonthNameAttribute() . ' ' . $this->year;
    }

    public function getPointsChangeFormattedAttribute(): string
    {
        if ($this->points_change > 0) {
            return '+' . $this->points_change;
        }
        return (string) $this->points_change;
    }

    public static function getCurrentMonthRankings(?string $gender = null)
    {
        $query = self::where('year', now()->year)
            ->where('month', now()->month)
            ->with('user.club')
            ->orderBy('rank');

        if ($gender) {
            $query->whereHas('user', function ($q) use ($gender) {
                $q->where('gender', $gender);
            });
        }

        return $query->get();
    }
}
