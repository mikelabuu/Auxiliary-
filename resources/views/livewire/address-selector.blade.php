<div>
    <div class="mb-2">
        <label class="block font-medium">Region</label>
        <select wire:model.live="selectedRegion" class="w-full border rounded p-2" name="region_code">
            <option value="">Select Region</option>
            @foreach($regions as $region)
                <option value="{{ $region->regCode }}">
                    {{ $region->regDesc }}
                </option>
            @endforeach
        </select>
    </div>
    @if($selectedRegion != '13')
    <div class="mb-2">
        <label class="block font-medium">Province</label>
        <select wire:model.live="selectedProvince" class="w-full border rounded p-2" name="province_code">
            <option value="">Select Province</option>
            @foreach($provinces as $province)
                <option value="{{ $province->provCode }}">
                    {{ $province->provDesc }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="mb-2">
        <label class="block font-medium">City / Municipality</label>
        <select wire:model.live="selectedCity" class="w-full border rounded p-2" name="city_code">
            <option value="">Select City / Municipality</option>
            @foreach($cities as $city)
                <option value="{{ $city->citymunCode }}">
                    {{ $city->citymunDesc }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block font-medium">Barangay</label>
        <select wire:model.live="selectedBarangay" name="barangay_code" class="w-full border rounded p-2">
            <option value="">Select Barangay</option>
            @foreach($barangays as $barangay)
                <option value="{{ $barangay->brgyCode }}">
                    {{ $barangay->brgyDesc }}
                </option>
            @endforeach
        </select>
    </div>
</div>
