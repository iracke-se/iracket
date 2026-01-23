<?php

namespace App\Livewire\Admin\Banners;

use App\Models\Banner;
use App\Traits\HasSearchableQueries;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, HasSearchableQueries;

    public string $search = '';
    public string $status = '';
    public string $position = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'position' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingPosition()
    {
        $this->resetPage();
    }

    public function delete($id)
    {
        $banner = Banner::findOrFail($id);

        // Delete image file
        if ($banner->image) {
            \Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();
        session()->flash('message', 'Banner deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $banner = Banner::findOrFail($id);
        $banner->status = $banner->status === 'active' ? 'inactive' : 'active';
        $banner->save();

        session()->flash('message', 'Banner status updated.');
    }

    public function render()
    {
        $banners = Banner::query()
            ->when($this->search, function ($query) {
                $this->applySearch($query, $this->search, ['name']);
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->position, function ($query) {
                $query->where('position', $this->position);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Stats
        $totalBanners = Banner::count();
        $activeBanners = Banner::where('status', 'active')->count();
        $totalViews = Banner::sum('views');
        $totalClicks = Banner::sum('clicks');
        $avgCtr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0;

        // Chart data - views and clicks over time (last 7 days)
        $chartData = $this->getChartData();

        // Position distribution
        $positionData = Banner::selectRaw('position, count(*) as count')
            ->groupBy('position')
            ->pluck('count', 'position')
            ->toArray();

        return view('livewire.admin.banners.index', [
            'banners' => $banners,
            'totalBanners' => $totalBanners,
            'activeBanners' => $activeBanners,
            'totalViews' => $totalViews,
            'totalClicks' => $totalClicks,
            'avgCtr' => $avgCtr,
            'chartData' => $chartData,
            'positionData' => $positionData,
            'positions' => Banner::POSITIONS,
            'statuses' => Banner::STATUSES,
        ])->layout('components.layouts.admin');
    }

    protected function getChartData(): array
    {
        // For now, return aggregated data
        // In production, you'd track daily views/clicks in a separate table
        $banners = Banner::all();

        return [
            'totalViews' => $banners->sum('views'),
            'totalClicks' => $banners->sum('clicks'),
        ];
    }
}
