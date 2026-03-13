<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

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

    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'user_fullname',
        'email',
        'phone_number',
        'gender',
        'age',
        'birth_year',
        'password',
        'profile_picture',
        'google_id',
        'apple_id',
        'sbtf_player_id',
        'sbtf_synced',
        'sbtf_synced_at',
        'terms_accepted',
        'terms_accepted_at',
        'verification_code',
        'verification_code_expires_at',
        'club_id',
        'district_id',
        'visible_in_players',
        'fcm_token',
        'device_type',
        'fcm_token_updated_at',
        'is_connected',
        'is_active_player',
        'accepts_push_notifications',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'terms_accepted' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'sbtf_synced' => 'boolean',
            'sbtf_synced_at' => 'datetime',
            'birth_year' => 'integer',
            'is_connected' => 'boolean',
            'is_active_player' => 'boolean',
            'accepts_push_notifications' => 'boolean',
        ];
    }

    /**
     * Get the user's full name
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->first_name . ' ' . $this->last_name),
        );
    }

    /**
     * Get the user's registered full name (with fallback to regular name)
     */
    protected function registeredName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user_fullname ?? $this->name,
        );
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::upper(
            Str::substr($this->first_name, 0, 1) . Str::substr($this->last_name, 0, 1)
        );
    }

    /**
     * Generate a new verification code
     */
    public function generateVerificationCode(): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(15),
        ]);

        return $code;
    }

    /**
     * Check if verification code is valid
     */
    public function isVerificationCodeValid(string $code): bool
    {
        return $this->verification_code === $code
            && $this->verification_code_expires_at
            && $this->verification_code_expires_at->isFuture();
    }

    /**
     * Mark email as verified
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();
    }

    /**
     * Send password reset notification
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the club the user belongs to
     */
    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    /**
     * Get the district the user belongs to
     */
    public function districtModel(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    /**
     * Get matches where user is player 1
     */
    public function matchesAsPlayer1(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'player1_id');
    }

    /**
     * Get matches where user is player 2
     */
    public function matchesAsPlayer2(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'player2_id');
    }

    /**
     * Get all matches for the user
     */
    public function allMatches()
    {
        return GameMatch::where('player1_id', $this->id)
            ->orWhere('player2_id', $this->id);
    }

    /**
     * Get monthly rankings
     */
    public function monthlyRankings(): HasMany
    {
        return $this->hasMany(MonthlyRanking::class);
    }

    /**
     * Get current month ranking
     */
    public function currentMonthRanking()
    {
        return $this->monthlyRankings()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();
    }

    /**
     * Get notifications
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get users that this user is monitoring
     */
    public function monitoring(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_monitors', 'user_id', 'monitored_user_id')
            ->withTimestamps();
    }

    /**
     * Get users who are monitoring this user
     */
    public function monitoredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_monitors', 'monitored_user_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Check if this user is monitoring another user
     */
    public function isMonitoring(User $user): bool
    {
        return $this->monitoring()->where('monitored_user_id', $user->id)->exists();
    }

    /**
     * Toggle monitoring status for a user
     */
    public function toggleMonitoring(User $user): bool
    {
        if ($this->isMonitoring($user)) {
            $this->monitoring()->detach($user->id);
            return false;
        }

        $this->monitoring()->attach($user->id);
        return true;
    }

    /**
     * Get current ranking position within gender group
     */
    public function getCurrentRankingPosition(): ?int
    {
        $currentRanking = $this->currentMonthRanking();

        if (!$currentRanking) {
            return null;
        }

        // Get position by counting users with more points in the same gender
        $position = MonthlyRanking::where('year', now()->year)
            ->where('month', now()->month)
            ->whereHas('user', function ($query) {
                $query->where('gender', $this->gender)
                    ->where('is_active_player', true);
            })
            ->where('points', '>', $currentRanking->points)
            ->count() + 1;

        return $position;
    }

    /**
     * Get current points
     */
    public function getCurrentPoints(): int
    {
        return $this->currentMonthRanking()?->points ?? 0;
    }

    /**
     * Get ranking category based on gender
     */
    public function getRankingCategory(): string
    {
        return $this->gender === 'female' ? 'women' : 'men';
    }

    /**
     * Get club transitions for this user
     */
    public function clubTransitions(): HasMany
    {
        return $this->hasMany(ClubTransition::class);
    }

    /**
     * Get upcoming club transitions (pending)
     */
    public function pendingTransitions(): HasMany
    {
        return $this->hasMany(ClubTransition::class)
            ->where('completion_date', '>', now())
            ->orderBy('completion_date');
    }

    /**
     * Get completed club transitions
     */
    public function completedTransitions(): HasMany
    {
        return $this->hasMany(ClubTransition::class)
            ->where('completion_date', '<=', now())
            ->orderByDesc('completion_date');
    }
}
