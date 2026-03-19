@php
    $bannerLocation = match(true) {
        request()->routeIs('home') => 'home',
        request()->routeIs('players.*') => 'players',
        request()->routeIs('matches.*') => 'matches',
        request()->routeIs('clubs.*') => 'clubs',
        request()->routeIs('bubbler.*') => 'bubbler',
        request()->routeIs('profile.*') => 'profile',
        request()->routeIs('*.edit'), request()->routeIs('settings.*') => 'settings',
        default => 'home',
    };

    $selectedBanner = \App\Models\Banner::active()
        ->forLocation($bannerLocation)
        ->inRandomOrder()
        ->first();

    $selectedBannerId = $selectedBanner?->id;
    $selectedBannerPosition = $selectedBanner?->position;

    if ($selectedBannerPosition === 'random') {
        $selectedBannerPosition = collect(['top', 'bottom', 'within_page'])->random();
    }
@endphp
<x-layouts.app.mobile
    :title="$title ?? null"
    :banner-location="$bannerLocation"
    :selected-banner-id="$selectedBannerId"
    :selected-banner-position="$selectedBannerPosition"
>
    <flux:main class="p-4">
        {{ $slot }}
    </flux:main>
</x-layouts.app.mobile>
