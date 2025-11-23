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
        $this->info("Starting player list scrape");

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

        // Apply limits for testing
        $limitPeriods = $this->getParameter('limit_periods');
        $limitClubs = $this->getParameter('limit_clubs');

        if ($limitPeriods && $limitPeriods > 0) {
            $periods = array_slice($periods, 0, $limitPeriods);
        }
        if ($limitClubs && $limitClubs > 0) {
            $clubs = array_slice(array_values($clubs), 0, $limitClubs);
        }

        $this->info("Found periods: " . count($periods) . ", clubs: " . count($clubs));

        // Process each period and club using fetch with POST
        foreach ($periods as $period) {
            if (!$this->shouldContinue()) {
                break;
            }

            foreach ($clubs as $club) {
                if (!$this->shouldContinue()) {
                    break;
                }

                try {
                    $this->scrapePlayersForClub(
                        $browser,
                        $period,
                        $club
                    );
                } catch (\Exception $e) {
                    $this->warning("Failed to scrape club {$club['text']}", [
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
}
