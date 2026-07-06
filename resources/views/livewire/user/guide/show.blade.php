<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('guide.index') }}" class="text-zinc-400 dark:text-zinc-500 hover:text-zinc-900 dark:hover:text-white" wire:navigate>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $section->title }}</h1>
    </div>

    <article class="guide-content text-zinc-700 dark:text-zinc-300">
        {!! $section->content !!}
    </article>

    <!-- Prev / Next -->
    <div class="mt-10 flex items-center justify-between gap-3 border-t border-zinc-200 dark:border-zinc-700 pt-6">
        @if($previous)
            <a href="{{ route('guide.show', $previous->slug) }}" wire:navigate class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 hover:text-accent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span class="truncate max-w-[9rem]">{{ $previous->title }}</span>
            </a>
        @else
            <span></span>
        @endif

        @if($next)
            <a href="{{ route('guide.show', $next->slug) }}" wire:navigate class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300 hover:text-accent text-right">
                <span class="truncate max-w-[9rem]">{{ $next->title }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        @else
            <span></span>
        @endif
    </div>
</div>

@push('styles')
<style>
    .guide-content h2 { font-size: 1.25rem; font-weight: 700; margin: 1.75rem 0 .5rem; color: rgb(24 24 27); }
    .dark .guide-content h2 { color: #fff; }
    .guide-content h3 { font-size: 1.05rem; font-weight: 600; margin: 1.25rem 0 .4rem; color: rgb(39 39 42); }
    .dark .guide-content h3 { color: rgb(228 228 231); }
    .guide-content p { margin: .6rem 0; line-height: 1.7; }
    .guide-content ul { list-style: disc; padding-left: 1.35rem; margin: .6rem 0; }
    .guide-content ol { list-style: decimal; padding-left: 1.35rem; margin: .6rem 0; }
    .guide-content li { margin: .3rem 0; line-height: 1.6; }
    .guide-content a { color: #34C759; text-decoration: underline; }
    .guide-content strong { color: rgb(24 24 27); font-weight: 600; }
    .dark .guide-content strong { color: #fff; }
    .guide-content img {
        display: block; max-width: 260px; width: 100%; height: auto; margin: 1rem auto;
        border-radius: 1rem; border: 1px solid rgb(228 228 231);
    }
    .dark .guide-content img { border-color: rgb(63 63 70); }
    .guide-content .guide-note {
        margin: 1rem 0; padding: .85rem 1rem; border-radius: .75rem;
        background: rgb(244 244 245); border-left: 3px solid #34C759;
        font-size: .925rem;
    }
    .dark .guide-content .guide-note { background: rgb(39 39 42); }
    .guide-content .guide-important {
        margin: 1rem 0; padding: .85rem 1rem; border-radius: .75rem;
        background: rgba(239,68,68,.08); border-left: 3px solid rgb(239 68 68);
        font-size: .925rem;
    }
</style>
@endpush
