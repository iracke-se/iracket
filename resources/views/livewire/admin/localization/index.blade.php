<div class="max-w-6xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Localization') }}</h1>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <!-- Info Card -->
    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">{{ __('About Translations') }}</p>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    {{ __('Manage translation keys for the application interface. Select a translation file to edit its keys in both English and Swedish.') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Available Languages -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 mb-6">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Available Languages') }}</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-zinc-200 dark:divide-zinc-700">
            @foreach($availableLocales as $code => $name)
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                            <span class="text-sm font-bold text-zinc-700 dark:text-white uppercase">{{ $code }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Language code:') }} {{ $code }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($code === $defaultLocale)
                            <span class="px-2 py-1 text-xs font-medium bg-accent/10 text-accent rounded-full">{{ __('Default') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Translation Editor -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Translation Keys') }}</h2>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                <select
                    wire:model.live="selectedFile"
                    class="px-3 py-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                >
                    @foreach($translationFiles as $file)
                        <option value="{{ $file }}">{{ ucfirst($file) }}</option>
                    @endforeach
                </select>
                <button
                    wire:click="save"
                    class="px-4 py-2 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors text-sm"
                >
                    {{ __('Save Translations') }}
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[600px]">
                <thead class="bg-zinc-100 dark:bg-zinc-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider w-1/4">{{ __('Key') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider w-[37.5%]">{{ __('English') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-600 dark:text-zinc-300 uppercase tracking-wider w-[37.5%]">{{ __('Swedish') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($translations['en'] ?? [] as $key => $value)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-4 py-3">
                                <code class="text-xs text-accent bg-zinc-100 dark:bg-zinc-900 px-2 py-1 rounded">{{ $key }}</code>
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    type="text"
                                    wire:model="translations.en.{{ $key }}"
                                    class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white text-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                >
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    type="text"
                                    wire:model="translations.sv.{{ $key }}"
                                    class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white text-sm placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
                                    placeholder="{{ __('Enter Swedish translation...') }}"
                                >
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No translation keys found in this file.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Translatable Content -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 mt-6">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Translatable Content') }}</h2>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-lg bg-accent/10">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Terms & Policies') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Title and content can be translated using language tabs in the editor') }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.terms.index') }}" class="text-accent hover:text-accent/80 text-sm" wire:navigate>
                    {{ __('Manage') }} →
                </a>
            </div>
        </div>
    </div>
</div>
