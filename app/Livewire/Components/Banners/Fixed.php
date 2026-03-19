<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Fixed extends Component
{
    public string $location;
    public string $position;
    public ?int $selectedBannerId = null;
    public ?string $selectedBannerPosition = null;

    public function mount(string $location, string $position = 'top', ?int $selectedBannerId = null, ?string $selectedBannerPosition = null)
    {
        $this->location = $location;
        $this->position = $position;
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

        if ($this->selectedBannerId && $this->selectedBannerPosition === $this->position) {
            $banner = Banner::find($this->selectedBannerId);
            if ($banner) {
                $banner->incrementViews();
            }
        }

        return view('livewire.components.banners.fixed', [
            'banner' => $banner,
        ]);
    }
}
