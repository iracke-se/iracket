<div class="max-w-2xl mx-auto">
    <!-- Action Buttons -->
    @if(auth()->id() === $player->id)
        <!-- Edit Button (only for own profile) -->
        <div class="flex justify-end mb-4">
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                {{ __('Edit Profile') }}
            </a>
        </div>
    @else
        <!-- Monitor Button (for other players) -->
        <div class="flex justify-end mb-4">
            <button
                wire:click="toggleMonitor"
                class="flex items-center gap-2 px-4 py-2 font-medium rounded-lg transition-colors {{ $isMonitoring ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50' : 'bg-accent text-white hover:bg-accent/90' }}"
            >
                @if($isMonitoring)
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                    {{ __('Stop Monitoring') }}
                @else
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{ __('Monitor Player') }}
                @endif
            </button>
        </div>
    @endif

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
            <div class="flex items-center justify-center gap-2">
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $player->name }}</h1>
                @if($rankingPosition && $rankingPosition <= 3)
                    <x-ranking-badge
                        :position="$rankingPosition"
                        :category="$rankingCategory"
                        size="md"
                    />
                @endif
            </div>

            <!-- Registered Name -->
            @if($player->user_fullname && $player->user_fullname !== $player->name)
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('Registered as: ') }}{{ $player->user_fullname }}
                </p>
            @endif

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

    <!-- Own Profile Section -->
    @if($isOwnProfile)
        <!-- Top Monitored Players Carousel -->
        @if($topMonitoredPlayers->isNotEmpty())
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Top Monitored Players') }}</h2>
                <div class="flex overflow-x-auto gap-3 pb-2 -mx-4 px-4 snap-x snap-mandatory scrollbar-hide">
                    @foreach($topMonitoredPlayers as $monitoredPlayer)
                        <a href="{{ route('players.show', $monitoredPlayer) }}" wire:navigate class="flex-shrink-0 w-32 snap-start">
                            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 text-center hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                @if($monitoredPlayer->profile_picture)
                                    <img src="{{ Storage::url($monitoredPlayer->profile_picture) }}" alt="{{ $monitoredPlayer->name }}" class="w-12 h-12 rounded-full object-cover mx-auto mb-2">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-2">
                                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $monitoredPlayer->initials() }}</span>
                                    </div>
                                @endif
                                <div class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $monitoredPlayer->first_name }}</div>
                                @php
                                    $playerRanking = $monitoredPlayer->monthlyRankings->first();
                                @endphp
                                @if($playerRanking)
                                    <div class="text-xs text-accent font-medium">{{ number_format($playerRanking->points) }} pts</div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- My Monitored Players Link -->
        <div class="mb-6">
            <a href="{{ route('my-monitored.index') }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    <span class="text-zinc-900 dark:text-white font-medium">{{ __('My Monitored Players') }}</span>
                </div>
                <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <!-- Last Matches from Monitored Players Carousel -->
        @if($monitoredPlayersMatches->isNotEmpty())
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('Monitored Players Matches') }}</h2>
                <div class="flex overflow-x-auto gap-3 pb-2 -mx-4 px-4 snap-x snap-mandatory scrollbar-hide">
                    @foreach($monitoredPlayersMatches as $match)
                        <a href="{{ route('matches.show', $match) }}" wire:navigate class="flex-shrink-0 w-64 snap-start">
                            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                <div class="text-center mb-3">
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $match->played_at->format('d M Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <!-- Player 1 -->
                                    <div class="flex-1 text-center">
                                        @if($match->player1->profile_picture)
                                            <img src="{{ Storage::url($match->player1->profile_picture) }}" alt="{{ $match->player1->name }}" class="w-10 h-10 rounded-full object-cover mx-auto mb-1">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-1">
                                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $match->player1->initials() }}</span>
                                            </div>
                                        @endif
                                        <div class="text-xs font-medium truncate {{ $match->winner_id === $match->player1_id ? 'text-green-500 dark:text-green-400' : 'text-zinc-900 dark:text-white' }}">
                                            {{ $match->player1->first_name }}
                                        </div>
                                    </div>
                                    <!-- Score -->
                                    <div class="px-2 text-center">
                                        <div class="text-lg font-bold text-zinc-700 dark:text-zinc-200">{{ $match->player1_sets }} - {{ $match->player2_sets }}</div>
                                    </div>
                                    <!-- Player 2 -->
                                    <div class="flex-1 text-center">
                                        @if($match->player2->profile_picture)
                                            <img src="{{ Storage::url($match->player2->profile_picture) }}" alt="{{ $match->player2->name }}" class="w-10 h-10 rounded-full object-cover mx-auto mb-1">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-1">
                                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $match->player2->initials() }}</span>
                                            </div>
                                        @endif
                                        <div class="text-xs font-medium truncate {{ $match->winner_id === $match->player2_id ? 'text-green-500 dark:text-green-400' : 'text-zinc-900 dark:text-white' }}">
                                            {{ $match->player2->first_name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- My Matches Link -->
        <div class="mb-6">
            <a href="{{ route('matches.index') }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <span class="text-zinc-900 dark:text-white font-medium">{{ __('My Matches') }}</span>
                </div>
                <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    @endif

    <!-- Rankings History -->
    <div>
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-player-show.rankings_history') }}</h2>

        @if($rankingsHistory->isEmpty())
            <div class="text-center py-8 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.no_ranking_history') }}</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($rankingsHistory as $ranking)
                    <div wire:key="ranking-{{ $ranking->id }}" class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                        <!-- Clickable Header -->
                        <button
                            wire:click="toggleRanking({{ $ranking->id }})"
                            class="w-full px-4 py-3 flex items-center justify-between hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                        >
                            <div class="flex items-center gap-4 flex-1">
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $ranking->formatted_date }}</span>
                                <span class="text-sm text-zinc-900 dark:text-white">#{{ $ranking->rank }}</span>
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($ranking->points) }} pts</span>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($ranking->points_change > 0)
                                    <span class="text-sm text-green-500 dark:text-green-400">+{{ $ranking->points_change }}</span>
                                @elseif($ranking->points_change < 0)
                                    <span class="text-sm text-red-500 dark:text-red-400">{{ $ranking->points_change }}</span>
                                @else
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">0</span>
                                @endif
                                <svg
                                    class="w-5 h-5 text-zinc-400 transition-transform {{ $this->expandedRankingId === $ranking->id ? 'rotate-180' : '' }}"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        <!-- Expandable Content -->
                        @if($this->expandedRankingId === $ranking->id)
                            <div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4">
                                <!-- Debug: Show which month we're displaying -->
                                <p class="text-xs text-zinc-400 mb-2">Showing matches for: {{ $ranking->formatted_date }}</p>
                                @if($expandedRankingMatches->isEmpty())
                                    <p class="text-sm text-center text-zinc-500 dark:text-zinc-400 py-4">{{ __('No matches found for this month') }}</p>
                                @else
                                    <div class="space-y-2">
                                        @foreach($expandedRankingMatches as $match)
                                            @php
                                                // Check if this is a ScrapedMatch or GameMatch
                                                $isScrapedMatch = $match instanceof \App\Models\Scraper\ScrapedMatch;

                                                if ($isScrapedMatch) {
                                                    // ScrapedMatch data
                                                    $playerFullName = $player->last_name . ', ' . $player->first_name;
                                                    $isPlayerMatch = $match->player_name === $playerFullName;
                                                    $opponentName = $isPlayerMatch ? $match->opponent_name : $match->player_name;
                                                    $myMatchPoints = $isPlayerMatch ? $match->match_points : null;
                                                    $matchDate = $match->match_date ? \Carbon\Carbon::parse($match->match_date) : null;
                                                    $score = $match->score ?? $match->result;
                                                    $won = $match->result === 'W' || ($match->winner && $match->winner === $playerFullName);
                                                } else {
                                                    // GameMatch data
                                                    $isPlayer1 = $match->player1_id === $player->id;
                                                    $opponent = $isPlayer1 ? $match->player2 : $match->player1;
                                                    $opponentName = $opponent->name;
                                                    $mySets = $isPlayer1 ? $match->player1_sets : $match->player2_sets;
                                                    $opponentSets = $isPlayer1 ? $match->player2_sets : $match->player1_sets;
                                                    $myPointsChange = $isPlayer1 ? $match->player1_points_change : $match->player2_points_change;
                                                    $myMatchPoints = $isPlayer1 ? $match->player1_match_points : $match->player2_match_points;
                                                    $matchDate = $match->played_at;
                                                    $score = "$mySets - $opponentSets";
                                                    $won = $match->winner_id === $player->id;
                                                }
                                            @endphp
                                            <div class="block p-3 bg-white dark:bg-zinc-800 rounded-lg">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ $matchDate ? $matchDate->format('d M Y') : 'N/A' }}
                                                    </span>
                                                    <div class="flex items-center gap-2">
                                                        @if($myMatchPoints)
                                                            <span class="text-xs font-medium px-2 py-0.5 rounded {{ $myMatchPoints > 0 ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-red-500/10 text-red-600 dark:text-red-400' }}">
                                                                {{ $myMatchPoints > 0 ? '+' : '' }}{{ $myMatchPoints }} pts
                                                            </span>
                                                        @elseif(!$isScrapedMatch && isset($myPointsChange) && $myPointsChange)
                                                            <span class="text-xs font-medium px-2 py-0.5 rounded {{ $myPointsChange > 0 ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-red-500/10 text-red-600 dark:text-red-400' }}">
                                                                {{ $myPointsChange > 0 ? '+' : '' }}{{ $myPointsChange }} pts
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center gap-2">
                                                        <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                            <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                                {{ substr($opponentName, 0, 2) }}
                                                            </span>
                                                        </div>
                                                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $opponentName }}</span>
                                                    </div>
                                                    <span class="text-sm font-bold {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                                        {{ $score }}
                                                        <span class="text-xs ml-1">{{ $won ? 'W' : 'L' }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- View All Matches Button -->
    <div class="mt-6">
        <a href="{{ route('players.matches', $player) }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <span class="text-zinc-900 dark:text-white font-medium">{{ __('View All Matches') }}</span>
            </div>
            <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <!-- Club Transitions Link -->
    <div class="mt-6">
        <a href="{{ route('players.transitions', $player) }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                </div>
                <div>
                    <span class="text-zinc-900 dark:text-white font-medium">{{ __('Club Transitions') }}</span>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clubTransitions->count() }} {{ __('transitions') }}</p>
                </div>
            </div>
            <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <!-- View All Matches Button (Own Profile Only) -->
    @if($isOwnProfile)
        <div class="mt-6">
            <a href="{{ route('matches.index') }}" class="flex items-center justify-between p-4 bg-accent text-white rounded-xl hover:bg-accent/90 transition-colors" wire:navigate>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-white/10">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="font-medium">{{ __('View All My Matches') }}</span>
                </div>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    @endif
</div>
