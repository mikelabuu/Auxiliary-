@props(['theme' => 'tailwind'])

@php
    // Ids are namespaced per instance so a page may mount this twice without
    // the second set of labels rebinding onto the first set of controls. The
    // Livewire version used its component id for this; a Blade component has
    // no equivalent, so a counter serves the same purpose.
    static $instance = 0;
    $uid = 'addr-' . (++$instance);

    $bootstrap = $theme === 'bootstrap';

    $labelClass = $bootstrap
        ? 'form-label'
        : 'block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5';

    $selectClass = $bootstrap
        ? 'form-select'
        : 'w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-wait';

    // Carried across a validation redirect. The chain rebuilds itself from
    // these on init, so a failed submit does not wipe the address.
    $initial = [
        'region'   => old('region_code', ''),
        'province' => old('province_code', ''),
        'city'     => old('city_code', ''),
        'barangay' => old('barangay_code', ''),
    ];
@endphp

@once
<script>
/**
 * Region → Province → City → Barangay, entirely client-side.
 *
 * This used to be a Livewire component whose mount() called psgc.gitlab.io
 * from the server. Every dropdown cost a round-trip through Laravel and out to
 * a third party: measured at ~1.8s each, so a guest typing their address waited
 * the better part of four seconds, with no spinner, watching selects that
 * looked broken. It also put a third party on the checkout critical path.
 *
 * Now one ~83 KB fetch of /psgc/locations covers region, province and city with
 * no further requests, and only barangays are fetched per city (~0.4 KB). The
 * locations promise is shared at module scope so mounting twice fetches once.
 */
(function () {
    if (window.addressSelector) return;

    let locationsPromise = null;
    const loadLocations = () => {
        if (!locationsPromise) {
            locationsPromise = fetch('{{ route('psgc.locations') }}', { headers: { Accept: 'application/json' } })
                .then((r) => {
                    if (!r.ok) throw new Error('locations ' + r.status);
                    return r.json();
                })
                .catch((e) => {
                    // Reset so a later mount can retry rather than inheriting
                    // a permanently rejected promise.
                    locationsPromise = null;
                    throw e;
                });
        }
        return locationsPromise;
    };

    window.addressSelector = function (initial) {
        return {
            regions: [], provinces: [], cities: [], barangays: [],
            allProvinces: [], allCities: [],
            region: initial.region || '',
            province: initial.province || '',
            city: initial.city || '',
            barangay: initial.barangay || '',
            loading: true,
            loadingBarangays: false,
            failed: false,

            // The code half of a "CODE|NAME" value.
            codeOf(v) { return (v || '').split('|')[0]; },

            // NCR has no provinces: its cities hang straight off the region.
            // The field stays on the page either way so the grid does not
            // reflow when a region is picked.
            get provinceApplies() { return this.provinces.length > 0; },

            init() {
                loadLocations()
                    .then((d) => {
                        this.regions = d.regions || [];
                        this.allProvinces = d.provinces || [];
                        this.allCities = d.cities || [];
                        this.loading = false;
                        if (this.region) this.rebuild();
                    })
                    .catch(() => { this.loading = false; this.failed = true; });
            },

            /**
             * Which cities belong under the current region/province pair.
             *
             * A city either sits under a province, or is independent of one and
             * is reached through its region instead. All 17 NCR cities are the
             * second kind — but so are Cotabato City and Isabela City, which
             * live inside regions that DO have provinces. Treating "region has
             * no provinces" as the only way to reach province-less cities left
             * those two unselectable entirely.
             *
             * So the rule is about the province field, not the region: a chosen
             * province narrows to its own cities, and no province means the
             * independent cities of that region. Note this must match on
             * region as well — the empty-provinceCode rows span three regions,
             * so matching on empty alone offered Manila under Central Luzon.
             */
            applyCities() {
                const rc = this.codeOf(this.region);
                if (!rc) { this.cities = []; return; }

                const pc = this.codeOf(this.province);

                this.cities = pc
                    ? this.allCities.filter((c) => c[2] === pc)
                    : this.allCities.filter((c) => c[2] === '' && c[3] === rc);
            },

            // Recompute the dependent lists without clearing what is already
            // chosen — this is what restores the chain after a failed submit.
            rebuild() {
                this.provinces = this.allProvinces.filter((p) => p[2] === this.codeOf(this.region));
                this.applyCities();
                if (this.city) this.fetchBarangays();
            },

            onRegion() {
                this.province = ''; this.city = ''; this.barangay = '';
                this.barangays = [];
                this.rebuild();
            },

            onProvince() {
                this.city = ''; this.barangay = '';
                this.barangays = [];
                this.applyCities();
            },

            onCity() {
                this.barangay = ''; this.barangays = [];
                this.fetchBarangays();
            },

            fetchBarangays() {
                const code = this.codeOf(this.city);
                if (!code) return;
                this.loadingBarangays = true;
                fetch('{{ url('psgc/barangays') }}/' + encodeURIComponent(code), { headers: { Accept: 'application/json' } })
                    .then((r) => r.json())
                    .then((d) => { this.barangays = d.barangays || []; })
                    .catch(() => { this.barangays = []; })
                    .finally(() => { this.loadingBarangays = false; });
            },
        };
    };
})();
</script>
@endonce

