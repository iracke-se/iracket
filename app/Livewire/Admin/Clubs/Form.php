<?php

namespace App\Livewire\Admin\Clubs;

use App\Models\Club;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Form extends Component
{
    use WithFileUploads;

    public ?Club $club = null;
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $location = '';
    public string $website = '';
    public string $email = '';
    public string $phone = '';
    public $logo;
    public ?string $current_logo = null;

    public function mount($id = null)
    {
        if ($id) {
            $this->club = Club::findOrFail($id);
            $this->name = $this->club->name;
            $this->slug = $this->club->slug;
            $this->description = $this->club->description ?? '';
            $this->location = $this->club->location ?? '';
            $this->website = $this->club->website ?? '';
            $this->email = $this->club->email ?? '';
            $this->phone = $this->club->phone ?? '';
            $this->current_logo = $this->club->logo;
        }
    }

    public function updatedName($value)
    {
        if (!$this->club) {
            $this->slug = Str::slug($value);
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clubs')->ignore($this->club?->id),
            ],
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($this->logo) {
            if ($this->club?->logo) {
                \Storage::disk('public')->delete($this->club->logo);
            }
            $validated['logo'] = $this->logo->store('clubs', 'public');
        } else {
            unset($validated['logo']);
        }

        if ($this->club) {
            $this->club->update($validated);
            session()->flash('message', 'Club updated successfully.');
        } else {
            Club::create($validated);
            session()->flash('message', 'Club created successfully.');
        }

        return redirect()->route('admin.clubs.index');
    }

    public function deleteLogo()
    {
        if ($this->club?->logo) {
            \Storage::disk('public')->delete($this->club->logo);
            $this->club->update(['logo' => null]);
            $this->current_logo = null;
        }
    }

    public function render()
    {
        return view('livewire.admin.clubs.form')
            ->layout('components.layouts.admin');
    }
}
