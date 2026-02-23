<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\Barangay;

class AddressSelector extends Component
{
    public $selectedRegion = null;
    public $selectedProvince = null;
    public $selectedCity = null;
    public $selectedBarangay = null;

    public $regions = [];
    public $provinces = [];
    public $cities = [];
    public $barangays = [];

    public function mount()
    {
        $this->regions = Region::orderBy('name')->get();
        $this->provinces = collect();
        $this->cities = collect();
        $this->barangays = collect();
    }

    public function updatedSelectedRegion($value)
    {
        $this->provinces = Province::where('region_code', $value)->orderBy('name')->get();
        $this->selectedProvince = null;
        $this->cities = collect();
        $this->selectedCity = null;
        $this->barangays = collect();
        $this->selectedBarangay = null;
    }

    public function updatedSelectedProvince($value)
    {
        $this->cities = City::where('province_code', $value)->orderBy('name')->get();
        // Include NCR/HUC cities with province_code = null if region matches
        $this->cities = City::where('province_code', $value)
            ->orWhereNull('province_code') // include NCR/HUC cities
            ->orderBy('name')
            ->get();

        $this->selectedCity = null;
        $this->barangays = collect();
        $this->selectedBarangay = null;
    }

    public function updatedSelectedCity($value)
    {
        $this->barangays = Barangay::where('city_code', $value)->orderBy('name')->get();
        $this->selectedBarangay = null;
    }

    public function render()
    {
        return view('livewire.address-selector');
    }
}
