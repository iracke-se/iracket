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

        // Fetch transitions page using the transitions tab URL
        $initJs = <<<JS
        (async function() {
            try {
                // Fetch the transitions page (transitions tab on player list)
                const response = await fetch('https://www.profixio.com/fx/lisens/public_overgang.php', {
                    credentials: 'include'
                });
                const html = await response.text();

                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Get periods from dropdown
                var periods = [];
                var periodsSelect = doc.getElementById('periode');
                if (periodsSelect) {
                    for (let i = 0; i < periodsSelect.options.length; i++) {
                        periods.push({
                            value: periodsSelect.options[i].value,
                            text: periodsSelect.options[i].innerHTML.trim()
                        });
                    }
                }

                return JSON.stringify({
                    periods: periods,
                    pageTitle: doc.title
                });
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $initJson = $this->withRetry(function () use ($browser, $initJs) {
            return $browser->evaluate($initJs);
        }, 'Fetch transitions page');

        $initData = json_decode($initJson, true);

        if (isset($initData['error'])) {
            $this->error("Failed to fetch data: " . $initData['error']);
            return;
        }

        $this->info("Page title: " . ($initData['pageTitle'] ?? 'unknown'));

        $periods = $initData['periods'] ?? [];

        // Filter out "all" option
        $periods = array_filter($periods, fn($p) => $p['value'] !== '0');

        // Apply limits for testing
        $limitPeriods = $this->getParameter('limit_periods');
        if ($limitPeriods && $limitPeriods > 0) {
            $periods = array_slice(array_values($periods), 0, $limitPeriods);
        }

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
        $periodValue = $period['value'];
        $periodName = $period['text'];

        // Fetch transitions data using POST with form data
        $fetchTransitionsJs = <<<JS
        (async function() {
            try {
                const formData = new URLSearchParams();
                formData.append('periode', '{$periodValue}');

                const response = await fetch('https://www.profixio.com/fx/lisens/public_overgang.php', {
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

                // Get transition data from table
                const rows = doc.querySelectorAll('#main-col > div.maincontent > form > table > tbody > tr');
                let transitions = [];

                for (let i = 0; i < rows.length; i++) {
                    const cells = rows[i].querySelectorAll('td');
                    if (cells.length >= 6) {
                        transitions.push({
                            surname: cells[0]?.innerText.trim() || '',
                            firstName: cells[1]?.innerText.trim() || '',
                            born: cells[2]?.innerText.trim() || '',
                            from: cells[3]?.innerText.trim() || '',
                            to: cells[4]?.innerText.trim() || '',
                            completionDate: cells[5]?.innerText.trim() || ''
                        });
                    }
                }

                return JSON.stringify(transitions);
            } catch (e) {
                return JSON.stringify({ error: e.message });
            }
        })();
        JS;

        $transitionsJson = $this->withRetry(function () use ($browser, $fetchTransitionsJs) {
            return $browser->evaluate($fetchTransitionsJs);
        }, "Get transitions for {$periodName}");

        $transitions = json_decode($transitionsJson, true) ?? [];

        if (isset($transitions['error'])) {
            $this->warning("Error fetching transitions: " . $transitions['error']);
            return;
        }

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
                'period' => $periodName,
                'surname' => trim($transition['surname'] ?? ''),
                'first_name' => trim($transition['firstName'] ?? ''),
                'born' => trim($transition['born'] ?? ''),
                'from_club' => trim($transition['from'] ?? ''),
                'to_club' => trim($transition['to'] ?? ''),
                'completion_date' => trim($transition['completionDate'] ?? ''),
            ]);

            $this->run->incrementScraped();
        }

        $this->info("Scraped {$periodName}: " . count($transitions) . " transitions");
    }
}
