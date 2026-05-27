<?php

namespace App\Services\Scraper;

use App\Models\Club;
use App\Models\ClubTransition;
use App\Models\Scraper\ScrapedTransition;
use App\Models\Scraper\ScraperRun;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TransitionSyncService
{
    protected array $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    /** @var array<string, int>  lower(first_name|surname) => user_id */
    protected array $userCache = [];

    /** @var array<string, int>  lower(club_name) => club_id */
    protected array $clubCache = [];

    protected bool $cacheInitialized = false;

    public function syncTransitions(?int $runId = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();
        $this->initializeCache();

        $query = ScrapedTransition::where('is_synced', false);
        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $totalCount = $query->count();
        Log::info("Syncing {$totalCount} transitions");
        $run?->log('info', "Starting transition sync: {$totalCount} rows");

        $processed = 0;

        $query->chunkById(200, function ($transitions) use (&$processed, $totalCount, $run) {
            foreach ($transitions as $transition) {
                try {
                    $this->syncOne($transition);
                    $processed++;
                } catch (\Throwable $e) {
                    $this->stats['errors']++;
                    Log::error('Transition sync failed', [
                        'scraped_transition_id' => $transition->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $run?->log('info', "Transitions: {$processed}/{$totalCount} (created: {$this->stats['created']}, updated: {$this->stats['updated']}, skipped: {$this->stats['skipped']}, errors: {$this->stats['errors']})");
        });

        $run?->log('info', "Transition sync completed: created={$this->stats['created']}, updated={$this->stats['updated']}, skipped={$this->stats['skipped']}, errors={$this->stats['errors']}");

        return $this->stats;
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    protected function resetStats(): void
    {
        $this->stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
    }

    protected function initializeCache(): void
    {
        if ($this->cacheInitialized) {
            return;
        }

        $this->userCache = User::select('id', 'first_name', 'last_name')
            ->get()
            ->mapWithKeys(fn ($u) => [strtolower(trim($u->first_name).'|'.trim($u->last_name)) => $u->id])
            ->all();

        $this->clubCache = Club::select('id', 'name')
            ->get()
            ->mapWithKeys(fn ($c) => [strtolower(trim($c->name)) => $c->id])
            ->all();

        $this->cacheInitialized = true;
    }

    protected function syncOne(ScrapedTransition $t): void
    {
        $userId = $this->resolveUserId($t->first_name, $t->surname);
        $fromClubId = $this->resolveClubId($t->from_club);
        $toClubId = $this->resolveClubId($t->to_club);

        $completionDate = $this->parseDate($t->completion_date);

        $attrs = [
            'period' => $t->period,
            'surname' => $t->surname,
            'first_name' => $t->first_name,
            'from_club_name' => $t->from_club,
            'to_club_name' => $t->to_club,
            'completion_date' => $completionDate,
        ];

        $existing = ClubTransition::where('first_name', $t->first_name)
            ->where('surname', $t->surname)
            ->where('from_club_name', $t->from_club)
            ->where('to_club_name', $t->to_club)
            ->where('completion_date', $completionDate)
            ->first();

        if ($existing) {
            $existing->fill(array_merge($attrs, [
                'user_id' => $userId ?: $existing->user_id,
                'from_club_id' => $fromClubId ?: $existing->from_club_id,
                'to_club_id' => $toClubId ?: $existing->to_club_id,
                'born' => $this->parseDate($t->born) ?: $existing->born,
                'is_synced' => true,
            ]))->save();
            $this->stats['updated']++;
        } else {
            ClubTransition::create(array_merge($attrs, [
                'user_id' => $userId,
                'from_club_id' => $fromClubId,
                'to_club_id' => $toClubId,
                'born' => $this->parseDate($t->born),
                'is_synced' => true,
            ]));
            $this->stats['created']++;
        }

        $t->update(['is_synced' => true]);
    }

    protected function resolveUserId(?string $first, ?string $surname): ?int
    {
        if (! $first || ! $surname) {
            return null;
        }
        $key = strtolower(trim($first).'|'.trim($surname));

        return $this->userCache[$key] ?? null;
    }

    protected function resolveClubId(?string $name): ?int
    {
        if (! $name) {
            return null;
        }

        return $this->clubCache[strtolower(trim($name))] ?? null;
    }

    protected function parseDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }
        try {
            // profixio uses YYYY.MM.DD — normalize to YYYY-MM-DD before parsing
            $normalized = str_replace('.', '-', $value);
            return \Carbon\Carbon::parse($normalized)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
