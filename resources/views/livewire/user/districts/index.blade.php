<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Districts') }}</h1>
        <button
            wire:click="toggleRaw"
            class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg border transition-colors {{ $showRaw ? 'bg-accent text-white border-accent' : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            {{ __('Raw Data') }}
        </button>
    </div>

    @if($showRaw)
        <!-- Raw Data Table -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
            <table class="w-full text-sm font-mono">
                <thead>
                    <tr class="bg-zinc-200 dark:bg-zinc-700 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 w-12">id</th>
                        <th class="px-4 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">name</th>
                        <th class="px-4 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 text-right w-32">profixio_id</th>
                        <th class="px-4 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 text-right w-24">players</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($districts as $district)
                        <tr
                            class="bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors cursor-pointer"
                            onclick="window.location='{{ route('districts.show', $district) }}'"
                        >
                            <td class="px-4 py-3 text-zinc-400 dark:text-zinc-500">{{ $district->id }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-white">{{ $district->name }}</td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-right">{{ $district->profixio_id ?? 'null' }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-accent/20 text-accent">
                                    {{ $district->users_count }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-3 text-right">{{ $districts->count() }} {{ __('districts') }}</p>
    @else
        <!-- Card List -->
        <div class="space-y-3">
            @foreach($districts as $district)
                <a
                    href="{{ route('districts.show', $district) }}"
                    wire:navigate
                    class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
                >
                    <div class="w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium text-zinc-900 dark:text-white">{{ $district->name }}</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $district->users_count }} {{ __('players') }}</p>
                    </div>

                    <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @endforeach
        </div>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-3 text-right">{{ $districts->count() }} {{ __('districts') }}</p>
    @endif
</div>
