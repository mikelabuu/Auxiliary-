@props([
    'name',
    // Absorb legacy per-call weights; the admin family uses one consistent stroke.
    'strokeWidth' => null,
])

{{-- Hugeicons Stroke Rounded (MIT). Change scripts/build-admin-icons.mjs to add an icon. --}}
<svg {{ $attributes->merge(['class' => 'icon icon-stroke']) }} viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
     aria-hidden="true" focusable="false">{!! \App\Support\AdminStrokeIcons::get($name) !!}</svg>
