<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('admin-scraper.scraper_management') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.manage_scraping') }}</p>
        </div>
        <a href="{{ route('admin.scraper.settings') }}" class="p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors" wire:navigate title="{{ __('admin-scraper.settings') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </a>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Runs -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.total_runs') }}</p>
                    <p class="text-3xl font-bold text-zinc-900 dark:text-white mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="p-3 bg-accent/10 rounded-lg">
                    <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Running -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.running') }}</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['running'] }}</p>
                </div>
                <div class="p-3 bg-blue-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-blue-500 dark:text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.completed') }}</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['completed'] }}</p>
                </div>
                <div class="p-3 bg-green-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.failed') }}</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['failed'] }}</p>
                </div>
                <div class="p-3 bg-red-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Section -->
    @if($stats['unsynced_players'] > 0 || $stats['unsynced_rankings'] > 0)
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700 mb-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin-scraper.sync_scraped_data') }}</h2>
        <div class="flex flex-wrap gap-4">
            @if($stats['unsynced_players'] > 0)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $stats['unsynced_players'] }} {{ __('admin-scraper.unsynced_players') }}
                    </span>
                    <button wire:click="syncAllPlayers" wire:loading.attr="disabled" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg font-medium transition-colors">
                        {{ __('admin-scraper.sync_players') }}
                    </button>
                </div>
            @endif
            @if($stats['unsynced_rankings'] > 0)
                <div class="flex items-center gap-3">
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $stats['unsynced_rankings'] }} {{ __('admin-scraper.unsynced_rankings') }}
                    </span>
                    <button wire:click="syncAllRankings" wire:loading.attr="disabled" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg font-medium transition-colors">
                        {{ __('admin-scraper.sync_rankings') }}
                    </button>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Batch Scrape -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700 mb-6">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('admin-scraper.batch_scrape') }}</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.batch_scrape_description') }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Select Types -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('admin-scraper.select_types') }}</label>
                    <button type="button" wire:click="toggleAllTypes" class="text-xs text-accent hover:text-accent/80">
                        {{ __('admin-scraper.select_all') }}
                    </button>
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedTypes" value="rankings" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('admin-scraper.rankings') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedTypes" value="players" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('admin-scraper.players') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedTypes" value="transitions" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('admin-scraper.transitions') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedTypes" value="series" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('admin-scraper.series') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedTypes" value="live_center" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('admin-scraper.live_center') }}</span>
                    </label>
                </div>
            </div>

            <!-- Select Genders (for Rankings) -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('admin-scraper.select_genders') }}</label>
                    <button type="button" wire:click="toggleAllGenders" class="text-xs text-accent hover:text-accent/80">
                        {{ __('admin-scraper.select_all') }}
                    </button>
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedGenders" value="male" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent" @disabled(!in_array('rankings', $selectedTypes))>
                        <span class="text-sm text-zinc-700 dark:text-zinc-300 @if(!in_array('rankings', $selectedTypes)) opacity-50 @endif">{{ __('admin-scraper.male') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="selectedGenders" value="female" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent" @disabled(!in_array('rankings', $selectedTypes))>
                        <span class="text-sm text-zinc-700 dark:text-zinc-300 @if(!in_array('rankings', $selectedTypes)) opacity-50 @endif">{{ __('admin-scraper.female') }}</span>
                    </label>
                </div>
                @if(!in_array('rankings', $selectedTypes))
                    <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">{{ __('admin-scraper.rankings') }} must be selected</p>
                @endif
            </div>

            <!-- Period and Submit -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">{{ __('admin-scraper.period') }}</label>
                <select wire:model="fullScrapePeriod" class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent mb-4">
                    <option value="">{{ __('admin-scraper.select_period') }}</option>
                    @foreach($periods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button wire:click="triggerFullScrape" class="w-full px-4 py-3 bg-accent hover:bg-accent/90 text-white rounded-lg font-medium transition-colors">
                    {{ __('admin-scraper.start_batch_scrape') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Trigger Single Scrape -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700 mb-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin-scraper.trigger_new_scrape') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('admin-scraper.type') }}</label>
                <select wire:model.live="scrapeType" class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                    <option value="">{{ __('admin-scraper.select_type') }}</option>
                    <option value="rankings">{{ __('admin-scraper.rankings') }}</option>
                    <option value="players">{{ __('admin-scraper.players') }}</option>
                    <option value="transitions">{{ __('admin-scraper.transitions') }}</option>
                    <option value="series">{{ __('admin-scraper.series') }}</option>
                    <option value="live_center">{{ __('admin-scraper.live_center') }}</option>
                </select>
            </div>

            @if($scrapeType === 'rankings')
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('admin-scraper.gender') }}</label>
                    <select wire:model="scrapeGender" class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                        <option value="male">{{ __('admin-scraper.male') }}</option>
                        <option value="female">{{ __('admin-scraper.female') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">{{ __('admin-scraper.period_optional') }}</label>
                    <select wire:model="scrapePeriod" class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                        <option value="">{{ __('admin-scraper.select_period') }}</option>
                        @foreach($periods as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex items-end">
                <button wire:click="triggerScrape" class="px-4 py-3 bg-accent hover:bg-accent/90 text-white rounded-lg font-medium transition-colors">
                    {{ __('admin-scraper.start_scrape') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('admin-scraper.search') }}" class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
        </div>
        <div>
            <select wire:model.live="typeFilter" class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                <option value="">{{ __('admin-scraper.all_types') }}</option>
                <option value="rankings">{{ __('admin-scraper.rankings') }}</option>
                <option value="players">{{ __('admin-scraper.players') }}</option>
                <option value="transitions">{{ __('admin-scraper.transitions') }}</option>
                <option value="series">{{ __('admin-scraper.series') }}</option>
                <option value="live_center">{{ __('admin-scraper.live_center') }}</option>
            </select>
        </div>
        <div>
            <select wire:model.live="statusFilter" class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                <option value="">{{ __('admin-scraper.all_statuses') }}</option>
                <option value="pending">{{ __('admin-scraper.pending') }}</option>
                <option value="running">{{ __('admin-scraper.status_running') }}</option>
                <option value="completed">{{ __('admin-scraper.status_completed') }}</option>
                <option value="failed">{{ __('admin-scraper.status_failed') }}</option>
            </select>
        </div>
    </div>

    <!-- Runs Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-scraper.type') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-scraper.status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-scraper.progress') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-scraper.duration') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-scraper.started') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-scraper.actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($runs as $run)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white capitalize">
                                {{ str_replace('_', ' ', $run->type) }}
                            </span>
                            @if($run->parameters)
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @foreach($run->parameters as $key => $value)
                                        {{ $key }}: {{ $value }}@if(!$loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($run->status)
                                @case('pending')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200">
                                        {{ __('admin-scraper.pending') }}
                                    </span>
                                    @break
                                @case('running')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
                                        <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ __('admin-scraper.status_running') }}
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200">
                                        {{ __('admin-scraper.status_completed') }}
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                                        {{ __('admin-scraper.status_failed') }}
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-zinc-900 dark:text-white">
                                {{ $run->items_scraped }} {{ __('admin-scraper.scraped') }}
                            </div>
                            @if($run->items_failed > 0)
                                <div class="text-xs text-red-500 dark:text-red-400">
                                    {{ $run->items_failed }} {{ __('admin-scraper.failed') }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $run->duration ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $run->started_at?->format('M d, H:i') ?? $run->created_at->format('M d, H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('admin.scraper.show', $run) }}" class="text-accent hover:text-accent/80 transition-colors" wire:navigate>
                                {{ __('admin-scraper.view') }}
                            </a>
                            @if($run->status === 'running')
                                <button wire:click="cancelRun({{ $run->id }})" wire:confirm="{{ __('admin-scraper.confirm_cancel') }}" class="text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300 transition-colors">
                                    {{ __('admin-scraper.cancel') }}
                                </button>
                            @endif
                            @if($run->status === 'failed')
                                <button wire:click="retryRun({{ $run->id }})" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 transition-colors">
                                    {{ __('admin-scraper.retry') }}
                                </button>
                            @endif
                            @if($run->status === 'completed' && in_array($run->type, ['players', 'rankings']))
                                <button wire:click="syncRun({{ $run->id }})" class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 transition-colors">
                                    {{ __('admin-scraper.sync') }}
                                </button>
                            @endif
                            <button wire:click="deleteRun({{ $run->id }})" wire:confirm="{{ __('admin-scraper.confirm_delete') }}" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                {{ __('admin-scraper.delete') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('admin-scraper.no_runs_found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($runs->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $runs->links() }}
            </div>
        @endif
    </div>
</div>
