<?php

namespace App\Services\Scraper;

use App\Models\Scraper\LiveMatchDetail;
use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class LiveCenterDetailsScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_LIVE_CENTER;
    }

    protected function execute(): void
    {
        $date = $this->getParameter('date');
        $month = $this->getParameter('month');
        $year = $this->getParameter('year');
        $fromMatches = $this->getParameter('from_matches', false);
        $limitMatches = $this->getParameter('limit_matches');
        $skipPoints = $this->getParameter('skip_points', false);

        // If from_matches mode, get dates from existing matches table
        if ($fromMatches) {
            $this->info("Mode: Scraping from existing matches in database");
            $this->scrapeFromExistingMatches($month, $year, $limitMatches, $skipPoints);
            return;
        }

        if (!$date && !$month && !$year) {
            throw new \Exception("Date, month, or year parameter is required for Live Center scraper");
        }

        $label = $date ? "date: {$date}" : ($month ? "month: {$month}" : "year: {$year}");
        $this->info("Starting Live Center details scraper for {$label}");

        // Call Python scraper script
        $result = $this->executePythonScraper($date, $limitMatches, $skipPoints, $year, $month);

        if (!$result['success']) {
            throw new \Exception("Python Live Center scraper failed: " . json_encode($result['errors']));
        }

        $data = $result['data'];
        $this->info("Python scraper completed: {$data['team_matches_count']} team matches, {$data['games_count']} games, {$data['sets_count']} sets, {$data['points_count']} points");

        // Save results to database
        if (!empty($data['team_matches'])) {
            $this->saveTeamMatchesToDatabase($data['team_matches'], $date);
        }

        // Log any partial failures
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $errorMsg = $error['error'] ?? 'Unknown error';
                $context = $error['team_match'] ?? $error['game'] ?? '';
                $this->warning("Error: {$context} - {$errorMsg}");
                $this->run->incrementFailed();
            }
        }

        $this->info("Live Center scraper completed: {$data['team_matches_count']} team matches, {$data['games_count']} games, {$data['sets_count']} sets, {$data['points_count']} points saved");
    }

    /**
     * Scrape Live Center data for matches that already exist in the matches table
     */
    protected function scrapeFromExistingMatches(?string $month, ?string $year, ?int $limitMatches, bool $skipPoints): void
    {
        // Get distinct dates from matches table
        $query = DB::table('matches')->whereNotNull('played_at');

        if ($month) {
            $query->where('played_at', 'like', $month . '%');
        } elseif ($year) {
            $query->where('played_at', 'like', $year . '%');
        }

        $dates = $query->distinct()
            ->pluck('played_at')
            ->map(fn($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        if (empty($dates)) {
            $filter = $month ?: $year ?: 'all time';
            $this->info("No matches found in database for: {$filter}");
            return;
        }

        $this->info("Found " . count($dates) . " distinct dates with matches");

        $totalScraped = 0;
        $totalGames = 0;
        $totalSets = 0;
        $totalPoints = 0;

        foreach ($dates as $index => $date) {
            $this->info("--- Scraping date " . ($index + 1) . "/" . count($dates) . ": {$date} ---");

            // Call Python scraper for this specific date
            $result = $this->executePythonScraper($date, $limitMatches, $skipPoints, null, null);

            if (!$result['success']) {
                $this->warning("Failed to scrape date {$date}: " . json_encode($result['errors']));
                continue;
            }

            $data = $result['data'];
            $this->info("Found: {$data['team_matches_count']} team matches, {$data['games_count']} games");

            // Save results to database
            if (!empty($data['team_matches'])) {
                $this->saveTeamMatchesToDatabase($data['team_matches'], $date);
                $totalScraped += $data['team_matches_count'];
                $totalGames += $data['games_count'];
                $totalSets += $data['sets_count'];
                $totalPoints += $data['points_count'];
            }
        }

        $this->info("\n=== Scraping from existing matches completed ===");
        $this->info("Total: {$totalScraped} team matches, {$totalGames} games, {$totalSets} sets, {$totalPoints} points");
    }

    /**
     * Execute Python scraper script
     */
    protected function executePythonScraper(?string $date, ?int $limitMatches, bool $skipPoints, ?string $year = null, ?string $month = null): array
    {
        $pythonBinary = config('scraper.python.binary', 'python3');
        $scriptPath = base_path('scripts/scraper/livecenter_scraper.py');

        if (!file_exists($scriptPath)) {
            throw new \Exception("Python Live Center scraper script not found at: {$scriptPath}");
        }

        $arguments = [
            $pythonBinary,
            $scriptPath,
        ];

        if ($date) {
            $arguments[] = '--date';
            $arguments[] = $date;
        } elseif ($month) {
            $arguments[] = '--month';
            $arguments[] = $month;
        } elseif ($year) {
            $arguments[] = '--year';
            $arguments[] = $year;
        }

        if ($limitMatches) {
            $arguments[] = '--limit-matches';
            $arguments[] = (string) $limitMatches;
        }

        if ($skipPoints) {
            $arguments[] = '--skip-points';
        }

        $process = new Process($arguments);
        $process->setTimeout(null); // No timeout - allow unlimited execution time

        $this->info("Executing Python script: " . $process->getCommandLine());

        try {
            $process->mustRun(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    // Don't trim the buffer to preserve real-time output
                    $lines = explode("\n", $buffer);
                    foreach ($lines as $line) {
                        if (!empty(trim($line))) {
                            $this->info("Python: {$line}");
                        }
                    }
                }
            });

            $output = $process->getOutput();

            if (empty($output)) {
                throw new \Exception("Python script produced no output");
            }

            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Failed to parse Python script output as JSON: " . json_last_error_msg() . "\nOutput: " . substr($output, 0, 1000));
            }

            return $result;

        } catch (ProcessFailedException $e) {
            $errorOutput = $e->getProcess()->getErrorOutput();
            $standardOutput = $e->getProcess()->getOutput();

            throw new \Exception(
                "Python Live Center scraper process failed:\n" .
                "Exit Code: {$e->getProcess()->getExitCode()}\n" .
                "Error Output: {$errorOutput}\n" .
                "Standard Output: " . substr($standardOutput, 0, 1000)
            );
        }
    }

    /**
     * Save team matches and all nested data to database
     */
    protected function saveTeamMatchesToDatabase(array $teamMatches, ?string $date): void
    {
        $totalMatches = count($teamMatches);
        $dateLabel = $date ? "for date {$date}" : "from scraper data";
        $this->info("Saving {$totalMatches} team matches to database {$dateLabel}...");

        foreach ($teamMatches as $index => $teamMatch) {
            $matchNum = $index + 1;

            $this->info("[{$matchNum}/{$totalMatches}] Processing: {$teamMatch['team1_name']} vs {$teamMatch['team2_name']}...");

            // Use match-specific date if available, otherwise use parameter date
            $matchDate = $teamMatch['played_at'] ?? $teamMatch['date'] ?? $date;

            // Check for duplicate across ALL scraper runs (not just current run)
            $duplicateQuery = LiveMatchDetail::where('team1_name', $teamMatch['team1_name'])
                ->where('team2_name', $teamMatch['team2_name']);

            if ($matchDate) {
                $duplicateQuery->where('played_at', $matchDate);
            }

            $existing = $duplicateQuery->first();

            if ($existing) {
                $this->info("[{$matchNum}/{$totalMatches}] Skipped (already scraped in run #{$existing->scraper_run_id}): {$teamMatch['team1_name']} vs {$teamMatch['team2_name']}");
                continue;
            }

            // Insert team match detail
            $detailId = DB::table('live_match_details')->insertGetId([
                'scraper_run_id' => $this->run->id,
                'division' => $teamMatch['division'] ?? null,
                'team1_name' => $teamMatch['team1_name'],
                'team2_name' => $teamMatch['team2_name'],
                'team1_score' => $teamMatch['team1_score'] ?? null,
                'team2_score' => $teamMatch['team2_score'] ?? null,
                'played_at' => $matchDate,
                'profixio_match_id' => $teamMatch['profixio_match_id'] ?? null,
                'status' => $teamMatch['status'] ?? 'completed',
                'is_synced' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->run->incrementScraped();

            // Insert games for this team match
            $games = $teamMatch['games'] ?? [];
            foreach ($games as $game) {
                $gameId = DB::table('live_match_games')->insertGetId([
                    'live_match_detail_id' => $detailId,
                    'game_number' => $game['game_number'],
                    'game_type' => $game['game_type'] ?? 'singles',
                    'player1_name' => $game['player1_name'] ?? '',
                    'player2_name' => $game['player2_name'] ?? '',
                    'player1_partner_name' => $game['player1_partner_name'] ?? null,
                    'player2_partner_name' => $game['player2_partner_name'] ?? null,
                    'player1_sets' => $game['player1_sets'] ?? null,
                    'player2_sets' => $game['player2_sets'] ?? null,
                    'winner_name' => $game['winner_name'] ?? null,
                    'profixio_game_id' => $game['profixio_game_id'] ?? null,
                    'is_synced' => false,
                    'synced_match_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Insert sets for this game
                $sets = $game['sets'] ?? [];
                foreach ($sets as $set) {
                    $setId = DB::table('live_match_sets')->insertGetId([
                        'live_match_game_id' => $gameId,
                        'set_number' => $set['set_number'],
                        'player1_points' => $set['player1_points'],
                        'player2_points' => $set['player2_points'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Insert points for this set (batch insert for performance)
                    $points = $set['points'] ?? [];
                    if (!empty($points)) {
                        $pointRows = [];
                        foreach ($points as $point) {
                            $pointRows[] = [
                                'live_match_set_id' => $setId,
                                'point_number' => $point['point_number'],
                                'player1_points' => $point['player1_points'],
                                'player2_points' => $point['player2_points'],
                                'serve' => $point['serve'] ?? null,
                                'comment' => $point['comment'] ?? null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        // Batch insert points in chunks of 500
                        foreach (array_chunk($pointRows, 500) as $chunk) {
                            DB::table('live_match_points')->insert($chunk);
                        }
                    }
                }
            }

            $this->info("[{$matchNum}/{$totalMatches}] Saved: {$teamMatch['team1_name']} vs {$teamMatch['team2_name']} ({$teamMatch['team1_score']}-{$teamMatch['team2_score']}) with " . count($games) . " games");
        }
    }
}
