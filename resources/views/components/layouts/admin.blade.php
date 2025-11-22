<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @stack('styles')
    </head>
    <body class="min-h-screen bg-zinc-900">
        <div class="flex">
            <!-- Sidebar -->
            <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-zinc-800 border-r border-zinc-700">
                <!-- Logo -->
                <div class="flex items-center gap-2 px-6 h-16 border-b border-zinc-700">
                    <img src="/assets/images/icon.png" alt="iRacket" class="h-8">
                    <span class="font-semibold text-white">iRacket Admin</span>
                </div>

                <!-- Navigation -->
                <nav class="p-4 space-y-1">
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-accent text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700' }} transition-colors" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        {{ __('Dashboard') }}
                    </a>

                    <a href="{{ route('admin.terms.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('admin.terms.*') ? 'bg-accent text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700' }} transition-colors" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ __('Terms') }}
                    </a>

                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-accent text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700' }} transition-colors" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        {{ __('Users') }}
                    </a>

                    <a href="{{ route('admin.clubs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('admin.clubs.*') ? 'bg-accent text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700' }} transition-colors" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        {{ __('Clubs') }}
                    </a>

                    <a href="{{ route('admin.matches.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('admin.matches.*') ? 'bg-accent text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700' }} transition-colors" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        {{ __('Matches') }}
                    </a>

                    <a href="{{ route('admin.staff.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs('admin.staff.*') ? 'bg-accent text-white' : 'text-zinc-400 hover:text-white hover:bg-zinc-700' }} transition-colors" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        {{ __('Staff') }}
                    </a>
                </nav>

                <!-- Bottom Actions -->
                <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-zinc-700">
                    <a href="{{ route('players.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-700 transition-colors" wire:navigate>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        {{ __('Back to App') }}
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="ml-64 flex-1 min-h-screen">
                <!-- Top Bar -->
                <header class="sticky top-0 z-40 bg-zinc-900 border-b border-zinc-800">
                    <div class="flex items-center justify-end px-6 h-16">
                        <flux:dropdown position="bottom" align="end">
                            <button class="flex items-center gap-2 text-zinc-400 hover:text-white transition-colors">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-700 text-sm font-medium text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                                <span class="text-sm">{{ auth()->user()->name }}</span>
                            </button>

                            <flux:menu class="w-[200px]">
                                <flux:menu.item :href="route('players.index')" icon="arrow-left-end-on-rectangle" wire:navigate>{{ __('User Dashboard') }}</flux:menu.item>
                                <flux:menu.separator />
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                                <flux:menu.separator />
                                <form method="POST" action="{{ route('logout') }}" class="w-full">
                                    @csrf
                                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                        {{ __('Log Out') }}
                                    </flux:menu.item>
                                </form>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </header>

                <!-- Page Content -->
                <div class="p-6">
                    {{ $slot }}
                </div>
            </main>
        </div>

        @fluxScripts
        @stack('scripts')
    </body>
</html>
