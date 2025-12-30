{{-- Theme Toggle Button Component --}}
<button
    onclick="window.toggleTheme()"
    class="flex items-center justify-between w-full px-4 py-3 text-sm rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors"
    aria-label="Toggle theme"
>
    <span class="flex items-center gap-3">
        <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <svg class="w-5 h-5 text-zinc-300 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
        <span class="font-medium text-zinc-900 dark:text-white">
            <span class="dark:hidden">Light Mode</span>
            <span class="hidden dark:inline">Dark Mode</span>
        </span>
    </span>
    <span class="text-xs text-zinc-500 dark:text-zinc-400">
        Tap to toggle
    </span>
</button>
