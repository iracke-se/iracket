<div class="max-w-7xl mx-auto" x-data="{
    polling: false,
    init() {
        // Listen for scraper started event
        $wire.on('scraper-started', () => {
            this.startPolling();
        });

        // Check if scraper is already running
        if ($wire.isScraperRunning) {
            this.startPolling();
        }
    },
    startPolling() {
        this.polling = true;
        this.pollInterval = setInterval(() => {
            $wire.call('refreshData');

            // Stop polling if scraper is no longer running
            if (!$wire.isScraperRunning) {
                this.stopPolling();
            }
        }, 2000); // Poll every 2 seconds
    },
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
        this.polling = false;
    }
}">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Scraper Management</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Scrape and sync data from profixio.com</p>
    </div>

    <!-- System Health Monitor -->
    @livewire('admin.dashboard.heartbeat-status')

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Total Runs -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Runs</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $stats['total_runs'] }}</p>
                </div>
                <div class="p-2 bg-accent/10 rounded-lg">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                    </svg>
                </div>
            </div>
            @if($stats['latest_scrape'])
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">Latest: {{ $stats['latest_scrape'] }}</p>
            @endif
        </div>

        <!-- Running -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Running</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">{{ $stats['running'] }}</p>
                </div>
                <div class="p-2 bg-blue-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" :class="{ 'animate-spin': {{ $stats['running'] }} > 0 }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Completed</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ $stats['completed'] }}</p>
                </div>
                <div class="p-2 bg-green-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Failed -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Failed</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $stats['failed'] }}</p>
                </div>
                <div class="p-2 bg-red-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Unsynced Data Alert -->
    @if($stats['unsynced_players'] > 0 || $stats['unsynced_rankings'] > 0 || $stats['unsynced_matches'] > 0)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="flex-1">
                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Unsynced Data Available</p>
                <p class="text-xs text-yellow-700 dark:text-yellow-300 mt-1">
                    {{ $stats['unsynced_players'] }} players,
                    {{ $stats['unsynced_rankings'] }} rankings,
                    {{ $stats['unsynced_matches'] }} matches waiting to be synced
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Start Scraper Section -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 border border-zinc-200 dark:border-zinc-700 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Start New Scrape</h2>
            @if($stats['running'] > 0)
                <button wire:click="stopAllRunning"
                        wire:confirm="Are you sure you want to stop all {{ $stats['running'] }} running scraper(s)? This cannot be undone."
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg font-medium transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Stop All Running ({{ $stats['running'] }})
                </button>
            @endif
        </div>
        <div class="flex items-center gap-4">
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Configure scraper options and dispatch to queue for background processing
            </p>
            <flux:modal.trigger name="scraper-options">
                <button x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'scraper-options')"
                        :disabled="$wire.isScraperRunning"
                        class="px-6 py-2.5 bg-accent hover:bg-accent/90 disabled:bg-zinc-400 disabled:cursor-not-allowed text-white rounded-lg font-medium transition-colors flex items-center gap-2 ml-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Start Scraper
                </button>
            </flux:modal.trigger>
        </div>
        @if($isScraperRunning)
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <p class="text-sm text-blue-700 dark:text-blue-300 font-medium">Scraper is currently running... Data updates every 2 seconds</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Scraper Options Modal -->
    <flux:modal name="scraper-options" class="max-w-2xl">
        <div class="p-6">
            <h2 class="text-xl font-bold text-zinc-900 dark:text-white mb-6">Configure Scraper Options</h2>

            <form wire:submit.prevent="startScraper" class="space-y-6">
                <!-- Scraper Type -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Scraper Type
                    </label>
                    <select wire:model.live="scraperType"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-accent focus:border-transparent">
                        @foreach($scraperTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Month Selection -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Month (YYYY-MM)
                    </label>
                    <input type="month"
                           wire:model="month"
                           :disabled="$wire.scrapeAll"
                           class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-accent focus:border-transparent disabled:bg-zinc-100 dark:disabled:bg-zinc-800 disabled:cursor-not-allowed">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Leave empty and enable "Scrape All" to scrape all historical data</p>
                </div>

                <!-- Scrape All Checkbox -->
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           wire:model.live="scrapeAll"
                           id="scrapeAll"
                           class="w-4 h-4 text-accent bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-600 rounded focus:ring-accent">
                    <label for="scrapeAll" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">
                        Scrape All Data (no month filter)
                    </label>
                </div>

                <!-- Gender Selection (for rankings) -->
                @if($scraperType === 'rankings')
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Gender <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="gender"
                            class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-accent focus:border-transparent">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                @endif

                <!-- Testing Limits -->
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Testing Limits (Optional)</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Limit Periods
                            </label>
                            <input type="number"
                                   wire:model="limitPeriods"
                                   placeholder="e.g., 2"
                                   min="1"
                                   class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-accent focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                Limit Divisions
                            </label>
                            <input type="number"
                                   wire:model="limitDivisions"
                                   placeholder="e.g., 2"
                                   min="1"
                                   class="w-full px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white focus:ring-2 focus:ring-accent focus:border-transparent">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Use these to limit the scrape for testing purposes</p>
                </div>

                <!-- Advanced Options -->
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Advanced Options</h3>
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   wire:model="skipSync"
                                   id="skipSync"
                                   class="w-4 h-4 text-accent bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-600 rounded focus:ring-accent">
                            <label for="skipSync" class="text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                Skip automatic sync to production tables
                            </label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   wire:model="skipBubbler"
                                   id="skipBubbler"
                                   class="w-4 h-4 text-accent bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-600 rounded focus:ring-accent">
                            <label for="skipBubbler" class="text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                Skip Bubbler recalculation
                            </label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   wire:model="noBackup"
                                   id="noBackup"
                                   class="w-4 h-4 text-accent bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-600 rounded focus:ring-accent">
                            <label for="noBackup" class="text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                Skip automatic database backup
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:modal.close>
                        <button type="button"
                                class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 font-medium transition-colors">
                            Cancel
                        </button>
                    </flux:modal.close>
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="px-6 py-2 bg-accent hover:bg-accent/90 disabled:bg-zinc-400 disabled:cursor-not-allowed text-white rounded-lg font-medium transition-colors flex items-center gap-2">
                        <svg wire:loading.remove wire:target="startScraper" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg wire:loading wire:target="startScraper" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span wire:loading.remove wire:target="startScraper">Start Scraper</span>
                        <span wire:loading wire:target="startScraper">Starting...</span>
                    </button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Scraper Runs Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700">
        <!-- Table Header -->
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Recent Scraper Runs</h2>
                <div class="flex items-center gap-3">
                    <!-- Type Filter -->
                    <select wire:model.live="typeFilter"
                            class="px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white">
                        <option value="">All Types</option>
                        @foreach($runTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    <!-- Status Filter -->
                    <select wire:model.live="statusFilter"
                            class="px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Scraped</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Started</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($runs as $run)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50">
                            <td class="px-6 py-4 text-sm text-zinc-900 dark:text-white font-medium">#{{ $run->id }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2 py-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded text-xs font-medium">
                                    {{ $runTypes[$run->type] ?? $run->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($run->status === 'completed')
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded text-xs font-medium">Completed</span>
                                @elseif($run->status === 'running')
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded text-xs font-medium flex items-center gap-1 w-fit">
                                        <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Running
                                    </span>
                                @elseif($run->status === 'failed')
                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded text-xs font-medium">Failed</span>
                                @else
                                    <span class="px-2 py-1 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded text-xs font-medium">{{ ucfirst($run->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ number_format($run->items_scraped) }}
                                @if($run->items_failed > 0)
                                    <span class="text-red-600 dark:text-red-400">({{ $run->items_failed }} failed)</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $run->duration ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $run->started_at?->format('M d, H:i') ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.scraper.show', $run) }}"
                                       wire:navigate
                                       class="px-3 py-1.5 bg-accent hover:bg-accent/90 text-white text-xs rounded font-medium transition-colors">
                                        View
                                    </a>
                                    @if($run->status === 'running')
                                        <button wire:click="stopRun({{ $run->id }})"
                                                wire:confirm="Are you sure you want to stop this scraper? This will mark it as failed."
                                                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded font-medium transition-colors flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Stop
                                        </button>
                                    @else
                                        <button wire:click="deleteRun({{ $run->id }})"
                                                wire:confirm="Are you sure you want to delete this run?"
                                                class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded font-medium transition-colors">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No scraper runs found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
            {{ $runs->links() }}
        </div>
    </div>
</div>
