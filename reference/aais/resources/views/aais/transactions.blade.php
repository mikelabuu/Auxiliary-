@php
    $title     = 'Transactions Logs';
    $role      = 'Admin';
    $topbarSub = 'Complete log of all document transactions and updates';

    $docs = [
        ['ref'=>'TL-2026-0412','name'=>'Maria Santos','type'=>'Transcript of Records','office'=>'Registrar','staff'=>'V. Santos','received'=>'Apr 1, 2026','status'=>'process'],
        ['ref'=>'TL-2026-0411','name'=>'Juan Dela Cruz','type'=>'Certificate of Enrollment','office'=>'Admissions','staff'=>'R. Reyes','received'=>'Apr 1, 2026','status'=>'pickup'],
        ['ref'=>'TL-2026-0410','name'=>'Ana Reyes','type'=>'Good Moral Certificate','office'=>'OSAS','staff'=>'P. Flores','received'=>'Mar 31, 2026','status'=>'approved'],
        ['ref'=>'TL-2026-0409','name'=>'Carlo Mendoza','type'=>'Diploma Authentication','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 31, 2026','status'=>'complete'],
        ['ref'=>'TL-2026-0408','name'=>'Rosa Garcia','type'=>'CAV Document','office'=>'Records','staff'=>'M. Torres','received'=>'Mar 30, 2026','status'=>'logged'],
        ['ref'=>'TL-2026-0407','name'=>'Kevin Lim','type'=>'Transfer Credentials','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 30, 2026','status'=>'void'],
        ['ref'=>'TL-2026-0406','name'=>'Liza Bautista','type'=>'Honorable Dismissal','office'=>'OSAS','staff'=>'P. Flores','received'=>'Mar 29, 2026','status'=>'complete'],
        ['ref'=>'TL-2026-0405','name'=>'Mark Cruz','type'=>'Transcript of Records','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 29, 2026','status'=>'process'],
        ['ref'=>'TL-2026-0404','name'=>'Julieta San Jose','type'=>'Certificate of Units Earned','office'=>'Registrar','staff'=>'J. Domingo','received'=>'Mar 28, 2026','status'=>'complete'],
        ['ref'=>'TL-2026-0403','name'=>'Luis Alcantara','type'=>'Authentication','office'=>'Registrar','staff'=>'J. Domingo','received'=>'Mar 28, 2026','status'=>'pickup'],
    ];

    $statusLabels = ['logged'=>'Logged','process'=>'In Process','approved'=>'Approved','pickup'=>'For Pickup','complete'=>'Completed','void'=>'Voided'];

    $kpis = [
        ['value'=>'842','label'=>'Total Logged','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z"/><path d="M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>','bg'=>'stat-icon-blue','trend'=>'+14 this week','up'=>true],
        ['value'=>'12','label'=>'In Process','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>','bg'=>'stat-icon-gold','trend'=>'Needs action','up'=>false],
        ['value'=>'3','label'=>'For Pickup','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20V10M18 20V4M6 20v-6"/></svg>','bg'=>'stat-icon-rose','trend'=>'Ready to release','up'=>true],
        ['value'=>'4,120','label'=>'Completed','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>','bg'=>'stat-icon-green','trend'=>'+18 today','up'=>true],
    ];
@endphp

@extends('layouts.admin')

