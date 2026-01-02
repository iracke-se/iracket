<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-white mb-6">{{ __('Clubs') }}</h1>

    <!-- Search -->
    <div class="mb-6">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search clubs...') }}"
                class="w-full px-4 py-3 pl-10 bg-zinc-800 border border-zinc-700 rounded-xl text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    <!-- Clubs List -->
    @if($clubs->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <p class="text-zinc-400">{{ __('No clubs found') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($clubs as $club)
                <a
                    href="{{ route('clubs.show', $club) }}"
                    wire:navigate
                    class="flex items-center gap-4 p-4 bg-zinc-800 rounded-xl hover:bg-zinc-700 transition-colors"
                >
                    <!-- Club Logo -->
                    @if($club->logo)
                        <img src="{{ Storage::url($club->logo) }}" alt="{{ $club->name }}" class="w-12 h-12 rounded-lg object-cover">
                    @else
                        <div class="w-12 h-12 rounded-lg bg-zinc-700 flex items-center justify-center">
                            <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    @endif

                    <!-- Club Info -->
                    <div class="flex-1 min-w-0">
                        <h3 class="text-white font-medium truncate">{{ $club->name }}</h3>
                        <div class="flex items-center gap-2 text-sm text-zinc-400">
                            @if($club->location)
                                <span>{{ $club->location }}</span>
                                <span>•</span>
                            @endif
                            <span>{{ $club->members_count }} {{ __('members') }}</span>
                        </div>
                    </div>

                    <!-- Arrow -->
                    <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            <!-- Mobile Pagination -->
            <div class="sm:hidden">
                {{ $clubs->onEachSide(0)->links() }}
            </div>
            <!-- Desktop Pagination -->
            <div class="hidden sm:block">
                {{ $clubs->onEachSide(2)->links() }}
            </div>
        </div>
    @endif
</div>
