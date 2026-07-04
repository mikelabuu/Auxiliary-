<div>
    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : 'mb-4' }}">
        <label class="{{ $theme === 'bootstrap' ? 'form-label' : 'block text-xs font-bold text-slate-700 tracking-wider uppercase mb-1.5' }}">Region</label>
        <select wire:model.live="selectedRegion" class="{{ $theme === 'bootstrap' ? 'form-select' : 'w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-850 text-sm focus:bg-white focus:border-brand focus:ring-1 focus:ring-brand outline-none transition-all cursor-pointer' }}" name="region_code" autocomplete="address-level1" required>
            <option value="">Select Region</option>
            @foreach($regions as $region)
                <option value="{{ $region['code'] }}|{{ $region['name'] }}">
                    {{ $region['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    
    @if(!empty($provinces))
    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : 'mb-4' }}">
        <label class="{{ $theme === 'bootstrap' ? 'form-label' : 'block text-xs font-bold text-slate-700 tracking-wider uppercase mb-1.5' }}">Province</label>
        <select wire:model.live="selectedProvince" class="{{ $theme === 'bootstrap' ? 'form-select' : 'w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-850 text-sm focus:bg-white focus:border-brand focus:ring-1 focus:ring-brand outline-none transition-all cursor-pointer' }}" name="province_code" autocomplete="address-level1">
            <option value="">Select Province</option>
            @foreach($provinces as $province)
                <option value="{{ $province['code'] }}|{{ $province['name'] }}">
                    {{ $province['name'] }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : 'mb-4' }}">
        <label class="{{ $theme === 'bootstrap' ? 'form-label' : 'block text-xs font-bold text-slate-700 tracking-wider uppercase mb-1.5' }}">City / Municipality</label>
        <select wire:model.live="selectedCity" class="{{ $theme === 'bootstrap' ? 'form-select' : 'w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-850 text-sm focus:bg-white focus:border-brand focus:ring-1 focus:ring-brand outline-none transition-all cursor-pointer' }}" name="city_code" autocomplete="address-level2" required>
            <option value="">Select City / Municipality</option>
            @foreach($cities as $city)
                <option value="{{ $city['code'] }}|{{ $city['name'] }}">
                    {{ $city['name'] }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="{{ $theme === 'bootstrap' ? 'mb-3' : 'mb-4' }}">
        <label class="{{ $theme === 'bootstrap' ? 'form-label' : 'block text-xs font-bold text-slate-700 tracking-wider uppercase mb-1.5' }}">Barangay</label>
        <select wire:model.live="selectedBarangay" name="barangay_code" class="{{ $theme === 'bootstrap' ? 'form-select' : 'w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-850 text-sm focus:bg-white focus:border-brand focus:ring-1 focus:ring-brand outline-none transition-all cursor-pointer' }}" autocomplete="address-level3" required>
            <option value="">Select Barangay</option>
            @foreach($barangays as $barangay)
                <option value="{{ $barangay['code'] }}|{{ $barangay['name'] }}">
                    {{ $barangay['name'] }}
                </option>
            @endforeach
        </select>
    </div>
</div>
