<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('admin-clubs.clubs') }}</h1>
        <a href="{{ route('admin.clubs.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
            {{ __('admin-clubs.create_new') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-600 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Total Clubs -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-clubs.total_clubs') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $totalClubs }}</p>
                </div>
                <div class="p-3 bg-accent/10 rounded-lg">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Members -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-clubs.total_members') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $totalMembers }}</p>
                </div>
                <div class="p-3 bg-blue-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Clubs -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-clubs.with_members') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $clubsWithMembers }}</p>
                </div>
                <div class="p-3 bg-green-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-clubs.this_month') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $clubsThisMonth }}</p>
                </div>
                <div class="p-3 bg-purple-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Selection Actions -->
    @if(count($selectedClubs) > 0)
        <div class="mb-4 p-4 bg-accent/10 border border-accent/20 rounded-lg flex items-center justify-between">
            <div class="text-accent font-medium">
                {{ count($selectedClubs) }} {{ count($selectedClubs) === 1 ? 'club' : 'clubs' }} selected
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="clearSelection" class="px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white">
                    Clear selection
                </button>
                <button wire:click="sendNotification" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Send Notification to Members
                </button>
            </div>
        </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('admin-clubs.search_clubs') }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
        </div>
        <select
            wire:model.live="hasMembers"
            class="px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            <option value="">{{ __('admin-clubs.all_clubs') }}</option>
            <option value="1">{{ __('admin-clubs.with_members') }}</option>
            <option value="0">{{ __('admin-clubs.no_members') }}</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead class="bg-zinc-100 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" wire:model.live="selectAll" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-clubs.name') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-clubs.location') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-clubs.members') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-clubs.created') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-clubs.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($clubs as $club)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 {{ in_array($club->id, $selectedClubs) ? 'bg-accent/5' : '' }}">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <input type="checkbox" wire:model.live="selectedClubs" value="{{ $club->id }}" class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($club->logo)
                                    <img src="{{ Storage::url($club->logo) }}" alt="{{ $club->name }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ Str::substr($club->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <span class="text-zinc-900 dark:text-white">{{ $club->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ $club->location ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ $club->members_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ $club->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('clubs.show', $club) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 mr-3" wire:navigate>{{ __('admin-clubs.view') }}</a>
                            <a href="{{ route('admin.clubs.edit', $club->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>{{ __('admin-clubs.edit') }}</a>
                            <button wire:click="delete({{ $club->id }})" wire:confirm="{{ __('admin-clubs.confirm_delete') }}" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300">{{ __('admin-clubs.delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('admin-clubs.no_clubs_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $clubs->links() }}
    </div>
</div>
