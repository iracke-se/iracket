<?php

namespace App\Livewire\Admin\Banners;

use App\Models\Banner;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Banner $banner = null;
    public string $name = '';
    public $image;
    public ?string $current_image = null;
    public string $position = 'top';
    public array $locations = [];
    public string $link = '';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public string $status = 'inactive';

    public function mount($id = null)
    {
        if ($id) {
            $this->banner = Banner::findOrFail($id);
            $this->name = $this->banner->name;
            $this->current_image = $this->banner->image;
            $this->position = $this->banner->position;
            $this->locations = $this->banner->locations ?? [];
            $this->link = $this->banner->link ?? '';
            $this->start_date = $this->banner->start_date?->format('Y-m-d');
            $this->end_date = $this->banner->end_date?->format('Y-m-d');
            $this->status = $this->banner->status;
        }
    }

    public function save()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'position' => 'required|in:' . implode(',', array_keys(Banner::POSITIONS)),
            'locations' => 'nullable|array',
            'link' => 'nullable|url|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:' . implode(',', array_keys(Banner::STATUSES)),
        ];

        // Image is required for new banners
        if (!$this->banner) {
            $rules['image'] = 'required|image|max:5120'; // 5MB max
        } else {
            $rules['image'] = 'nullable|image|max:5120';
        }

        $validated = $this->validate($rules);

        // Handle image upload
        if ($this->image) {
            if ($this->banner?->image) {
                \Storage::disk('public')->delete($this->banner->image);
            }
            $validated['image'] = $this->image->store('banners', 'public');
        } else {
            unset($validated['image']);
        }

        if (empty($validated['locations'])) {
            $validated['locations'] = null;
        }

        if ($this->banner) {
            $this->banner->update($validated);
            session()->flash('message', 'Banner updated successfully.');
        } else {
            Banner::create($validated);
            session()->flash('message', 'Banner created successfully.');
        }

        return redirect()->route('admin.banners.index');
    }

    public function deleteImage()
    {
        if ($this->banner?->image) {
            \Storage::disk('public')->delete($this->banner->image);
            $this->banner->update(['image' => null]);
            $this->current_image = null;
        }
    }

    public function render()
    {
        return view('livewire.admin.banners.form', [
            'positions' => Banner::POSITIONS,
            'statuses' => Banner::STATUSES,
            'availableLocations' => Banner::LOCATIONS,
        ])->layout('components.layouts.admin');
    }
}
