<?php

namespace App\Services\Scraper;

use App\Models\Club;
use App\Models\ClubStanding;
use App\Models\Scraper\ScrapedStanding;
use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\Log;

class StandingsSyncService
{
    protected array $stats = [
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    /** @var array<string, int>  lower(club_name) => club_id */
    protected array $clubCache = [];

    protected bool $cacheInitialized = false;

    public function syncStandings(?int $runId = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();
        $this->initializeCache();

        $query = ScrapedStanding::query();
        if ($runId) {
            $query->where('scraper_run_id', $runId);
        }

        $totalCount = $query->count();
        Log::info("Syncing {$totalCount} standings");
        $run?->log('info', "Starting standings sync: {$totalCount} rows");

        $processed = 0;

        $query->chunkById(200, function ($standings) use (&$processed, $totalCount, $run) {
            foreach ($standings as $standing) {
                try {
                    $this->syncOne($standing);
                    $processed++;
                } catch (\Throwable $e) {
                    $this->stats['errors']++;
                    Log::error('Standing sync failed', [
                        'scraped_standing_id' => $standing->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            $run?->log('info', "Standings: {$processed}/{$totalCount} (created: {$this->stats['created']}, updated: {$this->stats['updated']}, errors: {$this->stats['errors']})");
        });

        $run?->log('info', "Standings sync completed: created={$this->stats['created']}, updated={$this->stats['updated']}, skipped={$this->stats['skipped']}, errors={$this->stats['errors']}");

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
        $this->clubCache = Club::select('id', 'name')
            ->get()
            ->mapWithKeys(fn ($c) => [strtolower(trim($c->name)) => $c->id])
            ->all();
        $this->cacheInitialized = true;
    }

    protected function syncOne(ScrapedStanding $s): void
    {
        $clubId = $this->resolveClubId($s->team_name);

        $attrs = [
            'club_id' => $clubId,
            'team_name' => $s->team_name,
            'series_name' => $s->series_name,
            'session_name' => $s->session_name,
            'position' => (int) $s->position,
            'matches_played' => (int) $s->matches_played,
            'wins' => (int) $s->wins,
            'losses' => (int) $s->losses,
            'draws' => (int) $s->draws,
            'points' => (int) $s->points,
            'goal_difference' => $s->goal_difference,
            'period' => $s->period,
        ];

        $existing = ClubStanding::where('team_name', $s->team_name)
            ->where('series_name', $s->series_name)
            ->where('session_name', $s->session_name)
            ->first();

        if ($existing) {
            $existing->fill($attrs)->save();
            $this->stats['updated']++;
        } else {
            ClubStanding::create($attrs);
            $this->stats['created']++;
        }
    }

    protected function resolveClubId(?string $teamName): ?int
    {
        if (! $teamName) {
            return null;
        }
        return $this->clubCache[strtolower(trim($teamName))] ?? null;
    }
}
