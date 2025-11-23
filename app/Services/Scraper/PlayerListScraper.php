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

        // Navigate to player list page
        $mainUrl = $this->browserService->getMainUrl();

        $browser = Browsershot::url($mainUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle()
            ->noSandbox();

        if (config('scraper.browser.chrome_path')) {
            $browser->setChromePath(config('scraper.browser.chrome_path'));
        }

        // Click player list menu
        $playerListSelector = $this->browserService->getSelector('player_list');
        $clickJs = $this->browserService->jsClickAndWait($playerListSelector);

        $this->withRetry(function () use ($browser, $clickJs) {
            $browser->evaluate($clickJs);
        }, 'Click player list menu');

        $this->delay('after_click');

        // Get periods from dropdown
        $periodsJs = $this->browserService->jsGetDropdownOptions('periode');
        $periodsJson = $this->withRetry(function () use ($browser, $periodsJs) {
            return $browser->evaluate($periodsJs);
        }, 'Get periods dropdown');
        $periods = json_decode($periodsJson, true) ?? [];

        // Get clubs from dropdown
        $clubsJs = $this->browserService->jsGetDropdownOptions('klubbid');
        $clubsJson = $this->withRetry(function () use ($browser, $clubsJs) {
            return $browser->evaluate($clubsJs);
        }, 'Get clubs dropdown');
        $clubs = json_decode($clubsJson, true) ?? [];

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

        // Process each period
        foreach ($periods as $period) {
            if (!$this->shouldContinue()) {
                break;
            }

            // Select period
            $selectPeriodJs = $this->browserService->jsSelectOption('periode', $period['value']);
            $this->withRetry(function () use ($browser, $selectPeriodJs) {
                $browser->evaluate($selectPeriodJs);
            }, "Select period: {$period['text']}");

            $this->delay('after_select');

            // Process each club
            foreach ($clubs as $club) {
                if (!$this->shouldContinue()) {
                    break;
                }

                try {
                    $this->scrapePlayersForClub(
                        $browser,
                        $period['text'],
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
        string $period,
        array $club
    ): void {
        // Select club
        $selectClubJs = $this->browserService->jsSelectOption('klubbid', $club['value']);
        $this->withRetry(function () use ($browser, $selectClubJs) {
            $browser->evaluate($selectClubJs);
        }, "Select club: {$club['text']}");

        $this->delay('after_select');

        // Get player data
        $playersJs = $this->browserService->jsGetPlayerList('.table-condensed');

        $playersJson = $this->withRetry(function () use ($browser, $playersJs) {
            return $browser->evaluate($playersJs);
        }, "Get players for {$club['text']}");
        $players = json_decode($playersJson, true) ?? [];

        if (empty($players)) {
            return;
        }

        // Save players to database
        foreach ($players as $player) {
            if (empty(trim($player['surname'] ?? '')) && empty(trim($player['firstName'] ?? ''))) {
                continue;
            }

            ScrapedPlayer::create([
                'scraper_run_id' => $this->run->id,
                'period' => $period,
                'club_name' => trim($club['text']),
                'surname' => trim($player['surname'] ?? ''),
                'first_name' => trim($player['firstName'] ?? ''),
                'sex' => trim($player['sex'] ?? ''),
                'date_of_birth' => trim($player['dateOfBirth'] ?? ''),
                'license_type' => trim($player['licenseType'] ?? ''),
                'player_class' => trim($player['playerClass'] ?? ''),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$period} - {$club['text']}: " . count($players) . " players");
    }
}
