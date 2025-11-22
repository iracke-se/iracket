<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'gender',
        'age',
        'password',
        'profile_picture',
        'google_id',
        'apple_id',
        'terms_accepted',
        'terms_accepted_at',
        'verification_code',
        'verification_code_expires_at',
        'club_id',
        'visible_in_players',
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
}
