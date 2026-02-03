<div class="max-w-2xl mx-auto">
    @php
        $user = auth()->user();
        $isParticipant = $match->player1_id === $user->id || $match->player2_id === $user->id;
        $player1 = $match->player1;
        $player2 = $match->player2;
    @endphp

    <!-- Match Header -->
    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-6 mb-6">
        <!-- Date -->
        <div class="text-center mb-4">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $match->played_at->format('l, d F Y') }}</p>
        </div>

        <!-- Result -->
        <div class="text-center mb-6">
            @if($match->player1_sets > 0 || $match->player2_sets > 0)
                <div class="text-4xl font-bold text-zinc-900 dark:text-white">
                    {{ $match->player1_sets }} - {{ $match->player2_sets }}
                </div>
            @elseif($match->scrapedMatches->isNotEmpty())
                @php
                    $player1Name = $match->player1->last_name . ', ' . $match->player1->first_name;
                    $player2Name = $match->player2->last_name . ', ' . $match->player2->first_name;

                    $player1Points = null;
                    $player2Points = null;

                    foreach ($match->scrapedMatches as $sm) {
                        if ($sm->player_name === $player1Name) {
                            $player1Points = $sm->match_points;
                        }
                        if ($sm->player_name === $player2Name) {
                            $player2Points = $sm->match_points;
                        }
                    }

                    if ($player1Points !== null && $player2Points === null) {
                        $player2Points = -$player1Points;
                    } elseif ($player2Points !== null && $player1Points === null) {
                        $player1Points = -$player2Points;
                    }
                @endphp
                @if($player1Points !== null && $player2Points !== null)
                    <div class="flex items-center justify-center gap-3">
                        <span class="px-6 py-3 rounded-lg text-3xl font-bold {{ $player1Points > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                            {{ $player1Points > 0 ? 'W' : 'L' }}
                        </span>
                        <span class="px-6 py-3 rounded-lg text-3xl font-bold {{ $player2Points > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                            {{ $player2Points > 0 ? 'W' : 'L' }}
                        </span>
                    </div>
                @endif
            @elseif($match->winner_id)
                @php
                    $player1Score = $match->winner_id === $match->player1_id ? 2 : 0;
                    $player2Score = $match->winner_id === $match->player2_id ? 2 : 0;
                @endphp
                <div class="text-4xl font-bold text-zinc-900 dark:text-white">
                    {{ $player1Score }} - {{ $player2Score }}
                </div>
            @else
                <div class="text-4xl font-bold text-zinc-400">-</div>
            @endif

            @if($match->winner)
                <p class="text-sm text-accent mt-2">
                    {{ __('user-matches.winner') }}: {{ $match->winner->name }}
                </p>
            @endif
        </div>

        <!-- Players -->
        <div class="flex items-start justify-between">
            <!-- Player 1 -->
            <div class="flex-1 text-center">
                @if($player1->profile_picture)
                    <img src="{{ Storage::url($player1->profile_picture) }}" alt="{{ $player1->name }}" class="w-16 h-16 rounded-full object-cover mx-auto mb-2">
                @else
                    <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-2">
                        <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">{{ $player1->initials() }}</span>
                    </div>
                @endif
                <a href="{{ route('players.show', $player1) }}" wire:navigate class="font-medium text-zinc-900 dark:text-white hover:text-accent">
                    {{ $player1->name }}
                </a>
                @if($player1->club)
                    <a href="{{ route('clubs.show', $player1->club) }}" wire:navigate class="block text-xs text-zinc-500 dark:text-zinc-400 hover:text-accent">{{ $player1->club->name }}</a>
                @endif
            </div>

            <!-- VS -->
            <div class="px-4 py-8">
                <span class="text-zinc-400 dark:text-zinc-500 font-medium">VS</span>
            </div>

            <!-- Player 2 -->
            <div class="flex-1 text-center">
                @if($player2->profile_picture)
                    <img src="{{ Storage::url($player2->profile_picture) }}" alt="{{ $player2->name }}" class="w-16 h-16 rounded-full object-cover mx-auto mb-2">
                @else
                    <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-2">
                        <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">{{ $player2->initials() }}</span>
                    </div>
                @endif
                <a href="{{ route('players.show', $player2) }}" wire:navigate class="font-medium text-zinc-900 dark:text-white hover:text-accent">
                    {{ $player2->name }}
                </a>
                @if($player2->club)
                    <a href="{{ route('clubs.show', $player2->club) }}" wire:navigate class="block text-xs text-zinc-500 dark:text-zinc-400 hover:text-accent">{{ $player2->club->name }}</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Description -->
    @if($match->description)
        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 mb-6">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-2">{{ __('user-matches.match_notes') }}</h3>
            <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $match->description }}</p>
        </div>
    @endif

    <!-- Match Status -->
    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 mb-6">
        <div class="flex items-center justify-between">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('user-matches.status') }}</span>
            <span class="px-2 py-1 rounded text-xs font-medium {{ $match->status === 'confirmed' ? 'bg-green-500/20 text-green-600 dark:text-green-400' : ($match->status === 'disputed' ? 'bg-red-500/20 text-red-600 dark:text-red-400' : 'bg-yellow-500/20 text-yellow-600 dark:text-yellow-400') }}">
                {{ ucfirst($match->status) }}
            </span>
        </div>
    </div>

    <!-- Actions -->
    @if($match->created_by === $user->id)
        <div class="flex gap-3 mb-6">
            <a href="{{ route('matches.edit', $match) }}" wire:navigate class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-center rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                {{ __('user-matches.edit_match') }}
            </a>
        </div>
    @endif

    <!-- Other Matches Between These Players -->
    @if(isset($otherMatches) && $otherMatches->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-4">Other Matches</h2>
            <div class="space-y-3">
                @foreach($otherMatches as $otherMatch)
                    <a href="{{ route('matches.show', $otherMatch) }}" wire:navigate class="block bg-zinc-100 dark:bg-zinc-800 rounded-lg p-4 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                        <div class="flex items-center justify-between">
                            <!-- Players -->
                            <div class="flex-1">
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="{{ $otherMatch->winner_id === $otherMatch->player1_id ? 'font-bold text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ $otherMatch->player1->name }}
                                    </span>
                                    <span class="text-zinc-400">vs</span>
                                    <span class="{{ $otherMatch->winner_id === $otherMatch->player2_id ? 'font-bold text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400' }}">
                                        {{ $otherMatch->player2->name }}
                                    </span>
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ $otherMatch->played_at?->format('M d, Y') ?? '-' }}
                                </div>
                            </div>

                            <!-- Result -->
                            <div class="ml-4">
                                @if($otherMatch->player1_sets > 0 || $otherMatch->player2_sets > 0)
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $otherMatch->player1_sets }} - {{ $otherMatch->player2_sets }}</span>
                                @elseif($otherMatch->scrapedMatches->isNotEmpty())
                                    @php
                                        $p1Name = $otherMatch->player1->last_name . ', ' . $otherMatch->player1->first_name;
                                        $p2Name = $otherMatch->player2->last_name . ', ' . $otherMatch->player2->first_name;
                                        $p1Points = null;
                                        $p2Points = null;

                                        foreach ($otherMatch->scrapedMatches as $sm) {
                                            if ($sm->player_name === $p1Name) {
                                                $p1Points = $sm->match_points;
                                            }
                                            if ($sm->player_name === $p2Name) {
                                                $p2Points = $sm->match_points;
                                            }
                                        }

                                        if ($p1Points !== null && $p2Points === null) {
                                            $p2Points = -$p1Points;
                                        } elseif ($p2Points !== null && $p1Points === null) {
                                            $p1Points = -$p2Points;
                                        }
                                    @endphp
                                    @if($p1Points !== null && $p2Points !== null)
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2.5 py-0.5 rounded text-xs font-bold {{ $p1Points > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                                                {{ $p1Points > 0 ? 'W' : 'L' }}
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded text-xs font-bold {{ $p2Points > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                                                {{ $p2Points > 0 ? 'W' : 'L' }}
                                            </span>
                                        </div>
                                    @endif
                                @elseif($otherMatch->winner_id)
                                    @php
                                        $p1Score = $otherMatch->winner_id === $otherMatch->player1_id ? 2 : 0;
                                        $p2Score = $otherMatch->winner_id === $otherMatch->player2_id ? 2 : 0;
                                    @endphp
                                    <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $p1Score }} - {{ $p2Score }}</span>
                                @else
                                    <span class="text-sm text-zinc-400">-</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
