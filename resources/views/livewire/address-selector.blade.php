<div>
    <div class="mb-2">
        <label class="block font-medium">Region</label>
        <select wire:model.live="selectedRegion" class="w-full border rounded p-2" name="region_code">
            <option value="">Select Region</option>
            @foreach($regions as $region)
                <option value="{{ $region->code }}">{{ $region->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-2">
        <label class="block font-medium">Province</label>
        <select wire:model.live="selectedProvince" class="w-full border rounded p-2" name="province_code">
            <option value="">Select Province</option>
            @foreach($provinces as $province)
                <option value="{{ $province->code }}">{{ $province->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-2">
        <label class="block font-medium">City / Municipality</label>
        <select wire:model.live="selectedCity" class="w-full border rounded p-2" name="city_code">
            <option value="">Select City / Municipality</option>
            @foreach($cities as $city)
                <option value="{{ $city->code }}">{{ $city->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-2">
        <label class="block font-medium">Barangay</label>
        <input type="text" name="baranggay_code" class="w-full border rounded p-2" required>
    </div>
</div>
