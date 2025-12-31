<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScraperRun;
use Spatie\Browsershot\Browsershot;

class PlayerListScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_PLAYERS;
    }

    protected function execute(): void
    {
        $periodFilter = $this->getParameter('period');
        $direction = $this->getParameter('direction', 'gte');

        $this->info("Starting player list scrape");
        $this->info("Period filter: " . ($periodFilter ?? 'NONE'));
        $this->info("Direction: " . $direction);

        // Navigate to SBTF portal and get players in a single browser session
        // We'll do everything in one evaluate call since we can't persist sessions between Browsershot instances
        $loginUrl = 'https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT';

        $browser = Browsershot::url($loginUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle()
            ->noSandbox()
            ->addChromiumArguments(['--disable-web-security', '--disable-features=IsolateOrigins,site-per-process']);

        if (config('scraper.browser.chrome_path')) {
            $browser->setChromePath(config('scraper.browser.chrome_path'));
        }

        // Use a complex JavaScript that fetches player list page and extracts data
        // Since we can't navigate within Browsershot, we'll use fetch() with credentials
        $initJs = <<<JS
        (async function() {
            try {
                // Fetch the player list page (cookies will be sent automatically)
                const response = await fetch('https://www.profixio.com/fx/lisens/public_oversikt.php', {
                    credentials: 'include'
                });
                const html = await response.text();

                // Parse the HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get periods
                var periods = [];
                var periodsSelect = doc.getElementById('periode');
                if (periodsSelect) {
                    for (let i = 0; i < periodsSelect.options.length; i++) {
                        periods.push({
                            value: periodsSelect.options[i].value,
                            text: periodsSelect.options[i].innerHTML.trim()
                        });
                    }
                }

                // Get clubs
                var clubs = [];
                var clubsSelect = doc.getElementById('klubbid');
                if (clubsSelect) {
                    for (let i = 0; i < clubsSelect.options.length; i++) {
                        clubs.push({
                            value: clubsSelect.options[i].value,
                            text: clubsSelect.options[i].innerHTML.trim()
                        });
                    }
                }

                return JSON.stringify({
                    periods: periods,
                    clubs: clubs,
                    pageTitle: doc.title
                });
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $initJson = $this->withRetry(function () use ($browser, $initJs) {
            return $browser->evaluate($initJs);
        }, 'Fetch player list data');

        $initData = json_decode($initJson, true);

        if (isset($initData['error'])) {
            $this->error("Failed to fetch data: " . $initData['error']);
            return;
        }

        $this->info("Page title: " . ($initData['pageTitle'] ?? 'unknown'));

        $periods = $initData['periods'] ?? [];
        $clubs = $initData['clubs'] ?? [];

        // Filter out empty values
        $clubs = array_filter($clubs, fn($c) => !empty(trim($c['text'])));

        // Apply year filter FIRST (before limiting) so limits apply to filtered data
        if ($periodFilter) {
            $filterYear = (int)date('Y', strtotime($periodFilter));
            $this->info("Applying year filter: {$filterYear}");

            $periods = array_filter($periods, function($period) use ($filterYear) {
                $periodYear = $this->extractYearFromPeriod($period['text']);

                if (!$periodYear) {
                    return false; // Skip periods where year can't be extracted
                }

                // Keep only periods matching the filter year
                $matches = ($periodYear === $filterYear);

                if (!$matches) {
                    $this->info("⊘ Filtered out period {$period['text']} (year {$periodYear} != {$filterYear})");
                } else {
                    $this->info("✓ Keeping period {$period['text']} (year {$periodYear} matches {$filterYear})");
                }

                return $matches;
            });

            // Re-index array after filtering
            $periods = array_values($periods);
        }

        // Apply limits for testing (after year filtering)
        $limitPeriods = $this->getParameter('limit_periods');
        $limitClubs = $this->getParameter('limit_clubs');

        if ($limitPeriods && $limitPeriods > 0) {
            $periods = array_slice($periods, 0, $limitPeriods);
        }
        if ($limitClubs && $limitClubs > 0) {
            $clubs = array_slice(array_values($clubs), 0, $limitClubs);
        }

        $this->info("Found periods: " . count($periods) . ", clubs: " . count($clubs));

        // Log all available periods
        foreach ($periods as $idx => $p) {
            $this->info("Period {$idx}: {$p['text']} (value: {$p['value']})");
        }

        // Process clubs in parallel batches for better performance
        $batchSize = config('scraper.batch_size', 5); // Process 5 clubs at once by default

        // Process each period and club using fetch with POST
        foreach ($periods as $period) {
            if (!$this->shouldContinue()) {
                break;
            }

            // Period already filtered above, just log what we're processing
            $this->info("Processing period {$period['text']}");

            // Process clubs in batches
            $clubBatches = array_chunk($clubs, $batchSize);

            foreach ($clubBatches as $clubBatch) {
                if (!$this->shouldContinue()) {
                    break;
                }

                try {
                    $this->scrapePlayersForClubBatch(
                        $browser,
                        $period,
                        $clubBatch
                    );
                } catch (\Exception $e) {
                    $this->warning("Failed to scrape club batch", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->run->incrementFailed();
                }
            }
        }
    }

    protected function scrapePlayersForClub(
        Browsershot $browser,
        array $period,
        array $club
    ): void {
        // Fetch player data using POST with form data
        $periodValue = $period['value'];
        $clubValue = $club['value'];
        $clubName = $club['text'];
        $periodName = $period['text'];

        $fetchPlayersJs = <<<JS
        (async function() {
            try {
                const formData = new URLSearchParams();
                formData.append('periode', '{$periodValue}');
                formData.append('klubbid', '{$clubValue}');
                formData.append('kjonn', '');
                formData.append('klasse', '');
                formData.append('lisenstypeid', '');

                const response = await fetch('https://www.profixio.com/fx/lisens/public_oversikt.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData.toString(),
                    credentials: 'include'
                });

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get player data from table
                const rows = doc.querySelectorAll('.table-condensed tr');
                let players = [];

                for (let i = 0; i < rows.length; i++) {
                    const cells = rows[i].querySelectorAll('td');
                    if (cells.length > 0) {
                        players.push({
                            surname: cells[1]?.innerText.trim() || '',
                            firstName: cells[2]?.innerText.trim() || '',
                            sex: cells[3]?.innerText.trim() || '',
                            dateOfBirth: cells[4]?.innerText.trim() || '',
                            licenseType: cells[5]?.innerText.trim() || '',
                            playerClass: cells[6]?.innerText.trim() || ''
                        });
                    }
                }

                return JSON.stringify(players);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $playersJson = $this->withRetry(function () use ($browser, $fetchPlayersJs) {
            return $browser->evaluate($fetchPlayersJs);
        }, "Get players for {$clubName}");

        $players = json_decode($playersJson, true) ?? [];

        if (isset($players['error'])) {
            $this->warning("Error fetching players: " . $players['error']);
            return;
        }

        if (empty($players)) {
            $this->info("No players found for {$clubName}");
            return;
        }

        // Save players to database
        foreach ($players as $player) {
            if (empty(trim($player['surname'] ?? '')) && empty(trim($player['firstName'] ?? ''))) {
                continue;
            }

            ScrapedPlayer::create([
                'scraper_run_id' => $this->run->id,
                'period' => $periodName,
                'club_name' => trim($clubName),
                'surname' => trim($player['surname'] ?? ''),
                'first_name' => trim($player['firstName'] ?? ''),
                'sex' => trim($player['sex'] ?? ''),
                'date_of_birth' => trim($player['dateOfBirth'] ?? ''),
                'license_type' => trim($player['licenseType'] ?? ''),
                'player_class' => trim($player['playerClass'] ?? ''),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$periodName} - {$clubName}: " . count($players) . " players");
    }

    /**
     * Scrape players for multiple clubs in parallel
     */
    protected function scrapePlayersForClubBatch(
        Browsershot $browser,
        array $period,
        array $clubBatch
    ): void {
        $periodValue = $period['value'];
        $periodName = $period['text'];

        // Build JavaScript to fetch all clubs in parallel
        $clubFetches = [];
        foreach ($clubBatch as $index => $club) {
            $clubValue = $club['value'];
            $clubName = addslashes($club['text']);

            $clubFetches[] = <<<JS
            (async () => {
                try {
                    const formData = new URLSearchParams();
                    formData.append('periode', '{$periodValue}');
                    formData.append('klubbid', '{$clubValue}');
                    formData.append('kjonn', '');
                    formData.append('klasse', '');
                    formData.append('lisenstypeid', '');

                    const response = await fetch('https://www.profixio.com/fx/lisens/public_oversikt.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData.toString(),
                        credentials: 'include'
                    });

                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const rows = doc.querySelectorAll('.table-condensed tr');
                    let players = [];

                    for (let i = 0; i < rows.length; i++) {
                        const cells = rows[i].querySelectorAll('td');
                        if (cells.length > 0) {
                            players.push({
                                surname: cells[1]?.innerText.trim() || '',
                                firstName: cells[2]?.innerText.trim() || '',
                                sex: cells[3]?.innerText.trim() || '',
                                dateOfBirth: cells[4]?.innerText.trim() || '',
                                licenseType: cells[5]?.innerText.trim() || '',
                                playerClass: cells[6]?.innerText.trim() || ''
                            });
                        }
                    }

                    return {
                        clubName: '{$clubName}',
                        players: players,
                        success: true
                    };
                } catch (e) {
                    return {
                        clubName: '{$clubName}',
                        error: e.message,
                        success: false
                    };
                }
            })()
JS;
        }

        $batchJs = <<<JS
        (async function() {
            try {
                const results = await Promise.all([
                    {$this->joinJsArrayItems($clubFetches)}
                ]);
                return JSON.stringify(results);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $resultsJson = $this->withRetry(function () use ($browser, $batchJs) {
            return $browser->evaluate($batchJs);
        }, "Get players for batch of " . count($clubBatch) . " clubs");

        $results = json_decode($resultsJson, true) ?? [];

        if (isset($results['error'])) {
            $this->warning("Error fetching batch: " . $results['error']);
            return;
        }

        // Process results for each club
        foreach ($results as $result) {
            if (!$result['success']) {
                $this->warning("Failed to scrape {$result['clubName']}: " . ($result['error'] ?? 'unknown error'));
                $this->run->incrementFailed();
                continue;
            }

            $clubName = $result['clubName'];
            $players = $result['players'] ?? [];

            if (empty($players)) {
                $this->info("No players found for {$clubName}");
                continue;
            }

            // Save players to database
            foreach ($players as $player) {
                if (empty(trim($player['surname'] ?? '')) && empty(trim($player['firstName'] ?? ''))) {
                    continue;
                }

                ScrapedPlayer::create([
                    'scraper_run_id' => $this->run->id,
                    'period' => $periodName,
                    'club_name' => trim($clubName),
                    'surname' => trim($player['surname'] ?? ''),
                    'first_name' => trim($player['firstName'] ?? ''),
                    'sex' => trim($player['sex'] ?? ''),
                    'date_of_birth' => trim($player['dateOfBirth'] ?? ''),
                    'license_type' => trim($player['licenseType'] ?? ''),
                    'player_class' => trim($player['playerClass'] ?? ''),
                ]);

                $this->run->incrementScraped();
            }

            $this->info("Scraped {$periodName} - {$clubName}: " . count($players) . " players");
        }
    }

    /**
     * Helper to join JavaScript array items with commas
     */
    private function joinJsArrayItems(array $items): string
    {
        return implode(",\n                    ", $items);
    }

    /**
     * Extract year from period text like "Licens 2025-26" or "2024.01.01"
     */
    protected function extractYearFromPeriod(string $periodText): ?int
    {
        // Extract year from period text like "Licens 2025-26" (get first year)
        if (preg_match('/(\d{4})-\d{2}/', $periodText, $matches)) {
            return (int)$matches[1];
        }

        // Extract year from date like "2024.01.01"
        if (preg_match('/(\d{4})\.(\d{2})\.(\d{2})/', $periodText, $matches)) {
            return (int)$matches[1];
        }

        // Extract just year from text
        if (preg_match('/(\d{4})/', $periodText, $matches)) {
            return (int)$matches[1];
        }

        return null;
    }
}
