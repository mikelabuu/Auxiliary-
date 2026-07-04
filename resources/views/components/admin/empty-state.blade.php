@props([
    'icon' => 'search',
    'title' => null,
])

{{--
    Small centered "nothing here" placeholder for empty lists/filtered-out grids.

    <x-admin.empty-state icon="search" title="No rooms match your search or filters." />
    or with a message slot:
    <x-admin.empty-state icon="grid">No rooms yet. Add your first room to get started.</x-admin.empty-state>
--}}

<div {{ $attributes->merge(['class' => 'text-center py-10']) }}>
    @if($icon)
        <div class="w-10 h-10 rounded-full bg-stone-100 text-stone-400 flex items-center justify-center mx-auto mb-3">
            <x-admin.icon :name="$icon" class="w-4 h-4" />
        </div>
    @endif
    <p class="text-sm text-stone-400">{{ $title ?? $slot }}</p>
</div>
