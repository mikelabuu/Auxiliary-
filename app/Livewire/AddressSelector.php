<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AddressSelector extends Component
{
    public $theme = 'tailwind'; // 'tailwind' or 'bootstrap'

    public $selectedRegion = null;
    public $selectedProvince = null;
    public $selectedCity = null;
    public $selectedBarangay = null;
    
    public $regions = [];
    public $provinces = [];
    public $cities = [];
    public $barangays = [];
    
    public function mount($theme = 'tailwind')
    {
        $this->theme = $theme;

        // Fetch all regions from official API (cached for 30 days)
        $this->regions = Cache::remember('psgc_regions', 2592000, function () {
            $response = Http::get('https://psgc.gitlab.io/api/regions');
            $data = $response->successful() ? $response->json() : [];
            usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
            return $data;
        });

        // Restore state if old values exist (e.g. from validation redirects)
        if (old('region_code')) {
            $this->selectedRegion = old('region_code');
            $regionCode = explode('|', $this->selectedRegion)[0];

            if (str_starts_with($regionCode, '13')) {
                $this->cities = Cache::remember("psgc_cities_region_{$regionCode}", 2592000, function () use ($regionCode) {
                    $response = Http::get("https://psgc.gitlab.io/api/regions/{$regionCode}/cities-municipalities");
                    $data = $response->successful() ? $response->json() : [];
                    usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
                    return $data;
                });
            } else {
                $this->provinces = Cache::remember("psgc_provinces_region_{$regionCode}", 2592000, function () use ($regionCode) {
                    $response = Http::get("https://psgc.gitlab.io/api/regions/{$regionCode}/provinces");
                    $data = $response->successful() ? $response->json() : [];
                    usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
                    return $data;
                });
            }
        }

        if (old('province_code')) {
            $this->selectedProvince = old('province_code');
            $provinceCode = explode('|', $this->selectedProvince)[0];

            $this->cities = Cache::remember("psgc_cities_province_{$provinceCode}", 2592000, function () use ($provinceCode) {
                $response = Http::get("https://psgc.gitlab.io/api/provinces/{$provinceCode}/cities-municipalities");
                $data = $response->successful() ? $response->json() : [];
                usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
                return $data;
            });
        }

        if (old('city_code')) {
            $this->selectedCity = old('city_code');
            $cityCode = explode('|', $this->selectedCity)[0];

            $this->barangays = Cache::remember("psgc_barangays_city_{$cityCode}", 2592000, function () use ($cityCode) {
                $response = Http::get("https://psgc.gitlab.io/api/cities-municipalities/{$cityCode}/barangays");
                $data = $response->successful() ? $response->json() : [];
                usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
                return $data;
            });
        }

        if (old('barangay_code')) {
            $this->selectedBarangay = old('barangay_code');
        }
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
    
        if (!$value) return;

        $regionCode = explode('|', $value)[0];

        if (str_starts_with($regionCode, '13')) {
            $this->provinces = [];
            $this->cities = Cache::remember("psgc_cities_region_{$regionCode}", 2592000, function () use ($regionCode) {
                $response = Http::get("https://psgc.gitlab.io/api/regions/{$regionCode}/cities-municipalities");
                $data = $response->successful() ? $response->json() : [];
                usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
                return $data;
            });
        } else {
            $this->provinces = Cache::remember("psgc_provinces_region_{$regionCode}", 2592000, function () use ($regionCode) {
                $response = Http::get("https://psgc.gitlab.io/api/regions/{$regionCode}/provinces");
                $data = $response->successful() ? $response->json() : [];
                usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
                return $data;
            });
        }
    }

    public function updatedSelectedProvince($value)
    {
        $this->reset(['selectedCity', 'selectedBarangay']);
        $this->barangays = [];
    
        if (!$value) return;

        $provinceCode = explode('|', $value)[0];

        $this->cities = Cache::remember("psgc_cities_province_{$provinceCode}", 2592000, function () use ($provinceCode) {
            $response = Http::get("https://psgc.gitlab.io/api/provinces/{$provinceCode}/cities-municipalities");
            $data = $response->successful() ? $response->json() : [];
            usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
            return $data;
        });
    }

    public function updatedSelectedCity($value)
    {
        $this->reset('selectedBarangay');
    
        if (!$value) return;

        $cityCode = explode('|', $value)[0];

        $this->barangays = Cache::remember("psgc_barangays_city_{$cityCode}", 2592000, function () use ($cityCode) {
            $response = Http::get("https://psgc.gitlab.io/api/cities-municipalities/{$cityCode}/barangays");
            $data = $response->successful() ? $response->json() : [];
            usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
            return $data;
        });
    }

    public function render()
    {
        return view('livewire.address-selector');
    }
}
