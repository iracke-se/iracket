<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('connect.title')"
        :description="__('connect.description')"
    />

    <form wire:submit="connect" class="flex flex-col gap-6">
        <!-- Active Player Toggle -->
        <div class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
            <div>
                <label for="isActivePlayer" class="text-sm font-medium text-zinc-900 dark:text-white">
                    {{ __('connect.i_am_active_player') }}
                </label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('connect.active_player_hint') }}
                </p>
            </div>
            <button
                type="button"
                wire:click="$toggle('isActivePlayer')"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $isActivePlayer ? 'bg-accent' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                role="switch"
                aria-checked="{{ $isActivePlayer ? 'true' : 'false' }}"
            >
                <span class="sr-only">{{ __('connect.toggle_active_player') }}</span>
                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $isActivePlayer ? 'translate-x-6' : 'translate-x-1' }}"></span>
            </button>
        </div>

        @if($isActivePlayer)
            <!-- Player Selection Button -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    {{ __('connect.choose_player') }}
                </label>
                <button
                    type="button"
                    wire:click="openPlayerModal"
                    class="w-full flex items-center justify-between px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors"
                >
                    <span class="{{ $selectedPlayerName ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-500 dark:text-zinc-400' }}">
                        {{ $selectedPlayerName ?? __('connect.select_player') }}
                    </span>
                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('connect.player_hint') }}
                </p>
                @error('playerId')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Auto-selected Club Display -->
            @if($selectedClubName)
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <label class="block text-sm font-medium text-green-900 dark:text-green-100 mb-1">
                        {{ __('connect.your_club') }}
                    </label>
                    <p class="text-base font-semibold text-green-900 dark:text-green-100">
                        {{ $selectedClubName }}
                    </p>
                    <p class="text-xs text-green-700 dark:text-green-300 mt-1">
                        {{ __('connect.auto_selected_club') }}
                    </p>
                </div>
            @endif
        @endif

        <!-- Push Notifications -->
        <div class="flex items-start gap-3 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
            <input
                type="checkbox"
                id="acceptsPushNotifications"
                wire:model="acceptsPushNotifications"
                class="mt-1 h-4 w-4 rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent"
            >
            <div>
                <label for="acceptsPushNotifications" class="text-sm font-medium text-zinc-900 dark:text-white cursor-pointer">
                    {{ __('connect.accept_push_notifications') }}
                </label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('connect.push_notifications_hint') }}
                </p>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex flex-col gap-3">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('connect.save') }}
            </flux:button>

            <flux:button type="button" wire:click="continueAsGuest" variant="outline" class="w-full">
                {{ __('connect.continue_as_guest') }}
            </flux:button>

            <p class="text-xs text-center text-zinc-500 dark:text-zinc-400">
                {{ __('connect.guest_hint') }}
            </p>
        </div>
    </form>

    <!-- Logout Option -->
    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer">
                {{ __('auth.log_out') }}
            </flux:button>
        </form>
    </div>

    <!-- Player Selection Modal -->
    @if($showPlayerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-zinc-500 dark:bg-zinc-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" wire:click="closePlayerModal"></div>

            <!-- Modal panel -->
            <div class="relative bg-white dark:bg-zinc-800 rounded-xl text-left overflow-hidden shadow-xl transform transition-all w-full max-w-sm">
                    <!-- Header -->
                    <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('connect.choose_player') }}</h3>
                            <button type="button" wire:click="closePlayerModal" class="text-zinc-400 hover:text-zinc-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Search -->
                        <div class="mt-3 relative">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="playerSearch"
                                placeholder="{{ __('connect.search_player') }}"
                                class="w-full px-4 py-2 pl-10 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-accent"
                                autofocus
                            >
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Player List -->
                    <div class="max-h-96 overflow-y-auto">
                        @forelse($playersGrouped as $letter => $players)
                            <div class="sticky top-0 px-4 py-2 bg-zinc-100 dark:bg-zinc-700 text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                                {{ $letter }}
                            </div>
                            @foreach($players as $player)
                                <button
                                    type="button"
                                    wire:click="selectPlayer({{ $player->id }})"
                                    class="w-full px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700 border-b border-zinc-100 dark:border-zinc-700 last:border-0 {{ $playerId === $player->id ? 'bg-accent/10' : '' }}"
                                >
                                    <span class="text-zinc-900 dark:text-white">{{ $player->first_name }} {{ $player->last_name }}</span>
                                </button>
                            @endforeach
                        @empty
                            <div class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('connect.no_players_found') }}
                            </div>
                        @endforelse
                    </div>
            </div>
        </div>
    @endif
</div>
