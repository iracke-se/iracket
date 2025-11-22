<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ __('Terms & Policies') }}</h1>
        <a href="{{ route('admin.terms.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
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
            placeholder="{{ __('Search terms...') }}"
            class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
    </div>

    <!-- Table -->
    <div class="bg-zinc-800 rounded-xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Title') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Slug') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Created') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-700">
                @forelse($terms as $term)
                    <tr class="hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap text-white">{{ $term->title }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $term->slug }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($term->is_active)
                                <span class="px-2 py-1 text-xs font-medium bg-green-500/10 text-green-400 rounded-full">{{ __('Active') }}</span>
                            @else
                                <span class="px-2 py-1 text-xs font-medium bg-zinc-500/10 text-zinc-400 rounded-full">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $term->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.terms.edit', $term->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>{{ __('Edit') }}</a>
                            <button wire:click="delete({{ $term->id }})" wire:confirm="Are you sure you want to delete this term?" class="text-red-400 hover:text-red-300">{{ __('Delete') }}</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-zinc-400">{{ __('No terms found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $terms->links() }}
    </div>
</div>
