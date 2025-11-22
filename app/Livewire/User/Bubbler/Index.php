<?php

namespace App\Livewire\User\Bubbler;

use App\Models\ClubMonthlyRanking;
use App\Models\MonthlyRanking;
use Livewire\Component;

class Index extends Component
{
    public string $activeTab = 'ladies';

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $ladiesRankings = MonthlyRanking::where('year', now()->year)
            ->where('month', now()->month)
            ->whereHas('user', function ($query) {
                $query->where('gender', 'female');
            })
            ->with('user.club')
            ->orderBy('rank')
            ->get();

        $menRankings = MonthlyRanking::where('year', now()->year)
            ->where('month', now()->month)
            ->whereHas('user', function ($query) {
                $query->where('gender', 'male');
            })
            ->with('user.club')
            ->orderBy('rank')
            ->get();

        $clubRankings = ClubMonthlyRanking::where('year', now()->year)
            ->where('month', now()->month)
            ->with('club')
            ->orderBy('rank')
            ->get();

        return view('livewire.user.bubbler.index', [
            'ladiesRankings' => $ladiesRankings,
            'menRankings' => $menRankings,
            'clubRankings' => $clubRankings,
        ])->layout('components.layouts.app');
    }
}
