<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScrapedTransition;
use App\Models\Scraper\ScraperRun;
use Spatie\Browsershot\Browsershot;

class TransitionsScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_TRANSITIONS;
    }

    protected function execute(): void
    {
        $this->info("Starting transitions scrape");

        // Navigate to player list page
        $mainUrl = $this->browserService->getMainUrl();

        $browser = Browsershot::url($mainUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle();

        // Click player list menu
        $playerListSelector = $this->browserService->getSelector('player_list');
        $clickJs = $this->browserService->jsClickAndWait($playerListSelector);

        $this->withRetry(function () use ($browser, $clickJs) {
            $browser->evaluate($clickJs);
        }, 'Click player list menu');

        $this->delay('after_click');

        // Click transitions tab
        $transitionsTabSelector = '#main-col > div.meny > div > div.undermeny > ul > li:nth-child(2) > a';
        $clickTransitionsJs = $this->browserService->jsClickAndWait($transitionsTabSelector);

        $this->withRetry(function () use ($browser, $clickTransitionsJs) {
            $browser->evaluate($clickTransitionsJs);
        }, 'Click transitions tab');

        $this->delay('after_click');

        // Get periods from dropdown
        $periodsJs = $this->browserService->jsGetDropdownOptions('periode');
        $periods = $this->withRetry(function () use ($browser, $periodsJs) {
            return $browser->evaluate($periodsJs);
        }, 'Get periods dropdown');

        // Filter out "all" option
        $periods = array_filter($periods, fn($p) => $p['value'] !== '0');

        $this->info("Found periods: " . count($periods));

        // Process each period
        foreach ($periods as $period) {
            if (!$this->shouldContinue()) {
                break;
            }

            try {
                $this->scrapeTransitionsForPeriod($browser, $period);
            } catch (\Exception $e) {
                $this->warning("Failed to scrape period {$period['text']}", [
                    'error' => $e->getMessage(),
                ]);
                $this->run->incrementFailed();
            }
        }
    }

    protected function scrapeTransitionsForPeriod(Browsershot $browser, array $period): void
    {
        // Select period
        $selectPeriodJs = $this->browserService->jsSelectOption('periode', $period['value']);
        $this->withRetry(function () use ($browser, $selectPeriodJs) {
            $browser->evaluate($selectPeriodJs);
        }, "Select period: {$period['text']}");

        $this->delay('after_select');

        // Get transitions data
        $transitionsSelector = '#main-col > div.maincontent > form > table > tbody > tr';
        $transitionsJs = $this->browserService->jsGetTransitions($transitionsSelector);

        $transitions = $this->withRetry(function () use ($browser, $transitionsJs) {
            return $browser->evaluate($transitionsJs);
        }, "Get transitions for {$period['text']}");

        if (empty($transitions)) {
            return;
        }

        // Save transitions to database
        foreach ($transitions as $transition) {
            if (empty(trim($transition['surname'] ?? '')) && empty(trim($transition['firstName'] ?? ''))) {
                continue;
            }

            ScrapedTransition::create([
                'scraper_run_id' => $this->run->id,
                'period' => $period['text'],
                'surname' => trim($transition['surname'] ?? ''),
                'first_name' => trim($transition['firstName'] ?? ''),
                'born' => trim($transition['born'] ?? ''),
                'from_club' => trim($transition['from'] ?? ''),
                'to_club' => trim($transition['to'] ?? ''),
                'completion_date' => trim($transition['completionDate'] ?? ''),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$period['text']}: " . count($transitions) . " transitions");
    }
}
