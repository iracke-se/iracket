<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Club extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'location',
        'website',
        'email',
        'phone',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($club) {
            if (empty($club->slug)) {
                $club->slug = Str::slug($club->name);
            }
        });
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function monthlyRankings(): HasMany
    {
        return $this->hasMany(ClubMonthlyRanking::class);
    }

    public function currentMonthRanking()
    {
        return $this->monthlyRankings()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();
    }

    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Get all transitions where players are leaving this club
     */
    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(ClubTransition::class, 'from_club_id');
    }

    /**
     * Get all transitions where players are joining this club
     */
    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(ClubTransition::class, 'to_club_id');
    }

    /**
     * Get pending incoming players (transitions not yet completed)
     */
    public function pendingIncomingPlayers(): HasMany
    {
        return $this->hasMany(ClubTransition::class, 'to_club_id')
            ->where('completion_date', '>', now())
            ->orderBy('completion_date');
    }

    /**
     * Get pending outgoing players (transitions not yet completed)
     */
    public function pendingOutgoingPlayers(): HasMany
    {
        return $this->hasMany(ClubTransition::class, 'from_club_id')
            ->where('completion_date', '>', now())
            ->orderBy('completion_date');
    }
}
