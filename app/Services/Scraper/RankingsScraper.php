<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScraperRun;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\DB;

class RankingsScraper extends BaseScraperService
{
    protected Browsershot $browser;
    protected array $options = [];

    public function getType(): string
    {
        return ScraperRun::TYPE_RANKINGS;
    }

    protected function execute(): void
    {
        $year = $this->getParameter('year') ?? date('Y');
        $month = $this->getParameter('month') ?? date('m');
        $gender = $this->getParameter('gender', 'm'); // 'm' = men, 'k' = women

        // Store options for helper methods
        $this->options = [
            'year' => $year,
            'month' => $month,
            'gender' => $gender,
            'limit_players' => $this->getParameter('limit_players'),
        ];

        $this->info("Starting rankings scraper with popup interaction for {$year}-{$month}, gender: {$gender}");

        // Step 1: Get the rid (ranking ID) for the target month
        $rid = $this->getRidForMonth($year, $month, $gender);
        $this->info("Found rid={$rid} for {$year}-{$month}");

        // Step 2: Navigate directly to rankings page with rid parameter
        $url = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender={$gender}&rid={$rid}";
        $this->browser = Browsershot::url($url)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle()
            ->noSandbox();

        if (config('scraper.browser.chrome_path')) {
            $this->browser->setChromePath(config('scraper.browser.chrome_path'));
        }

        $this->delay('page_load');

        // Step 3: Get all players from rankings table
        $players = $this->getPlayersFromRankingsTable();
        $this->info("Found " . count($players) . " players in rankings table");

        if (empty($players)) {
            $this->warning("No players found in rankings table");
            return;
        }

        // Step 4: For each player, click name and scrape ranking history + matches
        $totalRankings = 0;
        $totalMatches = 0;
        $processedCount = 0;

        foreach ($players as $index => $player) {
            if (!$this->shouldContinue()) {
                break;
            }

            $this->info("Processing player " . ($index + 1) . "/" . count($players) . ": {$player['name']}");

            try {
                // Click player name to open popup
                $rankingData = $this->scrapePlayerRankingPopup($player);
                $totalRankings += count($rankingData['rankings']);

                // For the current month's ranking, click points to get matches
                $matches = $this->scrapePlayerMatchesFromPopup($player, $year, $month);
                $totalMatches += count($matches);

                // Close popup
                $this->closePopup();
                $this->delay('page_load');

                $processedCount++;

            } catch (\Exception $e) {
                $this->warning("Error processing player {$player['name']}: " . $e->getMessage());
                $this->run->incrementFailed();

                // Try to close popup in case it's stuck open
                try {
                    $this->closePopup();
                } catch (\Exception $closeError) {
                    // Ignore close errors
                }

                continue;
            }
        }

        $this->info("Rankings scraper completed: {$processedCount} players processed, {$totalRankings} rankings scraped, {$totalMatches} matches scraped");
    }

    /**
     * Get rid (ranking ID) for a specific month
     */
    protected function getRidForMonth(string $year, string $month, string $gender): string
    {
        $targetDate = "{$year}." . str_pad($month, 2, '0', STR_PAD_LEFT) . ".01";

        // Navigate to page without rid to get dropdown options
        $tempUrl = "https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_list.php?gender={$gender}";
        $tempBrowser = Browsershot::url($tempUrl)
            ->setNodeBinary(config('scraper.browser.node_binary'))
            ->setNpmBinary(config('scraper.browser.npm_binary'))
            ->timeout(config('scraper.browser.timeout'))
            ->waitUntilNetworkIdle()
            ->noSandbox();

        if (config('scraper.browser.chrome_path')) {
            $tempBrowser->setChromePath(config('scraper.browser.chrome_path'));
        }

        $rid = $tempBrowser->evaluate("
            (function() {
                const select = document.querySelector('select[name=\"rid\"]');
                if (!select) throw new Error('Month dropdown not found');

                const options = Array.from(select.options);
                const targetOption = options.find(opt => opt.text.startsWith('{$targetDate}'));

                if (!targetOption) {
                    throw new Error('Month {$targetDate} not found in dropdown');
                }

                return targetOption.value;
            })()
        ");

        return $rid;
    }

    /**
     * Select month from dropdown (DEPRECATED - use getRidForMonth and direct navigation instead)
     */
    protected function selectMonthFromDropdown(string $year, string $month): void
    {
        $targetDate = "{$year}." . str_pad($month, 2, '0', STR_PAD_LEFT) . ".01";

        $this->info("Selecting month: {$targetDate}");

        // Use JavaScript to select the option
        $selected = $this->withRetry(function () use ($targetDate) {
            return $this->browser->evaluate("
                (function() {
                    const select = document.querySelector('select[name=\"rid\"]');
                    if (!select) throw new Error('Month dropdown not found');

                    const options = Array.from(select.options);
                    const targetOption = options.find(opt => opt.text.startsWith('{$targetDate}'));

                    if (!targetOption) {
                        throw new Error('Month {$targetDate} not found in dropdown');
                    }

                    select.value = targetOption.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));

                    return targetOption.text;
                })()
            ");
        }, "Select month: {$targetDate}");

        $this->info("Selected month: {$selected}");

        // Wait longer for page to fully reload after dropdown change
        sleep(3); // Give page time to reload

        // Verify page loaded by checking for rankings table
        $this->withRetry(function () {
            $html = $this->browser->bodyHtml();
            if (strpos($html, 'Rankingpoäng') === false) {
                throw new \Exception('Rankings table not loaded yet');
            }
            return true;
        }, 'Wait for page reload');
    }

