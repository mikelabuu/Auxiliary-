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
        $this->regions = Region::orderBy('regDesc')->get();
    }

    public function updatedSelectedRegion($value)
    {
        $this->reset([
            'selectedProvince',
            'selectedCity',
            'selectedBarangay'
        ]);
    
        $this->cities = [];
        $this->barangays = [];
    
        // NCR special case
        if ($value == '13') {
    
            // Skip provinces
            $this->provinces = collect();
    
            // Load cities directly by regCode
            $this->cities = City::where('regCode', $value)
                                ->orderBy('citymunDesc')
                                ->get();
    
        } else {
    
            // Normal flow
            $this->provinces = Province::where('regCode', $value)
                                       ->orderBy('provDesc')
                                       ->get();
        }
    }


    public function updatedSelectedProvince($value)
    {
        $this->reset(['selectedCity', 'selectedBarangay']);
        $this->barangays = [];
    
        $this->cities = City::where('provCode', $value)
                            ->orderBy('citymunDesc')
                            ->get();
    }

    public function updatedSelectedCity($value)
    {
        $this->reset('selectedBarangay');
    
        $this->barangays = Barangay::where('citymunCode', $value)
                                   ->orderBy('brgyDesc')
                                   ->get();
    }

    public function render()
    {
        return view('livewire.address-selector');
    }
}
