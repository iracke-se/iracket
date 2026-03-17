<x-errors.layout>
    <div class="max-w-md">
        <p class="text-8xl font-bold text-accent">500</p>
        <h1 class="mt-4 text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Server error') }}</h1>
        <p class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('Something went wrong on our end. We\'re working on it.') }}</p>
        <div class="mt-8 flex items-center justify-center gap-3">
            <a href="javascript:location.reload()" class="px-5 py-2.5 text-sm font-medium bg-accent text-white rounded-lg hover:opacity-90 transition-opacity">
                {{ __('Try again') }}
            </a>
            <a href="{{ route('home') }}" class="px-5 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                {{ __('Go home') }}
            </a>
        </div>
    </div>
</x-errors.layout>
