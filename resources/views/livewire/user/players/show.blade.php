<div class="max-w-2xl mx-auto">
    @if($isOwnProfile)
        {{-- ===================== OWN PROFILE — New Design ===================== --}}

        <!-- Edit link -->
        <div class="flex justify-end px-4 pt-2 mb-2">
            <a href="{{ route('profile.edit') }}" class="text-accent text-sm font-medium py-1" wire:navigate>
                {{ __('Edit') }}
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
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 px-4">{{ __('About Me') }}</p>
            <div class="border-t border-zinc-200 dark:border-zinc-800">
                <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Club') }}</span>
                    @if($player->club)
                        <a href="{{ route('clubs.show', $player->club) }}" wire:navigate class="text-sm text-zinc-500 dark:text-zinc-400">{{ $player->club->name }}</a>
                    @else
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('Not set') }}</span>
                    @endif
                </div>

                <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Age') }}</span>
                    @php
                        $displayAge = $player->birth_year
                            ? now()->year - $player->birth_year
                            : $player->age;
                    @endphp
                    @if($displayAge)
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $displayAge }} {{ __('user-player-show.years') }}</span>
                    @else
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('Not set') }}</span>
                    @endif
                </div>

                <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('My Ranking') }}</span>
                    @if($currentRanking)
                        <span class="px-3 py-1 bg-accent text-white text-xs font-bold rounded-full">{{ number_format($currentRanking->points) }} p</span>
                    @else
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('Not set') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Latest Rankings Section -->
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 px-4">{{ __('Latest Ranking') }}</p>
            <div class="border-t border-zinc-200 dark:border-zinc-800">
                @if($rankingsHistory->isEmpty())
                    <div class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Ranking history') }}</span>
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('Not set') }}</span>
                    </div>
                @else
                    <div class="flex items-center px-4 py-2 border-b border-zinc-200 dark:border-zinc-800">
                        <span class="flex-1 text-xs font-bold text-zinc-900 dark:text-white">{{ __('Month') }}</span>
                        <span class="w-20 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('Position') }}</span>
                        <span class="w-16 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('Points') }}</span>
                        <span class="w-10 text-xs font-bold text-zinc-900 dark:text-white text-right">+/-</span>
                    </div>
                    @foreach($rankingsHistory as $ranking)
                        <div wire:key="ranking-{{ $ranking->id }}" class="flex items-center px-4 py-3 border-b border-zinc-100 dark:border-zinc-800/50">
                            <span class="flex-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $ranking->formatted_date }}</span>
                            <span class="w-20 text-sm text-zinc-600 dark:text-zinc-400 text-center">{{ number_format($ranking->rank) }}</span>
                            <span class="w-16 text-sm font-semibold text-accent text-center">{{ number_format($ranking->points) }}</span>
                            <span class="w-10 text-sm font-semibold text-right {{ $ranking->points_change > 0 ? 'text-green-500 dark:text-green-400' : ($ranking->points_change < 0 ? 'text-red-500 dark:text-red-400' : 'text-zinc-400 dark:text-zinc-500') }}">
                                {{ $ranking->points_change !== null ? ($ranking->points_change > 0 ? '+' : '') . $ranking->points_change : '-' }}
                            </span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Top Monitored Players Section -->
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 px-4">{{ __('Top Monitored Players') }}</p>
            @if($topMonitoredPlayers->isEmpty())
                <div class="px-4">
                    <a href="{{ route('players.index') }}" wire:navigate
                       class="flex items-center justify-center gap-2 w-full py-5 bg-zinc-100 dark:bg-zinc-800 rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 text-accent font-medium text-sm hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ __('Add Player') }}
                    </a>
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
                                <span class="bg-accent text-white text-xs font-bold px-2.5 py-0.5 rounded-full">{{ number_format($latestRanking->points) }} p</span>
                            @else
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
            <div class="border-t border-zinc-200 dark:border-zinc-800 mt-3">
                <a href="{{ route('my-monitored.index') }}" wire:navigate class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('My Monitored Players') }}</span>
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Latest Matches from Monitored Players -->
        @if($monitoredPlayersMatches->isNotEmpty())
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 px-4">{{ __('Latest Matches') }}</p>
            <div class="flex gap-3 overflow-x-auto px-4 pb-2" style="-webkit-overflow-scrolling: touch; scrollbar-width: none;">
                @foreach($monitoredPlayersMatches as $match)
                    @php
                        $p1 = $match->player1;
                        $p2 = $match->player2;
                        $p1Sets = $match->player1_sets ?? 0;
                        $p2Sets = $match->player2_sets ?? 0;
                        $p1Change = $match->player1_points_change;
                        $p2Change = $match->player2_points_change;
                        if ($match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty()) {
                            $s1 = 0; $s2 = 0;
                            foreach ($match->liveMatchGame->sets as $set) {
                                if ($set->player1_points > $set->player2_points) { $s1++; } else { $s2++; }
                            }
                            $p1Sets = $s1; $p2Sets = $s2;
                        }
                    @endphp
                    <a href="{{ route('matches.show', $match) }}" wire:navigate
                       class="flex-shrink-0 w-40 bg-zinc-100 dark:bg-zinc-800 rounded-xl p-3 flex flex-col items-center gap-2 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                        <!-- Players row -->
                        <div class="flex items-center justify-center gap-2 w-full">
                            <!-- Player 1 -->
                            <div class="relative flex flex-col items-center gap-1">
                                @if($p1 && $p1->profile_picture)
                                    <img src="{{ Storage::url($p1->profile_picture) }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $p1?->initials() ?? '?' }}</span>
                                    </div>
                                @endif
                                @if($p1Change !== null)
                                    <span class="text-xs font-bold px-1.5 py-0.5 rounded-full {{ $p1Change >= 0 ? 'bg-green-500/20 text-green-600 dark:text-green-400' : 'bg-red-500/20 text-red-600 dark:text-red-400' }}">
                                        {{ $p1Change >= 0 ? '+' : '' }}{{ $p1Change }}
                                    </span>
                                @endif
                            </div>
                            <!-- Score -->
                            <span class="text-base font-bold text-zinc-900 dark:text-white">{{ $p1Sets }}-{{ $p2Sets }}</span>
                            <!-- Player 2 -->
                            <div class="relative flex flex-col items-center gap-1">
                                @if($p2 && $p2->profile_picture)
                                    <img src="{{ Storage::url($p2->profile_picture) }}" class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $p2?->initials() ?? '?' }}</span>
                                    </div>
                                @endif
                                @if($p2Change !== null)
                                    <span class="text-xs font-bold px-1.5 py-0.5 rounded-full {{ $p2Change >= 0 ? 'bg-green-500/20 text-green-600 dark:text-green-400' : 'bg-red-500/20 text-red-600 dark:text-red-400' }}">
                                        {{ $p2Change >= 0 ? '+' : '' }}{{ $p2Change }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <!-- Names -->
                        <div class="text-center w-full">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $p1?->name ?? '—' }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $p2?->name ?? '—' }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Other Links -->
        <div class="mb-6">
            <div class="border-t border-zinc-200 dark:border-zinc-800">
                <a href="{{ route('players.transitions', $player) }}" wire:navigate class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Club Transitions') }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $clubTransitions->count() }}</span>
                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>

                <a href="{{ route('matches.index') }}" wire:navigate class="flex items-center justify-between px-4 py-3.5 border-b border-zinc-200 dark:border-zinc-800">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('My Matches') }}</span>
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
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
                        {{ __('Registered as: ') }}{{ $player->user_fullname }}
                    </p>
                @endif

                @if($player->club)
                    <a href="{{ route('clubs.show', $player->club) }}" wire:navigate class="text-sm text-accent hover:underline mt-1">
                        {{ $player->club->name }}
                    </a>
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

                @if($currentRanking)
                    <div class="mt-4 px-6 py-3 bg-zinc-200 dark:bg-zinc-700 rounded-lg">
                        <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($currentRanking->points) }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.points') }}</div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Latest Ranking -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">{{ __('Latest Ranking') }}</h2>

            @if($rankingsHistory->isEmpty())
                <div class="text-center py-8 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-player-show.no_ranking_history') }}</p>
                </div>
            @else
                <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">
                    <div class="flex items-center px-4 py-2 border-b border-zinc-200 dark:border-zinc-700">
                        <span class="flex-1 text-xs font-bold text-zinc-900 dark:text-white">{{ __('Month') }}</span>
                        <span class="w-20 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('Position') }}</span>
                        <span class="w-16 text-xs font-bold text-zinc-900 dark:text-white text-center">{{ __('Points') }}</span>
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
                                        <p class="text-sm text-center text-zinc-500 dark:text-zinc-400 py-4">{{ __('No matches found for this month') }}</p>
                                    @else
                                        <div class="space-y-2">
                                            @foreach($expandedRankingMatches as $match)
                                                @php
                                                    $isScrapedMatch = $match instanceof \App\Models\Scraper\ScrapedMatch;
                                                    if ($isScrapedMatch) {
                                                        $playerFullName = $player->last_name . ', ' . $player->first_name;
                                                        $isPlayerMatch = $match->player_name === $playerFullName;
                                                        $opponentName = $isPlayerMatch ? $match->opponent_name : $match->player_name;
                                                        $myMatchPoints = $isPlayerMatch ? $match->match_points : null;
                                                        $matchDate = $match->match_date ? \Carbon\Carbon::parse($match->match_date) : null;
                                                        $won = $match->result === 'W' || ($match->winner && $match->winner === $playerFullName);
                                                        $score = $match->score ?? '-';
                                                        $myPointsChange = null;
                                                    } else {
                                                        $isPlayer1 = $match->player1_id === $player->id;
                                                        $opponent = $isPlayer1 ? $match->player2 : $match->player1;
                                                        $opponentName = $opponent->name;
                                                        $myPointsChange = $isPlayer1 ? $match->player1_points_change : $match->player2_points_change;
                                                        $myMatchPoints = $isPlayer1 ? $match->player1_match_points : $match->player2_match_points;
                                                        $matchDate = $match->played_at;
                                                        $won = $match->winner_id === $player->id;
                                                        if ($match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty()) {
                                                            $mySetsWon = 0; $opponentSetsWon = 0;
                                                            foreach ($match->liveMatchGame->sets as $set) {
                                                                $mySetPoints = $isPlayer1 ? $set->player1_points : $set->player2_points;
                                                                $oppSetPoints = $isPlayer1 ? $set->player2_points : $set->player1_points;
                                                                if ($mySetPoints > $oppSetPoints) { $mySetsWon++; } else { $opponentSetsWon++; }
                                                            }
                                                            $mySets = $mySetsWon; $opponentSets = $opponentSetsWon;
                                                        } else {
                                                            $mySets = $isPlayer1 ? $match->player1_sets : $match->player2_sets;
                                                            $opponentSets = $isPlayer1 ? $match->player2_sets : $match->player1_sets;
                                                        }
                                                        $score = "$mySets - $opponentSets";
                                                    }
                                                @endphp
                                                <div class="block p-3 bg-white dark:bg-zinc-800 rounded-lg">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ $matchDate ? $matchDate->format('d M Y') : 'N/A' }}
                                                        </span>
                                                        @php $pointsToShow = $myMatchPoints ?? $myPointsChange ?? null; @endphp
                                                        @if($pointsToShow !== null)
                                                            <span class="text-xs font-bold px-2.5 py-1 rounded {{ $pointsToShow > 0 ? 'bg-green-500/20 text-green-600 dark:text-green-400' : ($pointsToShow < 0 ? 'bg-red-500/20 text-red-600 dark:text-red-400' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400') }}">
                                                                {{ $pointsToShow > 0 ? '+' : '' }}{{ $pointsToShow }} pts
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center justify-between mb-2">
                                                        <div class="flex items-center gap-2">
                                                            <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ substr($opponentName, 0, 2) }}</span>
                                                            </div>
                                                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $opponentName }}</span>
                                                        </div>
                                                        @php $hasSetData = !$isScrapedMatch && $score !== '0 - 0' && $score !== '-'; @endphp
                                                        @if($hasSetData)
                                                            <span class="text-sm font-bold {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                                                {{ $score }} <span class="text-xs ml-1">{{ $won ? 'W' : 'L' }}</span>
                                                            </span>
                                                        @else
                                                            <span class="text-lg font-bold px-3 py-1 rounded {{ $won ? 'bg-green-500/20 text-green-600 dark:text-green-400' : 'bg-red-500/20 text-red-600 dark:text-red-400' }}">
                                                                {{ $won ? 'W' : 'L' }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    @if(!$isScrapedMatch && $match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty())
                                                        <div class="flex items-center gap-1.5 mb-2 flex-wrap">
                                                            @foreach($match->liveMatchGame->sets->sortBy('set_number') as $set)
                                                                @php
                                                                    $isPlayer1 = $match->player1_id === $player->id;
                                                                    $myPoints = $isPlayer1 ? $set->player1_points : $set->player2_points;
                                                                    $opponentPoints = $isPlayer1 ? $set->player2_points : $set->player1_points;
                                                                    $wonSet = $myPoints > $opponentPoints;
                                                                @endphp
                                                                <div class="px-2 py-1 rounded text-xs {{ $wonSet ? 'bg-green-500/10 text-green-600 dark:text-green-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }}">
                                                                    <span class="font-medium">{{ $myPoints }}</span>
                                                                    <span class="opacity-50">-</span>
                                                                    <span class="font-medium">{{ $opponentPoints }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    @if(!$isScrapedMatch)
                                                        <div class="mt-2 pt-2 border-t border-zinc-100 dark:border-zinc-700">
                                                            <a href="{{ route('matches.show', $match) }}" wire:navigate class="text-xs text-accent hover:underline flex items-center gap-1 font-medium">
                                                                <span>View Full Match</span>
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                                </svg>
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
                        <span class="text-zinc-900 dark:text-white font-medium">{{ __('Club Transitions') }}</span>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $clubTransitions->count() }} {{ __('transitions') }}</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

    @endif
</div>
