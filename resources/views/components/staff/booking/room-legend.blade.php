{{-- Colour key for the tap-to-pick room board. --}}
@php
    $keys = [
        ['label' => 'Available',   'swatch' => 'border border-clsu-300 bg-white'],
        ['label' => 'Selected',    'swatch' => 'bg-emerald-deep'],
        ['label' => 'Booked',      'swatch' => 'border border-ember-200 bg-ember-50'],
        ['label' => 'Cleaning',    'swatch' => 'border border-palay-300 bg-palay-100'],
        ['label' => 'Maintenance', 'swatch' => 'border border-stone-300 bg-stone-100'],
    ];
@endphp

<div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1.5">
    @foreach($keys as $key)
        <span class="flex items-center gap-1.5 text-2xs font-bold uppercase tracking-wider text-faint">
            <span class="h-2.5 w-2.5 rounded-[4px] {{ $key['swatch'] }}"></span> {{ $key['label'] }}
        </span>
    @endforeach
</div>
