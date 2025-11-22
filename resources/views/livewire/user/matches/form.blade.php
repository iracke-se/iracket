<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">
        {{ $match ? __('Edit Match') : __('New Match') }}
    </h1>

    <form wire:submit="save" class="space-y-6">
        <!-- Date -->
        <div>
            <label for="played_at" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('Date') }}</label>
            <input
                type="date"
                id="played_at"
                wire:model="played_at"
                max="{{ now()->format('Y-m-d') }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('played_at')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Opponent -->
        <div>
            <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('Opponent') }}</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="opponentSearch"
                placeholder="{{ __('Search for opponent...') }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent mb-2"
            >

            <div class="max-h-48 overflow-y-auto bg-zinc-100 dark:bg-zinc-800 rounded-lg border border-zinc-300 dark:border-zinc-700">
                @forelse($opponents as $opponent)
                    <button
                        type="button"
                        wire:click="$set('opponent_id', {{ $opponent->id }})"
                        class="w-full flex items-center gap-3 p-3 text-left hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors {{ $opponent_id === $opponent->id ? 'bg-accent/20 border-l-2 border-accent' : '' }}"
                    >
                        @if($opponent->profile_picture)
                            <img src="{{ Storage::url($opponent->profile_picture) }}" alt="{{ $opponent->name }}" class="w-8 h-8 rounded-full object-cover">
                        @else
                            <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center">
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
                @empty
                    <p class="p-3 text-sm text-zinc-500 dark:text-zinc-400 text-center">{{ __('No players found') }}</p>
                @endforelse
            </div>
            @error('opponent_id')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Sets -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="my_sets" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('My Sets Won') }}</label>
                <select
                    id="my_sets"
                    wire:model="my_sets"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    @for($i = 0; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                @error('my_sets')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="opponent_sets" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __("Opponent's Sets Won") }}</label>
                <select
                    id="opponent_sets"
                    wire:model="opponent_sets"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    @for($i = 0; $i <= 5; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
                @error('opponent_sets')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Comments on Opponent -->
        <div>
            <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('Comments on Opponent') }}</label>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($availableComments as $comment)
                    <button
                        type="button"
                        wire:click="toggleComment('{{ $comment }}')"
                        class="px-3 py-1.5 text-sm rounded-lg transition-colors {{ in_array($comment, $opponent_comments) ? 'bg-accent text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-300 dark:hover:bg-zinc-600' }}"
                    >
                        {{ $comment }}
                    </button>
                @endforeach
            </div>

            <!-- Custom Comment -->
            <div class="flex gap-2">
                <input
                    type="text"
                    wire:model="custom_comment"
                    placeholder="{{ __('Add custom comment...') }}"
                    class="flex-1 px-4 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                <button
                    type="button"
                    wire:click="addCustomComment"
                    class="px-4 py-2 bg-zinc-200 dark:bg-zinc-600 text-zinc-700 dark:text-white rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-500 transition-colors"
                >
                    {{ __('Add') }}
                </button>
            </div>

            <!-- Selected Comments -->
            @if(!empty($opponent_comments))
                <div class="mt-3 p-3 bg-zinc-100 dark:bg-zinc-700/50 rounded-lg">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">{{ __('Selected comments:') }}</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($opponent_comments as $comment)
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-zinc-200 dark:bg-zinc-600 rounded text-xs text-zinc-700 dark:text-zinc-200">
                                {{ $comment }}
                                <button type="button" wire:click="toggleComment('{{ $comment }}')" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('Description') }}</label>
            <textarea
                id="description"
                wire:model="description"
                rows="3"
                placeholder="{{ __('Add notes about the match...') }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            ></textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit -->
        <div class="flex gap-3">
            <a href="{{ route('matches.index') }}" wire:navigate class="flex-1 px-4 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 text-center rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="flex-1 px-4 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $match ? __('Update Match') : __('Create Match') }}
            </button>
        </div>
    </form>
</div>
