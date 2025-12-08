<div class="max-w-2xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('players.show', $player) }}" class="text-sm text-accent hover:underline flex items-center gap-1 mb-2" wire:navigate>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('Back to player') }}
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $player->name }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Club Transitions') }}</p>
    </div>

    <!-- Transitions List -->
    @if($transitions->isEmpty())
        <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No transitions found') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($transitions as $transition)
                <div class="p-4 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-transparent">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2 text-sm">
                                    @if($transition->fromClub)
                                        <a href="{{ route('clubs.show', $transition->fromClub) }}" wire:navigate class="text-zinc-600 dark:text-zinc-400 hover:text-accent">
                                            {{ $transition->fromClub->name }}
                                        </a>
                                    @else
                                        <span class="text-zinc-600 dark:text-zinc-400">
                                            {{ $transition->from_club_name ?? __('Unknown') }}
                                        </span>
                                    @endif
                                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                    @if($transition->toClub)
                                        <a href="{{ route('clubs.show', $transition->toClub) }}" wire:navigate class="text-zinc-900 dark:text-white font-medium hover:text-accent">
                                            {{ $transition->toClub->name }}
                                        </a>
                                    @else
                                        <span class="text-zinc-900 dark:text-white font-medium">
                                            {{ $transition->to_club_name ?? __('Unknown') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                {{ $transition->period }}
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium {{ $transition->isPending() ? 'text-amber-500 dark:text-amber-400' : 'text-green-500 dark:text-green-400' }}">
                                {{ $transition->completion_date->format('d M Y') }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $transition->isPending() ? __('Pending') : __('Completed') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
