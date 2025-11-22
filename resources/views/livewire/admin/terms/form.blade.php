<div class="max-w-4xl mx-auto py-6 px-4"
    x-data="{
        quillInstance: null,
        init() {
            this.loadQuill().then(() => {
                this.initQuillEditor();
            });

            Livewire.on('localeChanged', (data) => {
                if (this.quillInstance) {
                    this.quillInstance.root.innerHTML = data.content || '';
                }
            });
        },
        loadQuill() {
            return new Promise((resolve) => {
                if (typeof Quill !== 'undefined') {
                    resolve();
                    return;
                }
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js';
                script.onload = resolve;
                document.head.appendChild(script);
            });
        },
        initQuillEditor() {
            const editorElement = document.getElementById('quill-editor');
            if (!editorElement || editorElement.classList.contains('ql-container')) {
                return;
            }

            this.quillInstance = new Quill('#quill-editor', {
                theme: 'snow',
                placeholder: '{{ __('admin-terms.write_content_here') }}',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        ['link'],
                        ['clean']
                    ]
                }
            });

            const activeLocale = @js($activeLocale);
            const content = @js($content);
            this.quillInstance.root.innerHTML = content[activeLocale] || '';

            this.quillInstance.on('text-change', () => {
                $wire.dispatch('contentUpdated', { content: this.quillInstance.root.innerHTML });
            });
        }
    }"
