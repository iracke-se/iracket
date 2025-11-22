<div class="max-w-2xl mx-auto">
    <!-- Club Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 mb-6 border border-zinc-200 dark:border-transparent">
        <div class="flex items-start gap-4">
            @if($club->logo)
                <img src="{{ Storage::url($club->logo) }}" alt="{{ $club->name }}" class="w-16 h-16 rounded-lg object-cover">
            @else
                <div class="w-16 h-16 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            @endif
            <div class="flex-1">
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $club->name }}</h1>
                @if($club->location)
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 flex items-center gap-1 mt-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $club->location }}
                    </p>
                @endif
            </div>
        </div>

        @if($club->description)
            <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">{{ $club->description }}</p>
        @endif

        <!-- Contact Info -->
        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @if($club->email)
                <a href="mailto:{{ $club->email }}" class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 hover:text-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ $club->email }}
                </a>
            @endif
            @if($club->phone)
                <a href="tel:{{ $club->phone }}" class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 hover:text-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    {{ $club->phone }}
                </a>
            @endif
            @if($club->website)
                <a href="{{ $club->website }}" target="_blank" class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 hover:text-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                    </svg>
                    {{ __('user-club-show.website') }}
                </a>
            @endif
        </div>
    </div>

    <!-- Members -->
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-club-show.members') }} ({{ $members->count() }})</h2>

        @if($members->isEmpty())
            <div class="text-center py-8 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-transparent">
                <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-club-show.no_members_yet') }}</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach($members as $member)
                    <a
                        href="{{ route('players.show', $member) }}"
                        wire:navigate
                        class="flex items-center gap-3 p-3 bg-white dark:bg-zinc-800 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors border border-zinc-200 dark:border-transparent"
                    >
                        @if($member->profile_picture)
                            <img src="{{ Storage::url($member->profile_picture) }}" alt="{{ $member->name }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $member->initials() }}</span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-zinc-900 dark:text-white font-medium truncate">{{ $member->name }}</p>
                            @if($member->current_ranking)
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">#{{ $member->current_ranking->rank }} • {{ $member->current_ranking->points }} pts</p>
                            @endif
                        </div>
                        <svg class="w-5 h-5 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Club Rankings History -->
    @if($clubRankings->isNotEmpty())
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-club-show.club_rankings') }}</h2>
            <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-200 dark:border-transparent">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="px-4 py-3 text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-club-show.month') }}</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-club-show.rank') }}</th>
                            <th class="px-4 py-3 text-right text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('user-club-show.points') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clubRankings as $ranking)
                            <tr class="border-b border-zinc-200 dark:border-zinc-700 last:border-0">
                                <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ $ranking->formatted_date }}</td>
                                <td class="px-4 py-3 text-sm text-center text-zinc-900 dark:text-white">#{{ $ranking->rank }}</td>
                                <td class="px-4 py-3 text-sm text-right text-zinc-900 dark:text-white">{{ number_format($ranking->total_points) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
