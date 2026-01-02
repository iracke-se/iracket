<?php

namespace App\Console\Commands;

use App\Models\ClubMonthlyRanking;
use App\Models\GameMatch;
use App\Models\MonthlyRanking;
use App\Services\BubblerService;
use App\Services\ClubRankingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RecalculateRankingsCommand extends Command
{
    protected $signature = 'fx:rankings
                            {--year=2025 : The year to recalculate rankings for}
                            {--month= : Optional: Specific month to recalculate (1-12)}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Recalculate monthly rankings for all months with matches';

    protected BubblerService $bubblerService;
    protected ClubRankingService $clubRankingService;

    public function __construct(BubblerService $bubblerService, ClubRankingService $clubRankingService)
    {
        parent::__construct();
        $this->bubblerService = $bubblerService;
        $this->clubRankingService = $clubRankingService;
    }

    public function handle(): int
    {
        $year = (int) $this->option('year');
        $specificMonth = $this->option('month') ? (int) $this->option('month') : null;
        $dryRun = $this->option('dry-run');

        $this->displayHeader($year, $specificMonth, $dryRun);

        // Get months that have matches
        $monthsWithMatches = $this->getMonthsWithMatches($year, $specificMonth);

        if ($monthsWithMatches->isEmpty()) {
            $this->warn("No matches found for year {$year}" . ($specificMonth ? " month {$specificMonth}" : ''));
            return self::SUCCESS;
        }

        $this->displayMatchSummary($monthsWithMatches);

        if ($dryRun) {
            $this->info("\n🔍 DRY RUN MODE - No changes will be made");
            return self::SUCCESS;
        }

        if (!$this->confirm('Continue with ranking recalculation?', true)) {
            $this->warn('Operation cancelled');
            return self::SUCCESS;
        }

        $totalStats = [
            'months_processed' => 0,
            'matches_processed' => 0,
            'player_rankings_created' => 0,
            'club_rankings_created' => 0,
        ];

        foreach ($monthsWithMatches as $period) {
            $stats = $this->processMonth($period->year, $period->month, $period->match_count);
            $totalStats['months_processed']++;
            $totalStats['matches_processed'] += $stats['matches_processed'];
            $totalStats['player_rankings_created'] += $stats['player_rankings'];
            $totalStats['club_rankings_created'] += $stats['club_rankings'];
        }

        $this->displaySummary($totalStats);

        return self::SUCCESS;
    }

    protected function getMonthsWithMatches(int $year, ?int $specificMonth)
    {
        $query = GameMatch::selectRaw('YEAR(played_at) as year, MONTH(played_at) as month, COUNT(*) as match_count')
            ->where('source', 'scraped')
            ->whereNotNull('winner_id')
            ->whereYear('played_at', $year)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month');

        if ($specificMonth) {
            $query->whereMonth('played_at', $specificMonth);
        }

        return $query->get();
    }

    protected function processMonth(int $year, int $month, int $matchCount): array
    {
        $periodStr = Carbon::create($year, $month)->format('F Y');
        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("📅 Processing {$periodStr} ({$matchCount} matches)");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $stats = [
            'matches_processed' => 0,
            'player_rankings' => 0,
            'club_rankings' => 0,
        ];

        // Step 1: Clear existing rankings
        $this->info('⏳ Clearing existing rankings...');
        $deleted = MonthlyRanking::where('year', $year)
            ->where('month', $month)
            ->delete();
        $this->line("  ✓ Cleared {$deleted} player rankings");

        $deletedClub = ClubMonthlyRanking::where('year', $year)
            ->where('month', $month)
            ->delete();
        $this->line("  ✓ Cleared {$deletedClub} club rankings");

        // Step 2: Calculate Bubbler points
        $this->info('⏳ Calculating Bubbler points...');
        $period = Carbon::create($year, $month);
        $result = $this->bubblerService->calculateMatchPoints($period);
        $stats['matches_processed'] = $result['matches_processed'];
        $this->line("  ✓ Processed {$result['matches_processed']} matches");
        $this->line("  ✓ Updated {$result['rankings_updated']} player rankings");

        // Step 3: Assign player ranks
        $this->info('⏳ Assigning player ranks...');
        $result = $this->bubblerService->assignPlayerRanks($year, $month);
        $stats['player_rankings'] = $result['male_players_ranked'] + $result['female_players_ranked'];
        $this->line("  ✓ Male: {$result['male_players_ranked']} players");
        $this->line("  ✓ Female: {$result['female_players_ranked']} players");
        if ($result['total_ties'] > 0) {
            $this->line("  ✓ Ties: {$result['total_ties']} players with same points");
        }

        // Step 4: Aggregate club rankings
        $this->info('⏳ Aggregating club rankings...');
        $period = Carbon::create($year, $month);
        $result = $this->clubRankingService->aggregateClubRankings($period);
        $stats['club_rankings'] = $result['total_processed'] ?? 0;
        $this->line("  ✓ Processed {$stats['club_rankings']} clubs");

        $this->info("✓ {$periodStr} completed");

        return $stats;
    }

    protected function displayHeader(int $year, ?int $specificMonth, bool $dryRun): void
    {
        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════╗");
        $this->info("║       FX RANKINGS - RECALCULATE MONTHLY RANKINGS       ║");
        $this->info("╚════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->line("  Year: <fg=cyan>{$year}</>");
        $this->line("  Month: <fg=cyan>" . ($specificMonth ? Carbon::create(null, $specificMonth)->format('F') : 'All months with matches') . "</>");
        $this->line("  Mode: <fg=" . ($dryRun ? 'yellow>DRY RUN' : 'green>LIVE') . "</>");
        $this->newLine();
    }

    protected function displayMatchSummary($monthsWithMatches): void
    {
        $this->info("Months with matches:");
        $this->newLine();

        $totalMatches = 0;
        foreach ($monthsWithMatches as $period) {
            $periodStr = Carbon::create($period->year, $period->month)->format('F Y');
            $this->line("  • {$periodStr}: <fg=cyan>{$period->match_count}</> matches");
            $totalMatches += $period->match_count;
        }

        $this->newLine();
        $this->line("  Total: <fg=cyan>{$totalMatches}</> matches across <fg=cyan>{$monthsWithMatches->count()}</> months");
        $this->newLine();
    }

    protected function displaySummary(array $stats): void
    {
        $this->newLine();
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->info("✓ RANKING RECALCULATION COMPLETED");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Months processed', $stats['months_processed']],
                ['Matches processed', $stats['matches_processed']],
                ['Player rankings created', $stats['player_rankings_created']],
                ['Club rankings created', $stats['club_rankings_created']],
            ]
        );

        $this->newLine();
        $this->info("Rankings have been recalculated successfully!");
        $this->line("View rankings at: <fg=cyan>https://dev.iracket.se/bubbler</>");
        $this->newLine();
    }
}
