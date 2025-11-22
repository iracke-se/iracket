<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.scraper.index') }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white capitalize">
                    {{ str_replace('_', ' ', $run->type) }} Run #{{ $run->id }}
                </h1>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Started {{ $run->started_at?->format('M d, Y H:i:s') ?? 'Not started' }}
            </p>
        </div>
        <div>
            @switch($run->status)
                @case('pending')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        Pending
                    </span>
                    @break
                @case('running')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        Running
                    </span>
                    @break
                @case('completed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Completed
                    </span>
                    @break
                @case('failed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        Failed
                    </span>
                    @break
            @endswitch
        </div>
    </div>

    <!-- Run Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Run Details</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Type</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white capitalize">{{ str_replace('_', ' ', $run->type) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Status</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white capitalize">{{ $run->status }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Items Scraped</dt>
                    <dd class="text-sm font-medium text-green-600 dark:text-green-400">{{ $run->items_scraped }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Items Failed</dt>
                    <dd class="text-sm font-medium text-red-600 dark:text-red-400">{{ $run->items_failed }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Duration</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $run->duration ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow p-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">Parameters</h2>
            @if($run->parameters && count($run->parameters) > 0)
                <dl class="space-y-3">
                    @foreach($run->parameters as $key => $value)
                        <div class="flex justify-between">
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400 capitalize">{{ str_replace('_', ' ', $key) }}</dt>
                            <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-zinc-500 dark:text-zinc-400">No parameters</p>
            @endif

            @if($run->error_message)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-red-600 dark:text-red-400 mb-2">Error Message</h3>
                    <p class="text-sm text-zinc-900 dark:text-white bg-red-50 dark:bg-red-900/20 p-3 rounded">
                        {{ $run->error_message }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Logs -->
    <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Logs</h2>
            <select wire:model.live="logLevel" class="text-sm rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                <option value="">All Levels</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
            </select>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700 max-h-96 overflow-y-auto">
            @forelse($logs as $log)
                <div class="px-4 py-3">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            @switch($log->level)
                                @case('info')
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900">
                                        <span class="text-xs text-blue-600 dark:text-blue-400">i</span>
                                    </span>
                                    @break
                                @case('warning')
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-yellow-100 dark:bg-yellow-900">
                                        <span class="text-xs text-yellow-600 dark:text-yellow-400">!</span>
                                    </span>
                                    @break
                                @case('error')
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100 dark:bg-red-900">
                                        <span class="text-xs text-red-600 dark:text-red-400">x</span>
                                    </span>
                                    @break
                            @endswitch
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-900 dark:text-white">{{ $log->message }}</p>
                            @if($log->context && count($log->context) > 0)
                                <pre class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 p-2 rounded overflow-x-auto">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                {{ $log->created_at->format('H:i:s') }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                    No logs found.
                </div>
            @endforelse
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
