<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Fixed extends Component
{
    public string $location;
    public string $position; // 'top', 'bottom', 'within_page', 'random'

    public function mount(string $location, string $position = 'top')
    {
        $this->location = $location;
        $this->position = $position;
    }

    public function trackClick($bannerId)
    {
        $banner = Banner::find($bannerId);
        if ($banner) {
            $banner->incrementClicks();
        }
    }

    public function render()
    {
        $query = Banner::active()->forLocation($this->location);

        if ($this->position === 'random') {
            $query->whereIn('position', ['top', 'bottom', 'within_page', 'random']);
        } else {
            $query->where('position', $this->position);
        }

        $banner = $query->inRandomOrder()->first();

        if ($banner) {
            $banner->incrementViews();
        }

        return view('livewire.components.banners.fixed', [
            'banner' => $banner,
        ]);
    }
}
