<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Popup extends Component
{
    public string $location;
    public array $bannerIds = [];
    public int $currentIndex = 0;

    public function mount(string $location)
    {
        $this->location = $location;

        $this->bannerIds = Banner::active()
            ->forLocation($this->location)
            ->where('position', 'popup')
            ->pluck('id')
            ->toArray();
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
