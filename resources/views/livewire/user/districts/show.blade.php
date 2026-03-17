<div class="max-w-2xl mx-auto">
    <!-- Back + Header -->
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('districts.index') }}" wire:navigate class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $district->name }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $players->total() }} {{ __('players') }}</p>
        </div>
    </div>

    <!-- Search + Gender -->
    <div class="flex gap-2 mb-6">
        <div class="relative flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search players...') }}"
                class="w-full px-4 py-3 pl-10 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
        <select
            wire:model.live="gender"
            class="px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            <option value="">{{ __('All') }}</option>
            <option value="male">{{ __('user-players.male') }}</option>
            <option value="female">{{ __('user-players.female') }}</option>
            <option value="other">{{ __('user-players.other') }}</option>
        </select>
    </div>

    <!-- Players List -->
    @if($players->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No players found') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($players as $player)
                @php
                    $currentRanking = $player->monthlyRankings->first();
                @endphp
                <a
                    href="{{ route('players.show', $player) }}"
                    wire:navigate
                    class="flex items-center gap-3 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                >
                    <!-- Avatar -->
                    @if($player->profile_picture)
                        <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-12 h-12 rounded-full object-cover shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center shrink-0">
                            <span class="text-lg font-medium text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                        </div>
                    @endif

                    <!-- Player Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-zinc-900 dark:text-white truncate">{{ $player->name }}</h3>
                        @if($player->club)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 truncate">{{ $player->club->name }}</p>
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
                    @php
                        $displayPoints = ($currentRanking?->points ?? 0) + ($manualPointsMap[$player->id] ?? 0);
                    @endphp
                    @if($displayPoints > 0)
                        <div class="flex flex-col items-center px-3 py-1.5 bg-accent rounded-full shrink-0">
                            <span class="text-sm font-bold text-white leading-tight">{{ number_format($displayPoints) }}</span>
                            <span class="text-xs text-white/80 leading-tight">{{ __('user-players.current_points') }}</span>
                        </div>
                    @else
                        <div class="flex flex-col items-center px-3 py-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full shrink-0">
                            <span class="text-sm font-bold text-zinc-500 leading-tight">--</span>
                            <span class="text-xs text-zinc-400 leading-tight">{{ __('user-players.current_points') }}</span>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($players->hasPages())
            <div class="mt-6">
                {{ $players->links() }}
            </div>
        @endif
    @endif
</div>
