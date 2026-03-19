<div>
    @if($banner)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" x-data x-init="$el.classList.remove('hidden')">
            <div class="relative bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-2xl max-w-lg w-full">
                <!-- Close button -->
                <button
                    wire:click="close"
                    class="absolute top-2 right-2 z-10 p-2 bg-black/50 hover:bg-black/70 rounded-full text-white transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- Banner content -->
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
                        class="w-full h-auto"
                    >
                </a>
            </div>
        </div>
    @endif
</div>
