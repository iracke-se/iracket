<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('auth.confirm_password_title')"
            :description="__('auth.confirm_password_description')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('auth.password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('auth.password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('auth.confirm') }}
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
