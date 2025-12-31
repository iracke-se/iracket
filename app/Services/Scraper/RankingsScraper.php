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

        // Apply period chunking for parallel processing
        $periodSkip = $this->getParameter('period_skip');
        $periodTake = $this->getParameter('period_take');

        if ($periodSkip !== null || $periodTake !== null) {
            $skip = $periodSkip ?? 0;
            $take = $periodTake ?? count($periods);
            $periods = array_slice($periods, $skip, $take);
            $this->info("Processing period chunk: skip={$skip}, take={$take}");
        }

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
        $limitDivisions = $this->getParameter('limit_divisions');

        if ($limitPeriods && $limitPeriods > 0) {
            $periods = array_slice($periods, 0, $limitPeriods);
        }
        if ($limitDivisions && $limitDivisions > 0) {
            $divisions = array_slice(array_values($divisions), 0, $limitDivisions);
        }

        $this->info("Found periods: " . count($periods) . ", divisions: " . count($divisions));

        // Process each period
        foreach ($periods as $period) {
            if (!$this->shouldContinue()) {
                break;
            }

            // Period already filtered above, just log what we're processing
            $this->info("Processing period {$period['text']}");

            // Select period
            $selectPeriodJs = $this->browserService->jsSelectOption('rid', $period['value']);
            $this->withRetry(function () use ($browser, $selectPeriodJs) {
                $browser->evaluate($selectPeriodJs);
            }, "Select period: {$period['text']}");

            $this->delay('after_select');

            // Process each division
            foreach ($divisions as $division) {
                if (!$this->shouldContinue()) {
                    break;
                }

                try {
                    $this->scrapeRankingsForDivision(
                        $browser,
                        $period['text'],
                        $division,
                        $gender
                    );
                } catch (\Exception $e) {
                    $this->warning("Failed to scrape division: {$division['text']}", [
                        'period' => $period['text'],
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
}
