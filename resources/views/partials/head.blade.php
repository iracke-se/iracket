<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

{{-- Prevent FOUC: apply theme class to <html> synchronously before any CSS paints. --}}
{{-- Reads Flux's `flux.appearance` (written by the Appearance settings page) with --}}
{{-- fallback to the legacy `theme` key. Default is dark. --}}
{{-- A MutationObserver re-asserts the correct class on every <html> class --}}
{{-- mutation — needed because Livewire's wire:navigate morph briefly strips --}}
{{-- the dark class from <html> mid-transition, which would otherwise cause a --}}
{{-- visible white flash on elements that use bg-white dark:bg-zinc-xxx. --}}
<script>
    (function () {
        var applying = false;
        function desiredDark() {
            try {
                var pref = localStorage.getItem('flux.appearance') || localStorage.getItem('theme') || 'dark';
                return pref === 'system'
                    ? window.matchMedia('(prefers-color-scheme: dark)').matches
                    : pref !== 'light';
            } catch (e) {
                return true;
            }
        }
        function applyTheme() {
            applying = true;
            document.documentElement.classList.toggle('dark', desiredDark());
            applying = false;
        }
        applyTheme();

        // Catch wire:navigate morph stripping the class
        new MutationObserver(function () {
            if (applying) return;
            var isDark = document.documentElement.classList.contains('dark');
            if (isDark !== desiredDark()) {
                applyTheme();
            }
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

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
