<?php

namespace App\Models;

use App\Models\Scraper\LiveMatchGame;
use App\Models\Scraper\ScrapedMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'source',
        'is_unofficial',
        'replaced_by_match_id',
        'player1_id',
        'player2_id',
        'played_at',
        'player1_sets',
        'player2_sets',
        'player1_match_points',
        'player2_match_points',
        'player1_opponent_rating',
        'player2_opponent_rating',
        'player1_points_before',
        'player2_points_before',
        'player1_points_change',
        'player2_points_change',
        'winner_id',
        'player1_comments',
        'player2_comments',
        'description',
        'status',
        'is_manual',
        'created_by',
        'live_match_game_id',
    ];

    protected $casts = [
        'played_at' => 'date',
        'player1_comments' => 'array',
        'player2_comments' => 'array',
        'is_manual' => 'boolean',
        'is_unofficial' => 'boolean',
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

    public function scrapedMatches(): HasMany
    {
        return $this->hasMany(ScrapedMatch::class, 'synced_match_id');
    }

    public function liveMatchGame(): BelongsTo
    {
        return $this->belongsTo(LiveMatchGame::class);
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

    /**
     * Scope to only official matches (counted in rankings)
     */
    public function scopeOfficial($query)
    {
        return $query->where(function ($q) {
            $q->where('source', 'scraped')
              ->orWhere(function ($subQ) {
                  $subQ->where('source', 'player_added')
                       ->where('is_unofficial', false);
              });
        });
    }

    /**
     * Scope to unofficial matches (not counted)
     */
    public function scopeUnofficial($query)
    {
        return $query->where('source', 'player_added')
            ->where('is_unofficial', true);
    }

    /**
     * Scope for player's own view (includes unofficial)
     */
    public function scopeForPlayer($query, $playerId)
    {
        return $query->where(function ($q) use ($playerId) {
            $q->where('player1_id', $playerId)
              ->orWhere('player2_id', $playerId);
        });
    }
}
