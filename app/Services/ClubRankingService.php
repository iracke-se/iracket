<?php

namespace App\Services;

use App\Models\Club;
use App\Models\ClubMonthlyRanking;
use App\Models\MonthlyRanking;
use App\Models\Scraper\ScraperRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ClubRankingService
{
    protected array $stats = [
        'clubs_processed' => 0,
        'rankings_created' => 0,
        'rankings_updated' => 0,
    ];

    /**
     * Aggregate club rankings from player points
     */
    public function aggregateClubRankings(?Carbon $period = null, ?ScraperRun $run = null): array
    {
        $this->resetStats();

        $period = $period ?? now();
        $year = $period->year;
        $month = $period->month;

        if ($run) {
            $run->log('info', "Starting club rankings aggregation for {$year}-{$month}");
        }

        // Get all clubs with active players
        $clubs = Club::whereHas('members', function ($query) use ($year, $month) {
            $query->whereHas('monthlyRankings', function ($q) use ($year, $month) {
                $q->where('year', $year)
                  ->where('month', $month);
            });
        })->get();

        if ($run) {
            $run->log('info', "Found {$clubs->count()} clubs with ranked players");
        }

        $clubRankings = [];

        // Calculate total points for each club
        foreach ($clubs as $club) {
            $totalPoints = MonthlyRanking::whereHas('user', function ($query) use ($club) {
                $query->where('club_id', $club->id);
            })
            ->where('year', $year)
            ->where('month', $month)
            ->sum('points');

            $clubRankings[] = [
                'club_id' => $club->id,
                'total_points' => $totalPoints,
            ];

            $this->stats['clubs_processed']++;
        }

        // Sort by total points descending
        usort($clubRankings, fn($a, $b) => $b['total_points'] <=> $a['total_points']);

        // Assign ranks and save
        foreach ($clubRankings as $index => $data) {
            $ranking = ClubMonthlyRanking::updateOrCreate(
                [
                    'club_id' => $data['club_id'],
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'rank' => $index + 1,
                    'total_points' => $data['total_points'],
                ]
            );

            if ($ranking->wasRecentlyCreated) {
                $this->stats['rankings_created']++;
            } else {
                $this->stats['rankings_updated']++;
            }
        }

        if ($run) {
            $run->log('info', "Club rankings aggregation completed: {$this->stats['clubs_processed']} clubs processed, {$this->stats['rankings_created']} created, {$this->stats['rankings_updated']} updated");
        }

        return $this->stats;
    }

    protected function resetStats(): void
    {
        $this->stats = [
            'clubs_processed' => 0,
            'rankings_created' => 0,
            'rankings_updated' => 0,
        ];
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
