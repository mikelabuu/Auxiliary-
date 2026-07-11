@props([
    'title' => null,
    'icon' => null,
    'subtitle' => null,
])

{{--
    Standalone AAIS card header for hand-rolled .card markup. `icon` accepts
    an x-admin.ui.icon name. Named `actions` slot renders right-aligned.

    <div class="card">
        <x-admin.ui.card-header icon="clipboard" title="Recent Bookings" subtitle="Last 7 days">
            <x-slot:actions><a href="..." class="btn btn-outline btn-sm">View all</a></x-slot:actions>
        </x-admin.ui.card-header>
        <div class="card-body">...</div>
    </div>
--}}

<div {{ $attributes->merge(['class' => 'card-header']) }}>
    <div>
        <h3 class="card-title">
            @if ($icon)
                <x-admin.ui.icon :name="$icon" class="w-[18px] h-[18px] shrink-0" style="color:var(--color-g-600);" />
            @endif
            {{ $title ?? $slot }}
        </h3>
        @if ($subtitle)
            <p class="card-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="card-header-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
