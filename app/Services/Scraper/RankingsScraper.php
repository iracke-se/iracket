<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScraperRun;
use Spatie\Browsershot\Browsershot;

class RankingsScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_RANKINGS;
    }

    protected function execute(): void
    {
        $gender = $this->getParameter('gender', 'male');
        $periodFilter = $this->getParameter('period');
        $direction = $this->getParameter('direction', 'gte');

        $this->info("Starting rankings scrape for gender: {$gender}");

        // Navigate directly to rankings page with gender parameter
        $genderParam = $gender === 'female' ? 'k' : 'm';
        $baseUrl = $this->browserService->getUrlFor('rankings');
        $rankingsUrl = $baseUrl . "?gender={$genderParam}";

        $browser = Browsershot::url($rankingsUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle()
            ->noSandbox();

        if (config('scraper.browser.chrome_path')) {
            $browser->setChromePath(config('scraper.browser.chrome_path'));
        }

        // Get dropdowns (query by name since they don't have ids)
        $initJs = <<<JS
        (function () {
            // Get periods
            var periods = [];
            var periodsSelect = document.querySelector('[name="rid"]');
            if (periodsSelect) {
                for (let i = 0; i < periodsSelect.options.length; i++) {
                    periods.push({
                        value: periodsSelect.options[i].value,
                        text: periodsSelect.options[i].innerHTML.trim()
                    });
                }
            }

            // Get divisions
            var divisions = [];
            var divisionsSelect = document.querySelector('[name="distr"]');
            if (divisionsSelect) {
                for (let i = 0; i < divisionsSelect.options.length; i++) {
                    divisions.push({
                        value: divisionsSelect.options[i].value,
                        text: divisionsSelect.options[i].innerHTML.trim()
                    });
                }
            }

            return JSON.stringify({ periods: periods, divisions: divisions });
        })();
        JS;

        $initJson = $this->withRetry(function () use ($browser, $initJs) {
            return $browser->evaluate($initJs);
        }, 'Initialize rankings page');

        $initData = json_decode($initJson, true) ?? ['periods' => [], 'divisions' => []];
        $periods = $initData['periods'] ?? [];
        $divisions = $initData['divisions'] ?? [];

        // Filter out empty values
        $divisions = array_filter($divisions, fn($d) => !empty($d['value']));

        // Apply limits for testing
        $limitPeriods = $this->getParameter('limit_periods');
        $limitDivisions = $this->getParameter('limit_divisions');

        if ($limitPeriods && $limitPeriods > 0) {
            $periods = array_slice($periods, 0, $limitPeriods);
        }
        if ($limitDivisions && $limitDivisions > 0) {
            $divisions = array_slice(array_values($divisions), 0, $limitDivisions);
        }

        $this->info("Found periods: " . count($periods) . ", divisions: " . count($divisions));

        // Process divisions in parallel batches for better performance
        $batchSize = config('scraper.batch_size', 5);

        // Process each period
        foreach ($periods as $period) {
            if (!$this->shouldContinue()) {
                break;
            }

            // Apply period filter if specified
            if ($periodFilter) {
                $periodDate = $this->extractDateFromPeriod($period['text']);
                if ($periodDate) {
                    $filterDate = strtotime($periodFilter);
                    if ($direction === 'gte' && $periodDate < $filterDate) {
                        continue;
                    } elseif ($direction === 'lte' && $periodDate > $filterDate) {
                        continue;
                    }
                }
            }

            // Process divisions in batches
            $divisionBatches = array_chunk($divisions, $batchSize);

            foreach ($divisionBatches as $divisionBatch) {
                if (!$this->shouldContinue()) {
                    break;
                }

                try {
                    $this->scrapeRankingsForDivisionBatch(
                        $browser,
                        $period,
                        $divisionBatch,
                        $gender
                    );
                } catch (\Exception $e) {
                    $this->warning("Failed to scrape division batch", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->run->incrementFailed();
                }
            }
        }
    }

    protected function scrapeRankingsForDivision(
        Browsershot $browser,
        string $period,
        array $division,
        string $gender
    ): void {
        // Select division
        $selectDivisionJs = $this->browserService->jsSelectOption('distr', $division['value']);
        $this->withRetry(function () use ($browser, $selectDivisionJs) {
            $browser->evaluate($selectDivisionJs);
        }, "Select division: {$division['text']}");

        $this->delay('after_select');

        // Get rankings data
        $rankingsSelector = '#main-col > div.maincontent > table.table.table-condensed.table-hover.table-striped > tbody tr';
        $rankingsJs = $this->browserService->jsGetRankings($rankingsSelector);

        $rankingsJson = $this->withRetry(function () use ($browser, $rankingsJs) {
            return $browser->evaluate($rankingsJs);
        }, "Get rankings data for {$division['text']}");
        $rankings = json_decode($rankingsJson, true) ?? [];

        if (empty($rankings)) {
            return;
        }

        // Save rankings to database
        foreach ($rankings as $ranking) {
            // Parse position and change (format is "WR05 1" - take the last part)
            $positionParts = explode(' ', trim($ranking['position'] ?? ''));
            $position = (int) (end($positionParts) ?: 0);

            // Parse points and change
            $pointsParts = explode(' ', trim($ranking['points'] ?? ''));
            $points = (int) str_replace(['.', ','], '', $pointsParts[0] ?? '0');

            ScrapedRanking::create([
                'scraper_run_id' => $this->run->id,
                'period' => $period,
                'division' => $division['text'],
                'gender' => $gender,
                'position' => $position,
                'position_change' => trim($ranking['positionChange'] ?? ''),
                'name' => trim($ranking['name'] ?? ''),
                'born' => trim($ranking['born'] ?? ''),
                'club' => trim($ranking['club'] ?? ''),
                'points' => $points,
                'points_change' => trim($ranking['pointsChange'] ?? ''),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$period} - {$division['text']}: " . count($rankings) . " rankings");
    }

    /**
     * Scrape rankings for multiple divisions in parallel
     */
    protected function scrapeRankingsForDivisionBatch(
        Browsershot $browser,
        array $period,
        array $divisionBatch,
        string $gender
    ): void {
        $periodValue = $period['value'];
        $periodText = $period['text'];
        $rankingsSelector = '#main-col > div.maincontent > table.table.table-condensed.table-hover.table-striped > tbody tr';

        // Build JavaScript to fetch all divisions in parallel
        $divisionFetches = [];
        foreach ($divisionBatch as $division) {
            $divisionValue = $division['value'];
            $divisionText = addslashes($division['text']);

            $divisionFetches[] = <<<JS
            (async () => {
                try {
                    // Fetch the rankings page with period and division parameters
                    const genderParam = '{$gender}' === 'female' ? 'k' : 'm';
                    const url = new URL(window.location.origin + '/fx/sbtf/rankning/');
                    url.searchParams.set('gender', genderParam);

                    const formData = new URLSearchParams();
                    formData.append('rid', '{$periodValue}');
                    formData.append('distr', '{$divisionValue}');

                    const response = await fetch(url.toString(), {
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

                    const rows = doc.querySelectorAll('{$rankingsSelector}');
                    let rankings = [];

                    for (let i = 0; i < rows.length; i++) {
                        const cells = rows[i].querySelectorAll('td');
                        if (cells.length > 0) {
                            rankings.push({
                                position: cells[0]?.innerText.trim() || '',
                                positionChange: cells[1]?.innerText.trim() || '',
                                name: cells[2]?.innerText.trim() || '',
                                born: cells[3]?.innerText.trim() || '',
                                club: cells[4]?.innerText.trim() || '',
                                points: cells[5]?.innerText.trim() || '',
                                pointsChange: cells[6]?.innerText.trim() || ''
                            });
                        }
                    }

                    return {
                        divisionText: '{$divisionText}',
                        rankings: rankings,
                        success: true
                    };
                } catch (e) {
                    return {
                        divisionText: '{$divisionText}',
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
                    {$this->joinJsArrayItems($divisionFetches)}
                ]);
                return JSON.stringify(results);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $resultsJson = $this->withRetry(function () use ($browser, $batchJs) {
            return $browser->evaluate($batchJs);
        }, "Get rankings for batch of " . count($divisionBatch) . " divisions");

        $results = json_decode($resultsJson, true) ?? [];

        if (isset($results['error'])) {
            $this->warning("Error fetching batch: " . $results['error']);
            return;
        }

        // Process results for each division
        foreach ($results as $result) {
            if (!$result['success']) {
                $this->warning("Failed to scrape {$result['divisionText']}: " . ($result['error'] ?? 'unknown error'));
                $this->run->incrementFailed();
                continue;
            }

            $divisionText = $result['divisionText'];
            $rankings = $result['rankings'] ?? [];

            if (empty($rankings)) {
                continue;
            }

            // Save rankings to database
            foreach ($rankings as $ranking) {
                // Parse position and change (format is "WR05 1" - take the last part)
                $positionParts = explode(' ', trim($ranking['position'] ?? ''));
                $position = (int) (end($positionParts) ?: 0);

                // Parse points and change
                $pointsParts = explode(' ', trim($ranking['points'] ?? ''));
                $points = (int) str_replace(['.', ','], '', $pointsParts[0] ?? '0');

                ScrapedRanking::create([
                    'scraper_run_id' => $this->run->id,
                    'period' => $periodText,
                    'division' => $divisionText,
                    'gender' => $gender,
                    'position' => $position,
                    'position_change' => trim($ranking['positionChange'] ?? ''),
                    'name' => trim($ranking['name'] ?? ''),
                    'born' => trim($ranking['born'] ?? ''),
                    'club' => trim($ranking['club'] ?? ''),
                    'points' => $points,
                    'points_change' => trim($ranking['pointsChange'] ?? ''),
                ]);

                $this->run->incrementScraped();
            }

            $this->info("Scraped {$periodText} - {$divisionText}: " . count($rankings) . " rankings");
        }
    }

    /**
     * Helper to join JavaScript array items with commas
     */
    private function joinJsArrayItems(array $items): string
    {
        return implode(",\n                    ", $items);
    }

    protected function extractDateFromPeriod(string $periodText): ?int
    {
        // Extract date from period text like "2024.01.01" or "January 2024"
        if (preg_match('/(\d{4})\.(\d{2})\.(\d{2})/', $periodText, $matches)) {
            return strtotime("{$matches[1]}-{$matches[2]}-{$matches[3]}");
        }

        if (preg_match('/(\d{4})/', $periodText, $matches)) {
            return strtotime("{$matches[1]}-01-01");
        }

        return null;
    }
}
