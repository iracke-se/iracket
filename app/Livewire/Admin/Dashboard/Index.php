<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Club;
use App\Models\GameMatch;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        // Total stats
        $totalUsers = User::count();
        $totalClubs = Club::count();
        $totalMatches = GameMatch::count();

        // This month stats
        $usersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $matchesThisMonth = GameMatch::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Last month for comparison
        $usersLastMonth = User::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $matchesLastMonth = GameMatch::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        // Growth calculations
        $userGrowth = $usersLastMonth > 0
            ? round((($usersThisMonth - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : ($usersThisMonth > 0 ? 100 : 0);

        $matchGrowth = $matchesLastMonth > 0
            ? round((($matchesThisMonth - $matchesLastMonth) / $matchesLastMonth) * 100, 1)
            : ($matchesThisMonth > 0 ? 100 : 0);

        // Gender distribution
        $genderDistribution = [
            'male' => User::where('gender', 'male')->count(),
            'female' => User::where('gender', 'female')->count(),
            'other' => User::where('gender', 'other')->count(),
            'unknown' => User::whereNull('gender')->orWhere('gender', '')->orWhere('gender', 'prefer_not_to_say')->count(),
        ];

        // Monthly user registrations (last 6 months)
        $monthlyUsers = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = User::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            $monthlyUsers->push([
                'month' => $date->format('M'),
                'count' => $count,
            ]);
        }

        // Monthly matches (last 6 months)
        $monthlyMatches = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = GameMatch::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            $monthlyMatches->push([
                'month' => $date->format('M'),
                'count' => $count,
            ]);
        }

        // Recent users
        $recentUsers = User::latest()->take(5)->get();

        // Recent matches
        $recentMatches = GameMatch::with(['player1', 'player2', 'winner'])
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.admin.dashboard.index', [
            'totalUsers' => $totalUsers,
            'totalClubs' => $totalClubs,
            'totalMatches' => $totalMatches,
            'usersThisMonth' => $usersThisMonth,
            'matchesThisMonth' => $matchesThisMonth,
            'userGrowth' => $userGrowth,
            'matchGrowth' => $matchGrowth,
            'genderDistribution' => $genderDistribution,
            'monthlyUsers' => $monthlyUsers,
            'monthlyMatches' => $monthlyMatches,
            'recentUsers' => $recentUsers,
            'recentMatches' => $recentMatches,
        ])->layout('components.layouts.admin');
    }
}
