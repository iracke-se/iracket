<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScraperRun;
use Spatie\Browsershot\Browsershot;

class LiveCenterScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_LIVE_CENTER;
    }

    protected function execute(): void
    {
        $take = $this->getParameter('take', 0);
        $skip = $this->getParameter('skip', 0);

        $this->info("Starting live center scrape");

        // Navigate to login page to establish session, then fetch Live Center
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

        // Fetch Live Center page to get dropdowns
        $initJs = <<<JS
        (async function() {
            try {
                const response = await fetch('https://www.profixio.com/fx/livecenter/', {
                    credentials: 'include'
                });
                const html = await response.text();

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get divisions from dropdown
                var divisions = [];
                var divisionsSelect = doc.getElementById('filter4_id');
                if (divisionsSelect) {
                    for (let i = 0; i < divisionsSelect.options.length; i++) {
                        divisions.push({
                            value: divisionsSelect.options[i].value,
                            text: divisionsSelect.options[i].innerHTML.trim()
                        });
                    }
                }

                // Get periods from dropdown
                var periods = [];
                var periodsSelect = doc.getElementById('filter1_id');
                if (periodsSelect) {
                    for (let i = 0; i < periodsSelect.options.length; i++) {
                        periods.push({
                            value: periodsSelect.options[i].value,
                            text: periodsSelect.options[i].innerHTML.trim()
                        });
                    }
                }

                return JSON.stringify({
                    divisions: divisions,
                    periods: periods,
                    pageTitle: doc.title
                });
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $initJson = $this->withRetry(function () use ($browser, $initJs) {
            return $browser->evaluate($initJs);
        }, 'Fetch live center page');

        $initData = json_decode($initJson, true);

        if (isset($initData['error'])) {
            $this->error("Failed to fetch data: " . $initData['error']);
            return;
        }

        $this->info("Page title: " . ($initData['pageTitle'] ?? 'unknown'));

        $divisions = $initData['divisions'] ?? [];
        $periods = $initData['periods'] ?? [];

        // Apply limits for testing - use "Alla" (all divisions) for better test coverage
        $limitDivisions = $this->getParameter('limit_divisions');
        if ($limitDivisions && $limitDivisions > 0) {
            // For testing, use "Alla" which has empty value but returns all matches
            $allaOption = array_filter($divisions, fn($d) => $d['text'] === 'Alla');
            if (!empty($allaOption)) {
                $divisions = array_slice(array_values($allaOption), 0, $limitDivisions);
            } else {
                // Fallback to first division with non-empty value
                $divisions = array_filter($divisions, fn($d) => !empty($d['value']));
                $divisions = array_slice(array_values($divisions), 0, $limitDivisions);
            }
        } else {
            // For full scrape, filter out empty values but keep "Alla"
            $divisions = array_filter($divisions, fn($d) => !empty($d['value']) || $d['text'] === 'Alla');

            // Apply skip/take for parallel processing
            if ($take > 0 || $skip > 0) {
                $divisions = array_slice($divisions, $skip, $take ?: null);
            }
        }

        $this->info("Processing divisions: " . count($divisions));

        if (empty($divisions)) {
            $this->warning("No divisions found to process");
            return;
        }

        // Apply limits for testing
        $limitPeriods = $this->getParameter('limit_periods');
        if ($limitPeriods && $limitPeriods > 0) {
            // When limiting for tests, prioritize dates from October 2024 which have many matches
            $periods202410 = array_filter($periods, fn($p) => str_contains($p['value'] ?? '', '2024-10'));
            if (!empty($periods202410)) {
                $periods = array_slice(array_values($periods202410), 0, $limitPeriods);
            } else {
                // Fall back to any 2024 date
                $periods2024 = array_filter($periods, fn($p) => str_contains($p['value'] ?? '', '2024'));
                if (!empty($periods2024)) {
                    $periods = array_slice(array_values($periods2024), 0, $limitPeriods);
                } else {
                    $periods = array_slice($periods, 0, $limitPeriods);
                }
            }
        }

        $this->info("Processing periods: " . count($periods));

        if (empty($periods)) {
            $this->warning("No periods found to process");
            return;
        }

        // Process each division/period - need to scrape in a single evaluate call
        // because each Browsershot evaluate creates a fresh page
        foreach ($divisions as $division) {
            if (!$this->shouldContinue()) {
                break;
            }

            foreach ($periods as $period) {
                if (!$this->shouldContinue()) {
                    break;
                }

                try {
                    $divisionValue = $division['value'];
                    $divisionName = $division['text'];
                    $periodValue = $period['value'];
                    $periodName = $period['text'];

                    // Call the AJAX endpoint directly to get matches
                    $scrapeJs = <<<JS
                    (async function() {
                        try {
                            // Call the callback.php API directly
                            const params = {
                                organisasjon: 'SBTF.SE.BT',
                                dato: '{$periodValue}',
                                turn_id: '{$divisionValue}',
                                match_id: 0,
                                refresh: 0,
                                selected_date: 1
                            };

                            // Format: metode=<method>&data=<URL encoded JSON>
                            const paramStr = 'metode=get_match_list&data=' + encodeURIComponent(JSON.stringify(params));

                            const response = await fetch('https://www.profixio.com/fx/livecenter/callback.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: paramStr,
                                credentials: 'include'
                            });

                            const jsonText = await response.text();
                            const json = JSON.parse(jsonText);

                            if (json.feilmelding && json.feilmelding !== '') {
                                return JSON.stringify({ error: json.feilmelding });
                            }

                            // The data contains HTML with matches
                            const html = json.data?.output || json.data || '';

                            // Parse the returned HTML
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');

                            const rows = doc.querySelectorAll('table tr');
                            let matches = [];

                            for (let i = 0; i < rows.length; i++) {
                                const cells = rows[i].querySelectorAll('td');
                                if (cells.length >= 2) {
                                    const teamsCell = cells[0]?.innerText.trim() || '';
                                    const scoreCell = cells[1]?.innerText.trim() || '';

                                    if (teamsCell.includes('Uppdaterad') || teamsCell.includes('Inga matcher')) {
                                        continue;
                                    }

                                    const teamParts = teamsCell.split(' - ');
                                    if (teamParts.length === 2) {
                                        matches.push({
                                            team1: teamParts[0].trim(),
                                            team2: teamParts[1].trim(),
                                            player1: teamParts[0].trim(),
                                            player2: teamParts[1].trim(),
                                            score: scoreCell,
                                            date: '{$periodValue}'
                                        });
                                    }
                                }
                            }

                            return JSON.stringify(matches);
                        } catch (e) {
                            return JSON.stringify({ error: e.message });
                        }
                    })();
                    JS;

                    $matchesJson = $this->withRetry(function () use ($browser, $scrapeJs) {
                        return $browser->evaluate($scrapeJs);
                    }, "Get matches for {$divisionName} - {$periodName}");

                    $matches = json_decode($matchesJson, true) ?? [];

                    if (isset($matches['error'])) {
                        $this->warning("Error fetching matches: " . $matches['error']);
                        continue;
                    }

                    if (empty($matches)) {
                        continue;
                    }

                    // Save matches to database
                    foreach ($matches as $match) {
                        if (empty(trim($match['player1'] ?? '')) || empty(trim($match['player2'] ?? ''))) {
                            continue;
                        }

                        ScrapedMatch::create([
                            'scraper_run_id' => $this->run->id,
                            'source' => 'live_center',
                            'period' => $periodName,
                            'division' => $divisionName,
                            'series_name' => null,
                            'team1_name' => trim($match['team1'] ?? ''),
                            'team2_name' => trim($match['team2'] ?? ''),
                            'player1_name' => trim($match['player1'] ?? ''),
                            'player2_name' => trim($match['player2'] ?? ''),
                            'score' => trim($match['score'] ?? ''),
                            'sets' => null,
                            'played_at' => trim($match['date'] ?? ''),
                            'winner' => $this->determineWinner($match),
                        ]);

                        $this->run->incrementScraped();
                    }

                    $this->info("Scraped {$divisionName} - {$periodName}: " . count($matches) . " matches");

                } catch (\Exception $e) {
                    $this->warning("Failed to scrape division/period", [
                        'division' => $division['text'],
                        'period' => $period['text'],
                        'error' => $e->getMessage(),
                    ]);
                    $this->run->incrementFailed();
                }
            }
        }
    }

    protected function scrapeMatchesForDivisionPeriod(
        Browsershot $browser,
        array $division,
        array $period
    ): void {
        $divisionName = $division['text'];
        $divisionValue = $division['value'];
        $periodValue = $period['value'];
        $periodName = $period['text'];

        // Use the AJAX function to load matches for this date and division
        // The Live Center uses get_match_list_by_obj() to dynamically load matches
        $fetchMatchesJs = <<<JS
        (async function() {
            try {
                // Set the division in the dropdown
                const divisionSelect = document.getElementById('filter4_id');
                if (divisionSelect) {
                    divisionSelect.value = '{$divisionValue}';
                }

                // Set the date in the dropdown
                const dateSelect = document.getElementById('filter1_id');
                if (dateSelect) {
                    dateSelect.value = '{$periodValue}';

                    // Call the AJAX function to load matches
                    if (typeof get_match_list_by_obj === 'function') {
                        get_match_list_by_obj('SBTF.SE.BT', dateSelect, 0, 1);
                    }
                }

                // Wait for AJAX to complete
                await new Promise(resolve => setTimeout(resolve, 2000));

                // Get match data from the matches div
                const matchesDiv = document.getElementById('matches');
                if (!matchesDiv) {
                    return JSON.stringify([]);
                }

                const rows = matchesDiv.querySelectorAll('table tr');
                let matches = [];

                for (let i = 0; i < rows.length; i++) {
                    const cells = rows[i].querySelectorAll('td');
                    // Match rows have: teams (td), score (td.resultat), empty (td)
                    if (cells.length >= 2) {
                        const teamsCell = cells[0]?.innerText.trim() || '';
                        const scoreCell = cells[1]?.innerText.trim() || '';

                        // Skip header/info rows
                        if (teamsCell.includes('Uppdaterad') || teamsCell.includes('Inga matcher')) {
                            continue;
                        }

                        // Parse teams from "Team1 - Team2" format
                        const teamParts = teamsCell.split(' - ');
                        if (teamParts.length === 2) {
                            matches.push({
                                team1: teamParts[0].trim(),
                                team2: teamParts[1].trim(),
                                player1: teamParts[0].trim(),
                                player2: teamParts[1].trim(),
                                score: scoreCell,
                                date: '{$periodValue}'
                            });
                        }
                    }
                }

                return JSON.stringify(matches);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $matchesJson = $this->withRetry(function () use ($browser, $fetchMatchesJs) {
            return $browser->evaluate($fetchMatchesJs);
        }, "Get matches for {$divisionName} - {$periodName}");

        $matches = json_decode($matchesJson, true) ?? [];

        if (isset($matches['error'])) {
            $this->warning("Error fetching matches: " . $matches['error']);
            return;
        }

        if (empty($matches)) {
            return;
        }

        // Save matches to database
        foreach ($matches as $match) {
            if (empty(trim($match['player1'] ?? '')) || empty(trim($match['player2'] ?? ''))) {
                continue;
            }

            ScrapedMatch::create([
                'scraper_run_id' => $this->run->id,
                'source' => 'live_center',
                'period' => $periodName,
                'division' => $divisionName,
                'series_name' => null,
                'team1_name' => trim($match['team1'] ?? ''),
                'team2_name' => trim($match['team2'] ?? ''),
                'player1_name' => trim($match['player1'] ?? ''),
                'player2_name' => trim($match['player2'] ?? ''),
                'score' => trim($match['score'] ?? ''),
                'sets' => null,
                'played_at' => trim($match['date'] ?? ''),
                'winner' => $this->determineWinner($match),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$divisionName} - {$periodName}: " . count($matches) . " matches");
    }

    protected function jsGetMatches(): string
    {
        return <<<JS
        (function() {
            const rows = document.querySelectorAll('#matches > div > table tr');
            let result = [];
            for (let i = 0; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('td');
                if (cells.length >= 4) {
                    // Parse team names and players
                    const team1Text = cells[0]?.innerText.trim() || '';
                    const team2Text = cells[2]?.innerText.trim() || '';
                    const score = cells[3]?.innerText.trim() || '';

                    result.push({
                        team1: team1Text,
                        team2: team2Text,
                        player1: team1Text,
                        player2: team2Text,
                        score: score,
                        date: ''
                    });
                }
            }
            return JSON.stringify(result);
        })();
        JS;
    }

    protected function determineWinner(array $match): ?string
    {
        $score = $match['score'] ?? '';
        if (empty($score)) {
            return null;
        }

        $parts = explode('-', $score);
        if (count($parts) !== 2) {
            return null;
        }

        $score1 = (int) trim($parts[0]);
        $score2 = (int) trim($parts[1]);

        if ($score1 > $score2) {
            return trim($match['player1'] ?? '');
        } elseif ($score2 > $score1) {
            return trim($match['player2'] ?? '');
        }

        return null; // Draw
    }
}
