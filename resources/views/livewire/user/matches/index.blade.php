<div class="max-w-2xl mx-auto">

    {{-- Connect Player Modal (pure Livewire, no Alpine timing issues) --}}
    @if($showConnectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="w-full max-w-sm bg-white dark:bg-zinc-900 rounded-2xl shadow-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-center w-14 h-14 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <svg class="w-7 h-7 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white text-center mb-2">{{ __('user-matches.connect_required_title') }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center">{{ __('user-matches.connect_to_create') }}</p>
            </div>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('user-matches.my_matches') }}</h1>

        @if(auth()->user()->is_active_player)
            <a href="{{ route('matches.create') }}" wire:navigate class="flex items-center gap-2 px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('user-matches.new_match') }}
            </a>
        @else
            <button wire:click="$set('showConnectModal', true)" class="flex items-center gap-2 px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 rounded-lg cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('user-matches.new_match') }}
            </button>
        @endif
    </div>

    <!-- Filters -->
    <div class="space-y-3 mb-6">
        <!-- Year Selector -->
        <select
            wire:model.live="selectedYear"
            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            @foreach($years as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>

        <!-- Opponent Filter -->
        <div class="relative">
            @if($selectedOpponentUser)
                <!-- Selected Opponent Display -->
                <div class="flex items-center justify-between px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        @if($selectedOpponentUser->profile_picture)
                            <img src="{{ Storage::url($selectedOpponentUser->profile_picture) }}" alt="{{ $selectedOpponentUser->name }}" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $selectedOpponentUser->initials() }}</span>
                            </div>
                        @endif
                        <span class="text-zinc-900 dark:text-white">{{ $selectedOpponentUser->name }}</span>
                    </div>
                    <button wire:click="clearOpponentFilter" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @else
                <!-- Opponent Search -->
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="opponentSearch"
                        placeholder="{{ __('user-matches.filter_by_opponent') }}"
                        class="w-full px-4 py-3 pl-10 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    >
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Opponents Dropdown -->
                @if($opponentSearch && $opponents->isNotEmpty())
                    <div class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto">
                        @foreach($opponents as $opponent)
                            <button
                                wire:click="selectOpponent({{ $opponent->id }})"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors text-left"
                            >
                                @if($opponent->profile_picture)
                                    <img src="{{ Storage::url($opponent->profile_picture) }}" alt="{{ $opponent->name }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $opponent->initials() }}</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm text-zinc-900 dark:text-white">{{ $opponent->name }}</div>
                                    @if($opponent->club)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $opponent->club->name }}</div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Matches by Month -->
    @if($matchesByMonth->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-matches.no_matches_in') }} {{ $selectedYear }}</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($matchesByMonth as $monthName => $matches)
                <div>
                    <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3">{{ $monthName }}</h2>
                    <div class="space-y-3">
                        @foreach($matches as $match)
                            @php
                                $user = auth()->user();
                                $isPlayer1 = $match->player1_id === $user->id;
                                $opponent = $isPlayer1 ? $match->player2 : $match->player1;

                                // Use match_points priority (same as show page)
                                $myPointsRaw = $isPlayer1
                                    ? ($match->player1_match_points ?? $match->player1_points_change)
                                    : ($match->player2_match_points ?? $match->player2_points_change);
                                $oppPointsRaw = $isPlayer1
                                    ? ($match->player2_match_points ?? $match->player2_points_change)
                                    : ($match->player1_match_points ?? $match->player1_points_change);

                                // Determine winner (winner_id → points sign → sets)
                                $won = $match->winner_id === $user->id;
                                if ($match->winner_id === null && $myPointsRaw !== null) {
                                    $won = $myPointsRaw > 0;
                                }

                                $sets = collect();
                                if ($match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty()) {
                                    $sets = $match->liveMatchGame->sets->sortBy('set_number');
                                    $mySetsWon = 0; $oppSetsWon = 0;
                                    foreach ($sets as $set) {
                                        $myPts  = $isPlayer1 ? $set->player1_points : $set->player2_points;
                                        $oppPts = $isPlayer1 ? $set->player2_points : $set->player1_points;
                                        if ($myPts > $oppPts) { $mySetsWon++; } else { $oppSetsWon++; }
                                    }
                                    $mySets = $mySetsWon;
                                    $opponentSets = $oppSetsWon;
                                } else {
                                    $mySets = $isPlayer1 ? ($match->player1_sets ?? 0) : ($match->player2_sets ?? 0);
                                    $opponentSets = $isPlayer1 ? ($match->player2_sets ?? 0) : ($match->player1_sets ?? 0);
                                }

                                // Fix reversed sets if inconsistent with the known result
                                if (($mySets + $opponentSets) > 0) {
                                    $setsConsistent = $won && $mySets >= $opponentSets || !$won && $mySets <= $opponentSets;
                                    if (!$setsConsistent) {
                                        [$mySets, $opponentSets] = [$opponentSets, $mySets];
                                    }
                                }

                                // Last resort: derive from sets
                                if ($match->winner_id === null && $myPointsRaw === null) {
                                    $won = $mySets > $opponentSets;
                                }

                                // Normalize badge points: winner gets positive, loser gets negative (same as show page)
                                $myPointsChange = $myPointsRaw !== null ? ($won ? abs($myPointsRaw) : -abs($myPointsRaw)) : null;
                                $oppPointsChange = $oppPointsRaw !== null ? ($won ? -abs($oppPointsRaw) : abs($oppPointsRaw)) : null;
                                if ($myPointsChange !== null && $oppPointsChange === null) { $oppPointsChange = -$myPointsChange; }
                                elseif ($oppPointsChange !== null && $myPointsChange === null) { $myPointsChange = -$oppPointsChange; }
                            @endphp
                            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl overflow-hidden">

                                {{-- Large score --}}
                                <div class="text-center pt-3 pb-1">
                                    @if($sets->isNotEmpty() || ($mySets + $opponentSets) > 0)
                                        <div class="text-4xl font-bold text-zinc-900 dark:text-white">
                                            {{ $mySets }} - {{ $opponentSets }}
                                        </div>
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
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $match->played_at->format('d M Y') }}</span>
                                </div>

                                {{-- Player vs Opponent --}}
                                <div class="flex items-center justify-between px-6 pb-4">
                                    {{-- Me (left) --}}
                                    <div class="flex-1 flex flex-col items-center text-center">
                                        <div class="relative mb-2">
                                            @if($user->profile_picture)
                                                <img src="{{ Storage::url($user->profile_picture) }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover">
                                            @else
                                                <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                    <span class="text-xl font-semibold text-zinc-600 dark:text-zinc-300">{{ $user->initials() }}</span>
                                                </div>
                                            @endif
                                            @if($myPointsChange !== null)
                                                <span class="absolute -top-1 -right-1 text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white dark:border-zinc-800 {{ $myPointsChange > 0 ? 'bg-green-500 text-white' : ($myPointsChange < 0 ? 'bg-red-500 text-white' : 'bg-zinc-400 text-white') }}">
                                                    {{ $myPointsChange > 0 ? '+' : '' }}{{ $myPointsChange }}
                                                </span>
                                            @endif
                                        </div>
                                        <span class="text-xs font-semibold text-zinc-900 dark:text-white leading-tight">{{ $user->name }}</span>
                                        @if($user->club)
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $user->club->name }}</span>
                                        @endif
                                    </div>

                                    {{-- VS --}}
                                    <div class="px-3">
                                        <span class="text-sm font-medium text-zinc-400 dark:text-zinc-500">VS</span>
                                    </div>

                                    {{-- Opponent (right) --}}
                                    <div class="flex-1 flex flex-col items-center text-center">
                                        <div class="relative mb-2">
                                            @if($opponent?->profile_picture)
                                                <img src="{{ Storage::url($opponent->profile_picture) }}" alt="{{ $opponent->name }}" class="w-16 h-16 rounded-full object-cover">
                                            @else
                                                <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                                    <span class="text-xl font-semibold text-zinc-600 dark:text-zinc-300">{{ $opponent?->initials() ?? '?' }}</span>
                                                </div>
                                            @endif
                                            @if($oppPointsChange !== null)
                                                <span class="absolute -top-1 -right-1 text-xs font-bold px-1.5 py-0.5 rounded-full border-2 border-white dark:border-zinc-800 {{ $oppPointsChange > 0 ? 'bg-green-500 text-white' : ($oppPointsChange < 0 ? 'bg-red-500 text-white' : 'bg-zinc-400 text-white') }}">
                                                    {{ $oppPointsChange > 0 ? '+' : '' }}{{ $oppPointsChange }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($opponent)
                                            <a href="{{ route('players.show', $opponent) }}" wire:navigate class="text-xs font-semibold text-zinc-900 dark:text-white hover:text-accent leading-tight">{{ $opponent->name }}</a>
                                            @if($opponent->club)
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $opponent->club->name }}</span>
                                            @endif
                                        @else
                                            <span class="text-xs font-semibold text-zinc-900 dark:text-white leading-tight">—</span>
                                        @endif
                                    </div>
                                </div>


                                {{-- View Full Match --}}
                                <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-2.5">
                                    <a href="{{ route('matches.show', $match) }}" wire:navigate class="text-xs text-accent hover:underline flex items-center gap-1 font-medium">
                                        <span>{{ __('user-matches.view_full_match') }}</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
