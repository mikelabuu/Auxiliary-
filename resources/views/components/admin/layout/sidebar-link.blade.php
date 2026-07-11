@props([
    'href',
    'active' => false,
    'badge' => null
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'sidebar-link !no-underline' . ($active ? ' active' : '')]) }}>
    {{ $icon ?? '' }}

    <span class="truncate flex-1">{{ $slot }}</span>

    @if (!is_null($badge))
        <span class="sidebar-link-chip">{{ $badge }}</span>
    @endif
</a>
