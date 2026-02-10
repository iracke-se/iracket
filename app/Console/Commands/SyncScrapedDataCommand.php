<?php

namespace App\Console\Commands;

use App\Services\Scraper\LiveCenterSyncService;
use App\Services\Scraper\MatchSyncService;
use App\Services\Scraper\SyncService;
use Illuminate\Console\Command;

class SyncScrapedDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'scraper:sync
                            {type : The type of data to sync (players, rankings, matches, live_center, all)}
                            {--run= : Only sync data from a specific scraper run ID}
                            {--dry-run : Show what would be synced without actually syncing}';

    /**
     * The console command description.
     */
    protected $description = 'Sync scraped data to production tables (users, clubs, rankings, matches)';

    /**
     * Execute the console command.
     */
    public function handle(SyncService $syncService, MatchSyncService $matchSyncService, LiveCenterSyncService $liveCenterSyncService): int
    {
        $type = $this->argument('type');
        $runId = $this->option('run') ? (int) $this->option('run') : null;
        $dryRun = $this->option('dry-run');

        $validTypes = ['players', 'rankings', 'matches', 'live_center', 'all'];

        if (!in_array($type, $validTypes)) {
            $this->error("Invalid type. Must be one of: " . implode(', ', $validTypes));
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be synced');
            $this->newLine();
        }

        $this->info("Starting sync for: {$type}" . ($runId ? " (Run ID: {$runId})" : " (all unsynced data)"));
        $this->newLine();

        if ($dryRun) {
            return $this->showDryRun($type, $runId);
        }

        try {
            $stats = [];

            if ($type === 'players' || $type === 'all') {
                $this->info('Syncing players...');
                $playerStats = $syncService->syncPlayers($runId);
                $this->displayStats('Players', $playerStats);
                $stats['players'] = $playerStats;
            }

            if ($type === 'rankings' || $type === 'all') {
                $this->info('Syncing rankings...');
                $rankingStats = $syncService->syncRankings($runId);
                $this->displayStats('Rankings', $rankingStats);
                $stats['rankings'] = $rankingStats;
            }

            if ($type === 'matches' || $type === 'all') {
                $this->info('Syncing matches...');
                $matchStats = $matchSyncService->syncMatches($runId);
                $this->displayMatchStats($matchStats);
                $stats['matches'] = $matchStats;
            }

            if ($type === 'live_center' || $type === 'all') {
                $this->info('Syncing live center games...');
                $liveCenterStats = $liveCenterSyncService->syncMatches($runId);
                $this->displayLiveCenterStats($liveCenterStats);
                $stats['live_center'] = $liveCenterStats;
            }

            $this->newLine();
            $this->info('✓ Sync completed successfully!');

            // Show summary if syncing all
            if ($type === 'all') {
                $this->newLine();
                $this->table(
                    ['Type', 'Created', 'Updated', 'Skipped', 'Errors'],
                    [
                        [
                            'Players',
                            $stats['players']['created'] ?? 0,
                            $stats['players']['updated'] ?? 0,
                            $stats['players']['skipped'] ?? 0,
                            $stats['players']['errors'] ?? 0,
                        ],
                        [
                            'Rankings',
                            $stats['rankings']['created'] ?? 0,
                            $stats['rankings']['updated'] ?? 0,
                            $stats['rankings']['skipped'] ?? 0,
                            $stats['rankings']['errors'] ?? 0,
                        ],
                    ]
                );
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sync failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Display sync statistics
     */
    protected function displayStats(string $label, array $stats): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );
        $this->newLine();
    }

    /**
     * Display match sync statistics
     */
    protected function displayMatchStats(array $stats): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Official matches created', $stats['created']],
                ['Comments migrated', $stats['comments_migrated']],
                ['Manual matches replaced', $stats['manual_matches_replaced']],
                ['Manual matches marked unofficial', $stats['manual_matches_marked_unofficial']],
                ['Errors', $stats['errors']],
            ]
        );
        $this->newLine();
    }

    /**
     * Display live center sync statistics
     */
    protected function displayLiveCenterStats(array $stats): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                ['Games synced', $stats['games_synced']],
                ['Matches created', $stats['matches_created']],
                ['Matches linked', $stats['matches_linked']],
                ['Skipped', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );
        $this->newLine();
    }

    /**
     * Show what would be synced (dry run)
     */
    protected function showDryRun(string $type, ?int $runId): int
    {
        if ($type === 'players' || $type === 'all') {
            $query = \App\Models\Scraper\ScrapedPlayer::where('is_synced', false);
            if ($runId) {
                $query->where('scraper_run_id', $runId);
            }
            $count = $query->count();

            $this->info("Would sync {$count} players");

            if ($count > 0) {
                $sample = $query->limit(5)->get(['first_name', 'surname', 'club_name', 'period']);
                $this->table(
                    ['First Name', 'Surname', 'Club', 'Period'],
                    $sample->map(fn($p) => [
                        $p->first_name,
                        $p->surname,
                        $p->club_name,
                        $p->period,
                    ])->toArray()
                );
            }
            $this->newLine();
        }

        if ($type === 'rankings' || $type === 'all') {
            $query = \App\Models\Scraper\ScrapedRanking::where('is_synced', false);
            if ($runId) {
                $query->where('scraper_run_id', $runId);
            }
            $count = $query->count();

            $this->info("Would sync {$count} rankings");

            if ($count > 0) {
                $sample = $query->limit(5)->get(['name', 'club', 'division', 'position', 'period']);
                $this->table(
                    ['Name', 'Club', 'Division', 'Position', 'Period'],
                    $sample->map(fn($r) => [
                        $r->name,
                        $r->club,
                        $r->division,
                        $r->position,
                        $r->period,
                    ])->toArray()
                );
            }
            $this->newLine();
        }

        if ($type === 'matches' || $type === 'all') {
            $query = \App\Models\Scraper\ScrapedMatch::where('is_synced', false);
            if ($runId) {
                $query->where('scraper_run_id', $runId);
            }
            $count = $query->count();

            $this->info("Would sync {$count} matches");

            if ($count > 0) {
                $sample = $query->limit(5)->get(['player1_name', 'player2_name', 'score', 'played_at']);
                $this->table(
                    ['Player 1', 'Player 2', 'Score', 'Date'],
                    $sample->map(fn($m) => [
                        $m->player1_name,
                        $m->player2_name,
                        $m->score,
                        $m->played_at,
                    ])->toArray()
                );
            }
            $this->newLine();
        }

        if ($type === 'live_center' || $type === 'all') {
            $query = \App\Models\Scraper\LiveMatchGame::where('is_synced', false)
                ->where('game_type', 'singles');
            if ($runId) {
                $query->whereHas('detail', fn($q) => $q->where('scraper_run_id', $runId));
            }
            $count = $query->count();

            $this->info("Would sync {$count} live center games");

            if ($count > 0) {
                $sample = $query->limit(5)->get(['player1_name', 'player2_name', 'player1_sets', 'player2_sets']);
                $this->table(
                    ['Player 1', 'Player 2', 'Sets'],
                    $sample->map(fn($g) => [
                        $g->player1_name,
                        $g->player2_name,
                        ($g->player1_sets ?? 0) . '-' . ($g->player2_sets ?? 0),
                    ])->toArray()
                );
            }
            $this->newLine();
        }

        $this->warn('This was a dry run. Use without --dry-run to actually sync the data.');
        return self::SUCCESS;
    }
}
