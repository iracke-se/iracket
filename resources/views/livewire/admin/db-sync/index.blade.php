<div class="max-w-[1400px] mx-auto py-6 px-4">
    @if (! $unlocked)
        {{-- Password gate: keeps other admins from opening the production sync by accident. --}}
        <div class="max-w-sm mx-auto mt-16">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white text-center">Sync to Production</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 text-center mt-1 mb-6">
                This page is protected. Enter the password to continue.
            </p>
            <form wire:submit="unlock" class="space-y-3">
                <input type="password" wire:model="gatePassword" placeholder="Password" autofocus autocomplete="off"
                    class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent" />
                @error('gate')
                    <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <button type="submit" class="w-full px-4 py-3 bg-accent text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
                    Unlock
                </button>
            </form>
        </div>
    @else
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Sync to Production</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
            Push selected tables to
            <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ config('datasync.target_url') ?: 'production' }}</span>.
            Rows are copied exactly — same IDs, same ownership.
        </p>
    </div>

    {{-- Not configured --}}
    @unless ($this->configured)
        <div class="mb-6 p-4 bg-amber-500/10 border border-amber-500/20 rounded-lg text-amber-600 dark:text-amber-400 text-sm">
            <p class="font-medium">This instance has no sync target configured.</p>
            <p class="mt-1">Set <code class="font-mono">DB_SYNC_TARGET_URL</code> and <code class="font-mono">DB_SYNC_SECRET</code> in this machine's <code class="font-mono">.env</code>, then reload.</p>
        </div>
    @endunless

    @error('sync')
        <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-600 dark:text-red-400 text-sm">{{ $message }}</div>
    @enderror

    @php $progress = $this->progress; @endphp

    {{-- ── Progress (while running or after finishing) ── --}}
    @if ($progress)
        <div @if ($this->running) wire:poll.800ms="step" @endif
             class="mb-6 bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    @if ($progress['status'] === 'running')
                        <svg class="w-5 h-5 text-accent animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span class="font-medium text-zinc-900 dark:text-white">Syncing… ({{ ucfirst($progress['mode']) }} mode)</span>
                    @elseif ($progress['status'] === 'completed')
                        <span class="font-medium text-green-600 dark:text-green-400">✓ Sync complete</span>
                    @elseif ($progress['status'] === 'failed')
                        <span class="font-medium text-red-600 dark:text-red-400">✗ Sync failed</span>
                    @endif
                </div>
                <span class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-[45%]" title="{{ $progress['target'] }}">→ {{ $progress['target'] }}</span>
            </div>

            @if ($progress['status'] === 'failed' && $progress['error'])
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-sm text-red-600 dark:text-red-400 break-words">{{ $progress['error'] }}</div>
            @endif

            <div class="space-y-3">
                @foreach ($progress['plan'] as $t)
                    @php
                        $m = $progress['tables'][$t];
                        $pct = $m['total'] > 0 ? min(100, (int) round($m['done'] / $m['total'] * 100)) : 100;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="font-mono text-zinc-700 dark:text-zinc-300">{{ $t }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400 tabular-nums">
                                {{ number_format($m['done']) }} / {{ number_format($m['total']) }}
                                @if ($m['status'] === 'done')<span class="text-green-500 ml-1">✓</span>@endif
                            </span>
                        </div>
                        <div class="h-2 rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 {{ $m['status'] === 'done' ? 'bg-green-500' : 'bg-accent' }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($progress['status'] !== 'running')
                <div class="mt-5">
                    <button wire:click="clearRun" class="px-4 py-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 rounded-lg text-sm hover:bg-zinc-200 dark:hover:bg-zinc-600 transition-colors">
                        Start another sync
                    </button>
                </div>
            @endif
        </div>
    @endif

    {{-- ── Picker (hidden while running) ── --}}
    @if (! $progress || $progress['status'] !== 'running')
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Choose tables to push ({{ count($this->selected) }} selected)</p>
                <div class="flex gap-2">
                    <button wire:click="selectAll" class="text-xs px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 transition-colors">Select all</button>
                    <button wire:click="deselectAll" class="text-xs px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 transition-colors">Clear</button>
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700/50">
                @foreach ($this->tables as $tbl)
                    <label class="flex items-center justify-between px-4 py-2.5 cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/40 transition-colors">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" wire:model="selected" value="{{ $tbl['name'] }}"
                                   class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent bg-zinc-100 dark:bg-zinc-700">
                            <span class="font-mono text-sm text-zinc-800 dark:text-zinc-200">{{ $tbl['name'] }}</span>
                        </div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400 tabular-nums">{{ number_format($tbl['rows']) }} rows</span>
                    </label>
                @endforeach
            </div>

            {{-- Replace-mode danger confirmation --}}
            @if ($this->mode === 'replace')
                <div class="px-4 pt-4">
                    <label class="flex items-start gap-3 p-3 rounded-lg bg-red-500/5 border border-red-500/20 cursor-pointer">
                        <input type="checkbox" wire:model.live="confirmed"
                               class="mt-0.5 rounded border-zinc-300 dark:border-zinc-600 text-red-600 focus:ring-red-500 bg-zinc-100 dark:bg-zinc-700">
                        <span class="text-sm text-red-600 dark:text-red-400">
                            I understand this runs in <strong>Replace</strong> mode: every selected table on production will be
                            <strong>wiped and replaced</strong> with this machine's copy, permanently deleting any production-only rows.
                            I have a production backup.
                        </span>
                    </label>
                </div>
            @endif

            <div class="p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <button wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection"
                            @unless ($this->configured) disabled @endunless
                            class="px-4 py-2 text-sm rounded-lg bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-600 disabled:opacity-50 transition-colors">
                        <span wire:loading.remove wire:target="testConnection">Test connection</span>
                        <span wire:loading wire:target="testConnection">Testing…</span>
                    </button>
                    @if ($ping !== null)
                        @if ($ping['ok'])
                            <span class="text-sm text-green-600 dark:text-green-400">✓ Connected</span>
                        @else
                            <span class="text-sm text-red-600 dark:text-red-400">
                                ✗ {{ is_array($ping['body']) ? ($ping['body']['message'] ?? 'Failed') : \Illuminate\Support\Str::limit((string) $ping['body'], 80) }}
                                @if (! empty($ping['status'])) ({{ $ping['status'] }})@endif
                            </span>
                        @endif
                    @endif
                </div>

                <button wire:click="startSync" wire:loading.attr="disabled" wire:target="startSync"
                        @unless ($this->configured) disabled @endunless
                        class="px-5 py-2.5 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 disabled:opacity-50 transition-colors">
                    Sync selected → Production
                </button>
            </div>
        </div>
    @endif
    @endif
</div>
