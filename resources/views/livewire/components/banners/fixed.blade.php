<div>
    @if($banner)
        <div class="px-6">
            <div class="max-w-2xl mx-auto my-4">
                <a
                    href="{{ $banner->link }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    wire:click="trackClick({{ $banner->id }})"
                    class="block"
                >
                    <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 hover:border-accent dark:hover:border-accent transition-colors flex items-center justify-center">
                        <img
                            src="{{ $banner->image_url }}"
                            alt="{{ $banner->name }}"
                            class="w-full max-h-[100px] object-contain"
                        >
                    </div>
                </a>
            </div>
        </div>
    @endif
</div>
