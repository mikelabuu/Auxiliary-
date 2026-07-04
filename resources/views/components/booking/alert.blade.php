@props([
    'type' => 'success',
    'message' => null
])

@php
    $styles = [
        'success' => 'bg-brand-muted border-brand-light/20 text-brand-dark',
        'danger' => 'bg-red-50 border-red-200/60 text-red-800',
        'error' => 'bg-red-50 border-red-200/60 text-red-800',
        'warning' => 'bg-amber-50 border-amber-200/60 text-amber-900',
        'info' => 'bg-blue-50 border-blue-200/60 text-blue-800',
    ];

    $icons = [
        'success' => '<svg class="w-5 h-5 text-brand" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'danger' => '<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'error' => '<svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        'warning' => '<svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
        'info' => '<svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    ];

    $class = $styles[$type] ?? $styles['success'];
    $icon = $icons[$type] ?? $icons['success'];
@endphp

<div {{ $attributes->merge(['class' => "p-4 rounded-xl border flex gap-3 items-start backdrop-blur-sm transition-all duration-300 $class"]) }}>
    <div class="flex-shrink-0 mt-0.5">
        {!! $icon !!}
    </div>
    <div class="flex-1 text-sm font-medium">
        {{ $message ?? $slot }}
    </div>
</div>
