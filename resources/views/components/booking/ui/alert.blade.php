@props([
    'type' => 'success',
    'message' => null,
])

@php
    $styles = [
        'success' => 'bg-clsu-50 border-clsu-200 text-clsu-800',
        'danger'  => 'bg-ember-50 border-ember-200 text-ember-800',
        'error'   => 'bg-ember-50 border-ember-200 text-ember-800',
        'warning' => 'bg-palay-50 border-palay-200 text-palay-900',
        'info'    => 'bg-clsu-50 border-clsu-200 text-clsu-800',
    ];

    $icons = [
        'success' => '<svg class="w-5 h-5 text-clsu-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'danger'  => '<svg class="w-5 h-5 text-ember-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>',
        'error'   => '<svg class="w-5 h-5 text-ember-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>',
        'warning' => '<svg class="w-5 h-5 text-palay-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        'info'    => '<svg class="w-5 h-5 text-clsu-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ];

    $class = $styles[$type] ?? $styles['success'];
    $icon = $icons[$type] ?? $icons['success'];
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-2xl border flex gap-3 items-start transition-all duration-300 $class"]) }}>
    <div class="flex-shrink-0 mt-0.5">
        {!! $icon !!}
    </div>
    <div class="flex-1 text-sm font-semibold">
        {{ $message ?? $slot }}
    </div>
</div>
