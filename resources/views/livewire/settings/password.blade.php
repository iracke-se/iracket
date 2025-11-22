<div class="max-w-2xl mx-auto">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('user-settings.password_heading')" :subheading="__('user-settings.password_subheading')">
        <form method="POST" wire:submit="updatePassword" class="space-y-6">
            <div>
                <label for="current_password" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.current_password') }}</label>
                <input
                    type="password"
                    id="current_password"
                    wire:model="current_password"
                    required
                    autocomplete="current-password"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('current_password')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.new_password') }}</label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    required
                    autocomplete="new-password"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.confirm_password') }}</label>
                <input
                    type="password"
                    id="password_confirmation"
                    wire:model="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                    {{ __('user-settings.save') }}
                </button>

                <x-action-message class="text-sm text-green-500 dark:text-green-400" on="password-updated">
                    {{ __('user-settings.saved') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
</div>
