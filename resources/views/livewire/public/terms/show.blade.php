<div>
    <div class="flex flex-col gap-6 w-full max-w-[1000px] mx-auto px-4">
        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-8 md:p-12">
            <div class="terms-content text-zinc-600 dark:text-zinc-400">
                {!! $term->content !!}
            </div>

            <div class="mt-8 pt-6 border-t border-zinc-200 dark:border-zinc-700 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Last updated') }}: {{ $term->updated_at->format('F j, Y') }}
            </div>
        </div>
    </div>
</div>
