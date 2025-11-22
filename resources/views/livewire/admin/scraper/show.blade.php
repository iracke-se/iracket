<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.scraper.index') }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors" wire:navigate>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white capitalize">
                    {{ str_replace('_', ' ', $run->type) }} Run #{{ $run->id }}
                </h1>
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                {{ __('admin-scraper.started') }} {{ $run->started_at?->format('M d, Y H:i:s') ?? __('admin-scraper.not_started') }}
            </p>
        </div>
        <div>
            @switch($run->status)
                @case('pending')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200">
                        {{ __('admin-scraper.pending') }}
                    </span>
                    @break
                @case('running')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
                        <svg class="animate-spin -ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ __('admin-scraper.status_running') }}
                    </span>
                    @break
                @case('completed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200">
                        {{ __('admin-scraper.status_completed') }}
                    </span>
                    @break
                @case('failed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200">
                        {{ __('admin-scraper.status_failed') }}
                    </span>
                    @break
            @endswitch
        </div>
    </div>

    <!-- Run Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin-scraper.run_details') }}</h2>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.type') }}</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white capitalize">{{ str_replace('_', ' ', $run->type) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.status') }}</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white capitalize">{{ $run->status }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.items_scraped') }}</dt>
                    <dd class="text-sm font-medium text-green-600 dark:text-green-400">{{ $run->items_scraped }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.items_failed') }}</dt>
                    <dd class="text-sm font-medium text-red-600 dark:text-red-400">{{ $run->items_failed }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.duration') }}</dt>
                    <dd class="text-sm font-medium text-zinc-900 dark:text-white">{{ $run->duration ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin-scraper.parameters') }}</h2>
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
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-scraper.no_parameters') }}</p>
            @endif

            @if($run->error_message)
                <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-red-600 dark:text-red-400 mb-2">{{ __('admin-scraper.error_message') }}</h3>
                    <p class="text-sm text-zinc-900 dark:text-white bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
                        {{ $run->error_message }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Logs -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('admin-scraper.logs') }}</h2>
            <select wire:model.live="logLevel" class="px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                <option value="">{{ __('admin-scraper.all_levels') }}</option>
                <option value="info">{{ __('admin-scraper.info') }}</option>
                <option value="warning">{{ __('admin-scraper.warning') }}</option>
                <option value="error">{{ __('admin-scraper.error') }}</option>
            </select>
        </div>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700 max-h-[500px] overflow-y-auto">
            @forelse($logs as $log)
                <div class="px-6 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-0.5">
                            @switch($log->level)
                                @case('info')
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/50">
                                        <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                    @break
                                @case('warning')
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-100 dark:bg-yellow-900/50">
                                        <svg class="w-3.5 h-3.5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    </span>
                                    @break
                                @case('error')
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/50">
                                        <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </span>
                                    @break
                            @endswitch
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-zinc-900 dark:text-white">{{ $log->message }}</p>
                            @if($log->context && count($log->context) > 0)
                                <pre class="mt-2 text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg overflow-x-auto">{{ json_encode($log->context, JSON_PRETTY_PRINT) }}</pre>
                            @endif
                            <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                {{ $log->created_at->format('H:i:s') }}
                            </p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                    <svg class="w-12 h-12 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ __('admin-scraper.no_logs_found') }}
                </div>
            @endforelse
        </div>

        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Back Button -->
    <div class="mt-6">
        <a href="{{ route('admin.scraper.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg font-medium transition-colors" wire:navigate>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            {{ __('admin-scraper.back') }}
        </a>
    </div>
</div>
