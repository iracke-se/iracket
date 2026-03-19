<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Popup extends Component
{
    public string $location;
    public array $bannerIds = [];
    public int $currentIndex = 0;

    public function mount(string $location, ?int $selectedBannerId = null, ?string $selectedBannerPosition = null)
    {
        $this->location = $location;

        if ($selectedBannerId && $selectedBannerPosition === 'popup') {
            $this->bannerIds = [$selectedBannerId];
        }
    }

    public function close()
    {
        $this->currentIndex++;
    }

    public function trackClick($bannerId)
    {
        $banner = Banner::find($bannerId);
        if ($banner) {
            $banner->incrementClicks();
        }
        $this->currentIndex++;
    }

    public function render()
    {
        $banner = null;

        if ($this->currentIndex < count($this->bannerIds)) {
            $banner = Banner::find($this->bannerIds[$this->currentIndex]);
            if ($banner) {
                $banner->incrementViews();
            }
        }

        return view('livewire.components.banners.popup', [
            'banner' => $banner,
        ]);
    }
}
