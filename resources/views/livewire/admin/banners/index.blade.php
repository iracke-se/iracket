<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Banners</h1>
        <a href="{{ route('admin.banners.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
            Create New
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <!-- Total Banners -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Banners</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $totalBanners }}</p>
                </div>
                <div class="p-3 bg-accent/10 rounded-lg">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Banners -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Active</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $activeBanners }}</p>
                </div>
                <div class="p-3 bg-green-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Views -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Views</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ number_format($totalViews) }}</p>
                </div>
                <div class="p-3 bg-blue-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Clicks -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total Clicks</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ number_format($totalClicks) }}</p>
                </div>
                <div class="p-3 bg-purple-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- CTR -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Avg CTR</p>
                    <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">{{ $avgCtr }}%</p>
                </div>
                <div class="p-3 bg-orange-500/10 rounded-lg">
                    <svg class="w-5 h-5 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    @if($totalBanners > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Performance Chart -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-4">Performance Overview</h3>
            <div class="flex items-end justify-center gap-8 h-32">
                <div class="flex flex-col items-center">
                    <div class="bg-blue-500 rounded-t w-16" style="height: {{ $totalViews > 0 ? min(100, 100) : 0 }}px;"></div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">Views</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ number_format($totalViews) }}</p>
                </div>
                <div class="flex flex-col items-center">
                    <div class="bg-purple-500 rounded-t w-16" style="height: {{ $totalViews > 0 ? min(100, ($totalClicks / max($totalViews, 1)) * 100) : 0 }}px;"></div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2">Clicks</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ number_format($totalClicks) }}</p>
                </div>
            </div>
        </div>

        <!-- Position Distribution -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
            <h3 class="text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-4">Position Distribution</h3>
            <div class="space-y-2">
                @foreach($positionData as $position => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $positions[$position] ?? $position }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-24 bg-zinc-200 dark:bg-zinc-700 rounded-full h-2">
                                <div class="bg-accent h-2 rounded-full" style="width: {{ $totalBanners > 0 ? ($count / $totalBanners) * 100 : 0 }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-zinc-900 dark:text-white w-6 text-right">{{ $count }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search banners..."
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
        </div>
        <select
            wire:model.live="status"
            class="px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            <option value="">All Statuses</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
        <select
            wire:model.live="position"
            class="px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            <option value="">All Positions</option>
            @foreach($positions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 overflow-x-auto">
        <table class="w-full min-w-[900px]">
            <thead class="bg-zinc-100 dark:bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Banner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Views</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Clicks</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">CTR</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($banners as $banner)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($banner->image)
                                    <img src="{{ $banner->image_url }}" alt="{{ $banner->name }}" class="w-12 h-8 rounded object-cover">
                                @else
                                    <div class="w-12 h-8 rounded bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <span class="text-zinc-900 dark:text-white font-medium">{{ $banner->name }}</span>
                                    @if($banner->link)
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-[200px]">{{ $banner->link }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                                {{ $positions[$banner->position] ?? $banner->position }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ number_format($banner->views) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ number_format($banner->clicks) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ $banner->click_through_rate }}%</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="toggleStatus({{ $banner->id }})" class="px-2 py-1 text-xs rounded-full {{ $banner->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : ($banner->status === 'scheduled' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400') }}">
                                {{ $statuses[$banner->status] ?? $banner->status }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.banners.edit', $banner->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>Edit</a>
                            <button wire:click="delete({{ $banner->id }})" wire:confirm="Are you sure you want to delete this banner?" class="text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-zinc-500 dark:text-zinc-400">No banners found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $banners->links() }}
    </div>
</div>
