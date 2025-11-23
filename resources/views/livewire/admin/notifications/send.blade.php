<div class="max-w-2xl mx-auto py-6 px-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Send Notification</h1>
        <p class="text-zinc-500 dark:text-zinc-400 mt-1">Send a notification to {{ $users->count() }} selected users</p>
    </div>

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-600 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <!-- Recipients Info -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700 mb-6">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Recipients</h3>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $usersWithToken }} with FCM token</span>
            </div>
            @if($usersWithoutToken > 0)
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $usersWithoutToken }} without token</span>
                </div>
            @endif
        </div>
        @if($usersWithoutToken > 0)
            <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-2">Users without FCM tokens will receive the notification in-app only (no push notification).</p>
        @endif
    </div>

    <!-- Notification Form -->
    <form wire:submit="send" class="bg-white dark:bg-zinc-800 rounded-xl p-6 border border-zinc-200 dark:border-zinc-700">
        <div class="space-y-4">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Title</label>
                <input
                    type="text"
                    id="title"
                    wire:model="title"
                    placeholder="Notification title"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                    maxlength="100"
                >
                @error('title')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Body -->
            <div>
                <label for="body" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Message</label>
                <textarea
                    id="body"
                    wire:model="body"
                    placeholder="Notification message"
                    rows="4"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent resize-none"
                    maxlength="500"
                ></textarea>
                @error('body')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- URL (optional) -->
            <div>
                <label for="url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    URL <span class="text-zinc-400 font-normal">(optional)</span>
                </label>
                <input
                    type="text"
                    id="url"
                    wire:model="url"
                    placeholder="https://example.com/page"
                    class="w-full px-4 py-3 bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">Users will be redirected to this URL when tapping the notification</p>
                @error('url')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Icon -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                    Notification Icon
                </label>

                <!-- Default Icon Toggle -->
                <div class="flex items-center gap-2 mb-3">
                    <input
                        type="checkbox"
                        id="useDefaultIcon"
                        wire:model.live="useDefaultIcon"
                        class="w-4 h-4 text-accent bg-white dark:bg-zinc-900 border-zinc-300 dark:border-zinc-700 rounded focus:ring-accent"
                    >
                    <label for="useDefaultIcon" class="text-sm text-zinc-600 dark:text-zinc-400">
                        Use default brand logo
                    </label>
                </div>

                <!-- Icon Preview -->
                <div class="flex items-center gap-4 mb-3">
                    @if($useDefaultIcon)
                        <div class="w-12 h-12 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center overflow-hidden">
                            <img src="{{ asset('assets/images/icon.png') }}" alt="Default icon" class="w-10 h-10 object-contain">
                        </div>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Default brand logo</span>
                    @elseif($icon)
                        <div class="w-12 h-12 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center overflow-hidden">
                            <img src="{{ $icon->temporaryUrl() }}" alt="Custom icon" class="w-10 h-10 object-contain">
                        </div>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $icon->getClientOriginalName() }}</span>
                    @else
                        <div class="w-12 h-12 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                            <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">No icon selected</span>
                    @endif
                </div>

                <!-- Custom Icon Upload -->
                @if(!$useDefaultIcon)
                    <div class="relative">
                        <input
                            type="file"
                            id="icon"
                            wire:model="icon"
                            accept="image/*"
                            class="w-full text-sm text-zinc-500 dark:text-zinc-400
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-lg file:border-0
                                file:text-sm file:font-medium
                                file:bg-zinc-100 file:dark:bg-zinc-700
                                file:text-zinc-700 file:dark:text-zinc-300
                                hover:file:bg-zinc-200 dark:hover:file:bg-zinc-600
                                cursor-pointer"
                        >
                        <div wire:loading wire:target="icon" class="absolute right-2 top-1/2 -translate-y-1/2">
                            <svg class="w-4 h-4 animate-spin text-accent" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">Maximum file size: 1MB. Supported formats: JPG, PNG, GIF</p>
                @endif

                @error('icon')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
            <button
                type="button"
                wire:click="cancel"
                class="px-4 py-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-colors"
            >
                Cancel
            </button>
            <button
                type="submit"
                class="px-6 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove wire:target="send">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </span>
                <span wire:loading wire:target="send">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
                <span wire:loading.remove wire:target="send">Send Notification</span>
                <span wire:loading wire:target="send">Sending...</span>
            </button>
        </div>
    </form>

    <!-- Selected Users Preview -->
    <div class="mt-6 bg-white dark:bg-zinc-800 rounded-xl p-4 border border-zinc-200 dark:border-zinc-700">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">Selected Users ({{ $users->count() }})</h3>
        <div class="max-h-48 overflow-y-auto space-y-2">
            @foreach($users as $user)
                <div class="flex items-center justify-between py-1">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $user->initials() }}</span>
                        </div>
                        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $user->name }}</span>
                    </div>
                    @if($user->fcm_token)
                        <span class="text-xs text-green-500">Has token</span>
                    @else
                        <span class="text-xs text-zinc-400">No token</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
