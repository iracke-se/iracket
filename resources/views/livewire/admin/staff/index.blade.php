<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ __('Staff') }}</h1>
        <a href="{{ route('admin.staff.create') }}" class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors" wire:navigate>
            {{ __('Create New') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <!-- Search -->
    <div class="mb-6">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('Search staff...') }}"
            class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
        >
    </div>

    <!-- Table -->
    <div class="bg-zinc-800 rounded-xl overflow-hidden">
        <table class="w-full">
            <thead class="bg-zinc-700/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Name') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Email') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Roles') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Show in Players') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-300 uppercase tracking-wider">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-700">
                @forelse($staff as $member)
                    <tr class="hover:bg-zinc-700/30">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-zinc-700 flex items-center justify-center">
                                    <span class="text-sm font-medium text-zinc-300">{{ $member->initials() }}</span>
                                </div>
                                <span class="text-white">{{ $member->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $member->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-1">
                                @foreach($member->roles as $role)
                                    <span class="px-2 py-1 text-xs font-medium bg-accent/10 text-accent rounded-full">{{ $role->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                wire:click="toggleVisibility({{ $member->id }})"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $member->visible_in_players ? 'bg-accent' : 'bg-zinc-600' }}"
                            >
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform {{ $member->visible_in_players ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <a href="{{ route('admin.staff.edit', $member->id) }}" class="text-accent hover:text-accent/80 mr-3" wire:navigate>{{ __('Edit') }}</a>
                            @if($member->id !== auth()->id())
                                <button wire:click="delete({{ $member->id }})" wire:confirm="Are you sure you want to delete this staff member?" class="text-red-400 hover:text-red-300">{{ __('Delete') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-zinc-400">{{ __('No staff members found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $staff->links() }}
    </div>
</div>
