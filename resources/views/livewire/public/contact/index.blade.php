<div class="max-w-2xl mx-auto py-6 px-4">
    <div class="flex items-center gap-3 mb-2">
        @auth
            <a href="{{ route('information') }}" class="text-zinc-400 dark:text-zinc-500 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
        @endauth
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('contact.title') }}</h1>
    </div>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">{{ __('contact.intro') }}</p>

    @if ($sent)
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl text-green-600 dark:text-green-400 flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ __('contact.success') }}</span>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-5">
        <div>
            <label for="name" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('contact.name') }}</label>
            <input type="text" id="name" wire:model="name" placeholder="{{ __('contact.name_placeholder') }}"
                class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
            @error('name')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('contact.email') }}</label>
            <input type="email" id="email" wire:model="email" placeholder="{{ __('contact.email_placeholder') }}"
                class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
            @error('email')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="message" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('contact.message') }}</label>
            <textarea id="message" wire:model="message" rows="6" placeholder="{{ __('contact.message_placeholder') }}"
                class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-xl text-zinc-900 dark:text-white placeholder-zinc-500 dark:placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"></textarea>
            @error('message')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="w-full px-6 py-3 bg-accent text-white font-medium rounded-xl hover:bg-accent/90 transition-colors" wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">{{ __('contact.send') }}</span>
            <span wire:loading wire:target="submit">{{ __('contact.sending') }}</span>
        </button>
    </form>
</div>
