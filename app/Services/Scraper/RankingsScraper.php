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
        $gender = $this->getParameter('gender', 'm');
        // Normalize gender to Python script format: m/k
        $gender = match ($gender) {
            'male', 'man', 'men' => 'm',
            'female', 'woman', 'women' => 'k',
            default => $gender,
        };

        // Store options for helper methods
        $this->options = [
            'year' => $year,
            'month' => $month,
            'gender' => $gender,
            'limit_players' => $this->getParameter('limit_players'),
            'concurrency' => $this->getParameter('concurrency') ?? config('scraper.python.concurrency', 1),
            'popup_delay' => (float) config('scraper.python.popup_delay', 3.0),
        ];

        $this->info("Starting Python Playwright rankings scraper for {$year}-{$month}, gender: {$gender}, concurrency: {$this->options['concurrency']}, popup delay: {$this->options['popup_delay']}s");

        // Call Python scraper script. Player data is saved as it streams in,
        // so a failed run still keeps what it managed to fetch.
        $result = $this->executePythonScraper($year, $month, $gender, $this->options['limit_players'], (int) $this->options['concurrency']);

        $data = $result['data'];
        $this->run->updateStepData('coverage', [
            'players_discovered' => $data['players_discovered'] ?? null,
            'players_processed' => $data['players_processed'] ?? null,
            'players_failed' => $data['players_failed'] ?? null,
            'pages_failed' => $data['pages_failed'] ?? null,
            'coverage' => $data['coverage'] ?? null,
        ]);

        // Player-level and page-level failures count against the run even when
        // it succeeds overall, so a "completed" run with failures is visible.
        $failedItems = (int) ($data['players_failed'] ?? 0) + (int) ($data['pages_failed'] ?? 0);
        if ($failedItems > 0) {
            $this->run->incrementFailed($failedItems);
        }

        if (!$result['success']) {
            // The script already saved whatever it fetched; the summary explains
            // why coverage was insufficient (or what aborted the run).
            $reasons = array_map(fn ($e) => $e['error'] ?? json_encode($e), $result['errors']);
            throw new \Exception("Python scraper failed: " . implode(' | ', array_slice($reasons, -3)));
        }

        $coverage = isset($data['coverage']) ? round($data['coverage'] * 100, 1) . '%' : 'n/a';
        $this->info("Python scraper completed: {$data['players_processed']}/{$data['players_discovered']} players ({$coverage} coverage), {$data['rankings_count']} rankings, {$data['matches_count']} matches");

        // Rows were already saved per player as they streamed in (see
        // executePythonScraper); the summary only carries counts. Saving the
        // summary lists here as well used to double every staging row.

        // Log any partial failures (already counted in items_failed above)
        foreach ($result['errors'] as $error) {
            if (isset($error['player'])) {
                $this->warning("Error processing player {$error['player']}: {$error['error']}");
            } elseif (isset($error['page_offset'])) {
                $this->warning("Page from={$error['page_offset']} could not be scraped: {$error['error']}");
            } else {
                $this->warning($error['error'] ?? json_encode($error));
            }
        }

        $this->info("Rankings scraper completed: {$data['players_processed']} players processed, {$data['rankings_count']} rankings saved, {$data['matches_count']} matches saved");
    }

    /**
     * Execute Python scraper script
     */
    protected function executePythonScraper(string $year, string $month, string $gender, ?int $limitPlayers, int $concurrency = 10): array
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

        // Resume: players already saved for this month/gender by an earlier
        // (failed) run are skipped instead of scraped again.
        $skipFile = null;
        if (config('scraper.python.resume', true)) {
            $alreadyScraped = $this->alreadyScrapedPlayerIds($year, $month, $gender);
            if ($alreadyScraped->isNotEmpty()) {
                $skipFile = tempnam(sys_get_temp_dir(), 'scraper-skip-');
                file_put_contents($skipFile, $alreadyScraped->implode("\n") . "\n");
                $arguments[] = '--skip-file';
                $arguments[] = $skipFile;
                $this->info("Resuming: {$alreadyScraped->count()} players already scraped for {$year}-{$month} ({$gender}) will be skipped");
            }
        }

        $arguments[] = '--concurrency';
        $arguments[] = (string) $concurrency;
        $arguments[] = '--delay';
        $arguments[] = (string) $this->options['popup_delay'];

        // The Cloudflare clearance (cookie + the user-agent it was issued for)
        // overrides the configured user-agent; without it the ranking list 403s.
        $clearance = app(CloudflareClearanceService::class);
        if ($clearance->enabled()) {
            $this->info("Obtaining Cloudflare clearance for profixio...");
        }

        $env = array_merge(getenv(), [
            'PUPPETEER_EXECUTABLE_PATH' => config('scraper.browser.chrome_path', '/usr/bin/chromium'),
            'SCRAPER_USER_AGENT' => config('scraper.browser.user_agent'),
        ], $clearance->env());
        $process = new Process($arguments, null, $env);
        $process->setTimeout(null); // No timeout — rankings scrape can take many hours

        $this->info("Executing Python script: " . $process->getCommandLine());

        $stdoutBuffer = '';
        $finalResult  = null;

        $process->start(function ($type, $buffer) use (&$stdoutBuffer, &$finalResult) {
            if ($type === Process::ERR) {
                // stderr = Python log lines
                foreach (explode("\n", trim($buffer)) as $line) {
                    if (!empty($line)) {
                        $this->info("Python: {$line}");
                    }
                }
                return;
            }

            // stdout = NDJSON stream — accumulate and process complete lines
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

                if ($decoded['type'] === 'clearance') {
                    // The scraper had to pass Cloudflare's challenge again;
                    // cache the new cookie for the other scrapers.
                    app(CloudflareClearanceService::class)->store($decoded);
                    $this->info("Cloudflare clearance renewed by the scraper ({$decoded['cleared_in']}s)");
                } elseif ($decoded['type'] === 'player') {
                    // Save this player's data immediately — safe even on Ctrl+C
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

        if ($skipFile) {
            @unlink($skipFile);
        }

        // The script exits non-zero when coverage is insufficient but still
        // prints its summary line; prefer that over a raw process dump.
        if ($finalResult === null) {
            if (!$process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                throw new \Exception(
                    "Python scraper process failed:\n" .
                    "Exit Code: {$process->getExitCode()}\n" .
                    "Error Output: " . substr($errorOutput, -4000)
                );
            }
            throw new \Exception("Python script produced no summary line");
        }

        return $finalResult;
    }

    /**
     * profixio player ids that already have a ranking row for this month and
     * gender — saved by an earlier run (each player is written as soon as its
     * popup is scraped, so a run that died half-way left the first half in).
     */
    protected function alreadyScrapedPlayerIds(string $year, string $month, string $gender): \Illuminate\Support\Collection
    {
        $start = sprintf('%04d-%02d-01', (int) $year, (int) $month);
        $end = date('Y-m-t', strtotime($start));

        return DB::table('scraped_rankings')
            ->where('gender', $gender === 'm' ? 'male' : 'female')
            ->whereBetween('ranking_date', [$start, $end])
            ->whereNotNull('profixio_player_id')
            ->distinct()
            ->pluck('profixio_player_id');
    }

    /**
     * Save rankings to database
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
                // Legacy fields for backward compatibility
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
     * Save matches to database
     */
    protected function saveMatchesToDatabase(array $matches): void
    {
        $rows = [];
        $now = now();

        $seen = [];
        foreach ($matches as $match) {
            // Deduplicate by player + opponent + date — skip if already queued this run
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
                // Legacy fields for backward compatibility
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
