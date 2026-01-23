<div class="max-w-2xl mx-auto">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('user-settings.profile_heading')" :subheading="__('user-settings.profile_subheading')">
        <form wire:submit="updateProfileInformation" class="space-y-6">
            <!-- Profile Picture -->
            <div>
                <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.profile_picture') }}</label>
                <div class="flex items-center gap-4">
                    @if ($profile_picture)
                        <img src="{{ $profile_picture->temporaryUrl() }}" alt="Preview" class="h-16 w-16 shrink-0 rounded-full object-cover aspect-square">
                    @elseif ($current_profile_picture)
                        <img src="{{ Storage::url($current_profile_picture) }}" alt="Profile" class="h-16 w-16 shrink-0 rounded-full object-cover aspect-square">
                    @else
                        <div class="h-16 w-16 shrink-0 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center aspect-square">
                            <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">
                                {{ auth()->user()->initials() }}
                            </span>
                        </div>
                    @endif
                    <div class="flex flex-col gap-2">
                        <input
                            type="file"
                            wire:model="profile_picture"
                            accept="image/*"
                            class="text-sm text-zinc-600 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-200 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-300 dark:hover:file:bg-zinc-600 file:cursor-pointer"
                        >
                        @if ($current_profile_picture)
                            <button type="button" wire:click="deleteProfilePicture" class="text-sm text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 text-left">
                                {{ __('user-settings.remove_photo') }}
                            </button>
                        @endif
                    </div>
                </div>
                @error('profile_picture')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Registered Full Name (Editable) -->
            <div>
                <label for="user_fullname" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">
                    {{ __('Registered Name') }}
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-normal">
                        ({{ __('This is your display name') }})
                    </span>
                </label>
                <input
                    type="text"
                    id="user_fullname"
                    wire:model="user_fullname"
                    autocomplete="name"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('user_fullname')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- SBTF Player Name (Read-only) -->
            <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-3">
                    {{ __('SBTF Player Name') }}
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-normal">
                        ({{ __('Synced from SBTF, cannot be edited') }})
                    </span>
                </p>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.first_name') }}</label>
                        <input
                            type="text"
                            id="first_name"
                            wire:model="first_name"
                            disabled
                            readonly
                            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-600 dark:text-zinc-400 cursor-not-allowed"
                        >
                        @error('first_name')
                            <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.last_name') }}</label>
                        <input
                            type="text"
                            id="last_name"
                            wire:model="last_name"
                            disabled
                            readonly
                            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-600 dark:text-zinc-400 cursor-not-allowed"
                        >
                        @error('last_name')
                            <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.email') }}</label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    required
                    autocomplete="email"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div class="mt-3">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('user-settings.email_unverified') }}
                            <button type="button" wire:click.prevent="resendVerificationNotification" class="text-accent hover:underline">
                                {{ __('user-settings.resend_verification') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 text-sm font-medium text-green-500 dark:text-green-400">
                                {{ __('user-settings.verification_link_sent') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Phone and Gender -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="phone_number" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.phone_number') }}</label>
                    <input
                        type="tel"
                        id="phone_number"
                        wire:model="phone_number"
                        autocomplete="tel"
                        class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    >
                    @error('phone_number')
                        <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="gender" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.gender') }}</label>
                    <select
                        id="gender"
                        wire:model="gender"
                        class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    >
                        <option value="">{{ __('user-settings.select') }}</option>
                        <option value="male">{{ __('user-settings.male') }}</option>
                        <option value="female">{{ __('user-settings.female') }}</option>
                        <option value="other">{{ __('user-settings.other') }}</option>
                        <option value="prefer_not_to_say">{{ __('user-settings.prefer_not_to_say') }}</option>
                    </select>
                    @error('gender')
                        <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Age -->
            <div>
                <label for="age" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.age') }}</label>
                <input
                    type="number"
                    id="age"
                    wire:model="age"
                    min="1"
                    max="120"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('age')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                    {{ __('user-settings.save') }}
                </button>

                <x-action-message class="text-sm text-green-500 dark:text-green-400" on="profile-updated">
                    {{ __('user-settings.saved') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</div>
