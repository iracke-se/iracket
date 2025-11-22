<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <!-- Social Login Buttons -->
        <div class="flex flex-col gap-3">
            <a href="{{ route('auth.google') }}" class="flex items-center justify-center gap-3 w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <svg class="size-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Continue with Google') }}</span>
            </a>

            <a href="{{ route('auth.apple') }}" class="flex items-center justify-center gap-3 w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                </svg>
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Continue with Apple') }}</span>
            </a>
        </div>

        <!-- Divider -->
        <div class="relative">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-zinc-300 dark:border-zinc-600"></div>
            </div>
            <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white dark:bg-zinc-900 text-zinc-500">{{ __('or') }}</span>
            </div>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6" enctype="multipart/form-data">
            @csrf

            <!-- Profile Picture -->
            <div class="flex flex-col items-center gap-3">
                <div class="relative">
                    <div class="size-20 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center overflow-hidden" id="profile-preview">
                        <svg class="size-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <label for="profile_picture" class="absolute bottom-0 right-0 size-6 bg-accent text-white rounded-full flex items-center justify-center cursor-pointer hover:bg-accent/90 transition-colors">
                        <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </label>
                </div>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden" onchange="previewImage(this)" />
                <span class="text-xs text-zinc-500">{{ __('Profile picture (optional)') }}</span>
                @error('profile_picture')
                    <span class="text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <!-- First Name & Last Name -->
            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    name="first_name"
                    :label="__('First name')"
                    :value="old('first_name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="given-name"
                    :placeholder="__('First name')"
                />

                <flux:input
                    name="last_name"
                    :label="__('Last name')"
                    :value="old('last_name')"
                    type="text"
                    required
                    autocomplete="family-name"
                    :placeholder="__('Last name')"
                />
            </div>

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <!-- Terms and Conditions -->
            <div class="flex items-start gap-2">
                <input
                    type="checkbox"
                    id="terms_accepted"
                    name="terms_accepted"
                    required
                    class="mt-1 size-4 rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent"
                    {{ old('terms_accepted') ? 'checked' : '' }}
                />
                <label for="terms_accepted" class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('I agree to the') }}
                    <a href="{{ route('terms.show', 'terms-and-conditions') }}" target="_blank" class="text-accent hover:underline">{{ __('Terms and Conditions') }}</a>
                    {{ __('and') }}
                    <a href="{{ route('terms.show', 'privacy-policy') }}" target="_blank" class="text-accent hover:underline">{{ __('Privacy Policy') }}</a>
                </label>
            </div>
            @error('terms_accepted')
                <span class="text-xs text-red-500 -mt-4">{{ $message }}</span>
            @enderror

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <script>
            function previewImage(input) {
                const preview = document.getElementById('profile-preview');
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" class="size-full object-cover" />`;
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }
        </script>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
