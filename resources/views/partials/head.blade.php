<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- Prevent FOUC: apply theme class to <html> synchronously before any CSS paints. --}}
{{-- Reads Flux's `flux.appearance` (written by the Appearance settings page) with --}}
{{-- fallback to the legacy `theme` key. Default is dark. --}}
{{-- Re-applies on wire:navigate transitions because Livewire updates <html> --}}
{{-- attributes from the new page's server response — pages whose <html> tag --}}
{{-- doesn't have class="dark" would otherwise briefly flash light. --}}
<script>
    (function () {
        function applyTheme() {
            try {
                var pref = localStorage.getItem('flux.appearance') || localStorage.getItem('theme') || 'dark';
                var isDark = pref === 'system'
                    ? window.matchMedia('(prefers-color-scheme: dark)').matches
                    : pref !== 'light';
                document.documentElement.classList.toggle('dark', isDark);
            } catch (e) {}
        }
        applyTheme();
        document.addEventListener('livewire:navigating', applyTheme);
        document.addEventListener('livewire:navigated', applyTheme);
    })();
</script>

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/assets/images/icon.png" type="image/png">
<link rel="apple-touch-icon" href="/assets/images/icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
