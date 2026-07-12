@props([
    'href',
    'active' => false,
    'label',
    'icon' => null,
    'chip' => null,
])

<a href="{{ $href }}" class="sidebar-link {{ $active ? 'active' : '' }}">
    @if ($icon)
        {!! $icon !!}
    @endif

    <span>{{ $label }}</span>

    @if (!is_null($chip))
        <span class="sidebar-link-chip">{{ $chip }}</span>
    @endif
</a>
