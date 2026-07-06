<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('information') }}" class="text-zinc-400 dark:text-zinc-500 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('user-guide.user_guide') }}</h1>
    </div>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">{{ __('user-guide.intro') }}</p>

    @if($sections->isEmpty())
        <div class="p-8 text-center text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
            {{ __('user-guide.coming_soon') }}
        </div>
    @else
        <div class="grid grid-cols-2 gap-3">
            @foreach($sections as $section)
                <a href="{{ route('guide.show', $section->slug) }}" wire:navigate
                   class="flex flex-col gap-3 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-accent/10 text-accent">
                        <x-guide-icon :name="$section->icon" class="w-5 h-5" />
                    </div>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white leading-snug">{{ $section->title }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
