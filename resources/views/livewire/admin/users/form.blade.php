<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ $user ? __('Edit User') : __('Create User') }}</h1>
        <a href="{{ route('admin.users.index') }}" class="text-zinc-400 hover:text-white" wire:navigate>
            {{ __('Back to list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Name Fields -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('First Name') }}</label>
                <input
                    type="text"
                    id="first_name"
                    wire:model="first_name"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('first_name')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Last Name') }}</label>
                <input
                    type="text"
                    id="last_name"
                    wire:model="last_name"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('last_name')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Email -->
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

        <!-- Phone and Gender -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="phone_number" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Phone Number') }}</label>
                <input
                    type="tel"
                    id="phone_number"
                    wire:model="phone_number"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('phone_number')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="gender" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Gender') }}</label>
                <select
                    id="gender"
                    wire:model="gender"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    <option value="">{{ __('Select...') }}</option>
                    <option value="male">{{ __('Male') }}</option>
                    <option value="female">{{ __('Female') }}</option>
                    <option value="other">{{ __('Other') }}</option>
                    <option value="prefer_not_to_say">{{ __('Prefer not to say') }}</option>
                </select>
                @error('gender')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Age -->
        <div>
            <label for="age" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Age') }}</label>
            <input
                type="number"
                id="age"
                wire:model="age"
                min="1"
                max="120"
                class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('age')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-sm font-medium text-zinc-300 mb-2">
                    {{ __('Password') }}
                    @if($user)
                        <span class="text-zinc-500">({{ __('leave blank to keep current') }})</span>
                    @endif
                </label>
                <input
                    type="password"
                    id="password"
                    wire:model="password"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                @error('password')
                    <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Confirm Password') }}</label>
                <input
                    type="password"
                    id="password_confirmation"
                    wire:model="password_confirmation"
                    class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
            </div>
        </div>

        <!-- Roles -->
        <div>
            <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Roles') }}</label>
            <div class="flex flex-wrap gap-3">
                @foreach($roles as $role)
                    <label class="flex items-center gap-2 px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-lg cursor-pointer hover:border-zinc-600">
                        <input
                            type="checkbox"
                            wire:model="selectedRoles"
                            value="{{ $role->name }}"
                            class="w-4 h-4 rounded bg-zinc-700 border-zinc-600 text-accent focus:ring-accent focus:ring-offset-zinc-900"
                        >
                        <span class="text-sm text-zinc-300">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Visible in Players -->
        <div class="flex items-center gap-3">
            <input
                type="checkbox"
                id="visible_in_players"
                wire:model="visible_in_players"
                class="w-5 h-5 rounded bg-zinc-700 border-zinc-600 text-accent focus:ring-accent focus:ring-offset-zinc-900"
            >
            <label for="visible_in_players" class="text-sm font-medium text-zinc-300">{{ __('Visible in Players List') }}</label>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $user ? __('Update') : __('Create') }}
            </button>
            <a href="{{ route('admin.users.index') }}" class="px-6 py-3 bg-zinc-700 text-zinc-300 font-medium rounded-lg hover:bg-zinc-600 transition-colors" wire:navigate>
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>
