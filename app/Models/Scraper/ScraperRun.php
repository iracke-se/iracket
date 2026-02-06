<?php

namespace App\Models\Scraper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScraperRun extends Model
{
    protected $fillable = [
        'type',
        'status',
        'current_step',
        'parameters',
        'steps_data',
        'items_scraped',
        'items_failed',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'steps_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    // Type constants
    const TYPE_RANKINGS = 'rankings';
    const TYPE_PLAYERS = 'players';
    const TYPE_TRANSITIONS = 'transitions';
    const TYPE_SERIES = 'series';
    const TYPE_SERIES_MATCHES = 'series_matches';
    const TYPE_FULL_SCRAPE = 'full_scrape';

    public function logs(): HasMany
    {
        return $this->hasMany(ScraperLog::class);
    }

    public function players(): HasMany
    {
        return $this->hasMany(ScrapedPlayer::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(ScrapedRanking::class);
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(ScrapedTransition::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ScrapedMatch::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(ScrapedStanding::class);
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }

    public function incrementScraped(int $count = 1): void
    {
        $this->increment('items_scraped', $count);
    }

    public function incrementFailed(int $count = 1): void
    {
        $this->increment('items_failed', $count);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->logs()->create([
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();
        $diff = $this->started_at->diff($end);

        if ($diff->h > 0) {
            return $diff->format('%hh %im %ss');
        } elseif ($diff->i > 0) {
            return $diff->format('%im %ss');
        }

        return $diff->format('%ss');
    }

    public function getProgressPercentageAttribute(): int
    {
        $total = $this->items_scraped + $this->items_failed;
        if ($total === 0) {
            return 0;
        }

        return (int) (($this->items_scraped / $total) * 100);
    }

    public function updateCurrentStep(string $step): void
    {
        $this->update(['current_step' => $step]);
    }

    public function updateStepData(string $step, array $data): void
    {
        $stepsData = $this->steps_data ?? [];
        $stepsData[$step] = $data;
        $this->update(['steps_data' => $stepsData]);
    }

    public function getStepData(string $step): ?array
    {
        return $this->steps_data[$step] ?? null;
    }
}
