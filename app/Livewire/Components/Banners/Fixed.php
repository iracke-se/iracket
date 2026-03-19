<?php

namespace App\Livewire\Components\Banners;

use App\Models\Banner;
use Livewire\Component;

class Fixed extends Component
{
    public string $location;
    public string $position;
    public ?int $forceBannerId = null;

    public function mount(string $location, string $position = 'top', ?int $forceBannerId = null)
    {
        $this->location = $location;
        $this->position = $position;
        $this->forceBannerId = $forceBannerId;
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
        if ($this->forceBannerId) {
            $banner = Banner::find($this->forceBannerId);
        } else {
            $banner = Banner::active()
                ->forLocation($this->location)
                ->where('position', $this->position)
                ->inRandomOrder()
                ->first();
        }

        if ($banner) {
            $banner->incrementViews();
        }

        return view('livewire.components.banners.fixed', [
            'banner' => $banner,
        ]);
    }
}
