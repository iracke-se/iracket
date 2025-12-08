<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $banner ? 'Edit Banner' : 'Create Banner' }}</h1>
        <a href="{{ route('admin.banners.index') }}" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
            Back to list
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Image -->
        <div>
            <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">Banner Image</label>
            <div class="flex items-start gap-4">
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="h-24 w-auto rounded object-cover">
                @elseif ($banner && $banner->image)
                    <img src="{{ $banner->image_url }}" alt="Banner" class="h-24 w-auto rounded object-cover">
                @else
                    <div class="h-24 w-40 rounded bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                        <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                @endif
                <div class="flex flex-col gap-2">
                    <input
                        type="file"
                        wire:model="image"
                        accept="image/*"
                        class="text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-200 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-300 dark:hover:file:bg-zinc-600 file:cursor-pointer"
                    >
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Max file size: 5MB. Recommended: 728x90px for horizontal banners.</p>
                    @if ($current_image)
                        <button type="button" wire:click="deleteImage" class="text-sm text-red-500 dark:text-red-400 hover:text-red-600 dark:hover:text-red-300 text-left">
                            Remove image
                        </button>
                    @endif
                </div>
            </div>
            @error('image')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">Name</label>
            <input
                type="text"
                id="name"
                wire:model="name"
                placeholder="Banner name for internal reference"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('name')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Position and Status -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="position" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">Position</label>
                <select
                    id="position"
                    wire:model="position"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    @foreach($positions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('position')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">Status</label>
                <select
                    id="status"
                    wire:model="status"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Link -->
        <div>
            <label for="link" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">Link URL</label>
            <input
                type="url"
                id="link"
                wire:model="link"
                placeholder="https://example.com"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('link')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Locations -->
        <div>
            <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">Display Locations</label>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Select where this banner should appear. Leave empty to show on all pages.</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach($availableLocations as $value => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            wire:model="locations"
                            value="{{ $value }}"
                            class="rounded border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent"
                        >
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('locations')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Date Range -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">Start Date</label>
                <input
                    type="date"
                    id="start_date"
                    wire:model="start_date"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('start_date')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">End Date</label>
                <input
                    type="date"
                    id="end_date"
                    wire:model="end_date"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('end_date')
                    <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $banner ? 'Update' : 'Create' }}
            </button>
            <a href="{{ route('admin.banners.index') }}" class="px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors" wire:navigate>
                Cancel
            </a>
        </div>
    </form>
</div>
