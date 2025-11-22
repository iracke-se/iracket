<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ $match ? __('Edit Match') : __('Create Match') }}</h1>
        <a href="{{ route('admin.matches.index') }}" class="text-zinc-400 hover:text-white" wire:navigate>
            {{ __('Back to list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Players -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="player1_id" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Player 1') }}</label>
                <select
                    id="player1_id"
                    wire:model="player1_id"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="">{{ __('Select player...') }}</option>
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">{{ $player->name }}</option>
                    @endforeach
                </select>
                @error('player1_id')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="player2_id" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Player 2') }}</label>
                <select
                    id="player2_id"
                    wire:model="player2_id"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="">{{ __('Select player...') }}</option>
                    @foreach($players as $player)
                        <option value="{{ $player->id }}">{{ $player->name }}</option>
                    @endforeach
                </select>
                @error('player2_id')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Score -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="player1_sets" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Player 1 Sets') }}</label>
                <input
                    type="number"
                    id="player1_sets"
                    wire:model="player1_sets"
                    min="0"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('player1_sets')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="player2_sets" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Player 2 Sets') }}</label>
                <input
                    type="number"
                    id="player2_sets"
                    wire:model="player2_sets"
                    min="0"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('player2_sets')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Date and Status -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="played_at" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Date Played') }}</label>
                <input
                    type="date"
                    id="played_at"
                    wire:model="played_at"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('played_at')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Status') }}</label>
                <select
                    id="status"
                    wire:model="status"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="pending">{{ __('Pending') }}</option>
                    <option value="confirmed">{{ __('Confirmed') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Description') }}</label>
            <textarea
                id="description"
                wire:model="description"
                rows="3"
                class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            ></textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $match ? __('Update') : __('Create') }}
            </button>
            <a href="{{ route('admin.matches.index') }}" class="px-6 py-3 bg-zinc-700 text-zinc-300 font-medium rounded-lg hover:bg-zinc-600 transition-colors" wire:navigate>
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>
