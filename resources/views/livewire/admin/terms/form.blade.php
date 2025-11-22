<div class="max-w-4xl mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-white">{{ $term ? __('Edit Term') : __('Create Term') }}</h1>
        <a href="{{ route('admin.terms.index') }}" class="text-zinc-400 hover:text-white" wire:navigate>
            {{ __('Back to list') }}
        </a>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Title') }}</label>
            <input
                type="text"
                id="title"
                wire:model.live="title"
                class="w-full px-4 py-3 bg-zinc-800 border border-zinc-700 rounded-lg text-white placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
            >
            @error('title')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Slug -->
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

        <!-- Content with Quill Editor -->
        <div>
            <label class="block text-sm font-medium text-zinc-300 mb-2">{{ __('Content') }}</label>
            <div wire:ignore>
                <div id="quill-editor" class="bg-zinc-800 border border-zinc-700 rounded-lg"></div>
            </div>
            @error('content')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <!-- Is Active -->
        <div class="flex items-center gap-3">
            <input
                type="checkbox"
                id="is_active"
                wire:model="is_active"
                class="w-5 h-5 rounded bg-zinc-700 border-zinc-600 text-accent focus:ring-accent focus:ring-offset-zinc-900"
            >
            <label for="is_active" class="text-sm font-medium text-zinc-300">{{ __('Active') }}</label>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-accent text-white font-medium rounded-lg hover:bg-accent/90 transition-colors">
                {{ $term ? __('Update') : __('Create') }}
            </button>
            <a href="{{ route('admin.terms.index') }}" class="px-6 py-3 bg-zinc-700 text-zinc-300 font-medium rounded-lg hover:bg-zinc-600 transition-colors" wire:navigate>
                {{ __('Cancel') }}
            </a>
        </div>
    </form>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
    /* Quill Dark Mode Styles */
    .ql-toolbar.ql-snow {
        background-color: rgb(63 63 70);
        border-color: rgb(82 82 91);
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .ql-container.ql-snow {
        background-color: rgb(39 39 42);
        border-color: rgb(82 82 91);
        border-radius: 0 0 0.5rem 0.5rem;
        min-height: 300px;
    }
    .ql-editor {
        color: white;
        min-height: 300px;
    }
    .ql-editor.ql-blank::before {
        color: rgb(161 161 170);
        font-style: normal;
    }
    .ql-snow .ql-stroke {
        stroke: rgb(161 161 170);
    }
    .ql-snow .ql-fill {
        fill: rgb(161 161 170);
    }
    .ql-snow .ql-picker {
        color: rgb(161 161 170);
    }
    .ql-snow .ql-picker-options {
        background-color: rgb(63 63 70);
        border-color: rgb(82 82 91);
    }
    .ql-snow .ql-picker-item:hover {
        color: white;
    }
    .ql-snow .ql-picker-item.ql-selected {
        color: #34C759;
    }
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Write your content here...',
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

        // Set initial content
        quill.root.innerHTML = @json($content);

        // Update Livewire when content changes
        quill.on('text-change', function() {
            @this.dispatch('contentUpdated', { content: quill.root.innerHTML });
        });
    });
</script>
@endpush
