<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScrapedStanding;
use App\Models\Scraper\ScraperRun;
use Spatie\Browsershot\Browsershot;

class SeriesScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_SERIES;
    }

    protected function execute(): void
    {
        $periodFilter = $this->getParameter('period');
        $direction = $this->getParameter('direction', 'gte');

        $this->info("Starting series scrape");

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

                // Get seasons from the page - look for links containing "Säsongen"
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
                $this->scrapeSeasonData($browser, $season);
            } catch (\Exception $e) {
                $this->warning("Failed to scrape season", [
                    'season' => $season['label'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
                $this->run->incrementFailed();
            }
        }
    }

    protected function scrapeSeasonData(Browsershot $browser, array $season): void
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
                    // Look for series links with actual content (not just season links)
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

        // Scrape standings from each series
        foreach ($seriesList as $series) {
            if (!$this->shouldContinue()) {
                break;
            }

            try {
                $this->scrapeSeriesStandings($browser, $seasonLabel, $series);
            } catch (\Exception $e) {
                $this->warning("Failed to scrape series {$series['name']}", [
                    'error' => $e->getMessage(),
                ]);
                $this->run->incrementFailed();
            }
        }
    }

    protected function scrapeSeriesStandings(Browsershot $browser, string $seasonLabel, array $series): void
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

        // Fetch series page to get standings
        $fetchStandingsJs = <<<JS
        (async function() {
            try {
                const response = await fetch('{$seriesUrl}', {
                    credentials: 'include'
                });
                const html = await response.text();

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get standings table data from #tabell_std
                const rows = doc.querySelectorAll('#tabell_std tr');
                let standings = [];
                for (let i = 1; i < rows.length; i++) {
                    const cells = rows[i].querySelectorAll('td');
                    // Table has 9 cells: team, M, V, O, F, Matcher, Set, Bollar, Poäng
                    if (cells.length >= 9) {
                        const teamName = cells[0]?.innerText.trim() || '';
                        // Remove position number from team name (e.g., "1. Team Name" -> "Team Name")
                        const cleanTeamName = teamName.replace(/^\d+\.\s*/, '');

                        standings.push({
                            teamName: cleanTeamName,
                            played: cells[1]?.innerText.trim() || '0',
                            wins: cells[2]?.innerText.trim() || '0',
                            draws: cells[3]?.innerText.trim() || '0',
                            losses: cells[4]?.innerText.trim() || '0',
                            matcher: cells[5]?.innerText.trim() || '',
                            sets: cells[6]?.innerText.trim() || '',
                            bollar: cells[7]?.innerText.trim() || '',
                            points: cells[8]?.innerText.trim() || '0'
                        });
                    }
                }

                return JSON.stringify(standings);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $standingsJson = $this->withRetry(function () use ($browser, $fetchStandingsJs) {
            return $browser->evaluate($fetchStandingsJs);
        }, "Get standings for {$seriesName}");

        $standings = json_decode($standingsJson, true) ?? [];

        if (isset($standings['error'])) {
            $this->warning("Error fetching standings: " . $standings['error']);
            return;
        }

        if (empty($standings)) {
            return;
        }

        // Save standings
        foreach ($standings as $index => $standing) {
            if (empty(trim($standing['teamName'] ?? ''))) {
                continue;
            }

            ScrapedStanding::create([
                'scraper_run_id' => $this->run->id,
                'period' => $seasonLabel,
                'series_name' => $seriesName,
                'session_name' => null,
                'position' => $index + 1,
                'team_name' => trim($standing['teamName'] ?? ''),
                'matches_played' => (int) ($standing['played'] ?? 0),
                'wins' => (int) ($standing['wins'] ?? 0),
                'losses' => (int) ($standing['losses'] ?? 0),
                'draws' => (int) ($standing['draws'] ?? 0),
                'points' => (int) ($standing['points'] ?? 0),
                'goal_difference' => trim($standing['matcher'] ?? ''),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$seriesName}: " . count($standings) . " standings");
    }

    protected function jsGetSeasons(): string
    {
        return <<<JS
        (function() {
            const items = document.querySelectorAll('#main-col > div.maincontent > div > div > div > div > ul li a');
            let result = [];
            items.forEach(item => {
                result.push({
                    label: item.innerText.trim(),
                    link: item.getAttribute('href') || ''
                });
            });
            return JSON.stringify(result);
        })();
        JS;
    }

    protected function jsGetStandings(): string
    {
        return <<<JS
        (function() {
            const rows = document.querySelectorAll('#tabell_std tr');
            let result = [];
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].querySelectorAll('td');
                if (cells.length >= 6) {
                    result.push({
                        teamName: cells[0]?.innerText.trim() || '',
                        played: cells[1]?.innerText.trim() || '0',
                        wins: cells[2]?.innerText.trim() || '0',
                        draws: cells[3]?.innerText.trim() || '0',
                        losses: cells[4]?.innerText.trim() || '0',
                        goalDiff: cells[5]?.innerText.trim() || '',
                        points: cells[6]?.innerText.trim() || '0'
                    });
                }
            }
            return JSON.stringify(result);
        })();
        JS;
    }

    protected function extractYearFromSeason(string $seasonText): ?int
    {
        if (preg_match('/(\d{4})/', $seasonText, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }
}
