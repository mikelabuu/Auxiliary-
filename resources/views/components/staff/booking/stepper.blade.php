@props([
    'label',
    'size' => 'md',
    'hint' => null,
])

{{--
    A -/+ number stepper. `size="sm"` is the in-card variant used inside the
    assignment template; `md` is the page-level Expected Guests control.

    The input itself comes in via the default slot so callers keep control of
    name/id/data-slot — the JS binds to those, not to this wrapper.
--}}
@php
    $box = $size === 'sm' ? 'h-9 w-9' : 'h-10 w-10';
    $ico = $size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4';
    $gap = $size === 'sm' ? 'gap-1.5' : 'gap-2';
@endphp

<div class="mb-stepper flex items-center {{ $gap }}">
    <button type="button" data-step="-1" class="mb-step press grid {{ $box }} shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-muted transition-colors hover:border-clsu-500 hover:text-brand-ink-deep" aria-label="Fewer {{ $label }}">
        <x-admin.ui.icon name="minus" :class="$ico" stroke-width="2" />
    </button>

    {{ $slot }}

    <button type="button" data-step="1" class="mb-step press grid {{ $box }} shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-muted transition-colors hover:border-clsu-500 hover:text-brand-ink-deep" aria-label="More {{ $label }}">
        <x-admin.ui.icon name="plus" :class="$ico" stroke-width="2" />
    </button>
</div>

@if($hint)
    <p class="mt-1.5 text-2xs font-medium text-faint">{{ $hint }}</p>
@endif
