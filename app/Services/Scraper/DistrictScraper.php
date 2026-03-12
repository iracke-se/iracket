<?php

namespace App\Services\Scraper;

use App\Models\District;
use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class DistrictScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_DISTRICTS;
    }

    protected function execute(): void
    {
        $gender         = $this->getParameter('gender', 'both');
        $limitDistricts = $this->getParameter('limit_districts');
        $limitPlayers   = $this->getParameter('limit_players');

        $this->info("Starting district scraper (gender={$gender})");

        $result = $this->executePythonScraper($gender, $limitDistricts, $limitPlayers);

        if (!$result['success']) {
            throw new \Exception('Python district scraper failed: ' . json_encode($result['errors']));
        }

        $data = $result['data'];
        $this->info(
            "Python scraper completed: {$data['districts_processed']} districts, " .
            "{$data['total_players']} total players"
        );

        $this->saveDistrictPlayers($data['districts']);

        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->warning('District scraper error: ' . json_encode($error));
                $this->run->incrementFailed();
            }
        }

        $this->info("District scraper finished. Items scraped: {$this->run->items_scraped}");
    }

    protected function executePythonScraper(
        string $gender,
        ?int $limitDistricts,
        ?int $limitPlayers
    ): array {
        $pythonBinary = config('scraper.python.binary', 'python3');
        $scriptPath   = base_path('scripts/scraper/district_scraper.py');

        if (!file_exists($scriptPath)) {
            throw new \Exception("Python district scraper script not found at: {$scriptPath}");
        }

        $arguments = [$pythonBinary, $scriptPath, '--gender', $gender];

        if ($limitDistricts) {
            $arguments[] = '--limit-districts';
            $arguments[] = (string) $limitDistricts;
        }

        if ($limitPlayers) {
            $arguments[] = '--limit-players';
            $arguments[] = (string) $limitPlayers;
        }

        $timeout = config('scraper.python.timeout', 3600);
        $env     = array_merge(getenv(), [
            'PUPPETEER_EXECUTABLE_PATH' => config('scraper.browser.chrome_path', '/usr/bin/chromium'),
        ]);

        $process = new Process($arguments, null, $env);
        $process->setTimeout($timeout);

        $this->info('Executing: ' . $process->getCommandLine());

        try {
            $process->mustRun(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    foreach (explode("\n", trim($buffer)) as $line) {
                        if (!empty($line)) {
                            $this->info("Python: {$line}");
                        }
                    }
                }
            });

            $output = $process->getOutput();

            if (empty($output)) {
                throw new \Exception('Python script produced no output');
            }

            $result = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception(
                    'Failed to parse Python output: ' . json_last_error_msg() .
                    ' | First 200 chars: ' . substr($output, 0, 200)
                );
            }

            return $result;

        } catch (ProcessFailedException $e) {
            throw new \Exception(
                "Python district scraper process failed:\n" .
                "Exit Code: {$e->getProcess()->getExitCode()}\n" .
                "Error Output: {$e->getProcess()->getErrorOutput()}\n" .
                "Standard Output: {$e->getProcess()->getOutput()}"
            );
        }
    }

    protected function saveDistrictPlayers(array $districts): void
    {
        $now = now();

        foreach ($districts as $districtData) {
            // Upsert district record
            District::updateOrCreate(
                ['profixio_id' => (int) $districtData['profixio_id']],
                ['name' => $districtData['name']]
            );

            $gender     = $districtData['gender'];
            $districtId = (int) $districtData['profixio_id'];
            $players    = $districtData['players'] ?? [];

            if (empty($players)) {
                continue;
            }

            // Layer 2 dedup: delete unsynced records without profixio_player_id
            // for this district+gender so they don't accumulate across runs.
            DB::table('scraped_district_players')
                ->where('profixio_district_id', $districtId)
                ->where('gender', $gender)
                ->whereNull('profixio_player_id')
                ->where('is_synced', false)
                ->delete();

            // Build rows
            $rows = [];
            foreach ($players as $player) {
                $rows[] = [
                    'scraper_run_id'       => $this->run->id,
                    'profixio_district_id' => $districtId,
                    'district_name'        => $districtData['name'],
                    'gender'               => $gender,
                    'profixio_player_id'   => $player['profixio_player_id'] ?? null,
                    'surname'              => $player['surname'],
                    'first_name'           => $player['first_name'],
                    'birth_year'           => $player['birth_year'] ?? null,
                    'club_name'            => $player['club'] ?? null,
                    'position'             => $player['position'] ?? 0,
                    'points'               => $player['points'] ?? 0,
                    'is_synced'            => false,
                    'synced_user_id'       => null,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ];
            }

            // Layer 1 dedup: upsert on (profixio_district_id, gender, profixio_player_id).
            // On conflict: refresh position/points/club and reset is_synced so sync re-runs.
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('scraped_district_players')->upsert(
                    $chunk,
                    ['profixio_district_id', 'gender', 'profixio_player_id'],
                    ['scraper_run_id', 'district_name', 'club_name', 'position', 'points', 'is_synced', 'updated_at']
                );
            }

            $this->run->incrementScraped(count($rows));
        }

        $this->info("Saved district player records. Total scraped: {$this->run->fresh()->items_scraped}");
    }
}
