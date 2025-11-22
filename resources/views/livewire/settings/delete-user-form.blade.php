<div class="mt-8 pt-8 border-t border-zinc-200 dark:border-zinc-700">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('user-settings.delete_account') }}</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ __('user-settings.delete_account_description') }}</p>
    </div>

    <flux:modal.trigger name="confirm-user-deletion">
        <button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="px-4 py-2 bg-red-500/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-500/30 transition-colors"
        >
            {{ __('user-settings.delete_account') }}
        </button>
    </flux:modal.trigger>

    <flux:modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
        <form method="POST" wire:submit="deleteUser" class="space-y-6">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('user-settings.delete_account_confirm') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-2">
                    {{ __('user-settings.delete_account_warning') }}
                </p>
            </div>

            <div>
                <label for="delete_password" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('user-settings.password') }}</label>
                <input
                    type="password"
                    id="delete_password"
                    wire:model="password"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <button type="button" class="px-4 py-2 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors">
                        {{ __('user-settings.cancel') }}
                    </button>
                </flux:modal.close>

                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                    {{ __('user-settings.delete_account') }}
                </button>
            </div>
        </form>
    </flux:modal>
</div>
