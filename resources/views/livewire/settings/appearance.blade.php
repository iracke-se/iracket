<div class="max-w-2xl mx-auto">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="flex gap-3">
            <button
                x-data
                @click="$flux.appearance = 'light'"
                :class="$flux.appearance === 'light' ? 'bg-accent/10 border-accent text-accent' : 'bg-zinc-100 border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500'"
                class="flex-1 flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <span class="text-sm font-medium">{{ __('Light') }}</span>
            </button>

            <button
                x-data
                @click="$flux.appearance = 'dark'"
                :class="$flux.appearance === 'dark' ? 'bg-accent/10 border-accent text-accent' : 'bg-zinc-100 border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500'"
                class="flex-1 flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span class="text-sm font-medium">{{ __('Dark') }}</span>
            </button>

            <button
                x-data
                @click="$flux.appearance = 'system'"
                :class="$flux.appearance === 'system' ? 'bg-accent/10 border-accent text-accent' : 'bg-zinc-100 border-zinc-300 text-zinc-600 hover:border-zinc-400 dark:bg-zinc-700 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500'"
                class="flex-1 flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm font-medium">{{ __('System') }}</span>
            </button>
        </div>
    </x-settings.layout>
</div>
