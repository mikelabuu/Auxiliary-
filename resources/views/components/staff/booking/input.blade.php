@props(['align' => 'left'])

{{--
    Single home for the staff booking form's field styling. It was pasted
    inline 13 times per page across two pages; changing the focus ring meant
    26 edits and getting one wrong was invisible until someone tabbed into it.

    `align="center"` is the numeric variant (guest/senior steppers): centred,
    bold, and with the spinner arrows removed so the -/+ buttons are the only
    affordance.
--}}
@php
    $base = 'w-full rounded-xl border border-emerald-deep/15 bg-white text-ink transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none';

    $variant = $align === 'center'
        ? 'px-2 py-2 text-center text-sm font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none'
        : 'px-4 py-2.5 text-sm placeholder:text-faint';
@endphp

<input {{ $attributes->merge(['class' => $base . ' ' . $variant]) }}>
