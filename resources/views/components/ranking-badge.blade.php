@if($position !== null && $position >= 1 && $position <= 3)
<span
    class="inline-flex items-center justify-center rounded-full font-bold {{ $badgeColor() }} {{ $sizeClasses() }}"
    title="{{ ucfirst($category) }} #{{ $position }}"
>
    {{ $position }}
</span>
@endif
