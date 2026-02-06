<div class="max-w-2xl mx-auto">
    <!-- Header with Back Button -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('players.show', $player) }}" wire:navigate class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                <svg class="w-5 h-5 text-zinc-900 dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Matches') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $player->name }}</p>
            </div>
        </div>
    </div>

    <!-- Stats Summary -->
    @if($totalMatches > 0)
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $totalMatches }}</div>
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</div>
            </div>
            <div class="bg-green-500/10 dark:bg-green-500/20 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $wins }}</div>
                <div class="text-xs text-green-600 dark:text-green-400">{{ __('Wins') }}</div>
            </div>
            <div class="bg-red-500/10 dark:bg-red-500/20 rounded-xl p-4 text-center">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $losses }}</div>
                <div class="text-xs text-red-600 dark:text-red-400">{{ __('Losses') }}</div>
            </div>
        </div>
    @endif

    <!-- Year Selector -->
    <div class="mb-6">
        <select
            wire:model.live="selectedYear"
            class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            @foreach($years as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>
    </div>

    <!-- Matches by Month -->
    @if($matchesByMonth->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-100 dark:bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-zinc-500 dark:text-zinc-400">{{ __('No matches found in') }} {{ $selectedYear }}</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($matchesByMonth as $monthName => $matches)
                <div>
                    <h2 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3">{{ $monthName }}</h2>
                    <div class="space-y-3">
                        @foreach($matches as $match)
                            @php
                                $isPlayer1 = $match->player1_id === $player->id;
                                $opponent = $isPlayer1 ? $match->player2 : $match->player1;
                                $playerClub = $isPlayer1 ? $match->player1->club : $match->player2->club;
                                $opponentClub = $isPlayer1 ? $match->player2->club : $match->player1->club;
                                $playerSets = $isPlayer1 ? $match->player1_sets : $match->player2_sets;
                                $opponentSets = $isPlayer1 ? $match->player2_sets : $match->player1_sets;
                                $won = $match->winner_id === $player->id;
                            @endphp
                            <a
                                href="{{ route('matches.show', $match) }}"
                                wire:navigate
                                class="block p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                            >
                                <!-- Date and Result -->
                                <div class="text-center mb-3">
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $match->played_at->format('d M Y') }}</div>

                                    @if($match->player1_sets > 0 || $match->player2_sets > 0)
                                        {{-- Show actual set scores --}}
                                        <div class="text-xl font-bold {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                            {{ $playerSets }} - {{ $opponentSets }}
                                        </div>
                                    @elseif($match->scrapedMatches->isNotEmpty())
                                        {{-- Show match points from scraped data --}}
                                        @php
                                            $playerName = $isPlayer1 ? ($match->player1->last_name . ', ' . $match->player1->first_name) : ($match->player2->last_name . ', ' . $match->player2->first_name);
                                            $opponentName = $isPlayer1 ? ($match->player2->last_name . ', ' . $match->player2->first_name) : ($match->player1->last_name . ', ' . $match->player1->first_name);

                                            $playerPoints = null;
                                            $opponentPoints = null;

                                            foreach ($match->scrapedMatches as $sm) {
                                                if ($sm->player_name === $playerName) {
                                                    $playerPoints = $sm->match_points;
                                                }
                                                if ($sm->player_name === $opponentName) {
                                                    $opponentPoints = $sm->match_points;
                                                }
                                            }

                                            if ($playerPoints !== null && $opponentPoints === null) {
                                                $opponentPoints = -$playerPoints;
                                            } elseif ($opponentPoints !== null && $playerPoints === null) {
                                                $playerPoints = -$opponentPoints;
                                            }
                                        @endphp
                                        @if($playerPoints !== null && $opponentPoints !== null)
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="px-3 py-1 rounded-lg text-lg font-bold font-mono {{ $playerPoints > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                                                    {{ $playerPoints > 0 ? '+' : '' }}{{ $playerPoints }}
                                                </span>
                                                <span class="px-3 py-1 rounded-lg text-lg font-bold font-mono {{ $opponentPoints > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                                                    {{ $opponentPoints > 0 ? '+' : '' }}{{ $opponentPoints }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="text-xl font-bold {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                                {{ $won ? '2 - 0' : '0 - 2' }}
                                            </div>
                                        @endif
                                    @elseif($match->winner_id)
                                        {{-- Show 2-0 based on winner --}}
                                        <div class="text-xl font-bold {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                            {{ $won ? '2 - 0' : '0 - 2' }}
                                        </div>
                                    @else
                                        {{-- No score available --}}
                                        <div class="text-xl font-bold text-zinc-400">-</div>
                                    @endif
                                </div>

                                <!-- Players -->
                                <div class="flex items-center justify-between">
                                    <!-- Player -->
                                    <div class="flex-1 text-center">
                                        @if($player->profile_picture)
                                            <img src="{{ Storage::url($player->profile_picture) }}" alt="{{ $player->name }}" class="w-10 h-10 rounded-full object-cover mx-auto mb-1">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-1">
                                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $player->initials() }}</span>
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $player->first_name }}</div>
                                        @if($playerClub)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $playerClub->name }}</div>
                                        @endif
                                    </div>

                                    <!-- Result Indicator -->
                                    <div class="px-4">
                                        <span class="text-xs font-medium {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                            {{ $won ? 'W' : 'L' }}
                                        </span>
                                    </div>

                                    <!-- Opponent -->
                                    <div class="flex-1 text-center">
                                        @if($opponent->profile_picture)
                                            <img src="{{ Storage::url($opponent->profile_picture) }}" alt="{{ $opponent->name }}" class="w-10 h-10 rounded-full object-cover mx-auto mb-1">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-1">
                                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $opponent->initials() }}</span>
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-zinc-900 dark:text-white truncate">{{ $opponent->first_name }}</div>
                                        @if($opponentClub)
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">{{ $opponentClub->name }}</div>
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
