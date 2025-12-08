<div>
    @if($banner)
        <div class="fixed {{ $position === 'top' ? 'top-0' : 'bottom-0' }} left-0 right-0 z-50 bg-white dark:bg-zinc-800 shadow-lg border-{{ $position === 'top' ? 'b' : 't' }} border-zinc-200 dark:border-zinc-700">
            <div class="max-w-7xl mx-auto px-4 py-2">
                <a
                    href="{{ $banner->link }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    wire:click="trackClick({{ $banner->id }})"
                    class="block"
                >
                    <img
                        src="{{ $banner->image_url }}"
                        alt="{{ $banner->name }}"
                        class="w-full h-auto max-h-20 object-contain mx-auto"
                    >
                </a>
            </div>
        </div>
    @endif
</div>
