<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('Notifications') }}</h1>
            @if($unreadCount > 0)
                <p class="text-sm text-zinc-400">{{ $unreadCount }} {{ __('unread') }}</p>
            @endif
        </div>

        @if($unreadCount > 0)
            <button
                wire:click="markAllAsRead"
                class="text-sm text-accent hover:underline"
            >
                {{ __('Mark all as read') }}
            </button>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="text-center py-12">
            <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-zinc-800">
                <svg class="w-8 h-8 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <p class="text-zinc-400">{{ __('No notifications yet') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($notifications as $notification)
                <div
                    wire:click="markAsRead({{ $notification->id }})"
                    class="relative p-4 rounded-xl cursor-pointer transition-colors {{ $notification->isRead() ? 'bg-zinc-800/50' : 'bg-zinc-800' }}"
                >
                    @if(!$notification->isRead())
                        <div class="absolute top-4 right-4 w-2 h-2 rounded-full bg-accent"></div>
                    @endif

                    <div class="flex gap-3">
                        <!-- Icon based on type -->
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-10 h-10 rounded-lg {{ $notification->isRead() ? 'bg-zinc-700/50' : 'bg-zinc-700' }}">
                                @switch($notification->type)
                                    @case('match_result')
                                        <svg class="w-5 h-5 {{ $notification->isRead() ? 'text-zinc-500' : 'text-zinc-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                        </svg>
                                        @break
                                    @case('ranking_update')
                                        <svg class="w-5 h-5 {{ $notification->isRead() ? 'text-zinc-500' : 'text-zinc-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                        </svg>
                                        @break
                                    @case('achievement')
                                        <svg class="w-5 h-5 {{ $notification->isRead() ? 'text-zinc-500' : 'text-yellow-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                                        </svg>
                                        @break
                                    @case('bubbler')
                                        <svg class="w-5 h-5 {{ $notification->isRead() ? 'text-zinc-500' : 'text-accent' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                        @break
                                    @case('follow')
                                        <svg class="w-5 h-5 {{ $notification->isRead() ? 'text-zinc-500' : 'text-blue-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                        @break
                                    @default
                                        <svg class="w-5 h-5 {{ $notification->isRead() ? 'text-zinc-500' : 'text-zinc-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                        </svg>
                                @endswitch
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <p class="font-medium {{ $notification->isRead() ? 'text-zinc-400' : 'text-white' }}">
                                {{ $notification->title }}
                            </p>
                            <p class="mt-1 text-sm {{ $notification->isRead() ? 'text-zinc-500' : 'text-zinc-400' }}">
                                {{ $notification->message }}
                            </p>
                            <p class="mt-2 text-xs text-zinc-500">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
