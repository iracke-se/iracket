<div class="max-w-2xl mx-auto">
    @php
        $user = auth()->user();
        $isParticipant = $match->player1_id === $user->id || $match->player2_id === $user->id;
        $isCreator = $match->created_by === $user->id;
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
            @php
                $player1Sets = $match->player1_sets;
                $player2Sets = $match->player2_sets;
                $player1Won = $match->winner_id === $match->player1_id;
                $player2Won = $match->winner_id === $match->player2_id;

                // Get scraped match data for points + result inference
                $p1Name = $player1->last_name . ', ' . $player1->first_name;
                $p2Name = $player2->last_name . ', ' . $player2->first_name;
                $p1ScrapedPoints = null; $p2ScrapedPoints = null;

                foreach ($match->scrapedMatches as $sm) {
                    if ($sm->player_name === $p1Name) { $p1ScrapedPoints = $sm->match_points; $p1ScrapedResult = $sm->result ?? null; }
                    if ($sm->player_name === $p2Name) { $p2ScrapedPoints = $sm->match_points; $p2ScrapedResult = $sm->result ?? null; }
                }

                // Infer winner when winner_id is not set
                if ($match->winner_id === null) {
                    if (isset($p1ScrapedResult) && $p1ScrapedResult === 'W') { $player1Won = true; }
                    elseif (isset($p2ScrapedResult) && $p2ScrapedResult === 'W') { $player2Won = true; }
                }

                // Calculate sets from live center data
                if ($match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty()) {
                    $p1SetsWon = 0; $p2SetsWon = 0;
                    foreach ($match->liveMatchGame->sets as $set) {
                        if ($set->player1_points > $set->player2_points) { $p1SetsWon++; } else { $p2SetsWon++; }
                    }
                    $player1Sets = $p1SetsWon;
                    $player2Sets = $p2SetsWon;
                    // Fix reversed player assignment in liveMatchGame
                    $p1Consistent = ($player1Won && $player1Sets >= $player2Sets) || (!$player1Won && $player1Sets <= $player2Sets);
                    if (!$p1Consistent) {
                        [$player1Sets, $player2Sets] = [$player2Sets, $player1Sets];
                    }
                }

                // Badge points with correct sign
                $p1BadgePoints = $match->player1_match_points ?? $p1ScrapedPoints ?? $match->player1_points_change;
                $p2BadgePoints = $match->player2_match_points ?? $p2ScrapedPoints ?? $match->player2_points_change;
                if ($p1BadgePoints !== null) { $p1BadgePoints = $player1Won ? abs($p1BadgePoints) : -abs($p1BadgePoints); }
                if ($p2BadgePoints !== null) { $p2BadgePoints = $player2Won ? abs($p2BadgePoints) : -abs($p2BadgePoints); }
                if ($p1BadgePoints !== null && $p2BadgePoints === null) { $p2BadgePoints = -$p1BadgePoints; }
                elseif ($p2BadgePoints !== null && $p1BadgePoints === null) { $p1BadgePoints = -$p2BadgePoints; }
            @endphp

            @if($player1Sets > 0 || $player2Sets > 0)
                <div class="text-4xl font-bold text-zinc-900 dark:text-white">
                    {{ $player1Sets }} - {{ $player2Sets }}
                </div>
            @elseif($player1Won || $player2Won)
                <div class="flex items-center justify-center gap-3">
                    <span class="px-6 py-3 rounded-lg text-3xl font-bold {{ $player1Won ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">{{ $player1Won ? 'W' : 'L' }}</span>
                    <span class="text-zinc-400 dark:text-zinc-500 text-2xl font-bold">-</span>
                    <span class="px-6 py-3 rounded-lg text-3xl font-bold {{ $player2Won ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">{{ $player2Won ? 'W' : 'L' }}</span>
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
                <div class="relative inline-block mb-2">
                    @if($player1->profile_picture)
                        <img src="{{ Storage::url($player1->profile_picture) }}" alt="{{ $player1->name }}" class="w-16 h-16 rounded-full object-cover">
                    @else
                        <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">{{ $player1->initials() }}</span>
                        </div>
                    @endif
                    @if(isset($p1BadgePoints) && $p1BadgePoints !== null)
                        <span class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full text-xs font-bold text-white {{ $p1BadgePoints >= 0 ? 'bg-green-500' : 'bg-red-500' }}">
                            {{ $p1BadgePoints >= 0 ? '+' : '' }}{{ $p1BadgePoints }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('players.show', $player1) }}" wire:navigate class="block font-medium text-zinc-900 dark:text-white hover:text-accent">
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
                <div class="relative inline-block mb-2">
                    @if($player2->profile_picture)
                        <img src="{{ Storage::url($player2->profile_picture) }}" alt="{{ $player2->name }}" class="w-16 h-16 rounded-full object-cover">
                    @else
                        <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">{{ $player2->initials() }}</span>
                        </div>
                    @endif
                    @if(isset($p2BadgePoints) && $p2BadgePoints !== null)
                        <span class="absolute -top-1 -right-1 px-1.5 py-0.5 rounded-full text-xs font-bold text-white {{ $p2BadgePoints >= 0 ? 'bg-green-500' : 'bg-red-500' }}">
                            {{ $p2BadgePoints >= 0 ? '+' : '' }}{{ $p2BadgePoints }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('players.show', $player2) }}" wire:navigate class="block font-medium text-zinc-900 dark:text-white hover:text-accent">
                    {{ $player2->name }}
                </a>
                @if($player2->club)
                    <a href="{{ route('clubs.show', $player2->club) }}" wire:navigate class="block text-xs text-zinc-500 dark:text-zinc-400 hover:text-accent">{{ $player2->club->name }}</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Live Center Set Scores -->
    @if($match->liveMatchGame && $match->liveMatchGame->sets->isNotEmpty())
        @php
            $liveGame = $match->liveMatchGame;
            $sets = $liveGame->sets->sortBy('set_number');
        @endphp
        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 mb-6">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3">Set Scores</h3>
            <div class="flex items-center justify-center gap-2 flex-wrap">
                @foreach($sets as $set)
                    @php
                        $p1Won = $set->player1_points > $set->player2_points;
                        $p2Won = $set->player2_points > $set->player1_points;
                    @endphp
                    <div class="text-center">
                        <div class="text-xs text-zinc-400 dark:text-zinc-500 mb-1">Set {{ $set->set_number }}</div>
                        <div class="px-3 py-2 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                            <span class="{{ $p1Won ? 'font-bold text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $set->player1_points }}</span>
                            <span class="text-zinc-400 mx-1">-</span>
                            <span class="{{ $p2Won ? 'font-bold text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $set->player2_points }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($liveGame->detail)
                <div class="mt-3 text-center text-xs text-zinc-400 dark:text-zinc-500">
                    {{ $liveGame->detail->team1_name }} vs {{ $liveGame->detail->team2_name }}
                    ({{ $liveGame->detail->team1_score }}-{{ $liveGame->detail->team2_score }})
                </div>
            @endif
        </div>

    @endif

    <!-- Description (creator only) -->
    @if($isCreator && $match->description)
        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 mb-6">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-2">{{ __('user-matches.match_notes') }}</h3>
            <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $match->description }}</p>
        </div>
    @endif

    <!-- Comments / Tags (creator only) -->
    @if($isCreator)
        @php
            $p1Comments = $match->player1_comments ?? [];
            $p2Comments = $match->player2_comments ?? [];
            $hasComments = !empty($p1Comments) || !empty($p2Comments);
        @endphp
        @if($hasComments)
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 mb-6">
                <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3">{{ __('user-matches.comments_on_opponent') }}</h3>
                <div class="space-y-3">
                    @if(!empty($p1Comments))
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ $player1->name }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($p1Comments as $comment)
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                                        {{ __('user-matches.' . $comment) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if(!empty($p2Comments))
                        <div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ $player2->name }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($p2Comments as $comment)
                                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                                        {{ __('user-matches.' . $comment) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
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
    @php
        $isOpponent = $isParticipant && !$isCreator;
        $isPending = $match->status === 'pending';
    @endphp

    {{-- Opponent: pending match → confirm, reject --}}
    @if($isOpponent && $isPending)
        <div x-data="{ showRejectModal: false }" class="space-y-3 mb-6">
            <div class="flex gap-3">
                <button
                    wire:click="confirmMatch"
                    wire:loading.attr="disabled"
                    class="flex-1 px-4 py-3 bg-accent text-white text-center rounded-lg hover:opacity-90 transition-opacity font-medium"
                >
                    {{ __('user-matches.confirm_match') }}
                </button>
                <button
                    @click="showRejectModal = true"
                    class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-center rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors"
                >
                    {{ __('user-matches.reject_match') }}
                </button>
            </div>

            <!-- Reject Confirmation Modal -->
            <div
                x-show="showRejectModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="showRejectModal = false"
                x-cloak
            >
                <div
                    x-show="showRejectModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white dark:bg-zinc-900 rounded-xl p-6 w-full max-w-sm shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">{{ __('user-matches.reject_match') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">{{ __('user-matches.reject_confirm') }}</p>
                    <div class="flex gap-3">
                        <button
                            @click="showRejectModal = false"
                            class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors text-sm font-medium"
                        >
                            {{ __('user-matches.cancel') }}
                        </button>
                        <button
                            wire:click="rejectMatch"
                            class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium"
                        >
                            {{ __('user-matches.reject_match') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Creator: pending match → waiting notice --}}
    @if($isCreator && $isPending)
        <div class="flex items-center gap-2 px-4 py-3 bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 rounded-lg mb-6 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ __('user-matches.awaiting_confirmation') }}
        </div>
    @endif

    {{-- Creator: pending match → edit + delete --}}
    @if($isCreator && $isPending)
        <div x-data="{ showDeleteModal: false }" class="flex gap-3 mb-6">
            <button
                @click="showDeleteModal = true"
                class="flex-1 px-4 py-3 bg-red-500/10 text-red-600 dark:text-red-400 text-center rounded-lg hover:bg-red-500/20 transition-colors"
            >
                {{ __('user-matches.delete_match') }}
            </button>
            <a href="{{ route('matches.edit', $match) }}" wire:navigate class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-center rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                {{ __('user-matches.edit_match') }}
            </a>

            <!-- Delete Confirmation Modal -->
            <div
                x-show="showDeleteModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
                @click.self="showDeleteModal = false"
                x-cloak
            >
                <div
                    x-show="showDeleteModal"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="bg-white dark:bg-zinc-900 rounded-xl p-6 w-full max-w-sm shadow-xl"
                >
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">{{ __('user-matches.delete_match') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">{{ __('user-matches.delete_confirm') }}</p>
                    <div class="flex gap-3">
                        <button
                            @click="showDeleteModal = false"
                            class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors text-sm font-medium"
                        >
                            {{ __('user-matches.cancel') }}
                        </button>
                        <button
                            wire:click="deleteMatch"
                            class="flex-1 px-4 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium"
                        >
                            {{ __('user-matches.delete_match') }}
                        </button>
                    </div>
                </div>
            </div>
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
