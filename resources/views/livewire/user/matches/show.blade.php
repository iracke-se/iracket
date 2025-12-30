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
            <div class="text-4xl font-bold text-zinc-900 dark:text-white">
                {{ $match->player1_sets }} - {{ $match->player2_sets }}
            </div>
            @if($match->winner)
                <p class="text-sm text-accent mt-1">
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
        <div class="flex gap-3">
            <a href="{{ route('matches.edit', $match) }}" wire:navigate class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-center rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                {{ __('user-matches.edit_match') }}
            </a>
        </div>
    @endif
</div>
