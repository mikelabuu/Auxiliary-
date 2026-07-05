@props([
    'status' => 'default',
])

@php
    $normalized = strtolower(str_replace(' ', '_', trim($status)));

    $colors = [
        // Green / Success
        'active'    => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'approved'  => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'completed' => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'paid'      => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'success'   => 'bg-clsu-50 text-clsu-700 border-clsu-200',

        // Gold / Warning / Pending
        'pending_payment'  => 'bg-palay-100 text-palay-800 border-palay-200',
        'pending_discount' => 'bg-palay-100 text-palay-800 border-palay-200',
        'pending'          => 'bg-palay-100 text-palay-800 border-palay-200',

        // Red / Danger / Cancelled / Rejected
        'cancelled' => 'bg-ember-50 text-ember-700 border-ember-200',
        'rejected'  => 'bg-ember-50 text-ember-700 border-ember-200',
        'failed'    => 'bg-ember-50 text-ember-700 border-ember-200',
        'expired'   => 'bg-ember-50 text-ember-700 border-ember-200',
        'no_show'   => 'bg-ember-50 text-ember-700 border-ember-200',

        // Neutral / Default
        'default'            => 'bg-stone-100 text-stone-600 border-stone-200',
        'not_yet_submitted'  => 'bg-stone-100 text-stone-500 border-stone-200',
        'not_submitted'      => 'bg-stone-100 text-stone-500 border-stone-200',
        'no_request'         => 'bg-stone-50 text-stone-400 border-stone-100',
    ];

    $classes = "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border tracking-wide " . ($colors[$normalized] ?? $colors['default']);

    $label = ucwords(str_replace('_', ' ', $status));
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot->isNotEmpty() ? $slot : $label }}
</span>
