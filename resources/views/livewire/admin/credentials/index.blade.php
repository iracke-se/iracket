<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('admin-credentials.credentials') }}</h1>
        <a href="{{ route('admin.credentials.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
            {{ __('admin-credentials.create_new') }}
        </a>
    </div>

    <p class="mb-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin-credentials.intro') }}</p>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Search -->
    <div class="mb-6">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('admin-credentials.search') }}"
            class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead class="bg-zinc-100 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-credentials.title') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-credentials.username') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-credentials.password') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-credentials.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($credentials as $credential)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-6 py-4 align-top">
                            <div class="text-zinc-900 dark:text-white font-medium">{{ $credential->title }}</div>
                            @if($credential->url)
                                <a href="{{ $credential->url }}" target="_blank" rel="noopener noreferrer" class="text-sm text-accent hover:text-accent/80 break-all">{{ $credential->url }}</a>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top">
                            @if($credential->username)
                                <div x-data="{ copied: false }" class="flex items-center gap-2">
                                    <span class="text-zinc-700 dark:text-zinc-200 break-all">{{ $credential->username }}</span>
                                    <button type="button"
                                        x-on:click="navigator.clipboard.writeText(@js($credential->username)); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="shrink-0 text-zinc-400 hover:text-accent" title="{{ __('admin-credentials.copy') }}">
                                        <span x-show="!copied">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </span>
                                        <span x-show="copied" x-cloak class="text-accent text-xs">{{ __('admin-credentials.copied') }}</span>
                                    </button>
                                </div>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 align-top">
                            @if($credential->password)
                                <div x-data="{ show: false, copied: false }" class="flex items-center gap-2">
                                    <span class="font-mono text-zinc-700 dark:text-zinc-200 break-all" x-show="show">{{ $credential->password }}</span>
                                    <span class="font-mono text-zinc-400 dark:text-zinc-500" x-show="!show">••••••••••</span>
                                    <button type="button" x-on:click="show = !show" class="shrink-0 text-zinc-400 hover:text-accent" title="{{ __('admin-credentials.reveal') }}">
                                        <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                    </button>
                                    <button type="button"
                                        x-on:click="navigator.clipboard.writeText(@js($credential->password)); copied = true; setTimeout(() => copied = false, 1500)"
                                        class="shrink-0 text-zinc-400 hover:text-accent" title="{{ __('admin-credentials.copy') }}">
                                        <span x-show="!copied">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                        </span>
                                        <span x-show="copied" x-cloak class="text-accent text-xs">{{ __('admin-credentials.copied') }}</span>
                                    </button>
                                </div>
                            @else
                                <span class="text-zinc-400 dark:text-zinc-500">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right align-top">
                            <a href="{{ route('admin.credentials.edit', $credential->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>{{ __('admin-credentials.edit') }}</a>
                            <button wire:click="delete({{ $credential->id }})" wire:confirm="{{ __('admin-credentials.confirm_delete') }}" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300">{{ __('admin-credentials.delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('admin-credentials.none_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $credentials->links() }}
    </div>
</div>
