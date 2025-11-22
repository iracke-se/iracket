<div class="max-w-2xl mx-auto">
    <!-- Player Header -->
    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-6 mb-6">
        <div class="flex flex-col items-center text-center">
            <!-- Profile Picture -->
            @if($player->profile_picture)
                <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-24 h-24 rounded-full object-cover mb-4">
            @else
                <div class="w-24 h-24 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mb-4">
                    <span class="text-3xl font-medium text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                </div>
            @endif

            <!-- Name -->
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $player->name }}</h1>

            <!-- Club -->
            @if($player->club)
                <a href="{{ route('clubs.show', $player->club) }}" wire:navigate class="text-sm text-accent hover:underline mt-1">
                    {{ $player->club->name }}
                </a>
            @endif

            <!-- Age & Current Ranking -->
            <div class="flex items-center gap-4 mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                @if($player->age)
                    <span>{{ $player->age }} {{ __('user-player-show.years') }}</span>
                @endif
                @if($currentRanking)
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        #{{ $currentRanking->rank }} {{ __('user-player-show.this_month') }}
                    </span>
                @endif
            </div>

            <!-- Current Points -->
            @if($currentRanking)
                <div class="mt-4 px-6 py-3 bg-zinc-200 dark:bg-zinc-700 rounded-lg">
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($currentRanking->points) }}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.points') }}</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Rankings History -->
    <div>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-player-show.rankings_history') }}</h2>

        @if($rankingsHistory->isEmpty())
            <div class="text-center py-8 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.no_ranking_history') }}</p>
            </div>
        @else
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.month') }}</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.ranking') }}</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.points_change') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rankingsHistory as $ranking)
                            <tr class="border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $ranking->formatted_date }}</td>
                                <td class="px-4 py-3 text-sm text-center text-zinc-900 dark:text-white">#{{ $ranking->rank }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    @if($ranking->points_change > 0)
                                        <span class="text-green-500 dark:text-green-400">+{{ $ranking->points_change }}</span>
                                    @elseif($ranking->points_change < 0)
                                        <span class="text-red-500 dark:text-red-400">{{ $ranking->points_change }}</span>
                                    @else
                                        <span class="text-zinc-500 dark:text-zinc-400">0</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
