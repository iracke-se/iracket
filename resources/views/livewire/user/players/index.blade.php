<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-white mb-6">{{ __('Players') }}</h1>

    <!-- Search -->
    <div class="mb-4">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search players...') }}"
                class="w-full px-4 py-3 pl-10 bg-zinc-800 border border-zinc-700 rounded-xl text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="relative mb-6">
        <div class="flex gap-2">
            <!-- Gender Filter -->
            <select
                wire:model.live="gender"
                class="flex-1 px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
                <option value="">{{ __('All Genders') }}</option>
                <option value="male">{{ __('Male') }}</option>
                <option value="female">{{ __('Female') }}</option>
                <option value="other">{{ __('Other') }}</option>
            </select>

            <!-- Advanced Filters Button -->
            <button
                wire:click="toggleFilters"
                class="flex items-center gap-2 px-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-lg text-white text-sm hover:bg-zinc-700 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                {{ __('Filters') }}
            </button>
        </div>

        <!-- Advanced Filters Panel (Dropdown Overlay) -->
        @if($showFilters)
            <div class="absolute top-full left-0 right-0 mt-2 bg-zinc-800 rounded-xl p-4 space-y-4 z-50 shadow-xl border border-zinc-700">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-white">{{ __('Advanced Filters') }}</h3>
                <button wire:click="toggleFilters" class="text-zinc-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Sort by') }}</label>
                <select
                    wire:model="sortBy"
                    class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="points_desc">{{ __('Points Descending') }}</option>
                    <option value="points_asc">{{ __('Points Ascending') }}</option>
                    <option value="name_asc">{{ __('Name A-Z') }}</option>
                    <option value="name_desc">{{ __('Name Z-A') }}</option>
                </select>
            </div>

            <!-- Location -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Place') }}</label>
                <select
                    wire:model="location"
                    class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="">{{ __('All of Sweden') }}</option>
                    <option value="stockholm">Stockholm</option>
                    <option value="gothenburg">Gothenburg</option>
                    <option value="malmo">Malmö</option>
                    <option value="uppsala">Uppsala</option>
                    <option value="vasteras">Västerås</option>
                    <option value="orebro">Örebro</option>
                    <option value="linkoping">Linköping</option>
                    <option value="helsingborg">Helsingborg</option>
                </select>
            </div>

            <!-- Rankings Range -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Rankings') }}</label>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1">{{ __('From') }}</label>
                        <input
                            type="number"
                            wire:model="rankingFrom"
                            placeholder="1500"
                            class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1">{{ __('To') }}</label>
                        <input
                            type="number"
                            wire:model="rankingTo"
                            placeholder="1749"
                            class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                </div>
            </div>

            <!-- Age Range -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Age') }}</label>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1">{{ __('From') }}</label>
                        <input
                            type="number"
                            wire:model="ageFrom"
                            placeholder="18"
                            class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-400 mb-1">{{ __('To') }}</label>
                        <input
                            type="number"
                            wire:model="ageTo"
                            placeholder="50"
                            class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                </div>
            </div>

            <!-- Date -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Date') }}</label>
                <input
                    type="date"
                    wire:model="selectedDate"
                    class="w-full px-4 py-3 bg-zinc-700 border border-zinc-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-2">
                <button
                    wire:click="clearFilters"
                    class="flex-1 px-4 py-3 bg-zinc-700 text-zinc-300 rounded-lg hover:bg-zinc-600 transition-colors"
                >
                    {{ __('Clear selection') }}
                </button>
                <button
                    wire:click="applyFilters"
                    class="flex-1 px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors"
                >
                    {{ __('Apply') }}
                </button>
            </div>
            </div>
        @endif
    </div>

    <!-- Players List -->
    @if($players->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <p class="text-zinc-400">{{ __('No players found') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($players as $player)
                <div class="flex items-center gap-4 p-4 bg-zinc-800 rounded-xl">
                    <!-- Avatar -->
                    @if($player->profile_picture)
                        <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 rounded-full bg-zinc-700 flex items-center justify-center">
                            <span class="text-lg font-medium text-zinc-300">
                                {{ $player->initials() }}
                            </span>
                        </div>
                    @endif

                    <!-- Player Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="text-white font-medium truncate">{{ $player->name }}</h3>
                        <div class="flex items-center gap-2 text-sm text-zinc-400">
                            @if($player->age)
                                <span>{{ $player->age }} {{ __('years') }}</span>
                            @endif
                            @if($player->gender && $player->age)
                                <span>•</span>
                            @endif
                            @if($player->gender)
                                <span class="capitalize">{{ __($player->gender) }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Points (placeholder for now) -->
                    <div class="text-right">
                        <div class="text-lg font-bold text-white">--</div>
                        <div class="text-xs text-zinc-400">{{ __('points') }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $players->links() }}
        </div>
    @endif
</div>
