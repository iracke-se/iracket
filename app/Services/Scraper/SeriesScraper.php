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

        // Navigate to series page
        $mainUrl = $this->browserService->getMainUrl();

        $browser = Browsershot::url($mainUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle();

        // Click series menu
        $seriesSelector = $this->browserService->getSelector('series');
        $clickJs = $this->browserService->jsClickAndWait($seriesSelector);

        $this->withRetry(function () use ($browser, $clickJs) {
            $browser->evaluate($clickJs);
        }, 'Click series menu');

        $this->delay('after_click');

        // Get seasons data
        $seasonsJs = $this->jsGetSeasons();
        $seasons = $this->withRetry(function () use ($browser, $seasonsJs) {
            return $browser->evaluate($seasonsJs);
        }, 'Get seasons');

        if (empty($seasons)) {
            $this->warning("No seasons found");
            return;
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

        // Navigate to season page
        $clickSeasonJs = <<<JS
        (async function() {
            const link = document.querySelector('a[href*="{$seasonLink}"]');
            if (link) {
                link.click();
                await new Promise(resolve => setTimeout(resolve, 1000));
            }
            return true;
        })();
        JS;

        $this->withRetry(function () use ($browser, $clickSeasonJs) {
            $browser->evaluate($clickSeasonJs);
        }, "Navigate to season: {$seasonLabel}");

        $this->delay('after_click');

        // Get standings table data
        $standingsJs = $this->jsGetStandings();
        $standings = $this->withRetry(function () use ($browser, $standingsJs) {
            return $browser->evaluate($standingsJs);
        }, "Get standings for {$seasonLabel}");

        // Save standings
        foreach ($standings as $index => $standing) {
            if (empty(trim($standing['teamName'] ?? ''))) {
                continue;
            }

            ScrapedStanding::create([
                'scraper_run_id' => $this->run->id,
                'period' => $seasonLabel,
                'series_name' => 'Main Series',
                'session_name' => null,
                'position' => $index + 1,
                'team_name' => trim($standing['teamName'] ?? ''),
                'matches_played' => (int) ($standing['played'] ?? 0),
                'wins' => (int) ($standing['wins'] ?? 0),
                'losses' => (int) ($standing['losses'] ?? 0),
                'draws' => (int) ($standing['draws'] ?? 0),
                'points' => (int) ($standing['points'] ?? 0),
                'goal_difference' => trim($standing['goalDiff'] ?? ''),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$seasonLabel}: " . count($standings) . " standings");
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
            return result;
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
            return result;
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
