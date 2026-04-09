<div class="max-w-2xl mx-auto">
    {{-- Find Players Modal --}}
    @if($isOwnProfile && $showFindPlayersModal)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/50" wire:click.self="closeFindPlayersModal">
            <div class="w-full max-w-lg bg-white dark:bg-zinc-900 rounded-t-2xl sm:rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700 flex flex-col max-h-[85vh]">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-800">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('user-player-show.find_players') }}</h2>
                    <button type="button" wire:click="closeFindPlayersModal" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Search -->
                <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-800">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="findPlayersSearch"
                            placeholder="{{ __('user-player-show.search_players_placeholder') }}"
                            class="w-full pl-9 pr-4 py-2.5 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-sm text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                        />
                    </div>
                    @if($findPlayersSearch === '')
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">{{ __('user-player-show.showing_top_10') }}</p>
                    @endif
                </div>

                <!-- Results -->
                <div class="flex-1 overflow-y-auto">
                    @if($findPlayersResults->isEmpty())
                        <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('user-player-show.no_players_found') }}
                        </div>
                    @else
                        <ul class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach($findPlayersResults as $foundPlayer)
                                @php
                                    $latest = $foundPlayer->monthlyRankings->first();
                                    $isMon = in_array($foundPlayer->id, $monitoringIds);
                                @endphp
                                <li class="flex items-center gap-3 px-4 py-3">
                                    <a href="{{ route('players.show', $foundPlayer) }}" wire:navigate class="flex items-center gap-3 flex-1 min-w-0">
                                        @if($foundPlayer->profile_picture)
                                            <img src="{{ Storage::url($foundPlayer->profile_picture) }}" alt="{{ $foundPlayer->name }}" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-200">{{ $foundPlayer->initials() }}</span>
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $foundPlayer->name }}</p>
                                            @if($latest)
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($latest->points) }} {{ __('user-player-show.pts') }}</p>
                                            @else
                                                <p class="text-xs text-zinc-400 dark:text-zinc-500">—</p>
                                            @endif
                                        </div>
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="toggleMonitorFor({{ $foundPlayer->id }})"
                                        class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors {{ $isMon ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50' : 'bg-accent text-white hover:bg-accent/90' }}"
                                    >
                                        @if($isMon)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            {{ __('user-player-show.stop_monitoring') }}
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            {{ __('user-player-show.monitor_player') }}
                                        @endif
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if($isOwnProfile)
        {{-- ===================== OWN PROFILE — New Design ===================== --}}

        <!-- Edit link -->
        <div class="flex justify-end px-4 pt-2 mb-2">
            <a href="{{ route('profile.edit') }}" class="text-accent text-sm font-medium py-1" wire:navigate>
                {{ __('user-player-show.edit') }}
            </a>
        </div>

        <!-- Avatar + Name -->
        <div class="flex flex-col items-center px-4 pb-6">
            <div class="relative mb-4">
                @if($player->profile_picture)
                    <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-24 h-24 rounded-full object-cover">
                @else
                    <div class="w-24 h-24 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                        <span class="text-3xl font-medium text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                    </div>
                @endif
                <div class="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-accent flex items-center justify-center border-2 border-white dark:border-zinc-900">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $player->name }}</h1>
                @if($rankingPosition && $rankingPosition <= 3)
                    <x-ranking-badge :position="$rankingPosition" :category="$rankingCategory" size="md" />
                @endif
            </div>

            @if($player->user_fullname && $player->user_fullname !== $player->name)
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $player->user_fullname }}</p>
            @endif
        </div>

        <!-- About Me Section -->
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 px-4">{{ __('user-player-show.about_me') }}</p>
            <div class="border-t border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('user-player-show.club') }}</span>
                    @if($player->club)
                        <a href="{{ route('clubs.show', $player->club) }}" wire:navigate class="text-sm text-zinc-500 dark:text-zinc-400">{{ $player->club->name }}</a>
                    @else
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('user-player-show.not_set') }}</span>
                    @endif
                </div>

                <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('user-player-show.district') }}</span>
                    @if($player->districtModel)
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $player->districtModel->name }}</span>
                    @else
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('user-player-show.not_set') }}</span>
                    @endif
                </div>

                <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('user-player-show.age') }}</span>
                    @php
                        $displayAge = $player->birth_year
                            ? now()->year - $player->birth_year
                            : $player->age;
                    @endphp
                    @if($displayAge)
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $displayAge }} {{ __('user-player-show.years') }}</span>
                    @else
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('user-player-show.not_set') }}</span>
                    @endif
                </div>

                <a href="{{ route('my-rankings.index') }}" wire:navigate class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('user-player-show.my_ranking') }}</span>
                    <div class="flex items-center gap-2">
                        @if($currentRanking || $currentRankingPoints > 0)
                            <span class="px-3 py-1 bg-accent text-white text-xs font-bold rounded-full">{{ number_format($currentRankingPoints) }} {{ __('user-player-show.pts') }}</span>
                        @else
                            <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('user-player-show.not_set') }}</span>
                        @endif
                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            </div>
        </div>

        <!-- Latest Rankings Section -->
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 px-4">{{ __('user-player-show.latest_ranking') }}</p>
            <div class="border-t border-zinc-200 dark:border-zinc-800">
                @if($rankingsHistory->isEmpty())
                    <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('user-player-show.ranking_history') }}</span>
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('user-player-show.not_set') }}</span>
                    </div>
                @else
                    <div class="flex items-center px-4 py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="flex-1 text-xs font-bold text-zinc-900 dark:text-white">{{ __('user-player-show.month') }}</span>
                        <span class="w-20 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('user-player-show.position') }}</span>
                        <span class="w-16 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('user-player-show.points') }}</span>
                        <span class="w-10 text-xs font-bold text-zinc-900 dark:text-white text-right">+/-</span>
                    </div>
                    @foreach($rankingsHistory->take(5) as $ranking)
                        <div wire:key="ranking-{{ $ranking->id }}" class="flex items-center px-4 py-3 border-b border-zinc-100 dark:border-zinc-800/50">
                            <span class="flex-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $ranking->formatted_date }}</span>
                            <span class="w-20 text-sm text-zinc-600 dark:text-zinc-400 text-center">{{ number_format($ranking->rank) }}</span>
                            <span class="w-16 text-sm font-semibold text-accent text-center">{{ number_format($ranking->points) }}</span>
                            <span class="w-10 text-sm font-semibold text-right {{ $ranking->points_change > 0 ? 'text-green-500 dark:text-green-400' : ($ranking->points_change < 0 ? 'text-red-500 dark:text-red-400' : 'text-zinc-400 dark:text-zinc-500') }}">
                                {{ $ranking->points_change !== null ? ($ranking->points_change > 0 ? '+' : '') . $ranking->points_change : '-' }}
                            </span>
                        </div>
                    @endforeach
                    <a href="{{ route('my-rankings.index') }}" wire:navigate class="flex items-center justify-center gap-1 px-4 py-3 border-b border-zinc-100 dark:border-zinc-800/50 text-accent hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="text-sm font-medium">{{ __('user-player-show.view_more') }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endif
            </div>
        </div>

        <!-- Top Monitored Players Section -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2 px-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.top_monitored_players') }}</p>
                <button type="button" wire:click="openFindPlayersModal" class="flex items-center gap-1 text-xs font-medium text-accent hover:opacity-80 transition-opacity">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('user-player-show.find_players') }}
                </button>
            </div>
            @if($topMonitoredPlayers->isEmpty())
                <div class="px-4">
                    <button type="button" wire:click="openFindPlayersModal"
                       class="flex items-center justify-center gap-2 w-full py-5 bg-zinc-100 dark:bg-zinc-800 rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 text-accent font-medium text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('user-player-show.add_player') }}
                    </button>
                </div>
            @else
                <div class="flex gap-3 overflow-x-auto px-4 pb-2" style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                    @foreach($topMonitoredPlayers as $monitoredPlayer)
                        @php $latestRanking = $monitoredPlayer->monthlyRankings->first(); @endphp
                        <a href="{{ route('players.show', $monitoredPlayer) }}" wire:navigate
                           class="flex-shrink-0 flex flex-col items-center gap-2 pt-4 pb-3 px-3 w-28 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                            @if($monitoredPlayer->profile_picture)
                                <img src="{{ Storage::url($monitoredPlayer->profile_picture) }}" alt="{{ $monitoredPlayer->name }}"
                                     class="w-16 h-16 rounded-full object-cover">
                            @else
                                <div class="w-16 h-16 rounded-full bg-zinc-300 dark:bg-zinc-600 flex items-center justify-center">
                                    <span class="text-xl font-semibold text-zinc-600 dark:text-zinc-200">{{ $monitoredPlayer->initials() }}</span>
                                </div>
                            @endif
                            <span class="text-xs font-medium text-zinc-900 dark:text-white text-center leading-tight line-clamp-2 w-full">{{ $monitoredPlayer->name }}</span>
                            @if($latestRanking)
                                <span class="bg-accent text-white text-xs font-bold px-2.5 py-0.5 rounded-full">{{ number_format($latestRanking->points) }} {{ __('user-player-show.pts') }}</span>
                            @else
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
            <div class="border-t border-zinc-200 dark:border-zinc-800 mt-3">
                <a href="{{ route('my-monitored.index') }}" wire:navigate class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('user-player-show.my_monitored_players') }}</span>
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Player's Own Latest Matches -->
        @if($playerLatestMatches->isNotEmpty())
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 px-4">{{ __('user-player-show.latest_matches') }}</p>
            <div class="flex gap-3 overflow-x-auto px-4 pb-2" style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                @foreach($playerLatestMatches as $match)
                    @php
                        $isPlayer1 = $match->player1_id === $player->id;
                        $opponent = $isPlayer1 ? $match->player2 : $match->player1;
                        $myPointsRaw = $isPlayer1
                            ? ($match->player1_match_points ?? $match->player1_points_change)
                            : ($match->player2_match_points ?? $match->player2_points_change);
                        $oppPointsRaw = $isPlayer1
                            ? ($match->player2_match_points ?? $match->player2_points_change)
                            : ($match->player1_match_points ?? $match->player1_points_change);
                        $p1Sets = 0; $p2Sets = 0;
                        if ($match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty()) {
                            foreach ($match->liveMatchGame->sets as $set) {
                                if ($set->player1_points > $set->player2_points) { $p1Sets++; } else { $p2Sets++; }
                            }
                        } else {
                            $p1Sets = $match->player1_sets ?? 0;
                            $p2Sets = $match->player2_sets ?? 0;
                        }
                        $mySets = $isPlayer1 ? $p1Sets : $p2Sets;
                        $oppSets = $isPlayer1 ? $p2Sets : $p1Sets;
                        $hasScore = ($mySets + $oppSets) > 0;
                        $won = $match->winner_id === $player->id;
                        if ($match->winner_id === null && $myPointsRaw !== null) {
                            $won = $myPointsRaw > 0;
                        }
                        // Fix reversed sets (same logic as matches index)
                        if ($hasScore) {
                            $setsConsistent = ($won && $mySets >= $oppSets) || (!$won && $mySets <= $oppSets);
                            if (!$setsConsistent) { [$mySets, $oppSets] = [$oppSets, $mySets]; }
                        }
                        // Normalize points sign: winner=positive, loser=negative
                        $myPointsChange = $myPointsRaw !== null ? ($won ? abs($myPointsRaw) : -abs($myPointsRaw)) : null;
                        $oppPointsChange = $oppPointsRaw !== null ? ($won ? -abs($oppPointsRaw) : abs($oppPointsRaw)) : null;
                        if ($myPointsChange !== null && $oppPointsChange === null) { $oppPointsChange = -$myPointsChange; }
                        elseif ($oppPointsChange !== null && $myPointsChange === null) { $myPointsChange = -$oppPointsChange; }
                    @endphp
                    <a href="{{ route('matches.show', $match) }}" wire:navigate
                       class="flex-shrink-0 w-44 bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 flex flex-col items-center gap-2 hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                        <!-- Date -->
                        <p class="text-xs text-zinc-400 dark:text-zinc-300">{{ $match->played_at ? \Carbon\Carbon::parse($match->played_at)->format('d M Y') : '' }}</p>
                        <!-- Avatars + Score -->
                        <div class="flex items-center justify-center gap-2 w-full">
                            <!-- Me -->
                            <div class="relative flex-shrink-0">
                                @if($player->profile_picture)
                                    <img src="{{ Storage::url($player->profile_picture) }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                                    </div>
                                @endif
                                @if($myPointsChange !== null)
                                    <span class="absolute -top-1 -right-1 text-xs font-bold px-1 py-0.5 rounded-full text-white {{ $myPointsChange >= 0 ? 'bg-green-500' : 'bg-red-500' }}">
                                        {{ $myPointsChange >= 0 ? '+' : '' }}{{ $myPointsChange }}
                                    </span>
                                @endif
                            </div>
                            <!-- Score -->
                            @if($hasScore)
                                <span class="text-base font-bold text-zinc-900 dark:text-white">{{ $mySets }} - {{ $oppSets }}</span>
                            @else
                                <div class="flex items-center justify-center gap-1">
                                    <span class="inline-block px-2 py-0.5 rounded text-sm font-bold {{ $won ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">{{ $won ? 'W' : 'L' }}</span>
                                    <span class="text-zinc-400 dark:text-zinc-500 text-sm font-bold">-</span>
                                    <span class="inline-block px-2 py-0.5 rounded text-sm font-bold {{ $won ? 'bg-red-500/20 text-red-700 dark:text-red-400' : 'bg-green-500/20 text-green-700 dark:text-green-400' }}">{{ $won ? 'L' : 'W' }}</span>
                                </div>
                            @endif
                            <!-- Opponent -->
                            <div class="relative flex-shrink-0">
                                @if($opponent?->profile_picture)
                                    <img src="{{ Storage::url($opponent->profile_picture) }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $opponent?->initials() ?? '?' }}</span>
                                    </div>
                                @endif
                                @if($oppPointsChange !== null)
                                    <span class="absolute -top-1 -right-1 text-xs font-bold px-1 py-0.5 rounded-full text-white {{ $oppPointsChange >= 0 ? 'bg-green-500' : 'bg-red-500' }}">
                                        {{ $oppPointsChange >= 0 ? '+' : '' }}{{ $oppPointsChange }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <!-- Names -->
                        <div class="flex justify-between w-full gap-1">
                            <div class="flex flex-col items-center flex-1 min-w-0">
                                <p class="text-xs font-medium text-zinc-900 dark:text-white truncate w-full text-center">{{ $player->first_name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate w-full text-center">{{ $player->last_name }}</p>
                            </div>
                            <div class="flex flex-col items-center flex-1 min-w-0">
                                <p class="text-xs font-medium text-zinc-900 dark:text-white truncate w-full text-center">{{ $opponent?->first_name ?? '—' }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate w-full text-center">{{ $opponent?->last_name ?? '' }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="px-4 mt-3">
                <a href="{{ route('matches.index') }}" wire:navigate class="flex items-center justify-center gap-1 text-accent hover:opacity-80 transition-opacity py-2">
                    <span class="text-sm font-medium">{{ __('user-player-show.view_more') }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        @endif

        <!-- Other Links -->
        <div class="mb-6">
            <div class="border-t border-zinc-200 dark:border-zinc-800">
                <a href="{{ route('players.transitions', $player) }}" wire:navigate class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('user-player-show.club_transitions') }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $clubTransitions->count() }}</span>
                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            </div>
        </div>

    @else
        {{-- ===================== OTHER PLAYER — Original Design ===================== --}}

        <!-- Monitor Button -->
        <div class="flex justify-end mb-4">
            <button
                wire:click="toggleMonitor"
                class="flex items-center gap-2 px-4 py-2 font-medium rounded-lg transition-colors {{ $isMonitoring ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50' : 'bg-accent text-white hover:bg-accent/90' }}"
            >
                @if($isMonitoring)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                    {{ __('user-player-show.stop_monitoring') }}
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ __('user-player-show.monitor_player') }}
                @endif
            </button>
        </div>

        <!-- Player Header Card -->
        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-6 mb-6">
            <div class="flex flex-col items-center text-center">
                @if($player->profile_picture)
                    <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-24 h-24 rounded-full object-cover mb-4">
                @else
                    <div class="w-24 h-24 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mb-4">
                        <span class="text-3xl font-medium text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                    </div>
                @endif

                <div class="flex items-center justify-center gap-2">
                    <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $player->name }}</h1>
                    @if($rankingPosition && $rankingPosition <= 3)
                        <x-ranking-badge :position="$rankingPosition" :category="$rankingCategory" size="md" />
                    @endif
                </div>

                @if($player->user_fullname && $player->user_fullname !== $player->name)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        {{ __('user-player-show.registered_as') }} {{ $player->user_fullname }}
                    </p>
                @endif

                @if($player->club)
                    <a href="{{ route('clubs.show', $player->club) }}" wire:navigate class="text-sm text-accent hover:underline mt-1">
                        {{ $player->club->name }}
                    </a>
                @endif

                @if($player->districtModel)
                    <span class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ $player->districtModel->name }}</span>
                @endif

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

                @if($currentRanking || $currentRankingPoints > 0)
                    <div class="mt-4 flex flex-wrap gap-2 justify-center">
                        <div class="flex flex-col items-center px-4 py-2 bg-accent rounded-full">
                            <span class="text-sm font-bold text-white">{{ number_format($currentRankingPoints) }}</span>
                            <span class="text-xs text-white/80">{{ __('user-player-show.current_points') }}</span>
                        </div>
                        @if($currentRanking)
                            <div class="flex flex-col items-center px-4 py-2 bg-zinc-200 dark:bg-zinc-700 rounded-full">
                                <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ number_format($currentRanking->points) }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.official_points') }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Latest Ranking -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">{{ __('user-player-show.latest_ranking') }}</h2>

            @if($rankingsHistory->isEmpty())
                <div class="text-center py-8 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.no_ranking_history') }}</p>
                </div>
            @else
                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                    <div class="flex items-center px-4 py-2 border-b border-zinc-200 dark:border-zinc-700">
                        <span class="flex-1 text-xs font-bold text-zinc-900 dark:text-white">{{ __('user-player-show.month') }}</span>
                        <span class="w-20 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('user-player-show.position') }}</span>
                        <span class="w-16 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('user-player-show.points') }}</span>
                        <span class="w-14 text-xs font-bold text-zinc-900 dark:text-white text-right">+/-</span>
                    </div>
                <div class="space-y-0">
                    @foreach($rankingsHistory as $ranking)
                        <div wire:key="ranking-{{ $ranking->id }}" class="border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                            <button
                                wire:click="toggleRanking({{ $ranking->id }})"
                                class="w-full px-4 py-3 flex items-center hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                            >
                                <span class="flex-1 text-sm text-zinc-600 dark:text-zinc-400 text-left">{{ $ranking->formatted_date }}</span>
                                <span class="w-20 text-sm text-zinc-600 dark:text-zinc-400 text-center">#{{ $ranking->rank }}</span>
                                <span class="w-16 text-sm font-semibold text-accent text-center">{{ number_format($ranking->points) }}</span>
                                <span class="w-10 text-sm font-semibold text-right {{ $ranking->points_change > 0 ? 'text-green-500 dark:text-green-400' : ($ranking->points_change < 0 ? 'text-red-500 dark:text-red-400' : 'text-zinc-400 dark:text-zinc-500') }}">
                                    {{ $ranking->points_change !== null ? ($ranking->points_change > 0 ? '+' : '') . $ranking->points_change : '—' }}
                                </span>
                                <svg class="w-4 h-4 ml-1 text-zinc-400 transition-transform flex-shrink-0 {{ $this->expandedRankingId === $ranking->id ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            @if($this->expandedRankingId === $ranking->id)
                                <div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4">
                                    @if($expandedRankingMatches->isEmpty())
                                        <p class="text-sm text-center text-zinc-500 dark:text-zinc-400 py-4">{{ __('user-player-show.no_matches_for_month') }}</p>
                                    @else
                                        <div class="space-y-4">
                                            @foreach($expandedRankingMatches as $match)
                                                @php
                                                    $isScrapedMatch = $match instanceof \App\Models\Scraper\ScrapedMatch;
                                                    if ($isScrapedMatch) {
                                                        $playerFullName = $player->last_name . ', ' . $player->first_name;
                                                        $isPlayerMatch = $match->player_name === $playerFullName;
                                                        $opponentName = $isPlayerMatch ? $match->opponent_name : $match->player_name;
                                                        $won = $isPlayerMatch ? ($match->result === 'W') : ($match->result === 'L');
                                                        $rawPoints = $isPlayerMatch ? $match->match_points : null;
                                                        $myPointsChange = $rawPoints !== null ? ($won ? abs($rawPoints) : -abs($rawPoints)) : null;
                                                        $oppPointsChange = $myPointsChange !== null ? -$myPointsChange : null;
                                                        $matchDate = $match->match_date ? \Carbon\Carbon::parse($match->match_date) : null;
                                                        $opponent = null;
                                                        $playerSets = null; $opponentSets = null;
                                                        $sets = collect();
                                                    } else {
                                                        $isPlayer1 = $match->player1_id === $player->id;
                                                        $opponent = $isPlayer1 ? $match->player2 : $match->player1;
                                                        $opponentName = $opponent?->name ?? '—';
                                                        $myPointsChange = $isPlayer1 ? $match->player1_points_change : $match->player2_points_change;
                                                        $oppPointsChange = $isPlayer1 ? $match->player2_points_change : $match->player1_points_change;
                                                        $dbMatchPoints = $isPlayer1 ? ($match->player1_match_points ?? null) : ($match->player2_match_points ?? null);
                                                        $myPointsChange = $dbMatchPoints ?? $match->match_points_scraped ?? $myPointsChange;
                                                        $matchDate = $match->played_at;
                                                        $won = $match->winner_id === $player->id;
                                                        // Infer win/loss from points sign when winner_id is not set
                                                        if ($match->winner_id === null && $myPointsChange !== null) {
                                                            $won = $myPointsChange > 0;
                                                        }
                                                        // Set opponent points as inverse when missing
                                                        if ($oppPointsChange === null && $myPointsChange !== null) {
                                                            $oppPointsChange = -$myPointsChange;
                                                        }
                                                        if ($match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty()) {
                                                            $mySetsWon = 0; $opponentSetsWon = 0;
                                                            foreach ($match->liveMatchGame->sets->sortBy('set_number') as $set) {
                                                                $mySetPoints = $isPlayer1 ? $set->player1_points : $set->player2_points;
                                                                $oppSetPoints = $isPlayer1 ? $set->player2_points : $set->player1_points;
                                                                if ($mySetPoints > $oppSetPoints) { $mySetsWon++; } else { $opponentSetsWon++; }
                                                            }
                                                            $playerSets = $mySetsWon; $opponentSets = $opponentSetsWon;
                                                        } else {
                                                            $playerSets = $isPlayer1 ? $match->player1_sets : $match->player2_sets;
                                                            $opponentSets = $isPlayer1 ? $match->player2_sets : $match->player1_sets;
                                                        }
                                                        // Fix reversed player assignment in liveMatchGame using $won (from winner_id or points inference)
                                                        if ($playerSets !== null && ($playerSets + $opponentSets) > 0) {
                                                            $setsConsistent = ($won && $playerSets >= $opponentSets) || (!$won && $playerSets <= $opponentSets);
                                                            if (!$setsConsistent) {
                                                                [$playerSets, $opponentSets] = [$opponentSets, $playerSets];
                                                            }
                                                        }
                                                    }
                                                @endphp

                                                <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden">
                                                    {{-- Score --}}
                                                    <div class="text-center pt-3 pb-1">
                                                        @if(!$isScrapedMatch && $playerSets !== null && ($playerSets + $opponentSets) > 0)
                                                            <div class="text-4xl font-bold text-zinc-900 dark:text-white">{{ $playerSets }} - {{ $opponentSets }}</div>
                                                        @else
                                                            <div class="flex items-center justify-center gap-2">
                                                                <span class="inline-block px-4 py-1.5 rounded-lg text-2xl font-bold {{ $won ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">{{ $won ? 'W' : 'L' }}</span>
                                                                <span class="text-zinc-400 dark:text-zinc-500 text-xl font-bold">-</span>
                                                                <span class="inline-block px-4 py-1.5 rounded-lg text-2xl font-bold {{ $won ? 'bg-red-500/20 text-red-700 dark:text-red-400' : 'bg-green-500/20 text-green-700 dark:text-green-400' }}">{{ $won ? 'L' : 'W' }}</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Date --}}
                                                    <div class="text-center pb-2">
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $matchDate ? $matchDate->format('d M Y') : 'N/A' }}</span>
                                                    </div>

                                                    {{-- Players --}}
                                                    <div class="flex items-center justify-between px-6 pb-4">
                                                        <div class="flex-1 flex flex-col items-center text-center">
                                                            <div class="relative mb-2">
                                                                @if($player->profile_picture)
                                                                    <img src="{{ Storage::url($player->profile_picture) }}" class="w-16 h-16 rounded-full object-cover">
                                                                @else
                                                                    <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                                        <span class="text-xl font-semibold text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                                                                    </div>
                                                                @endif
                                                                @if($myPointsChange !== null)
                                                                    <span class="absolute -top-1 -right-1 text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white dark:border-zinc-800 {{ $myPointsChange > 0 ? 'bg-green-500 text-white' : ($myPointsChange < 0 ? 'bg-red-500 text-white' : 'bg-zinc-400 text-white') }}">
                                                                        {{ $myPointsChange > 0 ? '+' : '' }}{{ $myPointsChange }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <a href="{{ route('players.show', $player) }}" wire:navigate class="text-xs font-semibold text-zinc-900 dark:text-white hover:text-accent leading-tight">{{ $player->name }}</a>
                                                            @if($player->club)<span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $player->club->name }}</span>@endif
                                                        </div>

                                                        <div class="px-3"><span class="text-sm font-medium text-zinc-400 dark:text-zinc-500">VS</span></div>

                                                        <div class="flex-1 flex flex-col items-center text-center">
                                                            <div class="relative mb-2">
                                                                @if(!$isScrapedMatch && $opponent?->profile_picture)
                                                                    <img src="{{ Storage::url($opponent->profile_picture) }}" class="w-16 h-16 rounded-full object-cover">
                                                                @else
                                                                    <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                                        <span class="text-xl font-semibold text-zinc-600 dark:text-zinc-300">{{ substr($opponentName, 0, 2) }}</span>
                                                                    </div>
                                                                @endif
                                                                @if($oppPointsChange !== null)
                                                                    <span class="absolute -top-1 -right-1 text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white dark:border-zinc-800 {{ $oppPointsChange > 0 ? 'bg-green-500 text-white' : ($oppPointsChange < 0 ? 'bg-red-500 text-white' : 'bg-zinc-400 text-white') }}">
                                                                        {{ $oppPointsChange > 0 ? '+' : '' }}{{ $oppPointsChange }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if(!$isScrapedMatch && $opponent)
                                                                <a href="{{ route('players.show', $opponent) }}" wire:navigate class="text-xs font-semibold text-zinc-900 dark:text-white hover:text-accent leading-tight">{{ $opponentName }}</a>
                                                                @if($opponent->club)<span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $opponent->club->name }}</span>@endif
                                                            @else
                                                                <span class="text-xs font-semibold text-zinc-900 dark:text-white leading-tight">{{ $opponentName }}</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- View Full Match --}}
                                                    @if(!$isScrapedMatch)
                                                        <div class="border-t border-zinc-100 dark:border-zinc-700 px-4 py-2.5">
                                                            <a href="{{ route('matches.show', $match) }}" wire:navigate class="text-xs text-accent hover:underline flex items-center gap-1 font-medium">
                                                                <span>{{ __('user-player-show.view_full_match') }}</span>
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                </div>
            @endif
        </div>

        <!-- Club Transitions -->
        <div class="mt-6 mb-6">
            <a href="{{ route('players.transitions', $player) }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-zinc-900 dark:text-white font-medium">{{ __('user-player-show.club_transitions') }}</span>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clubTransitions->count() }} {{ __('user-player-show.transitions') }}</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

    @endif
</div>
