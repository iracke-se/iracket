<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GameMatch extends Model
{
    protected $table = 'matches';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected $fillable = [
        'player1_id',
        'player2_id',
        'played_at',
        'player1_sets',
        'player2_sets',
        'winner_id',
        'player1_comments',
        'player2_comments',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'played_at' => 'date',
        'player1_comments' => 'array',
        'player2_comments' => 'array',
    ];

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getResultAttribute(): string
    {
        return $this->player1_sets . ' - ' . $this->player2_sets;
    }

    public function isWinner(User $user): bool
    {
        return $this->winner_id === $user->id;
    }

    public function isPlayer(User $user): bool
    {
        return $this->player1_id === $user->id || $this->player2_id === $user->id;
    }

    public function getOpponent(User $user): ?User
    {
        if ($this->player1_id === $user->id) {
            return $this->player2;
        }
        if ($this->player2_id === $user->id) {
            return $this->player1;
        }
        return null;
    }

    public function getUserSets(User $user): int
    {
        if ($this->player1_id === $user->id) {
            return $this->player1_sets;
        }
        if ($this->player2_id === $user->id) {
            return $this->player2_sets;
        }
        return 0;
    }

    public function getOpponentSets(User $user): int
    {
        if ($this->player1_id === $user->id) {
            return $this->player2_sets;
        }
        if ($this->player2_id === $user->id) {
            return $this->player1_sets;
        }
        return 0;
    }
}
