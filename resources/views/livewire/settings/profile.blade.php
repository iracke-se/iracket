<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile information')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <!-- Profile Picture -->
            <div>
                <flux:label>{{ __('Profile Picture') }}</flux:label>
                <div class="mt-2 flex items-center gap-4">
                    @if ($profile_picture)
                        <img src="{{ $profile_picture->temporaryUrl() }}" alt="Preview" class="h-16 w-16 rounded-full object-cover">
                    @elseif ($current_profile_picture)
                        <img src="{{ Storage::url($current_profile_picture) }}" alt="Profile" class="h-16 w-16 rounded-full object-cover">
                    @else
                        <div class="h-16 w-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">
                                {{ auth()->user()->initials() }}
                            </span>
                        </div>
                    @endif
                    <div class="flex flex-col gap-2">
                        <input type="file" wire:model="profile_picture" accept="image/*" class="text-sm text-zinc-600 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600 file:cursor-pointer">
                        @if ($current_profile_picture)
                            <button type="button" wire:click="deleteProfilePicture" class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                {{ __('Remove photo') }}
                            </button>
                        @endif
                    </div>
                </div>
                @error('profile_picture')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Name Fields -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="first_name" :label="__('First Name')" type="text" required autofocus autocomplete="given-name" />
                <flux:input wire:model="last_name" :label="__('Last Name')" type="text" required autocomplete="family-name" />
            </div>

            <!-- Email -->
            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Phone and Gender -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="phone_number" :label="__('Phone Number')" type="tel" autocomplete="tel" />
                <flux:select wire:model="gender" :label="__('Gender')">
                    <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                    <flux:select.option value="male">{{ __('Male') }}</flux:select.option>
                    <flux:select.option value="female">{{ __('Female') }}</flux:select.option>
                    <flux:select.option value="other">{{ __('Other') }}</flux:select.option>
                    <flux:select.option value="prefer_not_to_say">{{ __('Prefer not to say') }}</flux:select.option>
                </flux:select>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
