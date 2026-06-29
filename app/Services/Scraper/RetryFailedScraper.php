<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Recovers individual players that previously FAILED during a rankings scrape
 * (popup timed out) for a single year/month/gender.
 *
 * This is a SEPARATE service — it does not touch RankingsScraper or its Python
 * script. It drives scripts/scraper/rankings_retry_scraper.py, which loads only
 * the pages containing the target players and waits patiently (60s) for each
 * popup. Recovered rows land in the SAME scraped_rankings / scraped_matches
 * tables (is_synced = false) so the normal SyncService promotes them.
 *
 * Parameters (passed to scrape()):
 *   year    string   e.g. "2023"
 *   month   string   e.g. "06"
 *   gender  string   "m" | "k" | "male" | "female"
 *   targets array    [ ['name' => ..., 'profixio_id' => ..., 'est_position' => int|null], ... ]
 */
class RetryFailedScraper extends BaseScraperService
{
    protected array $options = [];

    public function getType(): string
    {
        return 'retry_failed';
    }

    protected function execute(): void
    {
        $year = (string) ($this->getParameter('year') ?? date('Y'));
        $month = str_pad((string) ($this->getParameter('month') ?? date('m')), 2, '0', STR_PAD_LEFT);
        $gender = $this->getParameter('gender', 'm');
        $gender = match ($gender) {
            'male', 'man', 'men' => 'm',
            'female', 'woman', 'women' => 'k',
            default => $gender,
        };

        $targets = $this->getParameter('targets', []);
        if (empty($targets)) {
            $this->info('No targets supplied — nothing to retry.');
            return;
        }

        $this->options = ['year' => $year, 'month' => $month, 'gender' => $gender];

        $this->info("Retry scraper: {$year}-{$month} gender={$gender}, " . count($targets) . ' player(s)');

        $result = $this->executePythonRetry($year, $month, $gender, $targets);

        if (!$result['success']) {
            throw new \Exception('Retry scraper failed: ' . json_encode($result['errors']));
        }

        $data = $result['data'];
        $recovered = $data['players_processed'] ?? 0;
        $total = $data['targets_total'] ?? count($targets);
        $this->info("Recovered {$recovered}/{$total} players, {$data['rankings_count']} rankings, {$data['matches_count']} matches");

        if (!empty($data['not_found'])) {
            $this->warning('Not found on any page (listing changed): ' . implode(', ', $data['not_found']));
        }

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->warning("Still failed: {$error['player']}: {$error['error']}");
                $this->run->incrementFailed();
            }
        }
    }

    /**
     * Write the target list to a temp JSON file and drive the Python retry script,
     * saving each player's data as its NDJSON line arrives (same protocol as the
     * main rankings scraper).
     */
    protected function executePythonRetry(string $year, string $month, string $gender, array $targets): array
    {
        $pythonBinary = config('scraper.python.binary', 'python3');
        $scriptPath = base_path('scripts/scraper/rankings_retry_scraper.py');

        if (!file_exists($scriptPath)) {
            throw new \Exception("Retry scraper script not found at: {$scriptPath}");
        }

        $targetsFile = tempnam(sys_get_temp_dir(), 'retry_targets_') . '.json';
        file_put_contents($targetsFile, json_encode(array_values($targets)));

        try {
            $arguments = [
                $pythonBinary,
                $scriptPath,
                '--year', $year,
                '--month', $month,
                '--gender', $gender,
                '--targets-file', $targetsFile,
            ];

            $env = array_merge(getenv(), [
                'PUPPETEER_EXECUTABLE_PATH' => config('scraper.browser.chrome_path', '/usr/bin/chromium'),
            ]);
            $process = new Process($arguments, null, $env);
            $process->setTimeout(null);

            $this->info('Executing: ' . $process->getCommandLine());

            $stdoutBuffer = '';
            $finalResult = null;

            $process->start(function ($type, $buffer) use (&$stdoutBuffer, &$finalResult) {
                if ($type === Process::ERR) {
                    foreach (explode("\n", trim($buffer)) as $line) {
                        if (!empty($line)) {
                            $this->info("Python: {$line}");
                        }
                    }
                    return;
                }

                $stdoutBuffer .= $buffer;
                while (($pos = strpos($stdoutBuffer, "\n")) !== false) {
                    $line = trim(substr($stdoutBuffer, 0, $pos));
                    $stdoutBuffer = substr($stdoutBuffer, $pos + 1);
                    if (empty($line)) {
                        continue;
                    }

                    $decoded = json_decode($line, true);
                    if (!$decoded || !isset($decoded['type'])) {
                        continue;
                    }

                    if ($decoded['type'] === 'player') {
                        if (!empty($decoded['rankings'])) {
                            $this->saveRankingsToDatabase($decoded['rankings']);
                        }
                        if (!empty($decoded['matches'])) {
                            $this->saveMatchesToDatabase($decoded['matches']);
                        }
                    } elseif ($decoded['type'] === 'summary') {
                        $finalResult = $decoded;
                    }
                }
            });

            $process->wait();

            if (!$process->isSuccessful()) {
                throw new \Exception(
                    "Retry scraper process failed:\n" .
                    "Exit Code: {$process->getExitCode()}\n" .
                    "Error Output: {$process->getErrorOutput()}\n" .
                    "Standard Output: {$process->getOutput()}"
                );
            }

            if ($finalResult === null) {
                throw new \Exception('Retry script produced no summary line');
            }

            return $finalResult;
        } finally {
            @unlink($targetsFile);
        }
    }

    /**
     * Identical shape to RankingsScraper::saveRankingsToDatabase (duplicated on
     * purpose so the original service stays untouched).
     */
    protected function saveRankingsToDatabase(array $rankings): void
    {
        $rows = [];
        $now = now();
        $gender = $this->options['gender'] === 'm' ? 'male' : 'female';

        foreach ($rankings as $ranking) {
            $rows[] = [
                'scraper_run_id' => $this->run->id,
                'profixio_player_id' => $ranking['profixio_player_id'],
                'ranking_date' => $ranking['ranking_date'],
                'points' => $ranking['points'],
                'position' => $ranking['position'],
                'points_diff' => $ranking['points_diff'],
                'rmld_id' => $ranking['rmld_id'],
                'is_synced' => false,
                'period' => date('Y-m', strtotime($ranking['ranking_date'])),
                'division' => '',
                'gender' => $gender,
                'position_change' => '',
                'name' => $ranking['player_name'] ?? '',
                'born' => $ranking['born'] ?? '',
                'club' => $ranking['club'] ?? '',
                'points_change' => $ranking['points_diff'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('scraped_rankings')->insert($chunk);
        }

        $this->run->incrementScraped(count($rows));
    }

    /**
     * Identical shape to RankingsScraper::saveMatchesToDatabase.
     */
    protected function saveMatchesToDatabase(array $matches): void
    {
        $rows = [];
        $now = now();
        $seen = [];

        foreach ($matches as $match) {
            $key = $match['profixio_player_id'] . '|' . $match['opponent_name'] . '|' . $match['match_date'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $rows[] = [
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
                'source' => 'rankings_popup_python',
                'period' => date('Y-m', strtotime($match['match_date'])),
                'division' => '',
                'series_name' => '',
                'team1_name' => '',
                'team2_name' => '',
                'player1_name' => $match['player_name'],
                'player2_name' => $match['opponent_name'],
                'score' => '',
                'sets' => null,
                'played_at' => \Carbon\Carbon::parse($match['match_date'])->format('Y-m-d'),
                'winner' => $match['result'] === 'W' ? $match['player_name'] : $match['opponent_name'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('scraped_matches')->insertOrIgnore($chunk);
        }

        $this->run->incrementScraped(count($rows));
    }
}
