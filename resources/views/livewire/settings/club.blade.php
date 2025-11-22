<div class="max-w-2xl mx-auto">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Club')" :subheading="__('Manage your club membership')">
        @if($currentClub)
            <!-- Current Club -->
            <div class="space-y-6">
                <div class="flex items-center gap-4">
                    @if($currentClub->logo)
                        <img src="{{ Storage::url($currentClub->logo) }}" alt="{{ $currentClub->name }}" class="w-16 h-16 rounded-lg object-cover">
                    @else
                        <div class="w-16 h-16 rounded-lg bg-zinc-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif
                    <div>
                        <h3 class="text-lg font-semibold text-white">{{ $currentClub->name }}</h3>
                        @if($currentClub->location)
                            <p class="text-sm text-zinc-400">{{ $currentClub->location }}</p>
                        @endif
                    </div>
                </div>

                @if($currentClub->description)
                    <p class="text-sm text-zinc-300">{{ $currentClub->description }}</p>
                @endif

                <div class="space-y-2">
                    @if($currentClub->email)
                        <div class="flex items-center gap-2 text-sm text-zinc-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ $currentClub->email }}
                        </div>
                    @endif
                    @if($currentClub->phone)
                        <div class="flex items-center gap-2 text-sm text-zinc-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $currentClub->phone }}
                        </div>
                    @endif
                    @if($currentClub->website)
                        <div class="flex items-center gap-2 text-sm text-zinc-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <a href="{{ $currentClub->website }}" target="_blank" class="hover:text-accent">{{ $currentClub->website }}</a>
                        </div>
                    @endif
                </div>

                <!-- Edit Club Form -->
                @if($showEditForm)
                    <form wire:submit="updateClub" class="space-y-4 bg-zinc-700/50 rounded-lg p-4">
                        <div>
                            <label for="edit_name" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Club Name') }} *</label>
                            <input
                                type="text"
                                id="edit_name"
                                wire:model="name"
                                required
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="edit_description" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Description') }}</label>
                            <textarea
                                id="edit_description"
                                wire:model="description"
                                rows="3"
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            ></textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="edit_location" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Location') }}</label>
                            <input
                                type="text"
                                id="edit_location"
                                wire:model="location"
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            >
                            @error('location')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="edit_clubEmail" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Email') }}</label>
                                <input
                                    type="email"
                                    id="edit_clubEmail"
                                    wire:model="clubEmail"
                                    class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                >
                                @error('clubEmail')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="edit_phone" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Phone') }}</label>
                                <input
                                    type="tel"
                                    id="edit_phone"
                                    wire:model="phone"
                                    class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                >
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="edit_website" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Website') }}</label>
                            <input
                                type="url"
                                id="edit_website"
                                wire:model="website"
                                placeholder="https://"
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            >
                            @error('website')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Club Logo') }}</label>
                            @if($currentLogo)
                                <div class="mb-2">
                                    <img src="{{ Storage::url($currentLogo) }}" alt="Current logo" class="w-12 h-12 rounded-lg object-cover">
                                </div>
                            @endif
                            <input
                                type="file"
                                wire:model="logo"
                                accept="image/*"
                                class="text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-600 file:text-zinc-300 hover:file:bg-zinc-500 file:cursor-pointer"
                            >
                            @error('logo')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                                {{ __('Save Changes') }}
                            </button>
                            <button type="button" wire:click="toggleEditForm" class="px-4 py-3 bg-zinc-600 text-zinc-300 font-medium rounded-lg hover:bg-zinc-500 transition-colors">
                                {{ __('Cancel') }}
                            </button>
                        </div>
                    </form>
                @endif

                <div class="pt-4 border-t border-zinc-700 flex gap-3">
                    <button
                        wire:click="toggleEditForm"
                        class="px-4 py-2 bg-zinc-700 text-zinc-300 rounded-lg hover:bg-zinc-600 transition-colors"
                    >
                        {{ $showEditForm ? __('Cancel Edit') : __('Edit Club') }}
                    </button>
                    <button
                        wire:click="leaveClub"
                        wire:confirm="{{ __('Are you sure you want to leave this club?') }}"
                        class="px-4 py-2 bg-red-500/20 text-red-400 rounded-lg hover:bg-red-500/30 transition-colors"
                    >
                        {{ __('Leave Club') }}
                    </button>
                </div>

                <x-action-message class="text-sm text-green-400" on="club-updated">
                    {{ __('Club updated successfully.') }}
                </x-action-message>

                <x-action-message class="text-sm text-green-400" on="club-left">
                    {{ __('You have left the club.') }}
                </x-action-message>
            </div>
        @else
            <!-- No Club - Show Options -->
            <div class="space-y-6">
                <!-- Create Club Button -->
                <div>
                    <button
                        wire:click="toggleCreateForm"
                        class="w-full px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors flex items-center justify-center gap-2"
                    >
                        @if($showCreateForm)
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            {{ __('Cancel') }}
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ __('Create a Club') }}
                        @endif
                    </button>
                </div>

                <!-- Create Club Form -->
                @if($showCreateForm)
                    <form wire:submit="createClub" class="space-y-4 bg-zinc-700/50 rounded-lg p-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Club Name') }} *</label>
                            <input
                                type="text"
                                id="name"
                                wire:model="name"
                                required
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Description') }}</label>
                            <textarea
                                id="description"
                                wire:model="description"
                                rows="3"
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            ></textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="location" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Location') }}</label>
                            <input
                                type="text"
                                id="location"
                                wire:model="location"
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            >
                            @error('location')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="clubEmail" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Email') }}</label>
                                <input
                                    type="email"
                                    id="clubEmail"
                                    wire:model="clubEmail"
                                    class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                >
                                @error('clubEmail')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Phone') }}</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    wire:model="phone"
                                    class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                >
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="website" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Website') }}</label>
                            <input
                                type="url"
                                id="website"
                                wire:model="website"
                                placeholder="https://"
                                class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                            >
                            @error('website')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Club Logo') }}</label>
                            <input
                                type="file"
                                wire:model="logo"
                                accept="image/*"
                                class="text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-600 file:text-zinc-300 hover:file:bg-zinc-500 file:cursor-pointer"
                            >
                            @error('logo')
                                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit" class="w-full px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                            {{ __('Create Club') }}
                        </button>
                    </form>

                    <x-action-message class="text-sm text-green-400" on="club-created">
                        {{ __('Club created successfully!') }}
                    </x-action-message>
                @endif

                <!-- Divider -->
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-zinc-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-zinc-800 text-zinc-400">{{ __('or join an existing club') }}</span>
                    </div>
                </div>

                <!-- Search -->
                <div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search clubs...') }}"
                        class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    >
                </div>

                <!-- Clubs List -->
                @if(count($clubs) > 0)
                    <div class="space-y-3">
                        @foreach($clubs as $club)
                            <div class="flex items-center justify-between bg-zinc-700/50 rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    @if($club->logo)
                                        <img src="{{ Storage::url($club->logo) }}" alt="{{ $club->name }}" class="w-10 h-10 rounded-lg object-cover">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-zinc-600 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-white">{{ $club->name }}</div>
                                        <div class="text-xs text-zinc-400">
                                            {{ $club->members_count }} {{ __('members') }}
                                            @if($club->location)
                                                &bull; {{ $club->location }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <button
                                    wire:click="joinClub({{ $club->id }})"
                                    class="px-3 py-1.5 bg-accent text-white text-sm font-medium rounded-lg hover:bg-accent/90 transition-colors"
                                >
                                    {{ __('Join') }}
                                </button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <p class="text-zinc-400">{{ __('No clubs found') }}</p>
                    </div>
                @endif

                <x-action-message class="text-sm text-green-400" on="club-joined">
                    {{ __('You have joined the club!') }}
                </x-action-message>
            </div>
        @endif
    </x-settings.layout>
</div>
