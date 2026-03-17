<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <style>
        body { font-family: 'Instrument Sans', -apple-system, sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900 antialiased">

    <!-- Logo -->
    <div class="fixed top-0 left-0 right-0 z-50 p-4 md:p-6">
        <a href="{{ route('home') }}" class="inline-flex items-center justify-center w-10 h-10 bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm hover:bg-white dark:hover:bg-zinc-800 transition-colors">
            <img src="/assets/images/icon.png" alt="iRacket" class="h-6">
        </a>
    </div>

    <!-- Content -->
    <div class="flex min-h-screen flex-col items-center justify-center px-6 text-center">
        {{ $slot }}
    </div>

    <!-- Dark Mode Toggle -->
    <div class="fixed bottom-6 right-6 z-50">
        <button onclick="toggleDarkMode()" class="flex items-center justify-center w-10 h-10 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-all duration-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-sm">
            <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
        </button>
    </div>

    <script>
        function toggleDarkMode() {
            const html = document.documentElement;
            if (html.classList.contains('dark')) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            }
        }
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @fluxScripts
</body>
</html>
