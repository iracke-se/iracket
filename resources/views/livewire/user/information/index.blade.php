<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">{{ __('user-information.information') }}</h1>

    <div class="space-y-3">
        <!-- Notifications -->
        <a href="{{ route('notifications') }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
            <div class="flex items-center gap-3">
                <div class="relative flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($unreadNotificationsCount > 0)
                        <span class="absolute -top-1 -right-1 flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">
                            {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                        </span>
                    @endif
                </div>
                <span class="text-zinc-900 dark:text-white font-medium">{{ __('user-information.notifications') }}</span>
            </div>
            <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- About Us -->
        <a href="{{ route('terms.show', 'about-us') }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-zinc-900 dark:text-white font-medium">{{ __('user-information.about_us') }}</span>
            </div>
            <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- How We Calculate (Bubbler) -->
        <a href="{{ route('terms.show', 'bubbler') }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="text-zinc-900 dark:text-white font-medium">{{ __('user-information.how_we_calculate') }}</span>
            </div>
            <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Privacy Policy -->
        <a href="{{ route('terms.show', 'privacy-policy') }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-zinc-900 dark:text-white font-medium">{{ __('user-information.privacy_policy') }}</span>
            </div>
            <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>

        <!-- Terms & Conditions -->
        <a href="{{ route('terms.show', 'terms-and-conditions') }}" class="flex items-center justify-between p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors" wire:navigate>
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700">
                    <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <span class="text-zinc-900 dark:text-white font-medium">{{ __('user-information.terms_conditions') }}</span>
            </div>
            <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    <!-- Language Switcher -->
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-information.language') }}</h2>
        <div class="flex gap-3">
            <a
                href="{{ route('locale.switch', 'en') }}"
                class="flex-1 flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all {{ $locale === 'en' ? 'bg-accent/10 border-accent text-accent' : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-zinc-400 dark:hover:border-zinc-600' }}"
            >
                <span class="text-xl">🇬🇧</span>
                <span class="font-medium">English</span>
            </a>

            <a
                href="{{ route('locale.switch', 'sv') }}"
                class="flex-1 flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all {{ $locale === 'sv' ? 'bg-accent/10 border-accent text-accent' : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-zinc-400 dark:hover:border-zinc-600' }}"
            >
                <span class="text-xl">🇸🇪</span>
                <span class="font-medium">Svenska</span>
            </a>
        </div>
    </div>

    <!-- App Version -->
    <div class="mt-8 text-center text-sm text-zinc-500 dark:text-zinc-500">
        <p>iRacket v1.0.0</p>
    </div>
</div>
