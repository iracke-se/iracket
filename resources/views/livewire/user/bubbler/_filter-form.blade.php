{{-- Shared filter form — used by both mobile bottom sheet and desktop dropdown --}}
{{-- Variables: $isSheet (bool), $availableDistricts (array), $activeTab (string) --}}

<div class="{{ $isSheet ? 'px-6 py-4 pb-24 space-y-6 overflow-y-auto max-h-[82vh]' : 'p-4 space-y-5' }}">

    @if($activeTab === 'clubs')

        {{-- ── CLUBS: SORT BY ── --}}
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">
                {{ __('user-bubbler.sort_by') }}
            </p>
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl divide-y divide-zinc-200 dark:divide-zinc-700">
                <label class="flex items-center justify-between px-4 py-3 cursor-pointer">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.sort_points_gained') }}</span>
                    <input type="radio" wire:model.live="sortClubsBy" value="points_gained" class="accent-accent w-4 h-4">
                </label>
                <label class="flex items-center justify-between px-4 py-3 cursor-pointer">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.sort_new_members') }}</span>
                    <input type="radio" wire:model.live="sortClubsBy" value="new_members" class="accent-accent w-4 h-4">
                </label>
                <label class="flex items-center justify-between px-4 py-3 cursor-pointer">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.sort_bubblare_count') }}</span>
                    <input type="radio" wire:model.live="sortClubsBy" value="bubblare" class="accent-accent w-4 h-4">
                </label>
            </div>
        </div>

    @else

        {{-- ── LOCATION ── --}}
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">
                {{ __('user-bubbler.location') }}
            </p>
            <select wire:model.live="filterDistrict"
                class="w-full px-4 py-3 bg-zinc-100 dark:bg-zinc-800 rounded-xl text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 text-sm focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent">
                <option value="">{{ __('user-bubbler.all_districts') }}</option>
                @foreach($availableDistricts as $district)
                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ── RANKING / POINT RANGE ── --}}
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">
                {{ __('user-bubbler.ranking') }}
            </p>
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl divide-y divide-zinc-200 dark:divide-zinc-700">
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.from') }}</span>
                    <input type="number" wire:model.live="filterPointsFrom" min="0" max="9999"
                        placeholder="—"
                        class="w-28 text-right bg-transparent text-sm text-zinc-500 dark:text-zinc-400 focus:outline-none focus:text-accent placeholder-zinc-400 dark:placeholder-zinc-500">
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.to') }}</span>
                    <input type="number" wire:model.live="filterPointsTo" min="0" max="9999"
                        placeholder="—"
                        class="w-28 text-right bg-transparent text-sm text-zinc-500 dark:text-zinc-400 focus:outline-none focus:text-accent placeholder-zinc-400 dark:placeholder-zinc-500">
                </div>
            </div>
        </div>

        {{-- ── AGE ── --}}
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">
                {{ __('user-bubbler.age') }}
            </p>
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl divide-y divide-zinc-200 dark:divide-zinc-700">
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.from') }}</span>
                    <input type="number" wire:model.live="filterAgeFrom" min="0" max="99"
                        placeholder="—"
                        class="w-28 text-right bg-transparent text-sm text-zinc-500 dark:text-zinc-400 focus:outline-none focus:text-accent placeholder-zinc-400 dark:placeholder-zinc-500">
                </div>
                <div class="flex items-center justify-between px-4 py-3">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.to') }}</span>
                    <input type="number" wire:model.live="filterAgeTo" min="0" max="99"
                        placeholder="—"
                        class="w-28 text-right bg-transparent text-sm text-zinc-500 dark:text-zinc-400 focus:outline-none focus:text-accent placeholder-zinc-400 dark:placeholder-zinc-500">
                </div>
            </div>
        </div>

        {{-- ── SORT ── --}}
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">
                {{ __('user-bubbler.sort') }}
            </p>
            <div class="bg-zinc-100 dark:bg-zinc-800 rounded-xl divide-y divide-zinc-200 dark:divide-zinc-700">
                <label class="flex items-center justify-between px-4 py-3 cursor-pointer">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.highest_first') }}</span>
                    <input type="radio" wire:model.live="sortPoints" value="desc" class="accent-accent w-4 h-4">
                </label>
                <label class="flex items-center justify-between px-4 py-3 cursor-pointer">
                    <span class="text-sm text-zinc-900 dark:text-white">{{ __('user-bubbler.lowest_first') }}</span>
                    <input type="radio" wire:model.live="sortPoints" value="asc" class="accent-accent w-4 h-4">
                </label>
            </div>
        </div>

    @endif

    {{-- ── Clear ── --}}
    <button wire:click="clearFilters"
        class="w-full py-3 text-sm font-medium text-red-500 hover:text-red-600 transition-colors">
        {{ __('user-bubbler.clear_filters') }}
    </button>

</div>
