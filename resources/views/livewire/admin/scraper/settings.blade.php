<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.scraper.index') }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" wire:navigate>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('admin-scraper.settings_title') }}</h1>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.settings_description') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
        <form wire:submit="updateSettings">
            <!-- URL Settings Section -->
            <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin-scraper.url_settings') }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">{{ __('admin-scraper.url_settings_description') }}</p>

                <div class="space-y-4">
                    <!-- Players URL -->
                    <div>
                        <label for="url_players" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('admin-scraper.url_players_label') }}
                        </label>
                        <input
                            type="url"
                            id="url_players"
                            wire:model="url_players"
                            class="w-full px-4 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                        @error('url_players')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Rankings URL -->
                    <div>
                        <label for="url_rankings" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('admin-scraper.url_rankings_label') }}
                        </label>
                        <input
                            type="url"
                            id="url_rankings"
                            wire:model="url_rankings"
                            class="w-full px-4 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                        @error('url_rankings')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Transitions URL -->
                    <div>
                        <label for="url_transitions" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('admin-scraper.url_transitions_label') }}
                        </label>
                        <input
                            type="url"
                            id="url_transitions"
                            wire:model="url_transitions"
                            class="w-full px-4 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                        @error('url_transitions')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Series URL -->
                    <div>
                        <label for="url_series" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('admin-scraper.url_series_label') }}
                        </label>
                        <input
                            type="url"
                            id="url_series"
                            wire:model="url_series"
                            class="w-full px-4 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                        @error('url_series')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Live Center URL -->
                    <div>
                        <label for="url_live_center" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            {{ __('admin-scraper.url_live_center_label') }}
                        </label>
                        <input
                            type="url"
                            id="url_live_center"
                            wire:model="url_live_center"
                            class="w-full px-4 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-accent focus:border-transparent"
                            placeholder="https://..."
                        >
                        @error('url_live_center')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Schedule Settings Section -->
            <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin-scraper.schedule_settings') }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">{{ __('admin-scraper.schedule_settings_description') }}</p>

                <div class="space-y-6">
                    @foreach(['players', 'rankings', 'transitions', 'series', 'live_center'] as $type)
                    <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('admin-scraper.' . $type) }}</h3>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="schedule_{{ $type }}_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent/20 dark:peer-focus:ring-accent/40 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-accent"></div>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('admin-scraper.frequency') }}</label>
                                <select wire:model="schedule_{{ $type }}_frequency" class="w-full px-3 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white focus:ring-2 focus:ring-accent focus:border-transparent">
                                    <option value="daily">{{ __('admin-scraper.daily') }}</option>
                                    <option value="weekly">{{ __('admin-scraper.weekly') }}</option>
                                    <option value="monthly">{{ __('admin-scraper.monthly') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('admin-scraper.day') }}</label>
                                <input type="text" wire:model="schedule_{{ $type }}_day" placeholder="{{ __('admin-scraper.day_placeholder') }}" class="w-full px-3 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 focus:ring-2 focus:ring-accent focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('admin-scraper.time') }}</label>
                                <input type="time" wire:model="schedule_{{ $type }}_time" class="w-full px-3 py-2 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white focus:ring-2 focus:ring-accent focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-b-xl flex items-center justify-between">
                <button
                    type="button"
                    wire:click="resetToDefaults"
                    wire:confirm="{{ __('admin-scraper.confirm_reset_defaults') }}"
                    class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white"
                >
                    {{ __('admin-scraper.reset_defaults') }}
                </button>
                <button
                    type="submit"
                    class="px-6 py-2 bg-accent text-white rounded-lg hover:bg-accent/90 focus:ring-2 focus:ring-accent focus:ring-offset-2 dark:focus:ring-offset-zinc-800 font-medium"
                >
                    {{ __('admin-scraper.save_settings') }}
                </button>
            </div>
        </form>
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
        <div class="flex items-start gap-3">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-medium mb-1">{{ __('admin-scraper.settings_help_title') }}</p>
                <p>{{ __('admin-scraper.settings_help_text') }}</p>
            </div>
        </div>
    </div>
</div>
