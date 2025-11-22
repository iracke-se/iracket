<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ __('Matches') }}</h1>
        <a href="{{ route('admin.matches.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
            {{ __('Create New') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="flex gap-4 mb-6">
        <div class="flex-1">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by player name...') }}"
                class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
        </div>
        <select
            wire:model.live="status"
            class="px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
            <option value="">{{ __('All Statuses') }}</option>
            <option value="pending">{{ __('Pending') }}</option>
            <option value="confirmed">{{ __('Confirmed') }}</option>
            <option value="cancelled">{{ __('Cancelled') }}</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-zinc-800 rounded-xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Players') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Result') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Winner') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-700">
                @forelse($matches as $match)
                    <tr class="hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap text-white">
                            {{ $match->player1?->name ?? 'Unknown' }} vs {{ $match->player2?->name ?? 'Unknown' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">
                            {{ $match->player1_sets }} - {{ $match->player2_sets }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-accent">
                            {{ $match->winner?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">
                            {{ $match->played_at?->format('M d, Y') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($match->status === 'confirmed')
                                <span class="px-2 py-1 text-xs font-medium bg-green-500/10 text-green-400 rounded-full">{{ __('Confirmed') }}</span>
                            @elseif($match->status === 'pending')
                                <span class="px-2 py-1 text-xs font-medium bg-yellow-500/10 text-yellow-400 rounded-full">{{ __('Pending') }}</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-red-500/10 text-red-400 rounded-full">{{ __('Cancelled') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.matches.edit', $match->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>{{ __('Edit') }}</a>
                            <button wire:click="delete({{ $match->id }})" wire:confirm="Are you sure you want to delete this match?" class="text-red-400 hover:text-red-300">{{ __('Delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-zinc-400">{{ __('No matches found.') }}</td>
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
