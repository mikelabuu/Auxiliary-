@props([
    'icon' => 'search',
    'title' => null,
])

{{--
    Centered "nothing here" placeholder (AAIS .empty-state).

    <x-admin.ui.empty-state icon="search" title="No rooms match your search or filters." />
    or with a message slot:
    <x-admin.ui.empty-state icon="grid">No rooms yet. Add your first room to get started.</x-admin.ui.empty-state>
--}}

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    @if($icon)
        <x-admin.ui.icon :name="$icon" class="w-11 h-11" />
    @endif
    <p>{{ $title ?? $slot }}</p>
</div>
