<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Popup extends Component
{
    public string $location;
    public bool $show = true;

    public function mount(string $location)
    {
        $this->location = $location;
    }

    public function close()
    {
        $this->show = false;
    }

    public function trackClick($bannerId)
    {
        $banner = Banner::find($bannerId);
        if ($banner) {
            $banner->incrementClicks();
        }
        $this->show = false;
    }

    public function render()
    {
        $banner = null;

        if ($this->show) {
            $banner = Banner::active()
                ->forLocation($this->location)
                ->where('position', 'popup')
                ->inRandomOrder()
                ->first();

            if ($banner) {
                $banner->incrementViews();
            }
        }

        return view('livewire.components.banners.popup', [
            'banner' => $banner,
        ]);
    }
}
