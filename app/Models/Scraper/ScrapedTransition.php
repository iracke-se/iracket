<?php

namespace App\Models\Scraper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedTransition extends Model
{
    protected $fillable = [
        'scraper_run_id',
        'period',
        'surname',
        'first_name',
        'born',
        'from_club',
        'to_club',
        'completion_date',
        'is_synced',
    ];

    protected $casts = [
        'is_synced' => 'boolean',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(ScraperRun::class, 'scraper_run_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->surname}");
    }
}
