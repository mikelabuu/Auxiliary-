{{-- One-tap stay lengths. `data-preset` is what the page script binds to. --}}
@php
    $presets = [
        'tonight' => 'Tonight',
        'two'     => '2 nights',
        'three'   => '3 nights',
        'weekend' => 'Weekend',
    ];
@endphp

<div class="mt-4 flex flex-wrap items-center gap-2">
    <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400">Quick pick</span>
    @foreach($presets as $value => $label)
        <button type="button" data-preset="{{ $value }}" class="stay-preset press cursor-pointer rounded-full border border-emerald-deep/15 bg-white px-3.5 py-1.5 text-[11px] font-bold text-emerald-deep transition-colors hover:border-clsu-400 hover:bg-clsu-50">{{ $label }}</button>
    @endforeach
</div>
