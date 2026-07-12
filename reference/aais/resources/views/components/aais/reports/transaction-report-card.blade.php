@props([
    'docs' => [],
    'statusLabels' => [],
    'title' => 'Transaction Report',
    'todayLabel' => null,
])

@php
    $today = $todayLabel ?? now()->format('M j, Y');
@endphp

<div class="card">
    <x-aais.ui.card-header
        :title="$title"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z'/></svg>"
    >
        <x-slot:actions>
            <div class="reports-header-controls">
                <div class="reports-date-controls">
                    <select class="form-select form-input-sm reports-date-select" x-model="dateRange" @change="applyDatePreset(dateRange)" aria-label="Select date range preset">
                    <option value="this_week">This Week</option><option value="this_month">This Month</option><option value="last_month">Last Month</option><option value="all_time">All Time</option><option value="custom">Custom</option>
                    </select>
                    <div class="reports-date-range-compact">
                        <input type="date" class="form-input form-input-sm reports-date-input" x-model="dateFrom" aria-label="From date">
                        <span class="text-faint reports-date-separator">to</span>
                        <input type="date" class="form-input form-input-sm reports-date-input" x-model="dateTo" aria-label="To date">
                    </div>
                </div>
                <div class="reports-export-controls">
                    <span class="reports-updated-meta" x-text="'Last updated: ' + relativeLastUpdated()"></span>
                    <button class="export-btn export-btn-excel" @click="openExportModal('csv')" aria-label="Open export options">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        Export
                    </button>
                </div>
            </div>
        </x-slot:actions>
    </x-aais.ui.card-header>

    <div class="filter-bar reports-filter-bar">
        <div class="reports-filter-search-row">
            <div class="reports-search-wrap">
                <input type="text" x-model="searchQuery" placeholder="Search client or ref..." class="form-input form-input-sm reports-search-input" aria-label="Search by client name or reference code">
                <button
                    type="button"
                    class="reports-search-clear"
                    x-show="searchQuery.trim().length > 0"
                    x-cloak
                    @click="searchQuery = ''"
                    aria-label="Clear search"
                    title="Clear search"
                >&times;</button>
            </div>
        </div>
        <div class="reports-filter-bottom">
            <div class="reports-office-tabs">
                <span class="section-label">Office:</span>
                <template x-for="f in officeFilters" :key="f">
                    <span class="filter-tab" :class="{ 'selected': activeOffice === f }" @click="activeOffice = f" x-text="f"></span>
                </template>
            </div>
            <div class="reports-filter-right">
                <span class="section-label reports-quick-label">Date:</span>
                <div class="reports-quick-chips">
                    <button type="button" class="reports-quick-chip" :class="{ 'active': quickDateRange === 'any' }" @click="setQuickDateRange('any')">Any Date</button>
                    <button type="button" class="reports-quick-chip" :class="{ 'active': quickDateRange === 'this_week' }" @click="setQuickDateRange('this_week')">This Week</button>
                    <button type="button" class="reports-quick-chip" :class="{ 'active': quickDateRange === 'last_30' }" @click="setQuickDateRange('last_30')">Last 30 Days</button>
                    <button type="button" class="reports-quick-chip" :class="{ 'active': quickDateRange === 'custom' }" @click="setQuickDateRange('custom')">Custom</button>
                </div>
            </div>
        </div>
    </div>

    <div class="selection-bar selection-bar-soft" x-show="selectedRows.length > 0" x-transition>
        <span class="selection-count" x-text="selectedStatusSummary"></span>
        <button class="btn btn-sm btn-outline" @click="selectedRows = []">Deselect All</button>
        <button class="btn btn-sm btn-outline" @click="bulkExportSelected()">Bulk Export</button>
        <button class="btn btn-sm btn-primary" @click="bulkComplete()">Mark as Complete</button>
        <button class="btn btn-sm btn-danger" @click="bulkVoid()">Bulk Void</button>
    </div>

    <div class="scroll-x">
        <table class="data-table reports-table">
            <thead>
                <tr>
                    <th><input type="checkbox" class="row-check" @change="toggleAll($event.target.checked)" :checked="selectedRows.length > 0 && selectedRows.length === visibleCount"></th>
                    <th>Ref Code</th><th>Client Name</th><th>Document Type</th>
                    <th>Received</th><th>Released</th><th>Office</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($docs as $idx => $doc)
                    <tr x-show="isVisible({{ $idx }})" x-transition :class="{ 'table-row-selected': selectedRows.includes({{ $idx }}), 'reports-row-zebra': rowHasZebra({{ $idx }}) }">
                        <td><input type="checkbox" class="row-check" :checked="selectedRows.includes({{ $idx }})" @change="toggleRow({{ $idx }}, $event.target.checked)"></td>
                        <td>
                            <button type="button" class="reports-ref-link" @click="openViewModal({{ $idx }})" title="Open transaction details">
                                <span class="ref-code">{{ $doc['ref'] }}</span>
                            </button>
                        </td>
                        <td class="cell-strong">{{ $doc['name'] }}</td>
                        <td>{{ $doc['type'] }}</td>
                        <td class="text-muted">{{ $doc['received'] }}</td>
                        <td class="text-muted" x-text="docs[{{ $idx }}].status === 'complete' && docs[{{ $idx }}].released === '—' ? '{{ $today }}' : docs[{{ $idx }}].released"></td>
                        <td><span class="chip chip-muted">{{ $doc['office'] }}</span></td>
                        <td>
                            <span :title="statusTooltip(docs[{{ $idx }}])">
                                <x-aais.ui.status-badge-dynamic
                                    class-expr="'status-' + docs[{{ $idx }}].status"
                                    text-expr="labels[docs[{{ $idx }}].status]"
                                />
                            </span>
                        </td>
                        <td>
                            <div class="reports-row-actions">
                                <button class="btn btn-outline btn-sm reports-action-btn" title="View Details" aria-label="View details" @click="openViewModal({{ $idx }})">
                                    View
                                </button>
                                <button class="btn btn-outline btn-sm reports-action-btn" title="Update Status" aria-label="Update status" @click="openEditModal({{ $idx }})">
                                    Edit
                                </button>
                                <div class="reports-action-menu" x-data="{ open: false }" @keydown.escape.stop="open = false">
                                    <button type="button" class="btn btn-ghost btn-sm btn-icon reports-menu-trigger" aria-label="More row actions" @click.stop="open = !open" @click.away="open = false">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>
                                    </button>
                                    <div class="reports-menu-panel" x-show="open" x-transition.origin.top.right.duration.150ms x-cloak>
                                        <button class="reports-menu-item reports-menu-item-danger" @click="open = false; requestVoid({{ $idx }})" x-show="docs[{{ $idx }}].status !== 'void' && docs[{{ $idx }}].status !== 'complete' && docs[{{ $idx }}].status !== 'pickup'">
                                            Void transaction
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <span class="reports-footer-meta" x-text="showingRangeText"></span>
        <div class="reports-pagination">
            <button class="btn btn-outline btn-sm" disabled>&larr; Prev</button>
            <button class="btn btn-primary btn-sm">1</button>
            <button class="btn btn-outline btn-sm">2</button>
            <button class="btn btn-outline btn-sm">Next &rarr;</button>
        </div>
    </div>
</div>
