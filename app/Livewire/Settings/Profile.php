<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public string $first_name = '';

    public string $last_name = '';

    public ?string $user_fullname = '';

    public string $email = '';

    public ?string $phone_number = '';

    public ?string $gender = '';

    public ?int $age = null;

    public $profile_picture;

    public ?string $current_profile_picture = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->first_name = $user->first_name;
        $this->last_name = $user->last_name;
        $this->user_fullname = $user->user_fullname ?? '';
        $this->email = $user->email;
        $this->phone_number = $user->phone_number ?? '';
        $this->gender = $user->gender ?? '';
        $this->age = $user->age;
        $this->current_profile_picture = $user->profile_picture;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        // Normalize empty strings to null so `nullable` rules apply
        // (otherwise '' fails the `in:` rule on gender and aborts the whole save)
        if ($this->gender === '') {
            $this->gender = null;
        }
        if ($this->phone_number === '') {
            $this->phone_number = null;
        }
        if ($this->user_fullname === '') {
            $this->user_fullname = null;
        }

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'user_fullname' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'profile_picture' => ['nullable', 'image', 'max:2048'],
        ]);

        // Handle profile picture upload
        if ($this->profile_picture) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $validated['profile_picture'] = $this->profile_picture->store('profile-pictures', 'public');
            $this->current_profile_picture = $validated['profile_picture'];
        } else {
            unset($validated['profile_picture']);
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->profile_picture = null;
        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Delete the current profile picture.
     */
    public function deleteProfilePicture(): void
    {
        $user = Auth::user();

        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
            $user->update(['profile_picture' => null]);
            $this->current_profile_picture = null;
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}
