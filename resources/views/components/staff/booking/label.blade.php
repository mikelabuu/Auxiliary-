@props(['for' => null])

{{-- The small emerald caps label used by every field on the staff booking form. --}}
<label @if($for) for="{{ $for }}" @endif {{ $attributes->merge(['class' => 'mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald']) }}>{{ $slot }}</label>
