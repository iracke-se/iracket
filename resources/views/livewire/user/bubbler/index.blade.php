<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">{{ __('user-bubbler.bubbler') }}</h1>

    <!-- Tabs -->
    <div class="flex gap-2 mb-6">
        <button
            wire:click="setTab('ladies')"
            class="flex-1 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'ladies' ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('user-bubbler.ladies') }}
        </button>
        <button
            wire:click="setTab('men')"
            class="flex-1 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'men' ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('user-bubbler.men') }}
        </button>
        <button
            wire:click="setTab('clubs')"
            class="flex-1 px-4 py-3 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'clubs' ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('user-bubbler.clubs') }}
        </button>
    </div>

    <!-- Month Label -->
    <div class="text-center mb-4">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ now()->format('F Y') }}</p>
    </div>

    <!-- Ladies Tab -->
    @if($activeTab === 'ladies')
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-bubbler.ladies_of_the_month') }}</h2>

            @if($ladiesRankings->isEmpty())
                <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.no_rankings_yet') }}</p>
                </div>
            @else
                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400 w-12">#</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.player') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.points') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400 w-20">+/-</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ladiesRankings as $ranking)
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $ranking->rank }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('players.show', $ranking->user) }}" wire:navigate class="flex items-center gap-3 hover:text-accent">
                                            @if($ranking->user->profile_picture)
                                                <img src="{{ Storage::url($ranking->user->profile_picture) }}" alt="{{ $ranking->user->name }}" class="w-8 h-8 rounded-full object-cover">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $ranking->user->initials() }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm text-zinc-900 dark:text-white">{{ $ranking->user->name }}</div>
                                                @if($ranking->user->club)
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ranking->user->club->name }}</div>
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-white">{{ number_format($ranking->points) }}</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $ranking->points_change > 0 ? 'text-green-500 dark:text-green-400' : ($ranking->points_change < 0 ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400') }}">
                                        @if($ranking->points_change > 0)
                                            +{{ number_format($ranking->points_change) }}
                                        @elseif($ranking->points_change < 0)
                                            {{ number_format($ranking->points_change) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <!-- Men Tab -->
    @if($activeTab === 'men')
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-bubbler.gentlemen_of_the_month') }}</h2>

            @if($menRankings->isEmpty())
                <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.no_rankings_yet') }}</p>
                </div>
            @else
                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400 w-12">#</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.player') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.points') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400 w-20">+/-</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($menRankings as $ranking)
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $ranking->rank }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('players.show', $ranking->user) }}" wire:navigate class="flex items-center gap-3 hover:text-accent">
                                            @if($ranking->user->profile_picture)
                                                <img src="{{ Storage::url($ranking->user->profile_picture) }}" alt="{{ $ranking->user->name }}" class="w-8 h-8 rounded-full object-cover">
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $ranking->user->initials() }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm text-zinc-900 dark:text-white">{{ $ranking->user->name }}</div>
                                                @if($ranking->user->club)
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ranking->user->club->name }}</div>
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-white">{{ number_format($ranking->points) }}</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $ranking->points_change > 0 ? 'text-green-500 dark:text-green-400' : ($ranking->points_change < 0 ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400') }}">
                                        @if($ranking->points_change > 0)
                                            +{{ number_format($ranking->points_change) }}
                                        @elseif($ranking->points_change < 0)
                                            {{ number_format($ranking->points_change) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif

    <!-- Clubs Tab -->
    @if($activeTab === 'clubs')
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-bubbler.clubs_of_the_month') }}</h2>

            @if($clubRankings->isEmpty())
                <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.no_rankings_yet') }}</p>
                </div>
            @else
                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400 w-16">#</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.club') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.points') }}</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400 w-20">+/-</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clubRankings as $ranking)
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                    <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $ranking->rank }}</td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('clubs.show', $ranking->club) }}" wire:navigate class="flex items-center gap-3 hover:text-accent">
                                            @if($ranking->club->logo)
                                                <img src="{{ Storage::url($ranking->club->logo) }}" alt="{{ $ranking->club->name }}" class="w-8 h-8 rounded-lg object-cover">
                                            @else
                                                <div class="w-8 h-8 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm text-zinc-900 dark:text-white">{{ $ranking->club->name }}</div>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ranking->club->members_count ?? 0 }} {{ __('user-bubbler.members') }}</div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-white">{{ number_format($ranking->total_points) }}</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $ranking->points_change > 0 ? 'text-green-500 dark:text-green-400' : ($ranking->points_change < 0 ? 'text-red-500 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400') }}">
                                        @if($ranking->points_change > 0)
                                            +{{ number_format($ranking->points_change) }}
                                        @elseif($ranking->points_change < 0)
                                            {{ number_format($ranking->points_change) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
