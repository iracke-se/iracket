<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;

class PlayerListScraper extends BaseScraperService
{
    public function getType(): string
    {
        return ScraperRun::TYPE_PLAYERS;
    }

    protected function execute(): void
    {
        $periodFilter = $this->getParameter('period');
        $direction    = $this->getParameter('direction', 'gte');

        $this->info("Starting player list scrape");
        $this->info("Period filter: " . ($periodFilter ?? 'NONE'));
        $this->info("Direction: " . $direction);

        // Single cookie jar shared across all requests (maintains session)
        $cookieJar = new CookieJar();

        // Establish guest session — no credentials needed
        $loginUrl = 'https://www.profixio.com/fx/login.php?login_public=SBTF.SE.BT';
        $loginResp = $this->withRetry(function () use ($loginUrl, $cookieJar) {
            return Http::withOptions(['cookies' => $cookieJar])
                ->timeout(30)
                ->get($loginUrl);
        }, 'Login to profixio');

        $this->info("Session established (HTTP {$loginResp->status()})");

        // Fetch player list overview page to extract period and club dropdowns
        $overviewUrl  = 'https://www.profixio.com/fx/lisens/public_oversikt.php';
        $overviewResp = $this->withRetry(function () use ($overviewUrl, $cookieJar) {
            return Http::withOptions(['cookies' => $cookieJar])
                ->timeout(30)
                ->get($overviewUrl);
        }, 'Fetch player list overview');

        $html = $overviewResp->body();
        $this->info("Overview page fetched (" . strlen($html) . " bytes)");

        [$periods, $clubs] = $this->parseDropdowns($html);

        $this->info("Found " . count($periods) . " periods, " . count($clubs) . " clubs");

        // Filter out empty club entries
        $clubs = array_filter($clubs, fn($c) => !empty(trim($c['text'])));

        // Apply year filter before limiting
        if ($periodFilter) {
            $filterYear = (int) date('Y', strtotime($periodFilter));
            $this->info("Applying year filter: {$filterYear}");

            $periods = array_filter($periods, function ($period) use ($filterYear) {
                $startYear = $this->extractYearFromPeriod($period['text']);
                if (! $startYear) {
                    return false;
                }
                $endYear = $startYear + 1;
                $matches = ($filterYear >= $startYear && $filterYear <= $endYear);
                $this->info($matches
                    ? "✓ Keeping period {$period['text']}"
                    : "⊘ Filtered out period {$period['text']}");
                return $matches;
            });

            $periods = array_values($periods);
        }

        // Apply test limits (after year filter)
        $limitPeriods = $this->getParameter('limit_periods');
        $limitClubs   = $this->getParameter('limit_clubs');

        if ($limitPeriods > 0) {
            $periods = array_slice($periods, 0, $limitPeriods);
        }
        if ($limitClubs > 0) {
            $clubs = array_slice(array_values($clubs), 0, $limitClubs);
        }

        $this->info("Processing " . count($periods) . " period(s), " . count($clubs) . " club(s)");

        foreach ($periods as $period) {
            if (! $this->shouldContinue()) {
                break;
            }

            $this->info("Processing period: {$period['text']}");

            $batchSize   = config('scraper.batch_size', 5);
            $clubBatches = array_chunk(array_values($clubs), $batchSize);

            foreach ($clubBatches as $clubBatch) {
                if (! $this->shouldContinue()) {
                    break;
                }

                try {
                    $this->scrapeClubBatch($cookieJar, $overviewUrl, $period, $clubBatch);
                } catch (\Exception $e) {
                    $this->warning("Batch failed: " . $e->getMessage());
                    $this->run->incrementFailed();
                }
            }
        }
    }

