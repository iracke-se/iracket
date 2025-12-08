<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClubTransition extends Model
{
    protected $fillable = [
        'period',
        'user_id',
        'from_club_id',
        'to_club_id',
        'surname',
        'first_name',
        'born',
        'from_club_name',
        'to_club_name',
        'completion_date',
        'is_synced',
    ];

    protected $casts = [
        'born' => 'date',
        'completion_date' => 'date',
        'is_synced' => 'boolean',
    ];

    /**
     * Get the user associated with this transition
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the club the player is leaving
     */
    public function fromClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'from_club_id');
    }

    /**
     * Get the club the player is joining
     */
    public function toClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'to_club_id');
    }

    /**
     * Check if transition is completed (completion date has passed)
     */
    public function isCompleted(): bool
    {
        return $this->completion_date->isPast();
    }

    /**
     * Check if transition is pending (completion date is in the future)
     */
    public function isPending(): bool
    {
        return $this->completion_date->isFuture();
    }

    /**
     * Get the player name (from scraped data or user)
     */
    public function getPlayerNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return trim($this->first_name . ' ' . $this->surname);
    }
}
