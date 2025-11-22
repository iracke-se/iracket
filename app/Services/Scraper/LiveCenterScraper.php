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

        // Navigate to live center page
        $mainUrl = $this->browserService->getMainUrl();

        $browser = Browsershot::url($mainUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle();

        // Click live center menu
        $liveCenterSelector = $this->browserService->getSelector('live_center');
        $clickJs = $this->browserService->jsClickAndWait($liveCenterSelector);

        $this->withRetry(function () use ($browser, $clickJs) {
            $browser->evaluate($clickJs);
        }, 'Click live center menu');

        $this->delay('after_click');

        // Get divisions from dropdown
        $divisionsJs = $this->browserService->jsGetDropdownOptions('filter4_id');
        $divisions = $this->withRetry(function () use ($browser, $divisionsJs) {
            return $browser->evaluate($divisionsJs);
        }, 'Get divisions dropdown');

        // Filter out empty values
        $divisions = array_filter($divisions, fn($d) => !empty($d['value']));

        // Apply skip/take for parallel processing
        if ($take > 0 || $skip > 0) {
            $divisions = array_slice($divisions, $skip, $take ?: null);
        }

        $this->info("Processing divisions: " . count($divisions));

        // Get periods from dropdown
        $periodsJs = $this->browserService->jsGetDropdownOptions('filter1_id');
        $periods = $this->withRetry(function () use ($browser, $periodsJs) {
            return $browser->evaluate($periodsJs);
        }, 'Get periods dropdown');

        // Process each division
        foreach ($divisions as $division) {
            if (!$this->shouldContinue()) {
                break;
            }

            // Select division
            $selectDivisionJs = $this->browserService->jsSelectOption('filter4_id', $division['value']);
            $this->withRetry(function () use ($browser, $selectDivisionJs) {
                $browser->evaluate($selectDivisionJs);
            }, "Select division: {$division['text']}");

            $this->delay('after_select');

            // Process each period for this division
            foreach ($periods as $period) {
                if (!$this->shouldContinue()) {
                    break;
                }

                try {
                    $this->scrapeMatchesForDivisionPeriod(
                        $browser,
                        $division['text'],
                        $period
                    );
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
        string $division,
        array $period
    ): void {
        // Select period
        $selectPeriodJs = $this->browserService->jsSelectOption('filter1_id', $period['value']);
        $this->withRetry(function () use ($browser, $selectPeriodJs) {
            $browser->evaluate($selectPeriodJs);
        }, "Select period: {$period['text']}");

        $this->delay('after_select');

        // Get match data
        $matchesJs = $this->jsGetMatches();
        $matches = $this->withRetry(function () use ($browser, $matchesJs) {
            return $browser->evaluate($matchesJs);
        }, "Get matches for {$division} - {$period['text']}");

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
                'period' => $period['text'],
                'division' => $division,
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

        $this->info("Scraped {$division} - {$period['text']}: " . count($matches) . " matches");
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
            return result;
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