>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $term ? __('admin-terms.edit_term') : __('admin-terms.create_term') }}</h1>
        <a href="{{ route('admin.terms.index') }}" class="text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
            {{ __('admin-terms.back_to_list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-600 dark:text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Language Tabs -->
        <div class="border-b border-zinc-300 dark:border-zinc-700">
            <nav class="flex gap-4">
                @foreach($availableLocales as $code => $name)
                    <button
                        type="button"
                        wire:click="setActiveLocale('{{ $code }}')"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors {{ $activeLocale === $code ? 'border-accent text-accent' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}"
                    >
                        {{ $name }}
                        @if($code === 'en')
                            <span class="text-xs text-zinc-400 dark:text-zinc-500">({{ __('admin-terms.active') }})</span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Title -->
        <div>
            <label for="title-{{ $activeLocale }}" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">
                {{ __('admin-terms.title') }}
                @if($activeLocale === 'en')
                    <span class="text-red-500 dark:text-red-400">*</span>
                @endif
            </label>
            <input
                type="text"
                id="title-{{ $activeLocale }}"
                wire:model.live="title.{{ $activeLocale }}"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('title.' . $activeLocale)
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Slug (only show for English) -->
        @if($activeLocale === 'en')
        <div>
            <label for="slug" class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">{{ __('admin-terms.slug') }} <span class="text-red-500 dark:text-red-400">*</span></label>
            <input
                type="text"
                id="slug"
                wire:model="slug"
                class="w-full px-4 py-3 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('slug')
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
        @endif

        <!-- Content with Quill Editor -->
        <div>
            <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-300 mb-2">
                {{ __('admin-terms.content') }}
                @if($activeLocale === 'en')
                    <span class="text-red-500 dark:text-red-400">*</span>
                @endif
            </label>
            <div wire:ignore>
                <div id="quill-editor" class="bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-700 rounded-lg"></div>
            </div>
            @error('content.' . $activeLocale)
                <p class="mt-1 text-sm text-red-500 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Is Active (only show for English) -->
        @if($activeLocale === 'en')
        <div class="flex items-center gap-3">
            <input
                type="checkbox"
                id="is_active"
                wire:model="is_active"
                class="w-5 h-5 rounded bg-white dark:bg-zinc-700 border-zinc-300 dark:border-zinc-600 text-accent focus:ring-accent focus:ring-offset-white dark:focus:ring-offset-zinc-900"
            >
            <label for="is_active" class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('admin-terms.is_active') }}</label>
        </div>
        @endif

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $term ? __('admin-terms.update') : __('admin-terms.create') }}
            </button>
            <a href="{{ route('admin.terms.index') }}" class="px-6 py-3 bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-zinc-300 dark:hover:bg-zinc-600 transition-colors" wire:navigate>
                {{ __('admin-terms.cancel') }}
            </a>
        </div>
    </form>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
    /* Quill Light Mode Styles */
    .ql-toolbar.ql-snow {
        background-color: rgb(244 244 245);
        border-color: rgb(212 212 216);
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .ql-container.ql-snow {
        background-color: white;
        border-color: rgb(212 212 216);
        border-radius: 0 0 0.5rem 0.5rem;
        min-height: 300px;
    }
    .ql-editor {
        color: rgb(24 24 27);
        min-height: 300px;
    }
    .ql-editor.ql-blank::before {
        color: rgb(161 161 170);
        font-style: normal;
    }
    .ql-snow .ql-stroke {
        stroke: rgb(113 113 122);
    }
    .ql-snow .ql-fill {
        fill: rgb(113 113 122);
    }
    .ql-snow .ql-picker {
        color: rgb(113 113 122);
    }
    .ql-snow .ql-picker-options {
        background-color: white;
        border-color: rgb(212 212 216);
    }
    .ql-snow .ql-picker-item:hover {
        color: rgb(24 24 27);
    }
    .ql-snow .ql-picker-item.ql-selected {
        color: #34C759;
    }

    /* Quill Dark Mode Styles */
    .dark .ql-toolbar.ql-snow {
        background-color: rgb(63 63 70);
        border-color: rgb(82 82 91);
    }
    .dark .ql-container.ql-snow {
        background-color: rgb(39 39 42);
        border-color: rgb(82 82 91);
    }
    .dark .ql-editor {
        color: white;
    }
    .dark .ql-snow .ql-stroke {
        stroke: rgb(161 161 170);
    }
    .dark .ql-snow .ql-fill {
        fill: rgb(161 161 170);
    }
    .dark .ql-snow .ql-picker {
        color: rgb(161 161 170);
    }
    .dark .ql-snow .ql-picker-options {
        background-color: rgb(63 63 70);
        border-color: rgb(82 82 91);
    }
    .dark .ql-snow .ql-picker-item:hover {
        color: white;
    }

    /* Hover/Active states for both modes */
    .ql-snow.ql-toolbar button:hover,
    .ql-snow .ql-toolbar button:hover,
    .ql-snow.ql-toolbar button:focus,
    .ql-snow .ql-toolbar button:focus,
    .ql-snow.ql-toolbar button.ql-active,
    .ql-snow .ql-toolbar button.ql-active,
    .ql-snow.ql-toolbar .ql-picker-label:hover,
    .ql-snow .ql-toolbar .ql-picker-label:hover,
    .ql-snow.ql-toolbar .ql-picker-label.ql-active,
    .ql-snow .ql-toolbar .ql-picker-label.ql-active,
    .ql-snow.ql-toolbar .ql-picker-item:hover,
    .ql-snow .ql-toolbar .ql-picker-item:hover,
    .ql-snow.ql-toolbar .ql-picker-item.ql-selected,
    .ql-snow .ql-toolbar .ql-picker-item.ql-selected {
        color: #34C759;
    }
    .ql-snow.ql-toolbar button:hover .ql-stroke,
    .ql-snow .ql-toolbar button:hover .ql-stroke,
    .ql-snow.ql-toolbar button:focus .ql-stroke,
    .ql-snow .ql-toolbar button:focus .ql-stroke,
    .ql-snow.ql-toolbar button.ql-active .ql-stroke,
    .ql-snow .ql-toolbar button.ql-active .ql-stroke,
    .ql-snow.ql-toolbar .ql-picker-label:hover .ql-stroke,
    .ql-snow .ql-toolbar .ql-picker-label:hover .ql-stroke,
    .ql-snow.ql-toolbar .ql-picker-label.ql-active .ql-stroke,
    .ql-snow .ql-toolbar .ql-picker-label.ql-active .ql-stroke {
        stroke: #34C759;
    }
    .ql-snow.ql-toolbar button:hover .ql-fill,
    .ql-snow .ql-toolbar button:hover .ql-fill,
    .ql-snow.ql-toolbar button:focus .ql-fill,
    .ql-snow .ql-toolbar button:focus .ql-fill,
    .ql-snow.ql-toolbar button.ql-active .ql-fill,
    .ql-snow .ql-toolbar button.ql-active .ql-fill {
        fill: #34C759;
    }
</style>
@endpush

