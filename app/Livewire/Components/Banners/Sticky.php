<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Sticky extends Component
{
    public string $location;
    public string $position; // 'top' or 'bottom'

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
        $positionType = $this->position === 'top' ? 'top_sticky' : 'bottom_sticky';

        $banner = Banner::active()
            ->forLocation($this->location)
            ->where('position', $positionType)
            ->inRandomOrder()
            ->first();

        if ($banner) {
            $banner->incrementViews();
        }

        return view('livewire.components.banners.sticky', [
            'banner' => $banner,
        ]);
    }
}
