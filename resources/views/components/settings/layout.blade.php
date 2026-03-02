<div class="space-y-6">
    <!-- Navigation Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 scrollbar-hide">
        <a
            href="{{ route('profile.edit') }}"
            wire:navigate
            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('profile.edit') ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('user-settings.nav_profile') }}
        </a>
        <a
            href="{{ route('user-password.edit') }}"
            wire:navigate
            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('user-password.edit') ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('user-settings.nav_password') }}
        </a>
        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            <a
                href="{{ route('two-factor.show') }}"
                wire:navigate
                class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('two-factor.show') ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
            >
                {{ __('user-settings.nav_two_factor') }}
            </a>
        @endif
        <a
            href="{{ route('appearance.edit') }}"
            wire:navigate
            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('appearance.edit') ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('user-settings.nav_appearance') }}
        </a>
        <a
            href="{{ route('club.edit') }}"
            wire:navigate
            class="flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('club.edit') ? 'bg-accent text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700' }}"
        >
            {{ __('user-settings.nav_club') }}
        </a>
    </div>

    <!-- Content -->
    <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl p-4 sm:p-6 overflow-hidden">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $heading ?? '' }}</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ $subheading ?? '' }}</p>

        <div class="mt-6">
            {{ $slot }}
        </div>
    </div>
</div>
