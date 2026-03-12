<?php

namespace App\Models\Scraper;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedDistrictPlayer extends Model
{
    protected $table = 'scraped_district_players';

    protected $fillable = [
        'scraper_run_id',
        'profixio_district_id',
        'district_name',
        'gender',
        'profixio_player_id',
        'surname',
        'first_name',
        'birth_year',
        'club_name',
        'position',
        'points',
        'is_synced',
        'synced_user_id',
    ];

    protected $casts = [
        'is_synced' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ScraperRun::class);
    }

    public function syncedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'synced_user_id');
    }
}
