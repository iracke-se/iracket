<div class="max-w-2xl mx-auto">
    @php
        $user = auth()->user();
        $isPlayer1 = $match->player1_id === $user->id;
        $opponent = $isPlayer1 ? $match->player2 : $match->player1;
        $mySets = $isPlayer1 ? $match->player1_sets : $match->player2_sets;
        $opponentSets = $isPlayer1 ? $match->player2_sets : $match->player1_sets;
        $won = $match->winner_id === $user->id;
        $myComments = $isPlayer1 ? $match->player1_comments : $match->player2_comments;
        $opponentComments = $isPlayer1 ? $match->player2_comments : $match->player1_comments;
    @endphp

    <!-- Match Header -->
    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-6 mb-6">
        <!-- Date -->
        <div class="text-center mb-4">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $match->played_at->format('l, d F Y') }}</p>
        </div>

        <!-- Result -->
        <div class="text-center mb-6">
            <div class="text-4xl font-bold {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                {{ $mySets }} - {{ $opponentSets }}
            </div>
            <p class="text-sm {{ $won ? 'text-green-500 dark:text-green-400' : 'text-red-500 dark:text-red-400' }} mt-1">
                {{ $won ? __('user-matches.victory') : __('user-matches.defeat') }}
            </p>
        </div>

        <!-- Players -->
        <div class="flex items-start justify-between">
            <!-- Me -->
            <div class="flex-1 text-center">
                @if($user->profile_picture)
                    <img src="{{ Storage::url($user->profile_picture) }}" alt="{{ $user->name }}" class="w-16 h-16 rounded-full object-cover mx-auto mb-2">
                @else
                    <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-2">
                        <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">{{ $user->initials() }}</span>
                    </div>
                @endif
                <p class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</p>
                @if($user->club)
                    <a href="{{ route('clubs.show', $user->club) }}" wire:navigate class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-accent">{{ $user->club->name }}</a>
                @endif
            </div>

            <!-- VS -->
            <div class="px-4 py-8">
                <span class="text-zinc-400 dark:text-zinc-500 font-medium">VS</span>
            </div>

            <!-- Opponent -->
            <div class="flex-1 text-center">
                @if($opponent->profile_picture)
                    <img src="{{ Storage::url($opponent->profile_picture) }}" alt="{{ $opponent->name }}" class="w-16 h-16 rounded-full object-cover mx-auto mb-2">
                @else
                    <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center mx-auto mb-2">
                        <span class="text-xl font-medium text-zinc-600 dark:text-zinc-300">{{ $opponent->initials() }}</span>
                    </div>
                @endif
                <a href="{{ route('players.show', $opponent) }}" wire:navigate class="font-medium text-zinc-900 dark:text-white hover:text-accent">
                    {{ $opponent->name }}
                </a>
                @if($opponent->club)
                    <a href="{{ route('clubs.show', $opponent->club) }}" wire:navigate class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-accent">{{ $opponent->club->name }}</a>
                @endif
            </div>
        </div>
    </div>

    <!-- Comments on Opponent -->
    @if(!empty($opponentComments))
        <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 mb-6">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3">{{ __('user-matches.comments_on_opponent') }}</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($opponentComments as $comment)
                    <span class="px-3 py-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-lg text-sm text-zinc-700 dark:text-zinc-200">
                        {{ $comment }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

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
        <div class="flex gap-3">
            <a href="{{ route('matches.edit', $match) }}" wire:navigate class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-center rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                {{ __('user-matches.edit_match') }}
            </a>
        </div>
    @endif
</div>
