@props([
    'greeting',
    'name' => 'Admin',
    'subtitle' => "Here's what's happening with your documents today.",
])

<div class="dashboard-greeting-row">
    <div class="dashboard-greeting-copy">
        <h1 class="dashboard-greeting-title">
            {{ $greeting }}, {{ $name }}
        </h1>
        <p class="dashboard-greeting-subtitle">{{ $subtitle }}</p>
    </div>

    <div class="dashboard-refresh-panel">
        <p class="dashboard-refresh-label">System freshness</p>
        <p class="dashboard-refresh-time" x-text="'Last refreshed: ' + relativeFrom(lastRefreshedAt)"></p>
        <button type="button" class="btn btn-outline btn-sm dashboard-refresh-btn" @click="refreshDashboardData(true)">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4"/></svg>
            Refresh Now
        </button>
    </div>
</div>
