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

        // Navigate to rankings page
        $mainUrl = $this->browserService->getMainUrl();

        $browser = Browsershot::url($mainUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle();

        // Click rankings menu
        $rankingsSelector = $this->browserService->getSelector('rankings');
        $clickJs = $this->browserService->jsClickAndWait($rankingsSelector);

        $this->withRetry(function () use ($browser, $clickJs) {
            $browser->evaluate($clickJs);
        }, 'Click rankings menu');

        $this->delay('after_click');

        // Get periods from dropdown
        $periodsJs = $this->browserService->jsGetDropdownOptions('rid');
        $periods = $this->withRetry(function () use ($browser, $periodsJs) {
            return $browser->evaluate($periodsJs);
        }, 'Get periods dropdown');

        // Get divisions from dropdown
        $divisionsJs = $this->browserService->jsGetDropdownOptions('distr');
        $divisions = $this->withRetry(function () use ($browser, $divisionsJs) {
            return $browser->evaluate($divisionsJs);
        }, 'Get divisions dropdown');

        // Filter out empty values
        $divisions = array_filter($divisions, fn($d) => !empty($d['value']));

        $this->info("Found periods: " . count($periods) . ", divisions: " . count($divisions));

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
                    $this->warning("Failed to scrape division {$division['text']}", [
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

        $rankings = $this->withRetry(function () use ($browser, $rankingsJs) {
            return $browser->evaluate($rankingsJs);
        }, "Get rankings data for {$division['text']}");

        if (empty($rankings)) {
            return;
        }

        // Save rankings to database
        foreach ($rankings as $ranking) {
            // Parse position and change
            $positionParts = explode(' ', trim($ranking['position'] ?? ''));
            $position = (int) ($positionParts[0] ?? 0);

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
