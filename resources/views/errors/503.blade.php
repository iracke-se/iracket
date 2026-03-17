<x-errors.layout>
    <div class="max-w-md">
        <p class="text-8xl font-bold text-accent">503</p>
        <h1 class="mt-4 text-2xl font-semibold text-zinc-900 dark:text-white">{{ __('Service unavailable') }}</h1>
        <p class="mt-2 text-zinc-500 dark:text-zinc-400">
            @if(isset($exception) && $exception->getMessage())
                {{ $exception->getMessage() }}
            @else
                {{ __('We\'re down for maintenance. Check back soon.') }}
            @endif
        </p>
        <div class="mt-8">
            <a href="javascript:location.reload()" class="px-5 py-2.5 text-sm font-medium bg-accent text-white rounded-lg hover:opacity-90 transition-opacity">
                {{ __('Try again') }}
            </a>
        </div>
    </div>
</x-errors.layout>
