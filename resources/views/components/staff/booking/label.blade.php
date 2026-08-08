@props(['for' => null])

{{-- The small emerald caps label used by every field on the staff booking form.
     The old colour (--color-emerald, #16b364) is a fill: as type it reads
     2.56:1, so every field label on the manual-booking form failed AA.
     text-brand-ink is the same hue held to 5.85:1. --}}
<label @if($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'mb-1.5 block text-2xs font-bold uppercase tracking-[0.24em] text-brand-ink']) }}>{{ $slot }}</label>
