<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('clubs.show', $club) }}" class="text-sm text-accent hover:underline flex items-center gap-1 mb-2" wire:navigate>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('Back to club') }}
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $club->name }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Club Transitions') }}</p>
    </div>

    <!-- Filters -->
    <div class="flex gap-2 mb-6">
        <button
            wire:click="$set('filter', 'all')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filter === 'all' ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('All') }}
        </button>
        <button
            wire:click="$set('filter', 'incoming')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filter === 'incoming' ? 'bg-green-500 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('Incoming') }}
        </button>
        <button
            wire:click="$set('filter', 'outgoing')"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $filter === 'outgoing' ? 'bg-red-500 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('Outgoing') }}
        </button>
    </div>

    <!-- Transitions List -->
    @if($transitions->isEmpty())
        <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No transitions found') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($transitions as $transition)
                <div class="p-4 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-transparent {{ $transition->direction === 'incoming' ? 'border-l-4 border-l-green-500' : 'border-l-4 border-l-red-500' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $transition->direction === 'incoming' ? 'bg-green-100 dark:bg-green-900/40' : 'bg-red-100 dark:bg-red-900/40' }}">
                                    <span class="text-xs font-medium {{ $transition->direction === 'incoming' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ strtoupper(substr($transition->first_name, 0, 1) . substr($transition->surname, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-zinc-900 dark:text-white font-medium">{{ $transition->player_name }}</p>
                                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        @if($transition->direction === 'incoming')
                                            <span>{{ __('From') }}: {{ $transition->fromClub?->name ?? $transition->from_club_name ?? __('Unknown') }}</span>
                                        @else
                                            <span>{{ __('To') }}: {{ $transition->toClub?->name ?? $transition->to_club_name ?? __('Unknown') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium {{ $transition->isPending() ? 'text-amber-500 dark:text-amber-400' : 'text-zinc-900 dark:text-white' }}">
                                {{ $transition->completion_date->format('d M Y') }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $transition->isPending() ? __('Pending') : __('Completed') }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $transition->period }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
