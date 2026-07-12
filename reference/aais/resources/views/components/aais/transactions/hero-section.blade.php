@props([
    'eyebrow' => 'Operations Center',
    'title' => 'Document Transactions',
    'subtitle' => 'Manage, monitor, and verify every document lifecycle update from intake to release.',
])

<section class="transactions-hero fade-in">
    <div class="transactions-hero-copy">
        <p class="transactions-eyebrow">{{ $eyebrow }}</p>
        <h1 class="transactions-title">{{ $title }}</h1>
        <p class="transactions-subtitle">{{ $subtitle }}</p>

        <div class="transactions-meta">
            <span class="transactions-meta-chip">
                <strong x-text="visibleCount"></strong>
                <span>Visible Now</span>
            </span>
            <span class="transactions-meta-chip">
                <strong x-text="statusCount('process')"></strong>
                <span>In Process</span>
            </span>
            <span class="transactions-meta-chip">
                <strong x-text="statusCount('pickup')"></strong>
                <span>For Pickup</span>
            </span>
        </div>
    </div>

    <div class="transactions-hero-aside">
        <p class="transactions-aside-label">Active Filter</p>
        <p class="transactions-aside-value" x-text="activeFilterLabel"></p>
        <p class="transactions-aside-note" x-text="officeFilter === 'All Offices' ? 'All offices included' : officeFilter"></p>
        <p class="transactions-last-updated" x-text="'Last updated: ' + relativeLastUpdated()"></p>
    </div>
</section>
