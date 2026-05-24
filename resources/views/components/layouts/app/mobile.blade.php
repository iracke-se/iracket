@props(['title' => null, 'bannerLocation' => 'home', 'selectedBannerId' => null, 'selectedBannerPosition' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ request()->cookie('theme') === 'light' ? '' : 'dark' }}">
    <head>
        @include('partials.head')
        @stack('styles')
    </head>
    @php
        $unreadNotificationsCount = auth()->check()
            ? \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count()
            : 0;
    @endphp
    <body class="min-h-screen bg-white dark:bg-zinc-900">
        <!-- Top Bar -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-between px-4 h-14">
                <!-- Back Button -->
                <button onclick="history.back()" class="flex items-center justify-center w-10 h-10 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <!-- Logo and Title -->
                <a href="{{ route('players.index') }}" class="flex items-center gap-2" wire:navigate>
                    <img src="/assets/images/icon.png" alt="iRacket" class="h-7">
                    <span class="font-semibold text-zinc-900 dark:text-white">iRacket</span>
                </a>

                <!-- Profile Dropdown -->
                @auth
                <flux:dropdown position="bottom" align="end">
                    <button class="flex items-center justify-center w-10 h-10 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700 text-sm font-medium text-zinc-900 dark:text-white">
                            {{ auth()->user()->initials() }}
                        </span>
                    </button>

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>

                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        @if(auth()->user()->hasRole(['Admin', 'Manager']))
                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('admin.dashboard')" icon="squares-2x2" wire:navigate>{{ __('Admin Dashboard') }}</flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />
                        @endif

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
                @else
                <a href="{{ route('login') }}" class="flex items-center justify-center w-10 h-10 rounded-lg text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </a>
                @endauth
            </div>
        </header>

        <!-- Top Sticky Banner -->
        @livewire('components.banners.sticky', ['location' => $bannerLocation, 'position' => 'top', 'selectedBannerId' => $selectedBannerId, 'selectedBannerPosition' => $selectedBannerPosition], key('banner-top-sticky-'.$bannerLocation))

        <!-- Bottom Sticky Banner -->
        @livewire('components.banners.sticky', ['location' => $bannerLocation, 'position' => 'bottom', 'selectedBannerId' => $selectedBannerId, 'selectedBannerPosition' => $selectedBannerPosition], key('banner-bottom-sticky-'.$bannerLocation))

        <!-- Main Content -->
        <main class="pt-14 pb-20" style="display: flex; flex-direction: column;">
            <!-- Top Fixed Banner -->
            @livewire('components.banners.fixed', ['location' => $bannerLocation, 'position' => 'top', 'selectedBannerId' => $selectedBannerId, 'selectedBannerPosition' => $selectedBannerPosition], key('banner-top-'.$bannerLocation))

            {{ $slot }}

            <!-- Within Page Banner -->
            @livewire('components.banners.fixed', ['location' => $bannerLocation, 'position' => 'within_page', 'selectedBannerId' => $selectedBannerId, 'selectedBannerPosition' => $selectedBannerPosition], key('banner-within-'.$bannerLocation))

            <!-- Bottom Fixed Banner -->
            @livewire('components.banners.fixed', ['location' => $bannerLocation, 'position' => 'bottom', 'selectedBannerId' => $selectedBannerId, 'selectedBannerPosition' => $selectedBannerPosition], key('banner-bottom-'.$bannerLocation))
        </main>

        <!-- Popup Banner -->
        @livewire('components.banners.popup', ['location' => $bannerLocation, 'selectedBannerId' => $selectedBannerId, 'selectedBannerPosition' => $selectedBannerPosition], key('banner-popup-'.$bannerLocation))

        <!-- Bottom Navigation -->
        <nav class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
            <div class="flex items-center justify-around h-16">
                <!-- Players -->
                <a href="{{ route('players.index') }}" class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 {{ request()->routeIs('players.*') ? 'text-accent' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }} transition-colors" wire:navigate>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="text-xs font-medium">{{ __('nav.players') }}</span>
                </a>

                <!-- Bubbler -->
                <a href="{{ route('bubbler.index') }}" class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 {{ request()->routeIs('bubbler.*') ? 'text-accent' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }} transition-colors" wire:navigate>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <span class="text-xs font-medium">{{ __('nav.bubbler') }}</span>
                </a>

                <!-- My Profile -->
                <a href="{{ auth()->check() ? route('players.show', auth()->user()) : route('login') }}" class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 {{ request()->routeIs('players.show') && request()->route('user')?->id === auth()->id() ? 'text-accent' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }} transition-colors" wire:navigate>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-xs font-medium">{{ __('nav.profile') }}</span>
                </a>

                <!-- My Matches -->
                <a href="{{ route('matches.index') }}" class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 {{ request()->routeIs('matches.*') ? 'text-accent' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }} transition-colors" wire:navigate>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <span class="text-xs font-medium">{{ __('nav.matches') }}</span>
                </a>

                <!-- Information -->
                <a href="{{ route('information') }}" class="flex flex-1 flex-col items-center justify-center gap-1 px-2 py-2 {{ request()->routeIs('information') ? 'text-accent' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }} transition-colors relative" wire:navigate>
                    <div class="relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        @if($unreadNotificationsCount > 0)
                            <span class="absolute -top-1 -right-1 flex items-center justify-center min-w-[16px] h-4 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">
                                {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                            </span>
                        @endif
                    </div>
                    <span class="text-xs font-medium">{{ __('nav.info') }}</span>
                </a>
            </div>
        </nav>

        @fluxScripts
        @stack('scripts')
    </body>
</html>
