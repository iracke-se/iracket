<div class="max-w-3xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $credential ? __('admin-credentials.edit_credential') : __('admin-credentials.create_credential') }}</h1>
        <a href="{{ route('admin.credentials.index') }}" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
            {{ __('admin-credentials.back_to_list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div>
            <label for="title" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-credentials.title') }}</label>
            <input type="text" id="title" wire:model="title" placeholder="{{ __('admin-credentials.title_placeholder') }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
            @error('title')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="url" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-credentials.url') }}</label>
            <input type="url" id="url" wire:model="url" placeholder="https://github.com/login"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
            @error('url')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="username" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-credentials.username') }}</label>
                <input type="text" id="username" wire:model="username" autocomplete="off"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                @error('username')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            <div x-data="{ show: false }">
                <label for="password" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-credentials.password') }}</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" id="password" wire:model="password" autocomplete="new-password"
                        class="w-full px-4 py-3 pr-11 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                    <button type="button" x-on:click="show = !show" class="absolute inset-y-0 right-0 px-3 flex items-center text-zinc-400 hover:text-accent">
                        <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                    </button>
                </div>
                @error('password')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-credentials.notes') }}</label>
            <textarea id="notes" wire:model="notes" rows="4" placeholder="{{ __('admin-credentials.notes_placeholder') }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"></textarea>
            @error('notes')<p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $credential ? __('admin-credentials.update') : __('admin-credentials.create') }}
            </button>
            <a href="{{ route('admin.credentials.index') }}" class="px-6 py-3 text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
                {{ __('admin-credentials.cancel') }}
            </a>
        </div>
    </form>
</div>
