<div class="filter-bar transactions-filter">
    <div class="transactions-filter-search-row">
        <div class="transactions-search-wrap transactions-search-wrap-full">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" x-model="searchQuery" @input="quickFilter = ''" placeholder="Search ref, client, type, office, or staff..." class="form-input form-input-sm transactions-search-input" aria-label="Search transactions">
        </div>
    </div>

    <div class="transactions-filter-bottom-row">
        <div class="transactions-filter-tabs">
            <span class="section-label transactions-section-label">Status</span>
            <template x-for="f in filters" :key="f.value">
                <button
                    type="button"
                    class="filter-tab transactions-filter-tab"
                    :class="{
                        'selected': activeFilter === f.value,
                        'transactions-filter-tab-all': f.value === 'all',
                        'transactions-filter-tab-all-active': f.value === 'all' && activeFilter === 'all'
                    }"
                    @click="activeFilter = f.value; quickFilter = ''"
                    x-text="f.label"
                ></button>
            </template>
        </div>

        <div class="transactions-filter-controls">
            <select class="form-select form-input-sm transactions-office-select" x-model="officeFilter" @change="quickFilter = ''" aria-label="Filter by office">
                <template x-for="office in offices" :key="office">
                    <option x-text="office"></option>
                </template>
            </select>

            <select class="form-select form-input-sm transactions-date-select" x-model="dateRangeFilter" @change="quickFilter = ''" aria-label="Quick date range filter">
                <template x-for="range in quickDateRanges" :key="range.value">
                    <option :value="range.value" x-text="range.label"></option>
                </template>
            </select>

            <button type="button" class="btn btn-outline btn-sm" x-show="hasFilters" x-transition.opacity @click="resetFilters()">Reset</button>
        </div>
    </div>

    <div class="transactions-quick-filters" aria-label="Quick filters">
        <span class="section-label transactions-quick-filters-label">Quick Filters</span>
        <button type="button" class="transactions-quick-filter-chip" :class="{ 'active': quickFilter === 'this-week' }" @click="applyQuickFilter('this-week')">This Week</button>
        <button type="button" class="transactions-quick-filter-chip" :class="{ 'active': quickFilter === 'admissions-only' }" @click="applyQuickFilter('admissions-only')">Admissions Only</button>
        <button type="button" class="transactions-quick-filter-chip" :class="{ 'active': quickFilter === 'pickup-only' }" @click="applyQuickFilter('pickup-only')">For Pickup Only</button>
    </div>
</div>
