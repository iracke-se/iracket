<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <!-- Header with Logo and Back Link -->
        <div class="absolute top-0 left-0 right-0 z-50 p-6">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex items-center">
                    <img src="/assets/images/icon.png" alt="iRacket" class="h-8">
                </div>

                <!-- Back to Website Link -->
                <a href="https://iracket.se" class="inline-flex items-center px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-all duration-200 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:scale-105 hover:-translate-y-0.5 transform">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Back to Website
                </a>
            </div>
        </div>

        <div class="bg-background flex min-h-svh flex-col items-center gap-6 p-6 md:p-10 pt-24 md:pt-32">
            <div class="flex w-full max-w-[1000px] flex-col gap-2 mt-8">
                {{ $slot }}
            </div>
        </div>
        @fluxScripts
    </body>
</html>
