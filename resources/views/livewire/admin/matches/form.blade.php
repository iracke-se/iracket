<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $match ? __('admin-matches.edit_match') : __('admin-matches.create_match') }}</h1>
        <a href="{{ route('admin.matches.index') }}" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
            {{ __('admin-matches.back_to_list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Players -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="player1_id" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-matches.player1') }}</label>
                <select
                    id="player1_id"
                    wire:model="player1_id"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="">{{ __('admin-matches.select_player') }}</option>
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">{{ $player->name }}</option>
                    @endforeach
                </select>
                @error('player1_id')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="player2_id" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-matches.player2') }}</label>
                <select
                    id="player2_id"
                    wire:model="player2_id"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="">{{ __('admin-matches.select_player') }}</option>
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">{{ $player->name }}</option>
                    @endforeach
                </select>
                @error('player2_id')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Score -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="player1_sets" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-matches.player1_sets') }}</label>
                <input
                    type="number"
                    id="player1_sets"
                    wire:model="player1_sets"
                    min="0"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('player1_sets')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="player2_sets" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-matches.player2_sets') }}</label>
                <input
                    type="number"
                    id="player2_sets"
                    wire:model="player2_sets"
                    min="0"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('player2_sets')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Date and Status -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="played_at" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-matches.played_at') }}</label>
                <input
                    type="date"
                    id="played_at"
                    wire:model="played_at"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('played_at')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-matches.status') }}</label>
                <select
                    id="status"
                    wire:model="status"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="pending">{{ __('admin-matches.pending') }}</option>
                    <option value="confirmed">{{ __('admin-matches.confirmed') }}</option>
                    <option value="cancelled">{{ __('admin-matches.cancelled') }}</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-matches.description') }}</label>
            <textarea
                id="description"
                wire:model="description"
                rows="3"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            ></textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Translation Review: User Match Comment Options -->
        <div class="rounded-lg border border-amber-300 dark:border-amber-600 bg-amber-50 dark:bg-amber-900/20 p-4">
            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-400 mb-3">
                Translation Review — User Match Comment Options
            </h3>
            <p class="text-xs text-amber-700 dark:text-amber-500 mb-3">
                These are the preset comment tags shown to users when logging a match. Verify the Swedish translations in <code>lang/sv/user-matches.php</code> under <code>comment_*</code> keys.
            </p>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-amber-700 dark:text-amber-500 border-b border-amber-200 dark:border-amber-700">
                            <th class="pb-1 pr-4">Key</th>
                            <th class="pb-1 pr-4">English (EN)</th>
                            <th class="pb-1">Swedish (SV)</th>
                        </tr>
                    </thead>
                    <tbody class="text-zinc-700 dark:text-zinc-300">
                        @foreach([
                            'comment_good_backhand', 'comment_good_forehand', 'comment_strong_serve',
                            'comment_fast_footwork', 'comment_excellent_net_play', 'comment_super_sensitive',
                            'comment_great_sportsmanship', 'comment_consistent_player',
                        ] as $key)
                        <tr class="border-b border-amber-100 dark:border-amber-800/50">
                            <td class="py-1 pr-4 font-mono text-zinc-500 dark:text-zinc-400">{{ $key }}</td>
                            <td class="py-1 pr-4">{{ __('user-matches.' . $key, [], 'en') }}</td>
                            <td class="py-1">{{ __('user-matches.' . $key, [], 'sv') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $match ? __('admin-matches.update') : __('admin-matches.create') }}
            </button>
            <a href="{{ route('admin.matches.index') }}" class="px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors" wire:navigate>
                {{ __('admin-matches.cancel') }}
            </a>
        </div>
    </form>
</div>
