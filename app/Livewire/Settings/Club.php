<?php

namespace App\Livewire\Settings;

use App\Models\Club as ClubModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Club extends Component
{
    use WithFileUploads;

    public string $search = '';
    public bool $showCreateForm = false;
    public bool $showEditForm = false;

    // Club form fields (used for both create and edit)
    public string $name = '';
    public string $description = '';
    public string $location = '';
    public string $website = '';
    public string $clubEmail = '';
    public string $phone = '';
    public $logo;
    public ?string $currentLogo = null;

    public function joinClub(int $clubId): void
    {
        $club = ClubModel::findOrFail($clubId);

        Auth::user()->update(['club_id' => $club->id]);

        $this->dispatch('club-joined');
    }

    public function leaveClub(): void
    {
        Auth::user()->update(['club_id' => null]);

        $this->dispatch('club-left');
    }

    public function toggleEditForm(): void
    {
        $this->showEditForm = !$this->showEditForm;

        if ($this->showEditForm) {
            $this->loadClubForEdit();
        } else {
            $this->resetCreateForm();
        }
    }

    protected function loadClubForEdit(): void
    {
        $club = Auth::user()->club;

        if ($club) {
            $this->name = $club->name;
            $this->description = $club->description ?? '';
            $this->location = $club->location ?? '';
            $this->website = $club->website ?? '';
            $this->clubEmail = $club->email ?? '';
            $this->phone = $club->phone ?? '';
            $this->currentLogo = $club->logo;
        }
    }

    public function updateClub(): void
    {
        $club = Auth::user()->club;

        if (!$club) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:clubs,name,' . $club->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'clubEmail' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'website' => $validated['website'] ?? null,
            'email' => $validated['clubEmail'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ];

        if ($this->logo) {
            // Delete old logo if exists
            if ($club->logo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($club->logo);
            }
            $updateData['logo'] = $this->logo->store('club-logos', 'public');
            $this->currentLogo = $updateData['logo'];
        }

        $club->update($updateData);

        $this->logo = null;
        $this->showEditForm = false;

        $this->dispatch('club-updated');
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = !$this->showCreateForm;

        if (!$this->showCreateForm) {
            $this->resetCreateForm();
        }
    }

    public function createClub(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:clubs,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'clubEmail' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $logoPath = null;
        if ($this->logo) {
            $logoPath = $this->logo->store('club-logos', 'public');
        }

        $club = ClubModel::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'website' => $validated['website'] ?? null,
            'email' => $validated['clubEmail'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'logo' => $logoPath,
        ]);

        // Automatically join the created club
        Auth::user()->update(['club_id' => $club->id]);

        $this->resetCreateForm();
        $this->showCreateForm = false;

        $this->dispatch('club-created');
    }

    protected function resetCreateForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->location = '';
        $this->website = '';
        $this->clubEmail = '';
        $this->phone = '';
        $this->logo = null;
    }

    public function render()
    {
        $user = Auth::user();
        $currentClub = $user->club;

        $clubs = [];
        if (!$currentClub) {
            $query = ClubModel::withCount('members')->orderBy('name');

            if ($this->search) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('location', 'like', '%' . $this->search . '%');
            }

            $clubs = $query->get();
        }

        return view('livewire.settings.club', [
            'currentClub' => $currentClub,
            'clubs' => $clubs,
        ]);
    }
}
