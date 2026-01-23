<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RankingsScraper extends BaseScraperService
{
    protected array $options = [];

    public function getType(): string
    {
        return ScraperRun::TYPE_RANKINGS;
    }

    protected function execute(): void
    {
        $year = $this->getParameter('year') ?? date('Y');
        $month = $this->getParameter('month') ?? date('m');
        $gender = $this->getParameter('gender', 'm'); // 'm' = men, 'k' = women

        // Store options for helper methods
        $this->options = [
            'year' => $year,
            'month' => $month,
            'gender' => $gender,
            'limit_players' => $this->getParameter('limit_players'),
        ];

        $this->info("Starting Python Playwright rankings scraper for {$year}-{$month}, gender: {$gender}");

        // Call Python scraper script
        $result = $this->executePythonScraper($year, $month, $gender, $this->options['limit_players']);

        if (!$result['success']) {
            throw new \Exception("Python scraper failed: " . json_encode($result['errors']));
        }

        // Parse and save results
        $data = $result['data'];
        $this->info("Python scraper completed: {$data['players_processed']} players, {$data['rankings_count']} rankings, {$data['matches_count']} matches");

        // Save rankings to database
        if (!empty($data['rankings'])) {
            $this->saveRankingsToDatabase($data['rankings']);
        }

        // Save matches to database
        if (!empty($data['matches'])) {
            $this->saveMatchesToDatabase($data['matches']);
        }

        // Log any partial failures
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->warning("Error processing player {$error['player']}: {$error['error']}");
                $this->run->incrementFailed();
            }
        }

        $this->info("Rankings scraper completed: {$data['players_processed']} players processed, {$data['rankings_count']} rankings saved, {$data['matches_count']} matches saved");
    }

    /**
     * Execute Python scraper script
     */
    protected function executePythonScraper(string $year, string $month, string $gender, ?int $limitPlayers): array
    {
        // Get Python binary path from config
        $pythonBinary = config('scraper.python.binary', 'python3');

        // Build script path
        $scriptPath = base_path('scripts/scraper/rankings_popup_scraper.py');

        if (!file_exists($scriptPath)) {
            throw new \Exception("Python scraper script not found at: {$scriptPath}");
        }

        // Build command arguments
        $arguments = [
            $pythonBinary,
            $scriptPath,
            '--year', $year,
            '--month', str_pad($month, 2, '0', STR_PAD_LEFT),
            '--gender', $gender,
        ];

        if ($limitPlayers) {
            $arguments[] = '--limit';
            $arguments[] = (string) $limitPlayers;
        }

        // Create process with timeout
        $timeout = config('scraper.python.timeout', 3600); // Default 1 hour
        $process = new Process($arguments);
        $process->setTimeout($timeout);

        $this->info("Executing Python script: " . $process->getCommandLine());

        try {
            $process->mustRun(function ($type, $buffer) {
                // Log stderr output (Python script logs go here)
                if ($type === Process::ERR) {
                    $lines = explode("\n", trim($buffer));
                    foreach ($lines as $line) {
                        if (!empty($line)) {
                            $this->info("Python: {$line}");
                        }
                    }
                }
            });

            // Parse JSON output from stdout
            $output = $process->getOutput();

            if (empty($output)) {
                throw new \Exception("Python script produced no output");
            }

            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Failed to parse Python script output as JSON: " . json_last_error_msg() . "\nOutput: " . $output);
            }

            return $result;

        } catch (ProcessFailedException $e) {
            $errorOutput = $e->getProcess()->getErrorOutput();
            $standardOutput = $e->getProcess()->getOutput();

            throw new \Exception(
                "Python scraper process failed:\n" .
                "Exit Code: {$e->getProcess()->getExitCode()}\n" .
                "Error Output: {$errorOutput}\n" .
                "Standard Output: {$standardOutput}"
            );
        }
    }

    /**
     * Save rankings to database
     */
    protected function saveRankingsToDatabase(array $rankings): void
    {
        foreach ($rankings as $ranking) {
            DB::table('scraped_rankings')->insert([
                'scraper_run_id' => $this->run->id,
                'profixio_player_id' => $ranking['profixio_player_id'],
                'ranking_date' => $ranking['ranking_date'],
                'points' => $ranking['points'],
                'position' => $ranking['position'],
                'points_diff' => $ranking['points_diff'],
                'rmld_id' => $ranking['rmld_id'],
                'is_synced' => false,
                // Legacy fields for backward compatibility
                'period' => $this->options['year'] . '-' . str_pad($this->options['month'], 2, '0', STR_PAD_LEFT),
                'division' => '',
                'gender' => $this->options['gender'] === 'm' ? 'male' : 'female',
                'position_change' => '',
                'name' => '',
                'born' => '',
                'club' => '',
                'points_change' => $ranking['points_diff'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->run->incrementScraped();
        }
    }

    /**
     * Save matches to database
     */
    protected function saveMatchesToDatabase(array $matches): void
    {
        foreach ($matches as $match) {
            DB::table('scraped_matches')->insert([
                'scraper_run_id' => $this->run->id,
                'profixio_player_id' => $match['profixio_player_id'],
                'player_name' => $match['player_name'],
                'opponent_name' => $match['opponent_name'],
                'result' => $match['result'],
                'opponent_points' => $match['opponent_points'],
                'match_points' => $match['match_points'],
                'match_date' => $match['match_date'],
                'scraped_month' => $match['scraped_month'],
                'is_synced' => false,
                // Legacy fields for backward compatibility
                'source' => 'rankings_popup_python',
                'period' => $this->options['year'] . '-' . str_pad($this->options['month'], 2, '0', STR_PAD_LEFT),
                'division' => '',
                'series_name' => '',
                'team1_name' => '',
                'team2_name' => '',
                'player1_name' => $match['player_name'],
                'player2_name' => $match['opponent_name'],
                'score' => '',
                'sets' => null,
                'played_at' => $match['match_date'],
                'winner' => $match['result'] === 'W' ? $match['player_name'] : $match['opponent_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->run->incrementScraped();
        }
    }
}
