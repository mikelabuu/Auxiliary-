@php
    // Each <label> here sat visually above its <select> but was never bound to
    // it, so assistive tech announced four unnamed comboboxes. axe reported it
    // as `select-name` (critical) on all three pages that mount this component:
    // public checkout, staff manual booking and front-desk walk-in.
    //
    // Ids are namespaced by the Livewire component id rather than hardcoded,
    // because a page is free to mount this twice and duplicate ids would rebind
    // the second set of labels onto the first set of controls.
    $uid = 'addr-' . $this->getId();

    $labelClass = $theme === 'bootstrap'
        ? 'form-label'
        : 'block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5';

    $selectClass = $theme === 'bootstrap'
        ? 'form-select'
        : 'w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors cursor-pointer';
@endphp

<div class="{{ $theme === 'bootstrap' ? '' : 'grid grid-cols-1 sm:grid-cols-2 gap-4' }}">
    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-region" class="{{ $labelClass }}">Region</label>
        <select wire:model.live="selectedRegion" id="{{ $uid }}-region" name="region_code"
                class="{{ $selectClass }}" autocomplete="address-level1" required>
            <option value="">Select Region</option>
            @foreach($regions as $region)
                <option value="{{ $region['code'] }}|{{ $region['name'] }}">
                    {{ $region['name'] }}
                </option>
            @endforeach
        </select>
    </div>

    @if(!empty($provinces))
    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-province" class="{{ $labelClass }}">Province</label>
        <select wire:model.live="selectedProvince" id="{{ $uid }}-province" name="province_code"
                class="{{ $selectClass }}" autocomplete="address-level1">
            <option value="">Select Province</option>
            @foreach($provinces as $province)
                <option value="{{ $province['code'] }}|{{ $province['name'] }}">
                    {{ $province['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-city" class="{{ $labelClass }}">City / Municipality</label>
        <select wire:model.live="selectedCity" id="{{ $uid }}-city" name="city_code"
                class="{{ $selectClass }}" autocomplete="address-level2" required>
            <option value="">Select City / Municipality</option>
            @foreach($cities as $city)
                <option value="{{ $city['code'] }}|{{ $city['name'] }}">
                    {{ $city['name'] }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-barangay" class="{{ $labelClass }}">Barangay</label>
        <select wire:model.live="selectedBarangay" id="{{ $uid }}-barangay" name="barangay_code"
                class="{{ $selectClass }}" autocomplete="address-level3" required>
            <option value="">Select Barangay</option>
            @foreach($barangays as $barangay)
                <option value="{{ $barangay['code'] }}|{{ $barangay['name'] }}">
                    {{ $barangay['name'] }}
                </option>
            @endforeach
        </select>
    </div>
</div>