    /**
     * Get players from rankings table
     */
    protected function getPlayersFromRankingsTable(): array
    {
        $html = $this->withRetry(function () {
            return $this->browser->bodyHtml();
        }, 'Get page HTML');

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);

        $players = [];

        // Find the rankings table - look for rows with 7 cells (data rows)
        $rows = $xpath->query("//table//tr[count(td)=7]");

        foreach ($rows as $row) {
            $cells = $xpath->query(".//td", $row);

            // Expected structure: Placering | Change | Namn | Born | Club | Points | Points Change
            $position = trim($cells->item(0)->textContent); // e.g., "WR05 1"
            $nameCell = $cells->item(2); // Name is in 3rd column
            $pointsCell = $cells->item(5); // Points in 6th column

            // Extract player name from span with class "rml_poeng"
            $nameSpan = $xpath->query(".//span[@class='rml_poeng']", $nameCell)->item(0);
            if (!$nameSpan) {
                continue;
            }

            $playerName = trim($nameSpan->textContent);
            $spanId = $nameSpan->getAttribute('id'); // Format: rml:14450:391:0

            // Extract player ID from span id (e.g., id="rml:14450:391:0" -> 14450)
            if (preg_match("/rml:(\d+):/", $spanId, $matches)) {
                $playerId = $matches[1];

                // Extract numeric position (e.g., "WR05 1" -> 1)
                preg_match("/\d+$/", $position, $posMatches);
                $numericPosition = $posMatches[0] ?? 0;

                $players[] = [
                    'profixio_id' => $playerId,
                    'name' => $playerName,
                    'position' => (int)$numericPosition,
                    'points' => (int)str_replace([' ', '.', ','], '', trim($pointsCell->textContent)),
                    'selector' => "span.rml_poeng[id*='rml:{$playerId}:']",
                    'span_id' => $spanId,
                ];
            }
        }

        // Apply limit if set in options
        if (isset($this->options['limit_players']) && $this->options['limit_players'] > 0) {
            $players = array_slice($players, 0, $this->options['limit_players']);
        }

