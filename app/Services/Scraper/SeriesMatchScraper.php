<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScraperRun;
use Spatie\Browsershot\Browsershot;

class SeriesMatchScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_SERIES_MATCHES;
    }

    protected function execute(): void
    {
        $periodFilter = $this->getParameter('period');
        $direction = $this->getParameter('direction', 'gte');

        $this->info("Starting series match scrape");

        // Navigate to SBTF portal to establish session
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

        // Fetch series page using fetch with credentials
        $initJs = <<<JS
        (async function() {
            try {
                const response = await fetch('https://www.profixio.com/fx/serieoppsett.php', {
                    credentials: 'include'
                });
                const html = await response.text();

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get seasons from the page
                const allLinks = doc.querySelectorAll('.maincontent a');
                let seasons = [];
                allLinks.forEach(item => {
                    const text = item.innerText.trim();
                    const href = item.getAttribute('href') || '';
                    if (text.includes('Säsongen') && href.includes('serieoppsett_sesong')) {
                        seasons.push({
                            label: text.replace('*', '').trim(),
                            link: href
                        });
                    }
                });

                return JSON.stringify({
                    seasons: seasons,
                    pageTitle: doc.title
                });
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $initJson = $this->withRetry(function () use ($browser, $initJs) {
            return $browser->evaluate($initJs);
        }, 'Fetch series page');

        $initData = json_decode($initJson, true);

        if (isset($initData['error'])) {
            $this->error("Failed to fetch data: " . $initData['error']);
            return;
        }

        $this->info("Page title: " . ($initData['pageTitle'] ?? 'unknown'));

        $seasons = $initData['seasons'] ?? [];

        if (empty($seasons)) {
            $this->warning("No seasons found");
            return;
        }

        // Apply limits for testing
        $limitSeasons = $this->getParameter('limit_seasons');
        if ($limitSeasons && $limitSeasons > 0) {
            $seasons = array_slice($seasons, 0, $limitSeasons);
        }

        $this->info("Found seasons: " . count($seasons));

        // Process each season
        foreach ($seasons as $season) {
            if (!$this->shouldContinue()) {
                break;
            }

            // Apply period filter if specified
            if ($periodFilter) {
                $seasonYear = $this->extractYearFromSeason($season['label'] ?? '');
                if ($seasonYear) {
                    $filterYear = (int) $periodFilter;
                    if ($direction === 'gte' && $seasonYear < $filterYear) {
                        continue;
                    } elseif ($direction === 'lte' && $seasonYear > $filterYear) {
                        continue;
                    }
                }
            }

            try {
                $this->scrapeSeasonMatches($browser, $season);
            } catch (\Exception $e) {
                $this->warning("Failed to scrape season", [
                    'season' => $season['label'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $this->run->incrementFailed();
            }
        }
    }

    protected function scrapeSeasonMatches(Browsershot $browser, array $season): void
    {
        $seasonLabel = $season['label'] ?? '';
        $seasonLink = $season['link'] ?? '';

        if (empty($seasonLink)) {
            return;
        }

        $this->info("Scraping season: {$seasonLabel}");

        // Build full URL for season page
        $seasonUrl = $seasonLink;
        if (!str_starts_with($seasonLink, 'http')) {
            $seasonUrl = 'https://www.profixio.com/fx/' . ltrim($seasonLink, '/');
        }

        // Fetch season page to get series links
        $fetchSeriesJs = <<<JS
        (async function() {
            try {
                const response = await fetch('{$seasonUrl}', {
                    credentials: 'include'
                });
                const html = await response.text();

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get series links from the season page
                const allLinks = doc.querySelectorAll('.maincontent a');
                let series = [];
                allLinks.forEach(item => {
                    const text = item.innerText.trim();
                    const href = item.getAttribute('href') || '';
                    // Look for series links
                    if (href.includes('serieoppsett.php?t=') && text && !text.includes('Säsongen')) {
                        series.push({
                            name: text,
                            link: href
                        });
                    }
                });

                return JSON.stringify(series);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $seriesJson = $this->withRetry(function () use ($browser, $fetchSeriesJs) {
            return $browser->evaluate($fetchSeriesJs);
        }, "Get series for {$seasonLabel}");

        $seriesList = json_decode($seriesJson, true) ?? [];

        if (isset($seriesList['error'])) {
            $this->warning("Error fetching series: " . $seriesList['error']);
            return;
        }

        if (empty($seriesList)) {
            $this->info("No series found for {$seasonLabel}");
            return;
        }

        // Apply limit for testing
        $limitSeries = $this->getParameter('limit_series');
        if ($limitSeries && $limitSeries > 0) {
            $seriesList = array_slice($seriesList, 0, $limitSeries);
        }

        $this->info("Found series in {$seasonLabel}: " . count($seriesList));

        // Scrape matches from each series
        foreach ($seriesList as $series) {
            if (!$this->shouldContinue()) {
                break;
            }

            try {
                $this->scrapeSeriesMatches($browser, $seasonLabel, $series);
            } catch (\Exception $e) {
                $this->warning("Failed to scrape series {$series['name']}", [
                    'error' => $e->getMessage(),
                ]);
                $this->run->incrementFailed();
            }
        }
    }

    protected function scrapeSeriesMatches(Browsershot $browser, string $seasonLabel, array $series): void
    {
        $seriesName = $series['name'] ?? '';
        $seriesLink = $series['link'] ?? '';

        if (empty($seriesLink)) {
            return;
        }

        // Build full URL for series page
        $seriesUrl = $seriesLink;
        if (!str_starts_with($seriesLink, 'http')) {
            $seriesUrl = 'https://www.profixio.com/fx/' . ltrim($seriesLink, '/');
        }

        // Fetch series page to get "Detaljer" links
        $fetchMatchLinksJs = <<<JS
        (async function() {
            try {
                const response = await fetch('{$seriesUrl}', {
                    credentials: 'include'
                });
                const html = await response.text();

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get all "Detaljer" links
                const allLinks = Array.from(doc.querySelectorAll('a'));
                const detaljerLinks = allLinks
                    .filter(link => link.textContent.includes('Detaljer'))
                    .map(link => link.href);

                return JSON.stringify({
                    matchLinks: detaljerLinks,
                    seriesName: '{$seriesName}'
                });
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $matchLinksJson = $this->withRetry(function () use ($browser, $fetchMatchLinksJs) {
            return $browser->evaluate($fetchMatchLinksJs);
        }, "Get match links for {$seriesName}");

        $matchLinksData = json_decode($matchLinksJson, true) ?? [];

        if (isset($matchLinksData['error'])) {
            $this->warning("Error fetching match links: " . $matchLinksData['error']);
            return;
        }

        $matchLinks = $matchLinksData['matchLinks'] ?? [];

        if (empty($matchLinks)) {
            $this->info("No match links found for {$seriesName}");
            return;
        }

        $this->info("Found {$seriesName}: " . count($matchLinks) . " matches");

        // Apply limit for testing
        $limitMatches = $this->getParameter('limit_matches');
        if ($limitMatches && $limitMatches > 0) {
            $matchLinks = array_slice($matchLinks, 0, $limitMatches);
        }

        // Scrape each match detail page
        foreach ($matchLinks as $matchLink) {
            if (!$this->shouldContinue()) {
                break;
            }

            try {
                $this->scrapeMatchDetails($browser, $matchLink, $seriesName, $seasonLabel);
            } catch (\Exception $e) {
                $this->warning("Failed to scrape match", [
                    'match_link' => $matchLink,
                    'error' => $e->getMessage(),
                ]);
                $this->run->incrementFailed();
            }
        }
    }

    protected function scrapeMatchDetails(Browsershot $browser, string $matchUrl, string $seriesName, string $seasonLabel): void
    {
        // Fetch match details page
        $fetchMatchJs = <<<JS
        (async function() {
            try {
                const response = await fetch('{$matchUrl}', {
                    credentials: 'include'
                });
                const html = await response.text();

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get match date and teams
                const matchInfo = {
                    date: '',
                    homeTeam: '',
                    awayTeam: '',
                    series: '',
                    matches: []
                };

                // Extract date from table: <td>Datum</td><td>DD.MM.YYYY</td>
                const datumCell = Array.from(doc.querySelectorAll('td')).find(el => el.textContent.trim() === 'Datum');
                if (datumCell && datumCell.nextElementSibling) {
                    const dateText = datumCell.nextElementSibling.textContent.trim();
                    const match = dateText.match(/\\d{2}\\.\\d{2}\\.\\d{4}/);
                    if (match) {
                        matchInfo.date = match[0];
                    }
                }

                // Extract series name
                const serieText = Array.from(doc.querySelectorAll('*')).find(el => el.textContent.includes('Serie'));
                if (serieText) {
                    const match = serieText.textContent.match(/Serie\\/Series:\\s*(.+)/);
                    if (match) {
                        matchInfo.series = match[1].trim();
                    }
                }

                // Get team names from table header
                const tableHeaders = doc.querySelectorAll('table thead th');
                if (tableHeaders.length >= 2) {
                    matchInfo.homeTeam = tableHeaders[0]?.textContent.trim() || '';
                    matchInfo.awayTeam = tableHeaders[1]?.textContent.trim() || '';
                }

                // Get individual matches from table
                const rows = doc.querySelectorAll('table tbody tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 6) {
                        // S1, A1, Andersson Harald, B2, Wederlich Damian, etc.
                        const matchNumber = cells[0]?.textContent.trim();
                        const player1Code = cells[1]?.textContent.trim();
                        const player1Name = cells[2]?.textContent.trim();
                        const player2Code = cells[3]?.textContent.trim();
                        const player2Name = cells[4]?.textContent.trim();

                        // Get set scores (columns 5+)
                        const sets = [];
                        for (let i = 5; i < cells.length - 1; i++) {
                            const score = cells[i]?.textContent.trim();
                            if (score && score !== '-') {
                                sets.push(score);
                            }
                        }

                        // Get final result from last column
                        const result = cells[cells.length - 1]?.textContent.trim();

                        if (player1Name && player2Name) {
                            matchInfo.matches.push({
                                matchNumber: matchNumber,
                                player1: player1Name,
                                player2: player2Name,
                                sets: sets.join(', '),
                                result: result
                            });
                        }
                    }
                });

                return JSON.stringify(matchInfo);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $matchJson = $this->withRetry(function () use ($browser, $fetchMatchJs) {
            return $browser->evaluate($fetchMatchJs);
        }, "Get match details from {$matchUrl}");

        $matchData = json_decode($matchJson, true);

        if (isset($matchData['error'])) {
            $this->warning("Error fetching match details: " . $matchData['error']);
            return;
        }

        $matches = $matchData['matches'] ?? [];

        if (empty($matches)) {
            return;
        }

        // Save each individual player match
        foreach ($matches as $match) {
            ScrapedMatch::create([
                'scraper_run_id' => $this->run->id,
                'source' => 'series',
                'period' => $seasonLabel,
                'division' => $seriesName,
                'series_name' => $matchData['series'] ?? $seriesName,
                'team1_name' => $matchData['homeTeam'] ?? '',
                'team2_name' => $matchData['awayTeam'] ?? '',
                'player1_name' => $match['player1'] ?? '',
                'player2_name' => $match['player2'] ?? '',
                'score' => $match['sets'] ?? '',
                'sets' => $match['sets'] ?? '',
                'played_at' => $this->parseDate($matchData['date'] ?? ''),
                'winner' => $this->determineWinner($match),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$seriesName}: " . count($matches) . " player matches");
    }

    protected function extractYearFromSeason(string $seasonLabel): ?int
    {
        // Extract year from "Säsongen 2024/2025" or similar formats
        if (preg_match('/(\d{4})/', $seasonLabel, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    protected function parseDate(string $dateStr): ?string
    {
        // Parse "DD.MM.YYYY" format to "YYYY-MM-DD"
        if (preg_match('/(\d{2})\.(\d{2})\.(\d{4})/', $dateStr, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        // If date parsing fails, log warning and return current date as fallback
        $this->warning("Failed to parse date: '{$dateStr}', using current date as fallback");
        return now()->format('Y-m-d');
    }

    protected function determineWinner(array $match): ?string
    {
        // Count individual game wins from sets (e.g., "9-11, 13-11, 9-11, 11-8, 11-13")
        $sets = $match['sets'] ?? '';
        if (empty($sets)) {
            return null;
        }

        // Split individual games
        $games = explode(',', $sets);
        $player1Wins = 0;
        $player2Wins = 0;

        foreach ($games as $game) {
            $game = trim($game);
            if (preg_match('/(\d+)-(\d+)/', $game, $matches)) {
                $score1 = (int) $matches[1];
                $score2 = (int) $matches[2];

                if ($score1 > $score2) {
                    $player1Wins++;
                } elseif ($score2 > $score1) {
                    $player2Wins++;
                }
            }
        }

        // Determine winner based on who won more games
        if ($player1Wins > $player2Wins) {
            return $match['player1'] ?? null;
        } elseif ($player2Wins > $player1Wins) {
            return $match['player2'] ?? null;
        }

        return null; // Draw or equal games won
    }
}
