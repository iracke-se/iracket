<div
    x-data="{ filterOpen: false }"
    class="max-w-2xl mx-auto"
>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6">{{ __('user-bubbler.bubbler') }}</h1>

    <!-- Period Selector -->
    <div class="mb-6 flex gap-3">
        <div class="flex-1">
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('user-bubbler.year') }}</label>
            <select wire:model.live="selectedYear"
                class="w-full px-4 py-2.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-accent focus:border-accent transition-colors">
                @foreach($this->availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">{{ __('user-bubbler.month') }}</label>
            <select wire:model.live="selectedMonth"
                class="w-full px-4 py-2.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-700 focus:ring-2 focus:ring-accent focus:border-accent transition-colors">
                @foreach($this->availableMonthsForYear as $month)
                    <option value="{{ $month }}">{{ \Carbon\Carbon::create(null, $month)->locale(app()->getLocale())->translatedFormat('F') }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Tabs + Desktop filter button -->
    <div class="flex mb-6 items-center gap-2">
        <div class="flex flex-1 rounded-lg border-2 border-accent overflow-hidden">
            <button wire:click="setTab('ladies')"
                class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors {{ $activeTab === 'ladies' ? 'bg-accent text-white' : 'bg-white dark:bg-zinc-900 text-accent hover:bg-accent/10' }}">
                {{ __('user-bubbler.ladies') }}
            </button>
            <button wire:click="setTab('men')"
                class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors border-x-2 border-accent {{ $activeTab === 'men' ? 'bg-accent text-white' : 'bg-white dark:bg-zinc-900 text-accent hover:bg-accent/10' }}">
                {{ __('user-bubbler.men') }}
            </button>
            <button wire:click="setTab('clubs')"
                class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors {{ $activeTab === 'clubs' ? 'bg-accent text-white' : 'bg-white dark:bg-zinc-900 text-accent hover:bg-accent/10' }}">
                {{ __('user-bubbler.clubs') }}
            </button>
        </div>

        {{-- Desktop filter button --}}
        <div class="relative hidden md:block" x-data="{ open: false }">
            <button @click="open = !open"
                class="relative flex items-center gap-2 px-4 py-3 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
                {{ __('user-bubbler.filter') }}
                @if($activeFilterCount > 0)
                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-accent text-white text-[10px] font-bold flex items-center justify-center">
                        {{ $activeFilterCount }}
                    </span>
                @endif
            </button>
            <div x-show="open" @click.outside="open = false"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 top-full mt-2 w-72 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-xl z-30 overflow-hidden"
                style="display:none">
                @include('livewire.user.bubbler._filter-form', [
                    'isSheet'            => false,
                    'availableDistricts' => $availableDistricts,
                    'activeTab'          => $activeTab,
                    'menClassRanges'     => $menClassRanges,
                    'womenClassRanges'   => $womenClassRanges,
                ])
            </div>
        </div>
    </div>

    @php
        $rankColors = [
            1 => 'bg-yellow-400 text-black',
            2 => 'bg-zinc-400 text-white',
            3 => 'bg-amber-600 text-white',
        ];
        $rankDefault = 'bg-zinc-300 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-200';
    @endphp

    <!-- Ladies Tab -->
    @if($activeTab === 'ladies')
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-bubbler.ladies_of_the_month') }}</h2>
            @if(empty($ladiesGrouped))
                <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.no_rankings_yet') }}</p>
                </div>
            @else
                @foreach($ladiesGrouped as $group)
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 mt-5 first:mt-0">
                        {{ $group['label'] }}
                    </p>
                    <div class="space-y-2 mb-2">
                        @foreach($group['players'] as $i => $ranking)
                            @php $rank = $i + 1; @endphp
                            <a href="{{ route('players.show', $ranking->user) }}" wire:navigate
                               class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                <div class="relative shrink-0">
                                    @if($ranking->user->profile_picture)
                                        <img src="{{ Storage::url($ranking->user->profile_picture) }}" alt="{{ $ranking->user->name }}" class="w-14 h-14 rounded-full object-cover">
                                    @else
                                        <div class="w-14 h-14 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                            <span class="text-base font-medium text-zinc-600 dark:text-zinc-300">{{ $ranking->user->initials() }}</span>
                                        </div>
                                    @endif
                                    <span class="absolute -top-1 -left-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $rankColors[$rank] ?? $rankDefault }}">{{ $rank }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-zinc-900 dark:text-white truncate">{{ $ranking->user->name }}</div>
                                    @if($ranking->user->club)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $ranking->user->club->name }}</div>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-400 text-black text-xs font-bold">{{ number_format($ranking->effective_points) }} p</span>
                                        @if($ranking->points_change != 0)
                                            <span class="text-xs font-medium {{ $ranking->points_change > 0 ? 'text-green-500' : 'text-red-500' }}">
                                                @if($ranking->points_change > 0)+@endif{{ number_format($ranking->points_change) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    <!-- Men Tab -->
    @if($activeTab === 'men')
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-bubbler.gentlemen_of_the_month') }}</h2>
            @if(empty($menGrouped))
                <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.no_rankings_yet') }}</p>
                </div>
            @else
                @foreach($menGrouped as $group)
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-2 mt-5 first:mt-0">
                        {{ $group['label'] }}
                    </p>
                    <div class="space-y-2 mb-2">
                        @foreach($group['players'] as $i => $ranking)
                            @php $rank = $i + 1; @endphp
                            <a href="{{ route('players.show', $ranking->user) }}" wire:navigate
                               class="flex items-center gap-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">
                                <div class="relative shrink-0">
                                    @if($ranking->user->profile_picture)
                                        <img src="{{ Storage::url($ranking->user->profile_picture) }}" alt="{{ $ranking->user->name }}" class="w-14 h-14 rounded-full object-cover">
                                    @else
                                        <div class="w-14 h-14 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                            <span class="text-base font-medium text-zinc-600 dark:text-zinc-300">{{ $ranking->user->initials() }}</span>
                                        </div>
                                    @endif
                                    <span class="absolute -top-1 -left-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $rankColors[$rank] ?? $rankDefault }}">{{ $rank }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-zinc-900 dark:text-white truncate">{{ $ranking->user->name }}</div>
                                    @if($ranking->user->club)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $ranking->user->club->name }}</div>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-400 text-black text-xs font-bold">{{ number_format($ranking->effective_points) }} p</span>
                                        @if($ranking->points_change != 0)
                                            <span class="text-xs font-medium {{ $ranking->points_change > 0 ? 'text-green-500' : 'text-red-500' }}">
                                                @if($ranking->points_change > 0)+@endif{{ number_format($ranking->points_change) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    <!-- Clubs Tab -->
    @if($activeTab === 'clubs')
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">{{ __('user-bubbler.clubs_of_the_month') }}</h2>

            @if($clubBubblerRankings->isEmpty())
                <div class="text-center py-12 bg-zinc-100 dark:bg-zinc-800 rounded-xl">
                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('user-bubbler.no_club_rankings_yet') }}</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($clubBubblerRankings as $data)
                        @php
                            $rank  = $data['rank'];
                            $club  = $data['club'];
                            $isTop = $rank <= 3;
                        @endphp

                        <a href="{{ route('clubs.show', $club) }}" wire:navigate
                           class="flex items-center gap-4 {{ $isTop ? 'p-4' : 'px-4 py-3' }} bg-zinc-100 dark:bg-zinc-800 rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition-colors">

                            {{-- Logo + rank badge --}}
                            <div class="relative shrink-0">
                                @if($club->logo)
                                    <img src="{{ Storage::url($club->logo) }}" alt="{{ $club->name }}"
                                         class="{{ $isTop ? 'w-14 h-14' : 'w-10 h-10' }} rounded-xl object-cover">
                                @else
                                    <div class="{{ $isTop ? 'w-14 h-14' : 'w-10 h-10' }} rounded-xl bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="{{ $isTop ? 'text-base' : 'text-xs' }} font-bold text-zinc-600 dark:text-zinc-300">
                                            {{ strtoupper(substr($club->name, 0, 2)) }}
                                        </span>
                                    </div>
                                @endif
                                <span class="absolute -top-1 -left-1 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $rankColors[$rank] ?? $rankDefault }}">
                                    {{ $rank }}
                                </span>
                            </div>

                            {{-- Name + stats --}}
                            <div class="flex-1 min-w-0">
                                <div class="{{ $isTop ? 'font-semibold' : 'text-sm font-medium' }} text-zinc-900 dark:text-white truncate">
                                    {{ $club->name }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ $club->members_count }} {{ __('user-bubbler.members') }}
                                </div>
                                {{-- Metric pills --}}
                                <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                    {{-- Points gained --}}
                                    @if($data['total_points_gained'] > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-400 text-black text-[11px] font-bold">
                                            +{{ number_format($data['total_points_gained']) }} {{ __('user-bubbler.points_gained') }}
                                        </span>
                                    @endif
                                    {{-- New members --}}
                                    @if($data['new_members'] > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-500 text-white text-[11px] font-bold">
                                            +{{ $data['new_members'] }} {{ __('user-bubbler.new_members_label') }}
                                        </span>
                                    @endif
                                    {{-- Bubblare count --}}
                                    @if($data['bubblare_count'] > 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-accent text-white text-[11px] font-bold">
                                            {{ $data['bubblare_count'] }} {{ __('user-bubbler.bubblare_label') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- ══════════════════════════════════
         MOBILE: FAB + bottom sheet
    ══════════════════════════════════ --}}
    {{-- FAB --}}
    <button @click="filterOpen = true"
        class="fixed bottom-20 right-4 z-40 md:hidden w-14 h-14 rounded-full bg-accent shadow-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
        </svg>
        @if($activeFilterCount > 0)
            <span class="absolute -top-0.5 -right-0.5 w-5 h-5 rounded-full bg-white text-accent text-[10px] font-bold flex items-center justify-center border-2 border-accent">
                {{ $activeFilterCount }}
            </span>
        @endif
    </button>

    {{-- Backdrop --}}
    <div x-show="filterOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="filterOpen = false"
        class="fixed inset-0 bg-black/50 z-40 md:hidden"
        style="display:none">
    </div>

    {{-- Bottom sheet --}}
    <div x-show="filterOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-white dark:bg-zinc-900 rounded-t-2xl shadow-2xl"
        style="display:none">
        <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 rounded-full bg-zinc-300 dark:bg-zinc-600"></div>
        </div>
        <div class="flex items-center justify-between px-6 py-3 border-b border-zinc-200 dark:border-zinc-700">
            <button @click="filterOpen = false" class="text-accent text-sm font-medium">
                {{ __('user-bubbler.cancel') }}
            </button>
            <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('user-bubbler.filter') }}</span>
            <button @click="filterOpen = false" class="text-accent text-sm font-medium">
                {{ __('user-bubbler.done') }}
            </button>
        </div>
        @include('livewire.user.bubbler._filter-form', [
            'isSheet'            => true,
            'availableDistricts' => $availableDistricts,
            'activeTab'          => $activeTab,
            'menClassRanges'     => $menClassRanges,
            'womenClassRanges'   => $womenClassRanges,
        ])
    </div>
</div>
