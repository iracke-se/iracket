<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.contacts.index') }}" class="p-2 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('admin-contacts.respond_to_contact') }}</h1>
        </div>
        @if($contact->status === 'replied')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                {{ __('admin-contacts.replied') }}
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400">
                {{ __('admin-contacts.pending') }}
            </span>
        @endif
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Contact Details -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-6">
        <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('admin-contacts.contact_details') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-contacts.name') }}</label>
                    <p class="text-zinc-900 dark:text-white mt-1">{{ $contact->name }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-contacts.email') }}</label>
                    <p class="text-zinc-900 dark:text-white mt-1">
                        <a href="mailto:{{ $contact->email }}" class="text-accent hover:text-accent/80">{{ $contact->email }}</a>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-contacts.submitted') }}</label>
                    <p class="text-zinc-900 dark:text-white mt-1">{{ $contact->created_at->format('d M Y, H:i') }}</p>
                </div>
                @if($contact->replied_at)
                    <div>
                        <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-contacts.replied_at') }}</label>
                        <p class="text-zinc-900 dark:text-white mt-1">{{ $contact->replied_at->format('d M Y, H:i') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="p-6">
            <label class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('admin-contacts.original_message') }}</label>
            <div class="mt-2 p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                <p class="text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $contact->message }}</p>
            </div>
        </div>
    </div>

    <!-- Reply Form -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
                {{ $contact->status === 'replied' ? __('admin-contacts.your_reply') : __('admin-contacts.write_reply') }}
            </h2>

            <form wire:submit="sendReply">
                <div class="mb-4">
                    <textarea
                        wire:model="replyMessage"
                        rows="6"
                        placeholder="{{ __('admin-contacts.reply_placeholder') }}"
                        class="w-full px-4 py-3 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent resize-none"
                        {{ $contact->status === 'replied' ? 'readonly' : '' }}
                    ></textarea>
                    @error('replyMessage')
                        <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                @if($contact->status !== 'replied')
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.contacts.index') }}" class="px-4 py-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
                            {{ __('admin-contacts.cancel') }}
                        </a>
                        <button type="submit" class="px-6 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            {{ __('admin-contacts.send_reply') }}
                        </button>
                    </div>
                @else
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('admin-contacts.replied_by') }}: {{ $contact->repliedBy?->name ?? 'Unknown' }}
                        </p>
                        <button
                            type="submit"
                            class="px-6 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors flex items-center gap-2"
                            onclick="this.form.querySelector('textarea').removeAttribute('readonly')"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            {{ __('admin-contacts.resend_reply') }}
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
