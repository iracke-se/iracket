<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Verify your email')"
        :description="__('Enter the 6-digit code we sent to your email address')"
    />

    <div class="text-center">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('We sent a code to') }} <strong>{{ Auth::user()->email }}</strong>
        </p>
    </div>

    @if ($error)
        <div class="p-3 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg text-center">
            {{ $error }}
        </div>
    @endif

    @if ($success)
        <div class="p-3 text-sm text-green-600 bg-green-50 dark:bg-green-900/20 dark:text-green-400 rounded-lg text-center">
            {{ $success }}
        </div>
    @endif

    <form wire:submit="verify" class="flex flex-col gap-6">
        <div class="flex flex-col items-center gap-2">
            <label for="code" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                {{ __('Verification Code') }}
            </label>
            <input
                type="text"
                id="code"
                wire:model="code"
                maxlength="6"
                placeholder="000000"
                class="w-48 text-center text-2xl font-mono tracking-[0.5em] px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-accent focus:border-accent outline-none"
                autofocus
                autocomplete="one-time-code"
                inputmode="numeric"
                pattern="[0-9]*"
            />
            <p class="text-xs text-zinc-500">{{ __('Code expires in 15 minutes') }}</p>
        </div>

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('Verify Email') }}
        </flux:button>
    </form>

    <div class="text-center">
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ __("Didn't receive the code?") }}
            <button
                type="button"
                wire:click="resend"
                class="text-accent hover:underline font-medium"
            >
                {{ __('Resend code') }}
            </button>
        </p>
    </div>

    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer">
                {{ __('Log out') }}
            </flux:button>
        </form>
    </div>
</div>
