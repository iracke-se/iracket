<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">{{ __('user-players.players') }}</h1>

    <!-- Search -->
    <div class="mb-4">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('user-players.search_players') }}"
                class="w-full px-4 py-3 pl-10 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="flex-1 px-4 py-2.5 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
                <option value="">{{ __('user-players.all_genders') }}</option>
                <option value="male">{{ __('user-players.male') }}</option>
                <option value="female">{{ __('user-players.female') }}</option>
                <option value="other">{{ __('user-players.other') }}</option>
            </select>

            <!-- Advanced Filters Button -->
            <button
                wire:click="toggleFilters"
                class="flex items-center gap-2 px-4 py-2.5 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                </svg>
                {{ __('user-players.filters') }}
            </button>
        </div>

        <!-- Advanced Filters Panel (Dropdown Overlay) -->
        @if($showFilters)
            <div class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-zinc-800 rounded-xl p-4 space-y-4 z-50 shadow-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('user-players.advanced_filters') }}</h3>
                <button wire:click="toggleFilters" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Sort By -->
            <div>
                <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-players.sort_by') }}</label>
                <select
                    wire:model.live="sortBy"
                    class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="points_desc">{{ __('user-players.points_descending') }}</option>
                    <option value="points_asc">{{ __('user-players.points_ascending') }}</option>
                    <option value="name_asc">{{ __('user-players.name_asc') }}</option>
                    <option value="name_desc">{{ __('user-players.name_desc') }}</option>
                </select>
            </div>

            <!-- District -->
            <div>
                <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-players.district') }}</label>
                <select
                    wire:model.live="filterDistrict"
                    class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-xl text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="">{{ __('user-players.all_districts') }}</option>
                    @foreach($availableDistricts as $district)
                        <option value="{{ $district->id }}">{{ $district->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Rankings Range -->
            <div>
                <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-players.rankings') }}</label>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ __('user-players.from') }}</label>
                        <input
                            type="number"
                            wire:model.blur="rankingFrom"
                            placeholder="1500"
                            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ __('user-players.to') }}</label>
                        <input
                            type="number"
                            wire:model.blur="rankingTo"
                            placeholder="1749"
                            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                </div>
            </div>

            <!-- Age Range -->
            <div>
                <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-players.age') }}</label>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ __('user-players.from') }}</label>
                        <input
                            type="number"
                            wire:model.blur="ageFrom"
                            placeholder="18"
                            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">{{ __('user-players.to') }}</label>
                        <input
                            type="number"
                            wire:model.blur="ageTo"
                            placeholder="50"
                            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        >
                    </div>
                </div>
            </div>

            <!-- Month -->
            <div>
                <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-players.month') }}</label>
                <input
                    type="month"
                    wire:model.live="selectedMonth"
                    class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-2">
                <button
                    wire:click="clearFilters"
                    class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors"
                >
                    {{ __('user-players.clear_selection') }}
                </button>
                <button
                    wire:click="applyFilters"
                    class="flex-1 px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors"
                >
                    {{ __('user-players.apply') }}
                </button>
            </div>
            </div>
        @endif
    </div>

    <!-- Players List -->
    @if($players->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-players.no_players_found') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($players as $player)
                @php
                    $currentRanking = $player->monthlyRankings->first();
                    $clubRanking = $player->club?->monthlyRankings->first();
                @endphp
                <div class="flex flex-nowrap items-center gap-2 sm:gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <a href="{{ route('players.show', $player) }}" wire:navigate class="flex flex-nowrap items-center gap-2 sm:gap-4 flex-1 min-w-0 hover:opacity-80 transition-opacity">
                        <!-- Avatar -->
                        @if($player->profile_picture)
                            <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                <span class="text-lg font-medium text-zinc-600 dark:text-zinc-300">
                                    {{ $player->initials() }}
                                </span>
                            </div>
                        @endif

                        <!-- Player Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-zinc-900 dark:text-white font-medium truncate">{{ $player->name }}</h3>
                                @if(isset($rankingPositions[$player->id]))
                                    <x-ranking-badge
                                        :position="$rankingPositions[$player->id]['position']"
                                        :category="$rankingPositions[$player->id]['category']"
                                        size="sm"
                                    />
                                @endif
                            </div>
                            @if($player->club)
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $player->club->name }}</span>
                                    @if($clubRanking)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-accent/20 text-accent">
                                            #{{ $clubRanking->rank }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                            @if($player->districtModel)
                                <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">{{ $player->districtModel->name }}</div>
                            @endif
                            <div class="flex items-center gap-2 text-xs text-zinc-500 mt-0.5">
                                @if($player->age)
                                    <span>{{ $player->age }} {{ __('user-players.years') }}</span>
                                @endif
                                @if($player->gender && $player->age)
                                    <span>•</span>
                                @endif
                                @if($player->gender)
                                    <span class="capitalize">{{ __('user-players.' . $player->gender) }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Points -->
                        <div class="text-right flex-shrink-0 min-w-[60px]">
                            @if($currentRanking)
                                <div class="text-lg font-bold text-zinc-900 dark:text-white">{{ number_format($currentRanking->points) }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('user-players.points') }}</div>
                            @else
                                <div class="text-lg font-bold text-zinc-500">--</div>
                                <div class="text-xs text-zinc-500">{{ __('user-players.points') }}</div>
                            @endif
                        </div>
                    </a>

                    <!-- Club Button -->
                    @if($player->club)
                        <a
                            href="{{ route('clubs.show', $player->club) }}"
                            wire:navigate
                            title="View {{ $player->club->name }}"
                            class="flex items-center justify-center w-10 h-10 flex-shrink-0 rounded-lg bg-zinc-200 dark:bg-zinc-700 hover:bg-accent hover:text-white dark:hover:bg-accent transition-colors"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </a>
                    @else
                        <div
                            title="No club"
                            class="flex items-center justify-center w-10 h-10 flex-shrink-0 rounded-lg bg-zinc-200 dark:bg-zinc-700 opacity-50 cursor-not-allowed"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            <!-- Mobile Pagination -->
            <div class="sm:hidden">
                {{ $players->onEachSide(0)->links() }}
            </div>
            <!-- Desktop Pagination -->
            <div class="hidden sm:block">
                {{ $players->onEachSide(2)->links() }}
            </div>
        </div>
    @endif
</div>
