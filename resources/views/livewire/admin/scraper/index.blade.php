<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Scraper Management</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Manage profixio.com data scraping</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Runs</div>
            <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $stats['total'] }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Running</div>
            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['running'] }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Completed</div>
            <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</div>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Failed</div>
            <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $stats['failed'] }}</div>
        </div>
    </div>

    <!-- Trigger New Scrape -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 mb-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Trigger New Scrape</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Type</label>
                <select wire:model="scrapeType" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                    <option value="">Select type...</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if($scrapeType === 'rankings')
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Gender</label>
                    <select wire:model="scrapeGender" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Period (optional)</label>
                    <input type="text" wire:model="scrapePeriod" placeholder="e.g., 2024.01.01" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                </div>
            @endif

            <div class="flex items-end">
                <button wire:click="triggerScrape" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium">
                    Start Scrape
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..." class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
            </div>
            <div>
                <select wire:model.live="typeFilter" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                    <option value="">All Types</option>
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select wire:model.live="statusFilter" class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Runs Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Progress</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Started</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($runs as $run)
                    <tr>
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        Pending
                                    </span>
                                    @break
                                @case('running')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        <svg class="animate-spin -ml-0.5 mr-1.5 h-3 w-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Running
                                    </span>
                                    @break
                                @case('completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Completed
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Failed
                                    </span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-zinc-900 dark:text-white">
                                {{ $run->items_scraped }} scraped
                            </div>
                            @if($run->items_failed > 0)
                                <div class="text-xs text-red-500">
                                    {{ $run->items_failed }} failed
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
                            <a href="{{ route('admin.scraper.show', $run) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                View
                            </a>
                            @if($run->status === 'running')
                                <button wire:click="cancelRun({{ $run->id }})" wire:confirm="Are you sure you want to cancel this run?" class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                                    Cancel
                                </button>
                            @endif
                            @if($run->status === 'failed')
                                <button wire:click="retryRun({{ $run->id }})" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                    Retry
                                </button>
                            @endif
                            <button wire:click="deleteRun({{ $run->id }})" wire:confirm="Are you sure you want to delete this run?" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-zinc-500 dark:text-zinc-400">
                            No scraper runs found.
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
