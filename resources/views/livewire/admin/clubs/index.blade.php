<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ __('Clubs') }}</h1>
        <a href="{{ route('admin.clubs.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
            {{ __('Create New') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Search -->
    <div class="mb-6">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Search clubs...') }}"
            class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
    </div>

    <!-- Table -->
    <div class="bg-zinc-800 rounded-xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Name') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Location') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Members') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Created') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-700">
                @forelse($clubs as $club)
                    <tr class="hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                @if($club->logo)
                                    <img src="{{ Storage::url($club->logo) }}" alt="{{ $club->name }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-zinc-700 flex items-center justify-center">
                                        <span class="text-sm font-medium text-zinc-300">{{ Str::substr($club->name, 0, 1) }}</span>
                                    </div>
                                @endif
                                <span class="text-white">{{ $club->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $club->location ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $club->members_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $club->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.clubs.edit', $club->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>{{ __('Edit') }}</a>
                            <button wire:click="delete({{ $club->id }})" wire:confirm="Are you sure you want to delete this club?" class="text-red-400 hover:text-red-300">{{ __('Delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-zinc-400">{{ __('No clubs found.') }}</td>
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
