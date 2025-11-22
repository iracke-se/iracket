<?php

namespace App\Models\Scraper;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedPlayer extends Model
{
    protected $fillable = [
        'scraper_run_id',
        'period',
        'club_name',
        'surname',
        'first_name',
        'sex',
        'date_of_birth',
        'license_type',
        'player_class',
        'is_synced',
        'synced_user_id',
    ];

    protected $casts = [
        'is_synced' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ScraperRun::class, 'scraper_run_id');
    }

    public function syncedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'synced_user_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->surname}");
    }

    public function getGenderAttribute(): ?string
    {
        return match (strtolower($this->sex ?? '')) {
            'm', 'male', 'man' => 'male',
            'f', 'k', 'female', 'woman', 'kvinna' => 'female',
            default => null,
        };
    }
}
