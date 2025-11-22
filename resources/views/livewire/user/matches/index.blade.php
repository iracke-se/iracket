<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ __('My Matches') }}</h1>

        <a href="{{ route('matches.create') }}" wire:navigate class="flex items-center gap-2 px-4 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('New Match') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="space-y-3 mb-6">
        <!-- Year Selector -->
        <select
            wire:model.live="selectedYear"
            class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            @foreach($years as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>

        <!-- Opponent Filter -->
        <div class="relative">
            @if($selectedOpponentUser)
                <!-- Selected Opponent Display -->
                <div class="flex items-center justify-between px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg">
                    <div class="flex items-center gap-3">
                        @if($selectedOpponentUser->profile_picture)
                            <img src="{{ Storage::url($selectedOpponentUser->profile_picture) }}" alt="{{ $selectedOpponentUser->name }}" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <div class="w-8 h-8 rounded-full bg-zinc-700 flex items-center justify-center">
                                <span class="text-xs font-medium text-zinc-300">{{ $selectedOpponentUser->initials() }}</span>
                            </div>
                        @endif
                        <span class="text-white">{{ $selectedOpponentUser->name }}</span>
                    </div>
                    <button wire:click="clearOpponentFilter" class="text-zinc-400 hover:text-white">
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
                        placeholder="{{ __('Filter by opponent...') }}"
                        class="w-full px-4 py-3 pl-10 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    >
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Opponents Dropdown -->
                @if($opponentSearch && $opponents->isNotEmpty())
                    <div class="absolute top-full left-0 right-0 mt-1 bg-zinc-800 border border-zinc-700 rounded-lg shadow-xl z-50 max-h-60 overflow-y-auto">
                        @foreach($opponents as $opponent)
                            <button
                                wire:click="selectOpponent({{ $opponent->id }})"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-zinc-700 transition-colors text-left"
                            >
                                @if($opponent->profile_picture)
                                    <img src="{{ Storage::url($opponent->profile_picture) }}" alt="{{ $opponent->name }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-zinc-700 flex items-center justify-center">
                                        <span class="text-xs font-medium text-zinc-300">{{ $opponent->initials() }}</span>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm text-white">{{ $opponent->name }}</div>
                                    @if($opponent->club)
                                        <div class="text-xs text-zinc-400">{{ $opponent->club->name }}</div>
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
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-zinc-400">{{ __('No matches in') }} {{ $selectedYear }}</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($matchesByMonth as $monthName => $matches)
                <div>
                    <h2 class="text-sm font-medium text-zinc-400 mb-3">{{ $monthName }}</h2>
                    <div class="space-y-3">
                        @foreach($matches as $match)
                            @php
                                $user = auth()->user();
                                $isPlayer1 = $match->player1_id === $user->id;
                                $opponent = $isPlayer1 ? $match->player2 : $match->player1;
                                $myClub = $isPlayer1 ? $match->player1->club : $match->player2->club;
                                $opponentClub = $isPlayer1 ? $match->player2->club : $match->player1->club;
                                $mySets = $isPlayer1 ? $match->player1_sets : $match->player2_sets;
                                $opponentSets = $isPlayer1 ? $match->player2_sets : $match->player1_sets;
                                $won = $match->winner_id === $user->id;
                            @endphp
                            <a
                                href="{{ route('matches.show', $match) }}"
                                wire:navigate
                                class="block p-4 bg-zinc-800 rounded-xl hover:bg-zinc-700 transition-colors"
                            >
                                <!-- Date and Result -->
                                <div class="text-center mb-3">
                                    <div class="text-xs text-zinc-400">{{ $match->played_at->format('d M Y') }}</div>
                                    <div class="text-xl font-bold {{ $won ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $mySets }} - {{ $opponentSets }}
                                    </div>
                                </div>

                                <!-- Players -->
                                <div class="flex items-center justify-between">
                                    <!-- Me -->
                                    <div class="flex-1 text-center">
                                        @if($user->profile_picture)
                                            <img src="{{ Storage::url($user->profile_picture) }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover mx-auto mb-1">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-zinc-700 flex items-center justify-center mx-auto mb-1">
                                                <span class="text-xs font-medium text-zinc-300">{{ $user->initials() }}</span>
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-white truncate">{{ $user->first_name }}</div>
                                        @if($myClub)
                                            <div class="text-xs text-zinc-400 truncate">{{ $myClub->name }}</div>
                                        @endif
                                    </div>

                                    <!-- Result Indicator -->
                                    <div class="px-4">
                                        <span class="text-xs font-medium {{ $won ? 'text-green-400' : 'text-red-400' }}">
                                            {{ $won ? 'W' : 'L' }}
                                        </span>
                                    </div>

                                    <!-- Opponent -->
                                    <div class="flex-1 text-center">
                                        @if($opponent->profile_picture)
                                            <img src="{{ Storage::url($opponent->profile_picture) }}" alt="{{ $opponent->name }}" class="w-10 h-10 rounded-full object-cover mx-auto mb-1">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-zinc-700 flex items-center justify-center mx-auto mb-1">
                                                <span class="text-xs font-medium text-zinc-300">{{ $opponent->initials() }}</span>
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-white truncate">{{ $opponent->first_name }}</div>
                                        @if($opponentClub)
                                            <div class="text-xs text-zinc-400 truncate">{{ $opponentClub->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
