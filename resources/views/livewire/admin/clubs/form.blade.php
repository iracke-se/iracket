<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ $club ? __('Edit Club') : __('Create Club') }}</h1>
        <a href="{{ route('admin.clubs.index') }}" class="text-zinc-400 hover:text-white" wire:navigate>
            {{ __('Back to list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Logo -->
        <div>
            <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Logo') }}</label>
            <div class="flex items-center gap-4">
                @if ($logo)
                    <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="h-16 w-16 rounded-full object-cover">
                @elseif ($current_logo)
                    <img src="{{ Storage::url($current_logo) }}" alt="Logo" class="h-16 w-16 rounded-full object-cover">
                @else
                    <div class="h-16 w-16 rounded-full bg-zinc-700 flex items-center justify-center">
                        <span class="text-xl font-medium text-zinc-300">{{ Str::substr($name, 0, 1) ?: '?' }}</span>
                    </div>
                @endif
                <div class="flex flex-col gap-2">
                    <input
                        type="file"
                        wire:model="logo"
                        accept="image/*"
                        class="text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-700 file:text-zinc-300 hover:file:bg-zinc-600 file:cursor-pointer"
                    >
                    @if ($current_logo)
                        <button type="button" wire:click="deleteLogo" class="text-sm text-red-400 hover:text-red-300 text-left">
                            {{ __('Remove logo') }}
                        </button>
                    @endif
                </div>
            </div>
            @error('logo')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Name and Slug -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="name" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Name') }}</label>
                <input
                    type="text"
                    id="name"
                    wire:model.live="name"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="slug" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Slug') }}</label>
                <input
                    type="text"
                    id="slug"
                    wire:model="slug"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('slug')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Description') }}</label>
            <textarea
                id="description"
                wire:model="description"
                rows="4"
                class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            ></textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Location -->
        <div>
            <label for="location" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Location') }}</label>
            <input
                type="text"
                id="location"
                wire:model="location"
                class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('location')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Contact Info -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="website" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Website') }}</label>
                <input
                    type="url"
                    id="website"
                    wire:model="website"
                    placeholder="https://"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('website')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Email') }}</label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('email')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Phone') }}</label>
                <input
                    type="tel"
                    id="phone"
                    wire:model="phone"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('phone')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $club ? __('Update') : __('Create') }}
            </button>
            <a href="{{ route('admin.clubs.index') }}" class="px-6 py-3 bg-zinc-700 text-zinc-300 font-medium rounded-lg hover:bg-zinc-600 transition-colors" wire:navigate>
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>
