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

        $this->regions = $this->fetchCached('psgc_regions', 'https://psgc.gitlab.io/api/regions');

        // Restore state if old values exist (e.g. from validation redirects)
        if (old('region_code')) {
            $this->selectedRegion = old('region_code');
            $regionCode = explode('|', $this->selectedRegion)[0];

            if (str_starts_with($regionCode, '13')) {
                $this->cities = $this->fetchCached("psgc_cities_region_{$regionCode}", "https://psgc.gitlab.io/api/regions/{$regionCode}/cities-municipalities");
            } else {
                $this->provinces = $this->fetchCached("psgc_provinces_region_{$regionCode}", "https://psgc.gitlab.io/api/regions/{$regionCode}/provinces");
            }
        }

        if (old('province_code')) {
            $this->selectedProvince = old('province_code');
            $provinceCode = explode('|', $this->selectedProvince)[0];

            $this->cities = $this->fetchCached("psgc_cities_province_{$provinceCode}", "https://psgc.gitlab.io/api/provinces/{$provinceCode}/cities-municipalities");
        }

        if (old('city_code')) {
            $this->selectedCity = old('city_code');
            $cityCode = explode('|', $this->selectedCity)[0];

            $this->barangays = $this->fetchCached("psgc_barangays_city_{$cityCode}", "https://psgc.gitlab.io/api/cities-municipalities/{$cityCode}/barangays");
        }

        if (old('barangay_code')) {
            $this->selectedBarangay = old('barangay_code');
        }
    }

    /**
     * Fetch a PSGC endpoint and cache it for 30 days — but only on success.
     * Caching a failed request's empty fallback would otherwise lock every
     * dropdown empty for the full 30 days regardless of the API recovering.
     */
    private function fetchCached(string $key, string $url): array
    {
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $response = Http::timeout(10)->get($url);
        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        usort($data, fn($a, $b) => strcmp($a['name'], $b['name']));
        Cache::put($key, $data, 2592000);

        return $data;
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
            $this->cities = $this->fetchCached("psgc_cities_region_{$regionCode}", "https://psgc.gitlab.io/api/regions/{$regionCode}/cities-municipalities");
        } else {
            $this->provinces = $this->fetchCached("psgc_provinces_region_{$regionCode}", "https://psgc.gitlab.io/api/regions/{$regionCode}/provinces");
        }
    }

    public function updatedSelectedProvince($value)
    {
        $this->reset(['selectedCity', 'selectedBarangay']);
        $this->barangays = [];

        if (!$value) return;

        $provinceCode = explode('|', $value)[0];

        $this->cities = $this->fetchCached("psgc_cities_province_{$provinceCode}", "https://psgc.gitlab.io/api/provinces/{$provinceCode}/cities-municipalities");
    }

    public function updatedSelectedCity($value)
    {
        $this->reset('selectedBarangay');

        if (!$value) return;

        $cityCode = explode('|', $value)[0];

        $this->barangays = $this->fetchCached("psgc_barangays_city_{$cityCode}", "https://psgc.gitlab.io/api/cities-municipalities/{$cityCode}/barangays");
    }

    public function render()
    {
        return view('livewire.address-selector');
    }
}
