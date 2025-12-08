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
                <!-- Top 3 -->
                @if($ladiesRankings->count() >= 3)
                    <div class="space-y-3 mb-6">
                        <!-- 1st Place -->
                        <a href="{{ route('players.show', $ladiesRankings[0]->user) }}" wire:navigate class="flex items-center gap-4 p-4 bg-gradient-to-r from-yellow-100 to-yellow-50 dark:from-yellow-900/30 dark:to-yellow-900/10 rounded-xl hover:from-yellow-200 hover:to-yellow-100 dark:hover:from-yellow-900/40 dark:hover:to-yellow-900/20 transition-colors border border-yellow-200 dark:border-yellow-800">
                            <div class="text-2xl">🥇</div>
                            @if($ladiesRankings[0]->user->profile_picture)
                                <img src="{{ Storage::url($ladiesRankings[0]->user->profile_picture) }}" alt="{{ $ladiesRankings[0]->user->name }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-yellow-400">
                            @else
                                <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center ring-2 ring-yellow-400">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $ladiesRankings[0]->user->initials() }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $ladiesRankings[0]->user->name }}</div>
                                @if($ladiesRankings[0]->user->club)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ladiesRankings[0]->user->club->name }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($ladiesRankings[0]->points) }}</div>
                                <div class="text-xs {{ $ladiesRankings[0]->points_change > 0 ? 'text-green-500' : ($ladiesRankings[0]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($ladiesRankings[0]->points_change > 0)+@endif{{ number_format($ladiesRankings[0]->points_change) }}
                                </div>
                            </div>
                        </a>

                        <!-- 2nd Place -->
                        <a href="{{ route('players.show', $ladiesRankings[1]->user) }}" wire:navigate class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <div class="text-2xl">🥈</div>
                            @if($ladiesRankings[1]->user->profile_picture)
                                <img src="{{ Storage::url($ladiesRankings[1]->user->profile_picture) }}" alt="{{ $ladiesRankings[1]->user->name }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $ladiesRankings[1]->user->initials() }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $ladiesRankings[1]->user->name }}</div>
                                @if($ladiesRankings[1]->user->club)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ladiesRankings[1]->user->club->name }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($ladiesRankings[1]->points) }}</div>
                                <div class="text-xs {{ $ladiesRankings[1]->points_change > 0 ? 'text-green-500' : ($ladiesRankings[1]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($ladiesRankings[1]->points_change > 0)+@endif{{ number_format($ladiesRankings[1]->points_change) }}
                                </div>
                            </div>
                        </a>

                        <!-- 3rd Place -->
                        <a href="{{ route('players.show', $ladiesRankings[2]->user) }}" wire:navigate class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <div class="text-2xl">🥉</div>
                            @if($ladiesRankings[2]->user->profile_picture)
                                <img src="{{ Storage::url($ladiesRankings[2]->user->profile_picture) }}" alt="{{ $ladiesRankings[2]->user->name }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $ladiesRankings[2]->user->initials() }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $ladiesRankings[2]->user->name }}</div>
                                @if($ladiesRankings[2]->user->club)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $ladiesRankings[2]->user->club->name }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($ladiesRankings[2]->points) }}</div>
                                <div class="text-xs {{ $ladiesRankings[2]->points_change > 0 ? 'text-green-500' : ($ladiesRankings[2]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($ladiesRankings[2]->points_change > 0)+@endif{{ number_format($ladiesRankings[2]->points_change) }}
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                <!-- Rankings Table (4th place onwards) -->
                @if($ladiesRankings->count() > 3)
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
                                @foreach($ladiesRankings->skip(3) as $ranking)
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
                <!-- Top 3 -->
                @if($menRankings->count() >= 3)
                    <div class="space-y-3 mb-6">
                        <!-- 1st Place -->
                        <a href="{{ route('players.show', $menRankings[0]->user) }}" wire:navigate class="flex items-center gap-4 p-4 bg-gradient-to-r from-yellow-100 to-yellow-50 dark:from-yellow-900/30 dark:to-yellow-900/10 rounded-xl hover:from-yellow-200 hover:to-yellow-100 dark:hover:from-yellow-900/40 dark:hover:to-yellow-900/20 transition-colors border border-yellow-200 dark:border-yellow-800">
                            <div class="text-2xl">🥇</div>
                            @if($menRankings[0]->user->profile_picture)
                                <img src="{{ Storage::url($menRankings[0]->user->profile_picture) }}" alt="{{ $menRankings[0]->user->name }}" class="w-12 h-12 rounded-full object-cover ring-2 ring-yellow-400">
                            @else
                                <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center ring-2 ring-yellow-400">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $menRankings[0]->user->initials() }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $menRankings[0]->user->name }}</div>
                                @if($menRankings[0]->user->club)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $menRankings[0]->user->club->name }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($menRankings[0]->points) }}</div>
                                <div class="text-xs {{ $menRankings[0]->points_change > 0 ? 'text-green-500' : ($menRankings[0]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($menRankings[0]->points_change > 0)+@endif{{ number_format($menRankings[0]->points_change) }}
                                </div>
                            </div>
                        </a>

                        <!-- 2nd Place -->
                        <a href="{{ route('players.show', $menRankings[1]->user) }}" wire:navigate class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <div class="text-2xl">🥈</div>
                            @if($menRankings[1]->user->profile_picture)
                                <img src="{{ Storage::url($menRankings[1]->user->profile_picture) }}" alt="{{ $menRankings[1]->user->name }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $menRankings[1]->user->initials() }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $menRankings[1]->user->name }}</div>
                                @if($menRankings[1]->user->club)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $menRankings[1]->user->club->name }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($menRankings[1]->points) }}</div>
                                <div class="text-xs {{ $menRankings[1]->points_change > 0 ? 'text-green-500' : ($menRankings[1]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($menRankings[1]->points_change > 0)+@endif{{ number_format($menRankings[1]->points_change) }}
                                </div>
                            </div>
                        </a>

                        <!-- 3rd Place -->
                        <a href="{{ route('players.show', $menRankings[2]->user) }}" wire:navigate class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <div class="text-2xl">🥉</div>
                            @if($menRankings[2]->user->profile_picture)
                                <img src="{{ Storage::url($menRankings[2]->user->profile_picture) }}" alt="{{ $menRankings[2]->user->name }}" class="w-12 h-12 rounded-full object-cover">
                            @else
                                <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $menRankings[2]->user->initials() }}</span>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $menRankings[2]->user->name }}</div>
                                @if($menRankings[2]->user->club)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $menRankings[2]->user->club->name }}</div>
                                @endif
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($menRankings[2]->points) }}</div>
                                <div class="text-xs {{ $menRankings[2]->points_change > 0 ? 'text-green-500' : ($menRankings[2]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($menRankings[2]->points_change > 0)+@endif{{ number_format($menRankings[2]->points_change) }}
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                <!-- Rankings Table (4th place onwards) -->
                @if($menRankings->count() > 3)
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
                                @foreach($menRankings->skip(3) as $ranking)
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
                <!-- Top 3 -->
                @if($clubRankings->count() >= 3)
                    <div class="space-y-3 mb-6">
                        <!-- 1st Place -->
                        <a href="{{ route('clubs.show', $clubRankings[0]->club) }}" wire:navigate class="flex items-center gap-4 p-4 bg-gradient-to-r from-yellow-100 to-yellow-50 dark:from-yellow-900/30 dark:to-yellow-900/10 rounded-xl hover:from-yellow-200 hover:to-yellow-100 dark:hover:from-yellow-900/40 dark:hover:to-yellow-900/20 transition-colors border border-yellow-200 dark:border-yellow-800">
                            <div class="text-2xl">🥇</div>
                            @if($clubRankings[0]->club->logo)
                                <img src="{{ Storage::url($clubRankings[0]->club->logo) }}" alt="{{ $clubRankings[0]->club->name }}" class="w-12 h-12 rounded-lg object-cover ring-2 ring-yellow-400">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center ring-2 ring-yellow-400">
                                    <svg class="w-6 h-6 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $clubRankings[0]->club->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clubRankings[0]->club->members_count ?? 0 }} {{ __('user-bubbler.members') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($clubRankings[0]->total_points) }}</div>
                                <div class="text-xs {{ $clubRankings[0]->points_change > 0 ? 'text-green-500' : ($clubRankings[0]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($clubRankings[0]->points_change > 0)+@endif{{ number_format($clubRankings[0]->points_change) }}
                                </div>
                            </div>
                        </a>

                        <!-- 2nd Place -->
                        <a href="{{ route('clubs.show', $clubRankings[1]->club) }}" wire:navigate class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <div class="text-2xl">🥈</div>
                            @if($clubRankings[1]->club->logo)
                                <img src="{{ Storage::url($clubRankings[1]->club->logo) }}" alt="{{ $clubRankings[1]->club->name }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $clubRankings[1]->club->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clubRankings[1]->club->members_count ?? 0 }} {{ __('user-bubbler.members') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($clubRankings[1]->total_points) }}</div>
                                <div class="text-xs {{ $clubRankings[1]->points_change > 0 ? 'text-green-500' : ($clubRankings[1]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($clubRankings[1]->points_change > 0)+@endif{{ number_format($clubRankings[1]->points_change) }}
                                </div>
                            </div>
                        </a>

                        <!-- 3rd Place -->
                        <a href="{{ route('clubs.show', $clubRankings[2]->club) }}" wire:navigate class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            <div class="text-2xl">🥉</div>
                            @if($clubRankings[2]->club->logo)
                                <img src="{{ Storage::url($clubRankings[2]->club->logo) }}" alt="{{ $clubRankings[2]->club->name }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                                <div class="w-12 h-12 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-white truncate">{{ $clubRankings[2]->club->name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clubRankings[2]->club->members_count ?? 0 }} {{ __('user-bubbler.members') }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-zinc-900 dark:text-white">{{ number_format($clubRankings[2]->total_points) }}</div>
                                <div class="text-xs {{ $clubRankings[2]->points_change > 0 ? 'text-green-500' : ($clubRankings[2]->points_change < 0 ? 'text-red-500' : 'text-zinc-500') }}">
                                    @if($clubRankings[2]->points_change > 0)+@endif{{ number_format($clubRankings[2]->points_change) }}
                                </div>
                            </div>
                        </a>
                    </div>
                @endif

                <!-- Rankings Table (4th place onwards) -->
                @if($clubRankings->count() > 3)
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
                                @foreach($clubRankings->skip(3) as $ranking)
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
            @endif
        </div>
    @endif
</div>
