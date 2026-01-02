<div>
    @if($banner)
        <div class="fixed {{ $position === 'top' ? 'top-14' : 'bottom-16' }} left-0 right-0 z-40 pointer-events-none">
            <div class="px-6 pointer-events-auto">
                <div class="max-w-2xl mx-auto">
                    <a
                        href="{{ $banner->link }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        wire:click="trackClick({{ $banner->id }})"
                        class="block"
                    >
                        <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 shadow-lg">
                            <img
                                src="{{ $banner->image_url }}"
                                alt="{{ $banner->name }}"
                                class="w-full h-auto max-h-20 object-contain mx-auto"
                            >
                        </div>
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
