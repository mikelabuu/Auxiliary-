@props([
    'activities' => [],
    'statusLabels' => [],
    'quickActions' => [],
    'breakdown' => [],
    'activityLogUrl' => null,
])

@php
    $resolvedActivityLogUrl = $activityLogUrl ?? route('aais.admin.transactions');
@endphp

<div class="dashboard-secondary-grid">
    <div class="card" style="overflow:hidden;">
        <x-aais.ui.card-header
            title="Recent Activity"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 16 14'/></svg>"
        >
            <x-slot:actions>
                <div class="dashboard-activity-filters">
                    <button type="button" class="btn btn-ghost btn-sm dashboard-activity-filter-btn" :class="{ 'dashboard-activity-filter-active': activityRange === 'today' }" @click="setActivityRange('today')">Today</button>
                    <button type="button" class="btn btn-ghost btn-sm dashboard-activity-filter-btn" :class="{ 'dashboard-activity-filter-active': activityRange === 'week' }" @click="setActivityRange('week')">This Week</button>
                </div>
            </x-slot:actions>
        </x-aais.ui.card-header>
        <div class="card-body">
            <div class="timeline">
                @foreach ($activities as $act)
                    @php
                        $activityScope = $act['scope'] ?? 'today';
                        $metaSuffix = $activityScope === 'week' ? 'this week' : 'today';
                    @endphp
                    <div x-show="activityRange === 'week' || '{{ $activityScope }}' === 'today'" x-transition.opacity.duration.200ms>
                        <x-aais.ui.timeline-item
                            :title="$act['msg']"
                            :meta="$act['time'] . ' ' . $metaSuffix"
                            :done="$act['done']"
                            :active="!$act['done']"
                            :status="$act['status']"
                            :status-label="$statusLabels[$act['status']]"
                            :last="$loop->last"
                        />
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer dashboard-activity-footer">
            <a href="{{ $resolvedActivityLogUrl }}" class="dashboard-activity-log-link">View full activity log &rarr;</a>
        </div>
    </div>

    <div class="dashboard-aside-stack">
        <div class="card" style="overflow:hidden;">
            <x-aais.ui.card-header
                title="Quick Actions"
                icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M13 10V3L4 14h7v7l9-11h-7z'/></svg>"
            />
            <div class="card-body dashboard-quick-actions-body">
                @foreach ($quickActions as $action)
                    <a href="{{ $action['href'] }}" class="btn dashboard-quick-action-link {{ ($action['variant'] ?? 'outline') === 'primary' ? 'btn-primary' : 'btn-outline' }}">
                        @if (!empty($action['icon']))
                            {!! $action['icon'] !!}
                        @endif
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="card" style="overflow:hidden;">
            <x-aais.ui.card-header
                title="Status Breakdown"
                icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><path d='M12 8v4l3 3'/></svg>"
            />
            <div class="card-body dashboard-breakdown-body">
                @foreach ($breakdown as $item)
                    <div class="dashboard-breakdown-row">
                        <x-aais.ui.status-badge :status="$item['status']" :label="$item['label']" :center="true" />
                        <div class="dashboard-breakdown-track">
                            <div class="dashboard-breakdown-fill" style="width:{{ $item['pct'] }}%;"></div>
                        </div>
                        <span class="dashboard-breakdown-count">{{ $item['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
