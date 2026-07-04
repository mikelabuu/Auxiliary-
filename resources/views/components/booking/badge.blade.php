@props([
    'status' => 'default'
])

@php
    $normalized = strtolower(str_replace(' ', '_', trim($status)));

    $colors = [
        // Green / Success
        'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        'success' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        
        // Yellow / Warning / Pending
        'pending_payment' => 'bg-amber-50 text-amber-800 border-amber-200/60',
        'pending_discount' => 'bg-amber-50 text-amber-800 border-amber-200/60',
        'pending' => 'bg-amber-50 text-amber-800 border-amber-200/60',

        // Red / Danger / Cancelled / Rejected
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200/60',
        'rejected' => 'bg-rose-50 text-rose-700 border-rose-200/60',
        'failed' => 'bg-rose-50 text-rose-700 border-rose-200/60',

        // Gray / Default
        'default' => 'bg-slate-50 text-slate-600 border-slate-200/60',
        'not_yet_submitted' => 'bg-slate-50 text-slate-500 border-slate-200/60',
        'not_submitted' => 'bg-slate-50 text-slate-500 border-slate-200/60',
        'no_request' => 'bg-slate-50 text-slate-400 border-slate-100',
    ];

    $classes = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border tracking-wide " . ($colors[$normalized] ?? $colors['default']);
    
    // Formatting label for display
    $label = str_replace('_', ' ', $status);
    $label = ucwords($label);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot->isNotEmpty() ? $slot : $label }}
</span>
