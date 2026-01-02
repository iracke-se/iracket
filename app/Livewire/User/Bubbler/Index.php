<?php

namespace App\Livewire\User\Bubbler;

use App\Models\ClubMonthlyRanking;
use App\Models\MonthlyRanking;
use Carbon\Carbon;
use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'ladies';
    public int $selectedYear;
    public int $selectedMonth;

    public function mount(): void
    {
        // Default to the most recent month with actual data
        $latestRanking = MonthlyRanking::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();

        if ($latestRanking) {
            $this->selectedYear = $latestRanking->year;
            $this->selectedMonth = $latestRanking->month;
        } else {
            // Fallback to current year/month if no data exists
            $this->selectedYear = now()->year;
            $this->selectedMonth = now()->month;
        }
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function updatedSelectedYear(): void
    {
        // When year changes, reset to current month if available
        $this->validatePeriod();
    }

    public function updatedSelectedMonth(): void
    {
        $this->validatePeriod();
    }

    protected function validatePeriod(): void
    {
        // Ensure the selected period doesn't exceed current month
        $now = now();
        $selected = Carbon::create($this->selectedYear, $this->selectedMonth);

        if ($selected->isFuture()) {
            $this->selectedYear = $now->year;
            $this->selectedMonth = $now->month;
        }
    }

    public function getAvailableYearsProperty(): array
    {
        // Get distinct years that have rankings
        $years = MonthlyRanking::selectRaw('DISTINCT year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Ensure current year is included
        if (!in_array(now()->year, $years)) {
            $years[] = now()->year;
            rsort($years);
        }

        return $years;
    }

    public function getAvailableMonthsForYearProperty(): array
    {
        // Get months that have rankings for the selected year
        $months = MonthlyRanking::selectRaw('DISTINCT month')
            ->where('year', $this->selectedYear)
            ->orderBy('month', 'desc')
            ->pluck('month')
            ->toArray();

        // If no months found, return current month
        if (empty($months) && $this->selectedYear == now()->year) {
            return [now()->month];
        }

        return $months ?: range(1, 12);
    }

    public function render()
    {
        $ladiesRankings = MonthlyRanking::where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth)
            ->whereHas('user', function ($query) {
                $query->where('gender', 'female');
            })
            ->with('user.club')
            ->orderBy('rank')
            ->get()
            ->filter(fn ($ranking) => $ranking->user !== null);

        $menRankings = MonthlyRanking::where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth)
            ->whereHas('user', function ($query) {
                $query->where('gender', 'male');
            })
            ->with('user.club')
            ->orderBy('rank')
            ->get()
            ->filter(fn ($ranking) => $ranking->user !== null);

        $clubRankings = ClubMonthlyRanking::where('year', $this->selectedYear)
            ->where('month', $this->selectedMonth)
            ->whereHas('club')
            ->with('club')
            ->orderBy('rank')
            ->get()
            ->filter(fn ($ranking) => $ranking->club !== null);

        // Load member counts for clubs
        $clubRankings->each(function ($ranking) {
            $ranking->club->loadCount('members');
        });

        return view('livewire.user.bubbler.index', [
            'ladiesRankings' => $ladiesRankings,
            'menRankings' => $menRankings,
            'clubRankings' => $clubRankings,
        ])->layout('components.layouts.app');
    }
}
