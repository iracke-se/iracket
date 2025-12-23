<div class="max-w-7xl mx-auto" x-data="{
    autoScroll: true,
    scrollToBottom() {
        if (this.autoScroll) {
            const console = document.getElementById('console-output');
            if (console) {
                console.scrollTop = console.scrollHeight;
            }
        }
    },
    init() {
        // Scroll to bottom on mount
        this.$nextTick(() => this.scrollToBottom());

        // Start polling if running
        if ($wire.isRunning) {
            this.startPolling();
        }
    },
    startPolling() {
        this.pollInterval = setInterval(() => {
            $wire.call('refreshData').then(() => {
                this.$nextTick(() => this.scrollToBottom());
            });

            // Stop polling if no longer running
            if (!$wire.isRunning) {
                this.stopPolling();
            }
        }, 1000); // Poll every second
    },
    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }
}">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.scraper.index') }}"
               wire:navigate
               class="p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Scraper Run #{{ $run->id }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ ucfirst($run->type) }} · {{ ucfirst($run->status) }}</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @if($isRunning)
                <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-sm font-medium">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Running...</span>
                </div>
            @endif

            <select wire:model.live="logLevel"
                    class="px-3 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-zinc-900 dark:text-white">
                <option value="">All Levels</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
            </select>

            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <input type="checkbox" x-model="autoScroll" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                Auto-scroll
            </label>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Items Scraped -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Items Scraped</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ number_format($run->items_scraped) }}</p>
        </div>

        <!-- Items Failed -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Items Failed</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ number_format($run->items_failed) }}</p>
        </div>

        <!-- Duration -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Duration</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $run->duration ?? '...' }}</p>
        </div>

        <!-- Status -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Status</p>
            <p class="text-2xl font-bold mt-1 @if($run->status === 'completed') text-green-600 dark:text-green-400 @elseif($run->status === 'failed') text-red-600 dark:text-red-400 @elseif($run->status === 'running') text-blue-600 dark:text-blue-400 @else text-zinc-600 dark:text-zinc-400 @endif">
                {{ ucfirst($run->status) }}
            </p>
        </div>
    </div>

    <!-- Console Output -->
    <div class="bg-zinc-900 rounded-lg border border-zinc-700 overflow-hidden">
        <!-- Console Header -->
        <div class="bg-zinc-800 px-4 py-2 border-b border-zinc-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                </div>
                <span class="text-xs font-mono text-zinc-400 ml-2">scraper:run {{ $run->type }}</span>
            </div>
            <div class="flex items-center gap-2 text-xs text-zinc-400">
                <span>{{ $logs->count() }} log entries</span>
            </div>
        </div>

        <!-- Console Body -->
        <div id="console-output" class="p-4 font-mono text-sm text-zinc-100 h-[600px] overflow-y-auto custom-scrollbar" @scroll="autoScroll = Math.abs(($event.target.scrollHeight - $event.target.scrollTop) - $event.target.clientHeight) < 10">
            @if($run->started_at)
                <div class="text-zinc-500 mb-4">
                    <span class="text-zinc-600">[{{ $run->started_at->format('Y-m-d H:i:s') }}]</span> Starting scraper run #{{ $run->id }}
                </div>
            @endif

            @forelse($logs as $log)
                <div class="mb-1 flex items-start gap-2">
                    <!-- Timestamp -->
                    <span class="text-zinc-600 flex-shrink-0">[{{ $log->created_at->format('H:i:s') }}]</span>

                    <!-- Level Icon -->
                    @if($log->level === 'error')
                        <span class="text-red-400 flex-shrink-0">✗</span>
                    @elseif($log->level === 'warning')
                        <span class="text-yellow-400 flex-shrink-0">⚠</span>
                    @else
                        <span class="text-blue-400 flex-shrink-0">ℹ</span>
                    @endif

                    <!-- Message -->
                    <span class="@if($log->level === 'error') text-red-300 @elseif($log->level === 'warning') text-yellow-300 @else text-zinc-300 @endif flex-1 break-words">
                        {{ $log->message }}
                    </span>
                </div>

                @if($log->context && !empty($log->context))
                    <div class="ml-14 mb-2 text-xs text-zinc-500">
                        @foreach($log->context as $key => $value)
                            <div>{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</div>
                        @endforeach
                    </div>
                @endif
            @empty
                <div class="text-center text-zinc-500 py-8">
                    No logs available yet...
                    @if($isRunning)
                        <div class="mt-2 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Waiting for scraper to start...
                        </div>
                    @endif
                </div>
            @endforelse

            @if($run->completed_at)
                <div class="text-zinc-500 mt-4">
                    <span class="text-zinc-600">[{{ $run->completed_at->format('Y-m-d H:i:s') }}]</span>
                    @if($run->status === 'completed')
                        <span class="text-green-400">✓ Scraper completed successfully</span>
                    @elseif($run->status === 'failed')
                        <span class="text-red-400">✗ Scraper failed</span>
                        @if($run->error_message)
                            <div class="ml-14 mt-1 text-red-300">{{ $run->error_message }}</div>
                        @endif
                    @endif
                </div>
            @elseif($isRunning)
                <div class="text-blue-400 mt-4 flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>Scraper is running...</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Parameters -->
    @if(!empty($run->parameters))
        <div class="mt-6 bg-white dark:bg-zinc-800 rounded-lg p-4 border border-zinc-200 dark:border-zinc-700">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">Parameters</h3>
            <dl class="grid grid-cols-2 gap-3">
                @foreach($run->parameters as $key => $value)
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                        <dd class="text-sm text-zinc-900 dark:text-white font-mono">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #27272a;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #52525b;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #71717a;
        }
    </style>
</div>