<div x-data="addressSelector(@js($initial))"
     class="{{ $bootstrap ? '' : 'grid grid-cols-1 sm:grid-cols-2 gap-4' }}">

    <div class="{{ $bootstrap ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-region" class="{{ $labelClass }}">Region</label>
        <select id="{{ $uid }}-region" name="region_code" class="{{ $selectClass }}"
                autocomplete="address-level1" required
                x-model="region" x-on:change="onRegion()" x-bind:disabled="loading">
            <option value="">Select Region</option>
            <template x-for="r in regions" :key="r[0]">
                <option :value="r[0] + '|' + r[1]" x-text="r[1]"></option>
            </template>
        </select>
        {{-- Only surfaces if the address data cannot be reached at all. The
             form is still submittable; this explains the empty dropdowns
             rather than leaving them looking broken. --}}
        <p x-show="failed" x-cloak class="mt-1.5 text-xs text-red-600">
            Address list unavailable. Please refresh the page.
        </p>
    </div>

    {{-- Always rendered. Conditionally *inserting* it pushed City and Barangay
         around the grid the moment a region was picked, which is a layout jump
         in the middle of a form. --}}
    <div class="{{ $bootstrap ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-province" class="{{ $labelClass }}">Province</label>
        <select id="{{ $uid }}-province" name="province_code" class="{{ $selectClass }}"
                autocomplete="address-level1"
                x-model="province" x-on:change="onProvince()"
                x-bind:disabled="loading || !region || !provinceApplies">
            <option value="" x-text="region && !provinceApplies ? 'Not applicable' : 'Select Province'"></option>
            <template x-for="p in provinces" :key="p[0]">
                <option :value="p[0] + '|' + p[1]" x-text="p[1]"></option>
            </template>
        </select>
    </div>

    <div class="{{ $bootstrap ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-city" class="{{ $labelClass }}">City / Municipality</label>
        <select id="{{ $uid }}-city" name="city_code" class="{{ $selectClass }}"
                autocomplete="address-level2" required
                x-model="city" x-on:change="onCity()"
                x-bind:disabled="loading || !cities.length">
            <option value="">Select City / Municipality</option>
            <template x-for="c in cities" :key="c[0]">
                <option :value="c[0] + '|' + c[1]" x-text="c[1]"></option>
            </template>
        </select>
    </div>

    <div class="{{ $bootstrap ? 'mb-3' : '' }}">
        <label for="{{ $uid }}-barangay" class="{{ $labelClass }}">Barangay</label>
        <select id="{{ $uid }}-barangay" name="barangay_code" class="{{ $selectClass }}"
                autocomplete="address-level3" required
                x-model="barangay" x-bind:disabled="loadingBarangays || !barangays.length">
            <option value="" x-text="loadingBarangays ? 'Loading barangays…' : 'Select Barangay'"></option>
            <template x-for="b in barangays" :key="b[0]">
                <option :value="b[0] + '|' + b[1]" x-text="b[1]"></option>
            </template>
        </select>
    </div>
</div>
