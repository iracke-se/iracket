<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">{{ __('My Monitored Players') }}</h1>

    <!-- Search -->
    <div class="mb-6">
        <div class="relative">
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
    </div>

    @if($monitoredPlayers->isEmpty())
        <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
            <svg class="w-12 h-12 mx-auto mb-4 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('You are not monitoring any players yet.') }}</p>
            <a href="{{ route('players.index') }}" class="inline-block mt-4 text-accent hover:underline" wire:navigate>
                {{ __('Browse players') }}
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach($monitoredPlayers as $player)
                <div class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <a href="{{ route('players.show', $player) }}" class="flex items-center gap-3 flex-1" wire:navigate>
                        <!-- Avatar -->
                        @if($player->profile_picture)
                            <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-12 h-12 rounded-full object-cover">
                        @else
                            <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                            </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-zinc-900 dark:text-white truncate">{{ $player->name }}</h3>
                            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                @if($player->club)
                                    <span class="truncate">{{ $player->club->name }}</span>
                                @endif
                                @php
                                    $ranking = $player->monthlyRankings->first();
                                @endphp
                                @if($ranking)
                                    <span class="flex items-center gap-1">
                                        <span class="text-accent font-medium">{{ number_format($ranking->points) }} pts</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>

                    <!-- Unmonitor Button -->
                    <button
                        wire:click="toggleMonitor({{ $player->id }})"
                        class="flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                        title="{{ __('Stop monitoring') }}"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $monitoredPlayers->links() }}
        </div>
    @endif
</div>
