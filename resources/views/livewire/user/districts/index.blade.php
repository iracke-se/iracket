<div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">{{ __('Districts') }}</h1>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm font-mono">
            <thead>
                <tr class="bg-zinc-200 dark:bg-zinc-700 text-left">
                    <th class="px-4 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 w-12">id</th>
                    <th class="px-4 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400">name</th>
                    <th class="px-4 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 text-right w-32">profixio_id</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($districts as $district)
                    <tr class="bg-zinc-100 dark:bg-zinc-800">
                        <td class="px-4 py-3 text-zinc-400 dark:text-zinc-500">{{ $district->id }}</td>
                        <td class="px-4 py-3 text-zinc-900 dark:text-white">{{ $district->name }}</td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-right">{{ $district->profixio_id ?? 'null' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-3 text-right">{{ $districts->count() }} {{ __('districts') }}</p>
</div>
