<div class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4">
    @if($matches->isEmpty())
        <p class="text-sm text-center text-zinc-500 dark:text-zinc-400 py-4">{{ __('user-player-show.no_matches_for_month') }}</p>
    @else
        <div class="space-y-4">
            @foreach($matches as $match)
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
