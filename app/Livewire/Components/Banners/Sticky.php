<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Sticky extends Component
{
    public string $location;
    public string $position; // 'top' or 'bottom'
    public string $offsetClass = '';
    public ?int $selectedBannerId = null;
    public ?string $selectedBannerPosition = null;

    public function mount(string $location, string $position = 'top', string $offsetClass = '', ?int $selectedBannerId = null, ?string $selectedBannerPosition = null)
    {
        $this->location = $location;
        $this->position = $position;
        $this->offsetClass = $offsetClass;
        $this->selectedBannerId = $selectedBannerId;
        $this->selectedBannerPosition = $selectedBannerPosition;
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
        $banner = null;
        $positionType = $this->position === 'top' ? 'top_sticky' : 'bottom_sticky';

        if ($this->selectedBannerId && $this->selectedBannerPosition === $positionType) {
            $banner = Banner::find($this->selectedBannerId);
            if ($banner) {
                $banner->incrementViews();
            }
        }

        return view('livewire.components.banners.sticky', [
            'banner' => $banner,
        ]);
    }
}
