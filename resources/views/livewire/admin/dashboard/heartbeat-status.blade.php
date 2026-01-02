<div class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700 mb-6"
     wire:poll.5s>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg {{ $overallStatus === 'healthy' ? 'bg-green-500/10' : ($overallStatus === 'unhealthy' ? 'bg-red-500/10' : 'bg-yellow-500/10') }}">
                @if($overallStatus === 'healthy')
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @elseif($overallStatus === 'unhealthy')
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                @else
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @endif
            </div>
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">System Health Monitor</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Supervisor process monitoring</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                {{ $overallStatus === 'healthy' ? 'bg-green-500/10 text-green-700 dark:text-green-400' :
                   ($overallStatus === 'unhealthy' ? 'bg-red-500/10 text-red-700 dark:text-red-400' :
                   'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400') }}">
                {{ ucfirst($overallStatus) }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Scheduler Status -->
        <div class="flex items-start gap-3 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50">
            <div class="mt-0.5">
                @if($scheduler['status'] === 'healthy')
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                @elseif($scheduler['status'] === 'unhealthy')
                    <div class="w-2 h-2 rounded-full bg-red-500"></div>
                @elseif($scheduler['status'] === 'unknown')
                    <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                @else
                    <div class="w-2 h-2 rounded-full bg-zinc-400"></div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Scheduler (Cron)</span>
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ $scheduler['status'] === 'healthy' ? 'bg-green-500/10 text-green-700 dark:text-green-400' :
                           ($scheduler['status'] === 'unhealthy' ? 'bg-red-500/10 text-red-700 dark:text-red-400' :
                           ($scheduler['status'] === 'unknown' ? 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400' :
                           'bg-zinc-500/10 text-zinc-700 dark:text-zinc-400')) }}">
                        {{ ucfirst($scheduler['status']) }}
                    </span>
                </div>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $scheduler['message'] }}</p>
                @if(isset($scheduler['last_beat']))
                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                        Last beat: {{ $scheduler['minutes_ago'] }} min ago
                    </p>
                @endif
            </div>
        </div>

        <!-- Queue Worker Status -->
        <div class="flex items-start gap-3 p-4 rounded-lg bg-zinc-50 dark:bg-zinc-900/50">
            <div class="mt-0.5">
                @if($queue['status'] === 'healthy')
                    <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                @elseif($queue['status'] === 'unhealthy')
                    <div class="w-2 h-2 rounded-full bg-red-500"></div>
                @elseif($queue['status'] === 'unknown')
                    <div class="w-2 h-2 rounded-full bg-yellow-500"></div>
                @else
                    <div class="w-2 h-2 rounded-full bg-zinc-400"></div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Queue Worker</span>
                    <span class="text-xs px-2 py-0.5 rounded
                        {{ $queue['status'] === 'healthy' ? 'bg-green-500/10 text-green-700 dark:text-green-400' :
                           ($queue['status'] === 'unhealthy' ? 'bg-red-500/10 text-red-700 dark:text-red-400' :
                           ($queue['status'] === 'unknown' ? 'bg-yellow-500/10 text-yellow-700 dark:text-yellow-400' :
                           'bg-zinc-500/10 text-zinc-700 dark:text-zinc-400')) }}">
                        {{ ucfirst($queue['status']) }}
                    </span>
                </div>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $queue['message'] }}</p>
                @if(isset($queue['last_beat']))
                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                        Last beat: {{ $queue['minutes_ago'] }} min ago
                    </p>
                @endif
            </div>
        </div>
    </div>

    @if($overallStatus === 'unhealthy')
        <div class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20">
            <p class="text-sm text-red-700 dark:text-red-400">
                <strong>Action Required:</strong> One or more supervisor processes are not responding.
                Please check the supervisor status: <code class="text-xs bg-red-900/20 px-1 py-0.5 rounded">sudo supervisorctl status</code>
            </p>
        </div>
    @elseif($scheduler['status'] === 'unknown' || $queue['status'] === 'unknown')
        <div class="mt-4 p-3 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                <strong>Info:</strong> Heartbeat monitoring is initializing.
                @if($scheduler['status'] === 'unknown')
                    Scheduler heartbeat will update within 1 minute.
                @endif
                @if($queue['status'] === 'unknown')
                    Queue heartbeat will update when jobs are processed.
                @endif
            </p>
        </div>
    @endif
</div>
