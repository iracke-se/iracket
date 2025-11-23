<?php

namespace App\Services\Scraper;

use App\Models\Scraper\ScrapedMatch;
use App\Models\Scraper\ScrapedPlayer;
use App\Models\Scraper\ScrapedRanking;
use App\Models\Scraper\ScrapedStanding;
use App\Models\Scraper\ScrapedTransition;
use App\Models\Scraper\ScraperRun;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ScraperExporter
{
    protected array $runIds = [];
    protected string $exportPath;

    public function __construct()
    {
        $date = now()->format('Y-m-d');
        $time = now()->format('H-i-s');
        $this->exportPath = storage_path("scraper/{$date}");
    }

    /**
     * Run all scrapers and export results to JSON
     */
    public function runAllAndExport(array $parameters = []): string
    {
        // Ensure directory exists
        if (!File::isDirectory($this->exportPath)) {
            File::makeDirectory($this->exportPath, 0755, true);
        }

        $results = [
            'meta' => [
                'exported_at' => now()->toIso8601String(),
                'scrapers' => [],
            ],
            'data' => [
                'rankings' => [],
                'players' => [],
                'transitions' => [],
                'matches' => [],
                'standings' => [],
            ],
        ];

        // Run Rankings Scraper
        $this->runScraper(
            RankingsScraper::class,
            'rankings',
            $parameters['rankings'] ?? [],
            $results
        );

        // Run Players Scraper
        $this->runScraper(
            PlayerListScraper::class,
            'players',
            $parameters['players'] ?? [],
            $results
        );

        // Run Transitions Scraper
        $this->runScraper(
            TransitionsScraper::class,
            'transitions',
            $parameters['transitions'] ?? [],
            $results
        );

        // Run Live Center Scraper
        $this->runScraper(
            LiveCenterScraper::class,
            'matches',
            $parameters['live_center'] ?? [],
            $results
        );

        // Run Series Scraper
        $this->runScraper(
            SeriesScraper::class,
            'standings',
            $parameters['series'] ?? [],
            $results
        );

        // Collect all data
        $results['data']['rankings'] = $this->collectRankings();
        $results['data']['players'] = $this->collectPlayers();
        $results['data']['transitions'] = $this->collectTransitions();
        $results['data']['matches'] = $this->collectMatches();
        $results['data']['standings'] = $this->collectStandings();

        // Add summary
        $results['meta']['summary'] = [
            'total_rankings' => count($results['data']['rankings']),
            'total_players' => count($results['data']['players']),
            'total_transitions' => count($results['data']['transitions']),
            'total_matches' => count($results['data']['matches']),
            'total_standings' => count($results['data']['standings']),
        ];

        // Save to JSON file
        $filename = 'full_scrape_' . now()->format('H-i-s') . '.json';
        $filepath = $this->exportPath . '/' . $filename;

        File::put($filepath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        Log::channel('scraper')->info('Scraper export completed', [
            'path' => $filepath,
            'summary' => $results['meta']['summary'],
        ]);

        return $filepath;
    }

    protected function runScraper(string $scraperClass, string $type, array $parameters, array &$results): void
    {
        try {
            $scraper = app($scraperClass);
            $run = $scraper->scrape($parameters);

            $this->runIds[$type] = $run->id;

            $results['meta']['scrapers'][$type] = [
                'run_id' => $run->id,
                'status' => $run->status,
                'items_scraped' => $run->items_scraped,
                'items_failed' => $run->items_failed,
                'started_at' => $run->started_at?->toIso8601String(),
                'completed_at' => $run->completed_at?->toIso8601String(),
            ];

            Log::channel('scraper')->info("Completed {$type} scraper", [
                'run_id' => $run->id,
                'items' => $run->items_scraped,
            ]);
        } catch (\Exception $e) {
            $results['meta']['scrapers'][$type] = [
                'error' => $e->getMessage(),
            ];

            Log::channel('scraper')->error("Failed {$type} scraper", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function collectRankings(): array
    {
        if (!isset($this->runIds['rankings'])) {
            return [];
        }

        return ScrapedRanking::where('scraper_run_id', $this->runIds['rankings'])
            ->get()
            ->map(fn($r) => [
                'period' => $r->period,
                'division' => $r->division,
                'gender' => $r->gender,
                'position' => $r->position,
                'position_change' => $r->position_change,
                'name' => $r->name,
                'born' => $r->born,
                'club' => $r->club,
                'points' => $r->points,
                'points_change' => $r->points_change,
            ])
            ->toArray();
    }

    protected function collectPlayers(): array
    {
        if (!isset($this->runIds['players'])) {
            return [];
        }

        return ScrapedPlayer::where('scraper_run_id', $this->runIds['players'])
            ->get()
            ->map(fn($p) => [
                'period' => $p->period,
                'club_name' => $p->club_name,
                'surname' => $p->surname,
                'first_name' => $p->first_name,
                'sex' => $p->sex,
                'date_of_birth' => $p->date_of_birth,
                'license_type' => $p->license_type,
                'player_class' => $p->player_class,
            ])
            ->toArray();
    }

    protected function collectTransitions(): array
    {
        if (!isset($this->runIds['transitions'])) {
            return [];
        }

        return ScrapedTransition::where('scraper_run_id', $this->runIds['transitions'])
            ->get()
            ->map(fn($t) => [
                'period' => $t->period,
                'surname' => $t->surname,
                'first_name' => $t->first_name,
                'born' => $t->born,
                'from_club' => $t->from_club,
                'to_club' => $t->to_club,
                'completion_date' => $t->completion_date,
            ])
            ->toArray();
    }

    protected function collectMatches(): array
    {
        if (!isset($this->runIds['matches'])) {
            return [];
        }

        return ScrapedMatch::where('scraper_run_id', $this->runIds['matches'])
            ->get()
            ->map(fn($m) => [
                'source' => $m->source,
                'period' => $m->period,
                'division' => $m->division,
                'series_name' => $m->series_name,
                'team1_name' => $m->team1_name,
                'team2_name' => $m->team2_name,
                'player1_name' => $m->player1_name,
                'player2_name' => $m->player2_name,
                'score' => $m->score,
                'sets' => $m->sets,
                'played_at' => $m->played_at,
                'winner' => $m->winner,
            ])
            ->toArray();
    }

    protected function collectStandings(): array
    {
        if (!isset($this->runIds['standings'])) {
            return [];
        }

        return ScrapedStanding::where('scraper_run_id', $this->runIds['standings'])
            ->get()
            ->map(fn($s) => [
                'period' => $s->period,
                'series_name' => $s->series_name,
                'session_name' => $s->session_name,
                'position' => $s->position,
                'team_name' => $s->team_name,
                'matches_played' => $s->matches_played,
                'wins' => $s->wins,
                'losses' => $s->losses,
                'draws' => $s->draws,
                'points' => $s->points,
                'goal_difference' => $s->goal_difference,
            ])
            ->toArray();
    }
}