    /**
     * Scrape a batch of clubs for a period in parallel using Http::pool().
     */
    protected function scrapeClubBatch(
        CookieJar $cookieJar,
        string $overviewUrl,
        array $period,
        array $clubs
    ): void {
        $periodValue = $period['value'];
        $periodName  = $period['text'];

        // Key clubs by name for result mapping
        $clubsByName = [];
        foreach ($clubs as $club) {
            $clubsByName[$club['text']] = $club;
        }

        // Send all club requests concurrently via pool
        $responses = Http::pool(function ($pool) use ($cookieJar, $overviewUrl, $periodValue, $clubs) {
            $requests = [];
            foreach ($clubs as $club) {
                $requests[$club['text']] = $pool->as($club['text'])
                    ->withOptions(['cookies' => $cookieJar])
                    ->timeout(30)
                    ->asForm()
                    ->post($overviewUrl, [
                        'periode'      => $periodValue,
                        'klubbid'      => $club['value'],
                        'kjonn'        => '',
                        'klasse'       => '',
                        'lisenstypeid' => '',
                    ]);
            }
            return $requests;
        });

        foreach ($responses as $clubName => $response) {
            try {
                if ($response instanceof \Throwable) {
                    throw $response;
                }
                $players = $this->parsePlayerTable($response->body());

                if (empty($players)) {
                    $this->info("No players: {$periodName} - {$clubName}");
                    continue;
                }

                $rows = [];
                $now  = now();
                foreach ($players as $player) {
                    if (empty(trim($player['surname'])) && empty(trim($player['firstName']))) {
                        continue;
                    }
                    $rows[] = [
                        'scraper_run_id' => $this->run->id,
                        'period'         => $periodName,
                        'club_name'      => trim($clubName),
                        'surname'        => trim($player['surname']),
                        'first_name'     => trim($player['firstName']),
                        'sex'            => trim($player['sex']),
                        'date_of_birth'  => trim($player['dateOfBirth']),
                        'license_type'   => trim($player['licenseType']),
                        'player_class'   => trim($player['playerClass']),
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }

                if (! empty($rows)) {
                    DB::table('scraped_players')->insert($rows);
                    $this->run->incrementScraped(count($rows));
                    $this->info("Scraped {$periodName} - {$clubName}: " . count($rows) . " players");
                }
            } catch (\Exception $e) {
                $this->warning("Failed to scrape {$clubName}: " . $e->getMessage());
                $this->run->incrementFailed();
            }
        }
    }

    /**
     * Parse period and club <select> dropdowns from the overview page HTML.
     * Returns [$periods, $clubs] — each an array of ['value' => ..., 'text' => ...].
     */
    protected function parseDropdowns(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

        $periods = $this->extractSelectOptions($dom, 'periode');
        $clubs   = $this->extractSelectOptions($dom, 'klubbid');

        $this->info("Parsed dropdowns: " . count($periods) . " periods, " . count($clubs) . " clubs");

        return [$periods, $clubs];
    }

    /**
     * Extract all <option> value/text pairs from a <select id="$id">.
     */
    protected function extractSelectOptions(\DOMDocument $dom, string $selectId): array
    {
        $options = [];
        $xpath   = new \DOMXPath($dom);
        $nodes   = $xpath->query("//select[@id='{$selectId}']/option");

        if (! $nodes) {
            return $options;
        }

        foreach ($nodes as $node) {
            $options[] = [
                'value' => $node->getAttribute('value'),
                'text'  => trim($node->textContent),
            ];
        }

        return $options;
    }

    /**
     * Parse player rows from the .table-condensed table in the response HTML.
     * Returns array of player arrays with keys: surname, firstName, sex, dateOfBirth,
     * licenseType, playerClass.
     */
    protected function parsePlayerTable(string $html): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

        $xpath   = new \DOMXPath($dom);
        $rows    = $xpath->query("//table[contains(@class,'table-condensed')]//tr");
        $players = [];

        if (! $rows) {
            return $players;
        }

        foreach ($rows as $row) {
            $cells = $xpath->query('td', $row);
            if (! $cells || $cells->length === 0) {
                continue; // skip header rows
            }

            $get = fn(int $i) => $cells->length > $i
                ? trim($cells->item($i)->textContent)
                : '';

            $surname   = $get(1);
            $firstName = $get(2);

            if ($surname === '' && $firstName === '') {
                continue;
            }

            $players[] = [
                'surname'     => $surname,
                'firstName'   => $firstName,
                'sex'         => $get(3),
                'dateOfBirth' => $get(4),
                'licenseType' => $get(5),
                'playerClass' => $get(6),
            ];
        }

        return $players;
    }
}
