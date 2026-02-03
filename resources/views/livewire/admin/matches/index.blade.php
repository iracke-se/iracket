<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('admin-matches.matches') }}</h1>
        <a href="{{ route('admin.matches.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
            {{ __('admin-matches.create_new') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Total Matches -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-matches.total_matches') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $totalMatches }}</p>
                </div>
                <div class="p-3 bg-accent/10 rounded-lg">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Confirmed -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-matches.confirmed') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $confirmedMatches }}</p>
                </div>
                <div class="p-3 bg-green-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-matches.pending') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $pendingMatches }}</p>
                </div>
                <div class="p-3 bg-yellow-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-500 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-matches.this_month') }}</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $matchesThisMonth }}</p>
                </div>
                <div class="p-3 bg-purple-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('admin-matches.search_by_player_name') }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
        </div>
        <select
            wire:model.live="status"
            class="px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            <option value="">{{ __('admin-matches.all_statuses') }}</option>
            <option value="pending">{{ __('admin-matches.pending') }}</option>
            <option value="confirmed">{{ __('admin-matches.confirmed') }}</option>
            <option value="cancelled">{{ __('admin-matches.cancelled') }}</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
        <table class="w-full min-w-[800px]">
            <thead class="bg-zinc-100 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-matches.players') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-matches.result') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-matches.winner') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Source</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-matches.date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-matches.status') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">{{ __('admin-matches.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($matches as $match)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-white">
                            <div class="flex items-center gap-2">
                                <span class="{{ $match->winner_id === $match->player1_id ? 'font-bold' : '' }}">
                                    {{ $match->player1?->name ?? 'Unknown' }}
                                </span>
                                <span class="text-zinc-400">vs</span>
                                <span class="{{ $match->winner_id === $match->player2_id ? 'font-bold' : '' }}">
                                    {{ $match->player2?->name ?? 'Unknown' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($match->player1_sets > 0 || $match->player2_sets > 0)
                                <span class="text-zinc-900 dark:text-white font-medium">{{ $match->player1_sets }} | {{ $match->player2_sets }}</span>
                            @elseif($match->scrapedMatches->isNotEmpty())
                                @php
                                    $player1Name = $match->player1->last_name . ', ' . $match->player1->first_name;
                                    $player2Name = $match->player2->last_name . ', ' . $match->player2->first_name;

                                    $player1Points = null;
                                    $player2Points = null;

                                    // Find data for both players from ALL scraped matches
                                    foreach ($match->scrapedMatches as $sm) {
                                        if ($sm->player_name === $player1Name) {
                                            $player1Points = $sm->match_points;
                                        }
                                        if ($sm->player_name === $player2Name) {
                                            $player2Points = $sm->match_points;
                                        }
                                    }

                                    // If we only have one player's points, calculate the other (inverse)
                                    if ($player1Points !== null && $player2Points === null) {
                                        $player2Points = -$player1Points;
                                    } elseif ($player2Points !== null && $player1Points === null) {
                                        $player1Points = -$player2Points;
                                    }
                                @endphp
                                <div class="flex items-center gap-2">
                                    @if($player1Points !== null && $player2Points !== null)
                                        <span class="px-3 py-1 rounded-md text-sm font-bold {{ $player1Points > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                                            {{ $player1Points > 0 ? 'W' : 'L' }}
                                        </span>
                                        <span class="px-3 py-1 rounded-md text-sm font-bold {{ $player2Points > 0 ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 'bg-red-500/20 text-red-700 dark:text-red-400' }}">
                                            {{ $player2Points > 0 ? 'W' : 'L' }}
                                        </span>
                                    @endif
                                </div>
                            @elseif($match->winner_id)
                                @php
                                    $player1Score = $match->winner_id === $match->player1_id ? 2 : 0;
                                    $player2Score = $match->winner_id === $match->player2_id ? 2 : 0;
                                @endphp
                                <span class="text-zinc-900 dark:text-white font-medium">{{ $player1Score }} | {{ $player2Score }}</span>
                            @else
                                <span class="text-zinc-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-accent font-medium">
                            {{ $match->winner?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($match->source === 'scraped')
                                <span class="px-2 py-1 text-xs font-medium bg-blue-500/10 text-blue-600 dark:text-blue-400 rounded-full">Official</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-zinc-500/10 text-zinc-600 dark:text-zinc-400 rounded-full">Manual</span>
                            @endif
                            @if($match->is_unofficial)
                                <span class="px-2 py-1 text-xs font-medium bg-orange-500/10 text-orange-600 dark:text-orange-400 rounded-full ml-1">Unofficial</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                            {{ $match->played_at?->format('M d, Y') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($match->status === 'confirmed')
                                <span class="px-2 py-1 text-xs font-medium bg-green-500/10 text-green-600 dark:text-green-400 rounded-full">{{ __('admin-matches.confirmed') }}</span>
                            @elseif($match->status === 'pending')
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-500/10 text-yellow-600 dark:text-yellow-400 rounded-full">{{ __('admin-matches.pending') }}</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-red-500/10 text-red-600 dark:text-red-400 rounded-full">{{ __('admin-matches.cancelled') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('matches.show', $match) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 mr-3" wire:navigate>{{ __('admin-matches.view') }}</a>
                            <a href="{{ route('admin.matches.edit', $match->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>{{ __('admin-matches.edit') }}</a>
                            <button wire:click="delete({{ $match->id }})" wire:confirm="{{ __('admin-matches.confirm_delete') }}" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300">{{ __('admin-matches.delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">{{ __('admin-matches.no_matches_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $matches->links() }}
    </div>
</div>
