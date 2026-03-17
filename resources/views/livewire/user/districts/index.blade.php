<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Districts') }}</h1>
        <button
            wire:click="toggleRaw"
            class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg border transition-colors {{ $showRaw ? 'bg-accent text-white border-accent' : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            {{ __('Raw Data') }}
        </button>
    </div>

    @if($showRaw)
        <!-- Raw Data View -->
        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-3">
                <span class="text-xs font-mono text-zinc-500 dark:text-zinc-400">#</span>
                <span class="text-xs font-mono font-semibold text-zinc-500 dark:text-zinc-400 flex-1">name</span>
                <span class="text-xs font-mono font-semibold text-zinc-500 dark:text-zinc-400 w-24 text-right">profixio_id</span>
                <span class="text-xs font-mono font-semibold text-zinc-500 dark:text-zinc-400 w-16 text-right">players</span>
            </div>
            @foreach($districts as $district)
                <div class="px-4 py-2.5 flex items-center gap-3 border-b border-zinc-200 dark:border-zinc-700 last:border-0 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                    <span class="text-xs font-mono text-zinc-400 dark:text-zinc-500 w-6">{{ $district->id }}</span>
                    <span class="font-mono text-sm text-zinc-900 dark:text-white flex-1">{{ $district->name }}</span>
                    <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400 w-24 text-right">{{ $district->profixio_id ?? '—' }}</span>
                    <span class="font-mono text-sm text-zinc-500 dark:text-zinc-400 w-16 text-right">{{ $district->users_count }}</span>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-3 text-right">{{ $districts->count() }} {{ __('districts') }}</p>
    @else

    <!-- Search -->
    <div class="mb-6">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search districts...') }}"
                class="w-full px-4 py-3 pl-10 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Districts List -->
    @if($districts->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No districts found') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($districts as $district)
                <a
                    href="{{ route('districts.show', $district) }}"
                    wire:navigate
                    class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                >
                    <!-- Icon -->
                    <div class="w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>

                    <!-- District Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-zinc-900 dark:text-white truncate">{{ $district->name }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $district->users_count }} {{ __('players') }}
                        </p>
                    </div>

                    <!-- Arrow -->
                    <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($districts->hasPages())
            <div class="mt-6">
                {{ $districts->links() }}
            </div>
        @endif
    @endif

    @endif {{-- end showRaw --}}
</div>
