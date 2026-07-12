<div class="transactions-empty-state" x-show="visibleCount === 0" x-transition.opacity>
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M8 11h6"/></svg>
    <p>No transactions match your current filters.</p>
    <button type="button" class="btn btn-outline btn-sm" @click="resetFilters()">Clear Filters</button>
</div>