        return $players;
    }

    /**
     * Scrape player ranking popup
     */
    protected function scrapePlayerRankingPopup(array $player): array
    {
        // Click player name to open popup using player ID
        $playerId = $player['profixio_id'];

        // Click and wait for popup in a single evaluation
        // This ensures the popup state persists within the same browser context
        $this->withRetry(function () use ($playerId, $player) {
            return $this->browser->evaluate("
                (function() {
                    // Find and click span
                    const spans = document.querySelectorAll('span.rml_poeng');
                    let targetSpan = null;
                    for (const span of spans) {
                        if (span.id && span.id.includes('rml:{$playerId}:')) {
                            targetSpan = span;
                            break;
                        }
                    }

                    if (!targetSpan) {
                        throw new Error('Player span not found for ID: {$playerId}');
                    }

                    targetSpan.click();

                    // Wait for popup to appear (synchronous busy-wait)
                    const startTime = Date.now();
                    while (Date.now() - startTime < 5000) {
                        const popup = document.querySelector('#multipurpose');
                        if (popup && popup.style.visibility === 'visible') {
                            return true;
                        }
                        // Busy wait - no async needed
                    }

                    throw new Error('Popup did not appear within 5 seconds');
                })()
            ");
        }, "Click player and wait for popup: {$player['name']}");

        // Get popup HTML
        $popupHtml = $this->withRetry(function () {
            return $this->browser->evaluate("
                (function() {
                    const popup = document.querySelector('#multipurpose');
                    if (popup && popup.style.visibility === 'visible') {
                        return popup.innerHTML;
                    }
                    throw new Error('Popup not visible');
                })()
            ");
        }, "Get popup content");

        // Parse popup content
        $dom = new \DOMDocument();
        @$dom->loadHTML($popupHtml);
        $xpath = new \DOMXPath($dom);

        $rankings = [];

        // Find ranking history table
        // Structure: <tr><td>Datum</td><td>Poäng</td><td>Placering</td><td>Poängskillnad</td></tr>
        $rows = $xpath->query("//table//tr[td]");

        foreach ($rows as $row) {
            $cells = $xpath->query(".//td", $row);
            if ($cells->length < 4) {
                continue;
            }

            $date = trim($cells->item(0)->textContent);
            $pointsCell = $cells->item(1);
            $position = trim($cells->item(2)->textContent);
            $pointsDiff = trim($cells->item(3)->textContent);

            // Extract points value and rmld ID
            $pointsSpan = $xpath->query(".//span[@class='rmld_poeng']", $pointsCell)->item(0);
            if (!$pointsSpan) {
                continue;
            }

            $points = trim($pointsSpan->textContent);
            $rmldId = $pointsSpan->getAttribute('id'); // e.g., "rmld:14450:391:0"

            $rankings[] = [
                'profixio_player_id' => $player['profixio_id'],
                'date' => $date,
                'points' => (int)str_replace([' ', '.', ','], '', $points),
                'position' => (int)$position,
                'points_diff' => $pointsDiff,
                'rmld_id' => $rmldId,
            ];
        }

        if (!empty($rankings)) {
            $this->saveRankingsToDatabase($rankings);
            $this->info("Found " . count($rankings) . " rankings for {$player['name']}");
        }

        return [
            'player' => $player,
            'rankings' => $rankings,
        ];
    }

    /**
     * Scrape matches from nested popup
     */
    protected function scrapePlayerMatchesFromPopup(array $player, string $year, string $month): array
    {
        // Player popup should already be open from previous step
        // Find the current month's ranking row and click on the points value

        $targetDate = "{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT);

        // Use JavaScript to find and click the points span for the target month
        $clicked = $this->browser->evaluate("
            (function() {
                const rows = Array.from(document.querySelectorAll('#multipurpose table tr'));

                for (const row of rows) {
                    const dateCell = row.querySelector('td:first-child');
                    if (!dateCell) continue;

                    const dateText = dateCell.textContent.trim();
                    if (dateText.startsWith('{$targetDate}')) {
                        const pointsSpan = row.querySelector('span.rmld_poeng');
                        if (pointsSpan) {
                            pointsSpan.click();
                            return true;
                        }
                    }
                }

                return false;
            })()
        ");

        if (!$clicked) {
            $this->info("No points to click for {$targetDate}, player may have no matches");
            return [];
        }

        $this->delay('page_load'); // Wait for nested popup to load

        // Get nested popup HTML (should now show matches)
        $popupHtml = $this->withRetry(function () {
            return $this->browser->evaluate("
                (function() {
                    const popup = document.querySelector('#multipurpose');
                    if (popup && popup.style.visibility === 'visible') {
                        return popup.innerHTML;
                    }
                    throw new Error('Popup not visible');
                })()
            ");
        }, "Get matches popup content");

        // Parse matches
        $dom = new \DOMDocument();
        @$dom->loadHTML($popupHtml);
        $xpath = new \DOMXPath($dom);

        $matches = [];

        // Structure: <tr><td>W/L</td><td>Opponent Name</td><td>Opponent Points</td><td>Match Points</td><td>Date</td></tr>
        $rows = $xpath->query("//table//tr[td]");

        foreach ($rows as $row) {
            $cells = $xpath->query(".//td", $row);
            if ($cells->length < 5) {
                continue;
            }

            $result = trim($cells->item(0)->textContent); // W or L
            $opponentName = trim($cells->item(1)->textContent);
            $opponentPoints = trim($cells->item(2)->textContent);
            $matchPoints = trim($cells->item(3)->textContent);
            $matchDate = trim($cells->item(4)->textContent);

            // Skip header rows
            if ($result === 'W' || $result === 'L') {
                $matches[] = [
                    'profixio_player_id' => $player['profixio_id'],
                    'player_name' => $player['name'],
                    'result' => $result, // 'W' or 'L'
                    'opponent_name' => $opponentName,
                    'opponent_points' => (int)str_replace(['+', ' ', '.', ','], '', $opponentPoints),
                    'match_points' => (int)str_replace(['+', ' ', '.', ','], '', $matchPoints), // Keep negative sign
                    'match_date' => $matchDate,
                    'scraped_month' => $targetDate,
                ];
            }
        }

        if (!empty($matches)) {
            $this->saveMatchesToDatabase($matches);
            $this->info("Found " . count($matches) . " matches for {$player['name']}");
        }

        // Click back button to return to ranking history popup
        $this->browser->evaluate("
            (function() {
                const buttons = Array.from(document.querySelectorAll('button'));
                const backButton = buttons.find(btn => btn.textContent.includes('Tilbake'));
                if (backButton) {
                    backButton.click();
                    return true;
                }
                return false;
            })()
        ");

        $this->delay('page_load');

        return $matches;
    }

    /**
     * Close popup
     */
    protected function closePopup(): void
    {
        $this->browser->evaluate("
            (function() {
                const buttons = Array.from(document.querySelectorAll('button'));
                const closeButton = buttons.find(btn => btn.textContent.includes('Stäng'));
                if (closeButton) {
                    closeButton.click();
                    return true;
                }
                return false;
            })()
        ");

        $this->delay('page_load');
    }

    /**
     * Save rankings to database
     */
    protected function saveRankingsToDatabase(array $rankings): void
    {
        foreach ($rankings as $ranking) {
            DB::table('scraped_rankings')->insert([
                'scraper_run_id' => $this->run->id,
                'profixio_player_id' => $ranking['profixio_player_id'],
                'ranking_date' => $ranking['date'],
                'points' => $ranking['points'],
                'position' => $ranking['position'],
                'points_diff' => $ranking['points_diff'],
                'rmld_id' => $ranking['rmld_id'],
                'is_synced' => false,
                // Legacy fields for backward compatibility
                'period' => $this->options['year'] . '-' . str_pad($this->options['month'], 2, '0', STR_PAD_LEFT),
                'division' => '',
                'gender' => $this->options['gender'] === 'm' ? 'male' : 'female',
                'position_change' => '',
                'name' => '',
                'born' => '',
                'club' => '',
                'points_change' => $ranking['points_diff'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->run->incrementScraped();
        }
    }

    /**
     * Save matches to database
     */
    protected function saveMatchesToDatabase(array $matches): void
    {
        foreach ($matches as $match) {
            DB::table('scraped_matches')->insert([
                'scraper_run_id' => $this->run->id,
                'profixio_player_id' => $match['profixio_player_id'],
                'player_name' => $match['player_name'],
                'opponent_name' => $match['opponent_name'],
                'result' => $match['result'],
                'opponent_points' => $match['opponent_points'],
                'match_points' => $match['match_points'],
                'match_date' => $match['match_date'],
                'scraped_month' => $match['scraped_month'],
                'is_synced' => false,
                // Legacy fields for backward compatibility
                'source' => 'rankings_popup',
                'period' => $this->options['year'] . '-' . str_pad($this->options['month'], 2, '0', STR_PAD_LEFT),
                'division' => '',
                'series_name' => '',
                'team1_name' => '',
                'team2_name' => '',
                'player1_name' => $match['player_name'],
                'player2_name' => $match['opponent_name'],
                'score' => '',
                'sets' => null,
                'played_at' => $match['match_date'],
                'winner' => $match['result'] === 'W' ? $match['player_name'] : $match['opponent_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->run->incrementScraped();
        }
    }
}