@section('content')
<style>
    .transactions-page {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }
    .transactions-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
        border: 1px solid rgba(11, 95, 42, 0.14);
        border-radius: 22px;
        padding: 26px 28px;
        background: linear-gradient(120deg, var(--color-g-800) 0%, var(--color-g-900) 32%, var(--color-g-700) 68%, var(--color-g-600) 100%);
        background-size: 150% 150%;
        color: #ffffff;
        box-shadow: 0 20px 38px -28px rgba(6, 26, 14, 0.82);
        animation: tx-hero-flow 14s ease-in-out infinite;
    }
    .transactions-hero::before,
    .transactions-hero::after {
        content: '';
        position: absolute;
        pointer-events: none;
    }
    .transactions-hero::before {
        top: -120px;
        left: -80px;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 70%);
    }
    .transactions-hero::after {
        right: -50px;
        bottom: -100px;
        width: 290px;
        height: 290px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(242, 195, 0, 0.28) 0%, rgba(242, 195, 0, 0) 68%);
    }
    .transactions-hero-copy,
    .transactions-hero-aside {
        position: relative;
        z-index: 1;
    }
    .transactions-eyebrow {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        opacity: 0.86;
    }
    .transactions-title {
        margin-top: 6px;
        font-size: 30px;
        font-weight: 900;
        letter-spacing: -0.03em;
        line-height: 1.1;
    }
    .transactions-subtitle {
        margin-top: 8px;
        max-width: 640px;
        font-size: 13px;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.88);
    }
    .transactions-meta {
        margin-top: 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .transactions-meta-chip {
        display: inline-flex;
        align-items: baseline;
        gap: 7px;
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        backdrop-filter: blur(10px);
    }
    .transactions-meta-chip strong {
        font-size: 14px;
        font-weight: 900;
        line-height: 1;
    }
    .transactions-meta-chip span {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        opacity: 0.94;
    }
    .transactions-hero-aside {
        min-width: 210px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        border-radius: 16px;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        text-align: left;
    }
    .transactions-aside-label {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        opacity: 0.8;
    }
    .transactions-aside-value {
        margin-top: 6px;
        font-size: 21px;
        font-weight: 900;
        letter-spacing: -0.02em;
        line-height: 1.1;
    }
    .transactions-aside-note {
        margin-top: 8px;
        font-size: 11px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.84);
    }
    .transactions-last-updated {
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.24);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.74);
    }
    .transactions-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 28px;
    }
    .transactions-kpi-grid .stat-card-pickup {
        border-color: #fdba74;
        background: linear-gradient(180deg, #fffaf2 0%, #ffffff 100%);
    }
    .transactions-kpi-grid .stat-card-pickup::before {
        opacity: 1;
        background: linear-gradient(90deg, #f59e0b, #f97316);
    }
    .transactions-kpi-grid .stat-card-pickup .stat-trend {
        background: #fff7ed;
        border: 1px solid #fdba74;
        color: #9a3412;
    }
    .transactions-kpi-grid .stat-card-needs-action {
        border-color: #fdba74;
        background: linear-gradient(180deg, #fffaf2 0%, #ffffff 100%);
    }
    .transactions-kpi-grid .stat-card-needs-action::before {
        opacity: 1;
        background: linear-gradient(90deg, #f59e0b, #f97316);
    }
    .transactions-log-card {
        overflow: hidden;
        border-color: #dbe3ec;
    }
    .transactions-log-header {
        gap: 10px;
        padding-top: 16px;
        padding-bottom: 16px;
    }
    .transactions-log-header .card-title {
        font-size: 15px;
    }
    .transactions-filter {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
        background: linear-gradient(180deg, #f9fbfd 0%, #f4f8fb 100%);
    }
    .transactions-filter-search-row {
        width: 100%;
    }
    .transactions-search-wrap-full {
        width: 100%;
    }
    .transactions-filter-bottom-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
    }
    .transactions-filter-tabs {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }
    .transactions-section-label {
        margin-right: 2px;
    }
    .transactions-filter-tab {
        appearance: none;
        cursor: pointer;
        font-size: 11.5px;
        padding: 7px 14px;
    }
    .transactions-filter-tab.selected {
        font-size: 12px;
        font-weight: 800;
    }
    .transactions-filter-tab-all {
        position: relative;
    }
    .transactions-filter-tab-all-active {
        background: var(--color-g-900);
        color: #fff;
        box-shadow: 0 2px 8px rgba(11,95,42,.22);
    }
    .transactions-filter-tab-all-active::after {
        content: '';
        position: absolute;
        left: 10px;
        right: 10px;
        bottom: 2px;
        height: 3px;
        border-radius: 999px;
        background: var(--color-au-500);
    }
    .transactions-filter-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }
    .transactions-search-wrap {
        position: relative;
        min-width: 0;
    }
    .transactions-search-wrap svg {
        position: absolute;
        left: 10px;
        top: 50%;
        width: 15px;
        height: 15px;
        transform: translateY(-50%);
        color: var(--color-faint);
        pointer-events: none;
    }
    .transactions-search-input {
        min-width: 235px;
        padding-left: 34px;
    }
    .transactions-search-wrap-full .transactions-search-input {
        width: 100%;
        min-width: 0;
    }
    .transactions-office-select {
        min-width: 156px;
    }
    .transactions-date-select {
        min-width: 170px;
    }
    .transactions-quick-filters {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding-top: 2px;
    }
    .transactions-quick-filters-label {
        margin-right: 2px;
    }
    .transactions-quick-filter-chip {
        border: 1px solid var(--color-border);
        background: #fff;
        color: var(--color-muted);
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        cursor: pointer;
        transition: all var(--transition);
    }
    .transactions-quick-filter-chip:hover {
        border-color: var(--color-g-300);
        background: var(--color-g-50);
        color: var(--color-g-800);
    }
    .transactions-quick-filter-chip.active {
        background: var(--color-g-900);
        color: #fff;
        border-color: var(--color-g-900);
        box-shadow: 0 2px 8px rgba(11,95,42,.2);
    }
    .transactions-table-wrap {
        position: relative;
        background: #ffffff;
    }
    .transactions-table {
        min-width: 980px;
        font-size: 14px;
    }
    .transactions-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc;
        font-size: 11px;
        letter-spacing: 0.1em;
    }
    .transactions-table tbody td {
        white-space: normal;
        font-size: 13.5px;
        line-height: 1.35;
        padding: 11px 14px;
    }
    .transactions-table tbody tr:nth-child(even) {
        background: #f8f9fa;
    }
    .transactions-table .ref-code {
        font-size: 12.5px;
        padding: 5px 11px;
    }
    .transactions-table .chip {
        font-size: 11px;
        padding: 5px 12px;
    }
    .transactions-table .status {
        font-size: 11px;
        padding: 5px 12px;
    }
    .transactions-ref-cell {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 3px;
    }
    .transactions-ref-link {
        border: 0;
        background: transparent;
        padding: 0;
        cursor: pointer;
    }
    .transactions-ref-link .ref-code {
        transition: all var(--transition);
    }
    .transactions-ref-link:hover .ref-code {
        border-color: var(--color-g-400);
        background: #ecfdf5;
    }
    .transactions-copy-ref {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 0;
        background: transparent;
        padding: 0;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--color-faint);
        cursor: pointer;
    }
    .transactions-copy-ref:hover {
        color: var(--color-g-700);
    }
    .transactions-copy-ref svg {
        width: 11px;
        height: 11px;
    }
    .transactions-ref-meta {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        color: var(--color-faint);
        text-transform: uppercase;
    }
    .transactions-client-cell {
        display: flex;
        align-items: center;
        gap: 9px;
    }
    .transactions-client-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 26px;
        height: 26px;
        border-radius: 999px;
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
        border: 1px solid #86efac;
        color: #166534;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        flex-shrink: 0;
    }
    .transactions-client-name {
        font-weight: 700;
        font-size: 14px;
        color: var(--color-ink);
    }
    .transactions-doc-type-badge {
        display: inline-flex;
        align-items: center;
        border: 1px solid #dbe3ec;
        background: #f8fafc;
        color: #334155;
        border-radius: 999px;
        padding: 5px 11px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .02em;
    }
    .transactions-date {
        font-size: 12.5px;
        font-weight: 500;
    }
    .transactions-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }
    .transactions-action-btn {
        min-width: 74px;
        justify-content: center;
        text-transform: none;
        letter-spacing: 0;
        font-size: 11.5px;
    }
    .tx-detail-panel {
        text-align: left;
        font-size: 15px;
        line-height: 1.65;
        color: var(--color-ink);
        margin-top: 2px;
    }
    .tx-detail-row {
        display: grid;
        grid-template-columns: 130px minmax(0, 1fr);
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px dashed var(--color-border);
    }
    .tx-detail-row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    .tx-detail-label {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--color-muted);
    }
    .tx-detail-value {
        font-size: 15px;
        font-weight: 600;
        color: var(--color-ink);
        word-break: break-word;
    }
    .transactions-row-process td:first-child,
    .transactions-row-approved td:first-child,
    .transactions-row-pickup td:first-child {
        border-left: 3px solid #f0f4f8;
    }
    .transactions-row-process td:first-child { border-left-color: #f59e0b; }
    .transactions-row-approved td:first-child { border-left-color: #3b82f6; }
    .transactions-row-pickup td:first-child { border-left-color: #f97316; }
    .transactions-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 34px 18px 38px;
        text-align: center;
        color: var(--color-muted);
    }
    .transactions-empty-state svg {
        width: 42px;
        height: 42px;
        color: var(--color-faint);
        opacity: 0.7;
    }
    .transactions-empty-state p {
        font-size: 13px;
        font-weight: 600;
    }
    .transactions-footer {
        padding-top: 14px;
        padding-bottom: 14px;
        background: #f8fbf9;
    }
    .transactions-footer-note {
        font-size: 12px;
        color: var(--color-muted);
    }
    .transactions-pagination {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        justify-content: flex-end;
    }
    @keyframes tx-hero-flow {
        0% { background-position: 0% 45%; }
        50% { background-position: 100% 55%; }
        100% { background-position: 0% 45%; }
    }
    @media (max-width: 960px) {
        .transactions-hero {
            grid-template-columns: minmax(0, 1fr);
            padding: 22px 20px;
            border-radius: 18px;
        }
        .transactions-title {
            font-size: 26px;
        }
        .transactions-hero-aside {
            width: 100%;
        }
        .transactions-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .transactions-filter {
            gap: 10px;
        }
        .transactions-filter-controls {
            justify-content: flex-start;
        }
        .transactions-filter-bottom-row {
            flex-direction: column;
            align-items: stretch;
        }
        .transactions-search-input,
        .transactions-office-select,
        .transactions-date-select {
            min-width: 160px;
        }
    }
    @media (max-width: 640px) {
        .transactions-kpi-grid {
            grid-template-columns: 1fr;
        }
        .transactions-meta {
            gap: 6px;
        }
        .transactions-meta-chip {
            padding: 5px 10px;
        }
        .transactions-table {
            font-size: 13px;
        }
        .transactions-table tbody td {
            font-size: 13px;
        }
        .transactions-actions .transactions-action-btn {
            width: 100%;
            min-width: 0;
        }
        .tx-detail-row {
            grid-template-columns: 1fr;
            gap: 4px;
            padding: 6px 0;
        }
        .tx-detail-label {
            font-size: 11px;
        }
        .tx-detail-value {
            font-size: 14px;
        }
        .transactions-footer {
            justify-content: center;
        }
        .transactions-pagination {
            justify-content: center;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .transactions-hero {
            animation: none;
        }
    }
</style>

<div x-data="transactionsApp()" x-cloak @keydown.escape.window="closeAllModals()" class="transactions-page">
    <x-aais.transactions.hero-section />
    <x-aais.transactions.kpi-grid :kpis="$kpis" />
    <x-aais.transactions.log-card :docs="$docs" />
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('transactionsApp', () => ({
        docs: @json($docs),
        labels: @json($statusLabels),
        filters: [
            { label: 'All', value: 'all' },
            { label: 'Logged', value: 'logged' },
            { label: 'In Process', value: 'process' },
            { label: 'Approved', value: 'approved' },
            { label: 'For Pickup', value: 'pickup' },
            { label: 'Completed', value: 'complete' },
            { label: 'Voided', value: 'void' },
        ],
        activeFilter: 'all',
        officeFilter: 'All Offices',
        searchQuery: '',
        quickDateRanges: [
            { label: 'Any Date', value: 'all' },
            { label: 'Today', value: 'today' },
            { label: 'Last 7 Days', value: '7d' },
            { label: 'Last 30 Days', value: '30d' },
        ],
        dateRangeFilter: 'all',
        quickFilter: '',
        copiedRef: '',
        lastUpdatedAt: Date.now(),
        relativeTick: Date.now(),

        init() {
            setInterval(() => {
                this.relativeTick = Date.now();
            }, 60000);
        },

        get offices() {
            const officeSet = [...new Set(this.docs.map((doc) => doc.office))]
                .sort((left, right) => left.localeCompare(right));
            return ['All Offices', ...officeSet];
        },

        get activeFilterLabel() {
            const selected = this.filters.find((f) => f.value === this.activeFilter);
            return selected ? selected.label : 'All';
        },

        parseDocDate(value) {
            const parsed = new Date(value);
            if (Number.isNaN(parsed.getTime())) {
                return null;
            }
            parsed.setHours(0, 0, 0, 0);
            return parsed;
        },

        matchesDateRange(doc) {
            if (this.dateRangeFilter === 'all') {
                return true;
            }

            const receivedDate = this.parseDocDate(doc.received);
            if (!receivedDate) {
                return false;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (this.dateRangeFilter === 'today') {
                return receivedDate.getTime() === today.getTime();
            }

            if (this.dateRangeFilter === '7d') {
                const start = new Date(today);
                start.setDate(today.getDate() - 6);
                return receivedDate >= start;
            }

            if (this.dateRangeFilter === '30d') {
                const start = new Date(today);
                start.setDate(today.getDate() - 29);
                return receivedDate >= start;
            }

            return true;
        },

        touchUpdated() {
            this.lastUpdatedAt = Date.now();
            this.relativeTick = Date.now();
        },

        relativeLastUpdated() {
            const diffMs = Math.max(0, this.relativeTick - this.lastUpdatedAt);
            const minuteMs = 60000;
            const minutes = Math.floor(diffMs / minuteMs);

            if (minutes <= 0) {
                return 'just now';
            }

            if (minutes === 1) {
                return '1 minute ago';
            }

            if (minutes < 60) {
                return `${minutes} minutes ago`;
            }

            const hours = Math.floor(minutes / 60);
            return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
        },

        isVisible(idx) {
            const doc = this.docs[idx];
            if (!doc) return false;

            let matchFilter = true;
            if (this.activeFilter !== 'all') {
                matchFilter = doc.status.toLowerCase() === this.activeFilter;
            }

            let matchSearch = true;
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                matchSearch = [doc.ref, doc.name, doc.type, doc.office, doc.staff]
                    .join(' ')
                    .toLowerCase()
                    .includes(q);
            }

            let matchOffice = true;
            if (this.officeFilter !== 'All Offices') {
                matchOffice = doc.office === this.officeFilter;
            }

            const matchDate = this.matchesDateRange(doc);

            return matchFilter && matchSearch && matchOffice && matchDate;
        },

        get visibleCount() {
            return this.docs.filter((_, idx) => this.isVisible(idx)).length;
        },

        get hasFilters() {
            return this.activeFilter !== 'all'
                || this.officeFilter !== 'All Offices'
                || this.dateRangeFilter !== 'all'
                || this.searchQuery.trim() !== '';
        },

        statusCount(status) {
            return this.docs.filter((doc) => doc.status === status).length;
        },

        resetFilters() {
            this.activeFilter = 'all';
            this.officeFilter = 'All Offices';
            this.dateRangeFilter = 'all';
            this.searchQuery = '';
            this.quickFilter = '';
        },

        applyQuickFilter(type) {
            this.quickFilter = this.quickFilter === type ? '' : type;

            if (!this.quickFilter) {
                this.resetFilters();
                return;
            }

            if (this.quickFilter === 'this-week') {
                this.activeFilter = 'all';
                this.officeFilter = 'All Offices';
                this.dateRangeFilter = '7d';
            }

            if (this.quickFilter === 'admissions-only') {
                this.activeFilter = 'all';
                this.officeFilter = 'Admissions';
                this.dateRangeFilter = 'all';
            }

            if (this.quickFilter === 'pickup-only') {
                this.activeFilter = 'pickup';
                this.officeFilter = 'All Offices';
                this.dateRangeFilter = 'all';
            }
        },

        statusTooltip(doc) {
            if (!doc) {
                return '';
            }

            const office = String(doc.office || '').trim();
            const officeLabel = office ? (office.toLowerCase().includes('office') ? office : `${office} Office`) : 'assigned office';

            if (doc.status === 'process') {
                return `Currently with ${officeLabel}`;
            }
            if (doc.status === 'approved') {
                return `Approved and queued for release by ${officeLabel}`;
            }
            if (doc.status === 'pickup') {
                return 'Ready for client pickup';
            }
            if (doc.status === 'logged') {
                return 'Logged and awaiting processing assignment';
            }
            if (doc.status === 'complete') {
                return 'Completed and released to client';
            }
            if (doc.status === 'void') {
                return 'Marked void by staff';
            }

            return this.labels[doc.status] || 'Status unavailable';
        },

        async copyRef(reference) {
            const value = String(reference || '').trim();
            if (!value) {
                return;
            }

            try {
                if (navigator?.clipboard?.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = value;
                    temp.setAttribute('readonly', '');
                    temp.style.position = 'absolute';
                    temp.style.left = '-9999px';
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }

                this.copiedRef = value;
                setTimeout(() => {
                    if (this.copiedRef === value) {
                        this.copiedRef = '';
                    }
                }, 1400);
            } catch (_) {
                this.notify('Copy failed', 'Reference could not be copied on this browser.', 'error');
            }
        },

        viewDoc(idx) {
            const doc = this.docs[idx];
            if (!doc) return;

            Swal.fire({
                title: doc.ref,
                width: 680,
                html: `<div class="tx-detail-panel">
                    <div class="tx-detail-row">
                        <span class="tx-detail-label">Client</span>
                        <span class="tx-detail-value">${doc.name}</span>
                    </div>
                    <div class="tx-detail-row">
                        <span class="tx-detail-label">Type</span>
                        <span class="tx-detail-value">${doc.type}</span>
                    </div>
                    <div class="tx-detail-row">
                        <span class="tx-detail-label">Office</span>
                        <span class="tx-detail-value">${doc.office}</span>
                    </div>
                    <div class="tx-detail-row">
                        <span class="tx-detail-label">Staff</span>
                        <span class="tx-detail-value">${doc.staff}</span>
                    </div>
                    <div class="tx-detail-row">
                        <span class="tx-detail-label">Status</span>
                        <span class="tx-detail-value">${this.labels[doc.status]}</span>
                    </div>
                    <div class="tx-detail-row">
                        <span class="tx-detail-label">Received</span>
                        <span class="tx-detail-value">${doc.received}</span>
                    </div>
                </div>`,
                showCloseButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#1fa64a'
            });
        },

        editDoc(idx) {
            const doc = this.docs[idx];
            if (!doc) return;

            Swal.fire({
                title: 'Update Status: ' + doc.ref,
                icon: 'info',
                text: 'Feature simulation: Status update dialog would open here.',
                confirmButtonColor: '#1fa64a'
            });
        },

        closeAllModals() {
            Swal.close();
        }
    }));
});
</script>
@endpush
