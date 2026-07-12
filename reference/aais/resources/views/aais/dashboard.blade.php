@php
    $title     = 'Dashboard';
    $role      = 'Admin';
    $topbarSub = 'Real-time overview of all document transactions';

    $docs = [
        ['ref'=>'TL-2026-0412','name'=>'Maria Santos','type'=>'Transcript of Records','office'=>'Registrar','staff'=>'V. Santos','received'=>'Apr 1, 2026','status'=>'process'],
        ['ref'=>'TL-2026-0411','name'=>'Juan Dela Cruz','type'=>'Certificate of Enrollment','office'=>'Admissions','staff'=>'R. Reyes','received'=>'Apr 1, 2026','status'=>'pickup'],
        ['ref'=>'TL-2026-0410','name'=>'Ana Reyes','type'=>'Good Moral Certificate','office'=>'OSAS','staff'=>'P. Flores','received'=>'Mar 31, 2026','status'=>'approved'],
        ['ref'=>'TL-2026-0409','name'=>'Carlo Mendoza','type'=>'Diploma Authentication','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 31, 2026','status'=>'complete'],
        ['ref'=>'TL-2026-0408','name'=>'Rosa Garcia','type'=>'CAV Document','office'=>'Records','staff'=>'M. Torres','received'=>'Mar 30, 2026','status'=>'logged'],
        ['ref'=>'TL-2026-0407','name'=>'Kevin Lim','type'=>'Transfer Credentials','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 30, 2026','status'=>'void'],
        ['ref'=>'TL-2026-0406','name'=>'Liza Bautista','type'=>'Honorable Dismissal','office'=>'OSAS','staff'=>'P. Flores','received'=>'Mar 29, 2026','status'=>'complete'],
        ['ref'=>'TL-2026-0405','name'=>'Mark Cruz','type'=>'Transcript of Records','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 29, 2026','status'=>'process'],
    ];

    $statusLabels = ['logged'=>'Logged','process'=>'In Process','approved'=>'Approved','pickup'=>'For Pickup','complete'=>'Completed','void'=>'Voided'];

    $kpis = [
        ['value'=>'1,284','label'=>'Total Transactions','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z"/><path d="M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>','bg'=>'stat-icon-green','trend'=>'+12 this week','up'=>true],
        ['value'=>'48','label'=>"Today's Documents",'icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>','bg'=>'stat-icon-gold','trend'=>'+8 vs yesterday','up'=>true],
        ['value'=>'7','label'=>'Pending / In-Process','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>','bg'=>'stat-icon-blue','trend'=>'-3 from last week','up'=>false],
        ['value'=>'3','label'=>'Ready for Pickup','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20V10M18 20V4M6 20v-6"/></svg>','bg'=>'stat-icon-rose','trend'=>'Needs attention','up'=>false],
    ];

    $activities = [
        ['time' => '10:42 AM', 'msg' => 'TL-2026-0412 received by V. Santos - status: In Process', 'status' => 'process', 'done' => true, 'scope' => 'today'],
        ['time' => '10:28 AM', 'msg' => 'TL-2026-0411 marked For Pickup - email sent to client', 'status' => 'pickup', 'done' => true, 'scope' => 'today'],
        ['time' => '09:55 AM', 'msg' => 'TL-2026-0410 approved by P. Flores', 'status' => 'approved', 'done' => true, 'scope' => 'today'],
        ['time' => '09:30 AM', 'msg' => 'TL-2026-0413 encoded via kiosk by student', 'status' => 'logged', 'done' => false, 'scope' => 'today'],
        ['time' => '08:17 AM', 'msg' => 'TL-2026-0409 completed and released', 'status' => 'complete', 'done' => true, 'scope' => 'today'],
        ['time' => 'Thu 04:58 PM', 'msg' => 'TL-2026-0406 moved to Records for verification', 'status' => 'process', 'done' => true, 'scope' => 'week'],
        ['time' => 'Wed 02:14 PM', 'msg' => 'TL-2026-0403 finalized and released to client', 'status' => 'complete', 'done' => true, 'scope' => 'week'],
    ];

    $breakdown = [
        ['status' => 'logged', 'label' => 'Logged', 'count' => 11, 'pct' => 23],
        ['status' => 'process', 'label' => 'In Process', 'count' => 7, 'pct' => 15],
        ['status' => 'approved', 'label' => 'Approved', 'count' => 5, 'pct' => 10],
        ['status' => 'pickup', 'label' => 'For Pickup', 'count' => 3, 'pct' => 6],
        ['status' => 'complete', 'label' => 'Completed', 'count' => 21, 'pct' => 44],
        ['status' => 'void', 'label' => 'Voided', 'count' => 1, 'pct' => 2],
    ];

    $hour = (int) now()->format('H');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $quickActions = [
        [
            'href' => route('aais.admin.portal'),
            'label' => 'Open Scan Portal',
            'variant' => 'primary',
            'icon' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>',
        ],
        [
            'href' => route('aais.client.kiosk'),
            'label' => 'New Document (Kiosk)',
            'variant' => 'outline',
            'icon' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>',
        ],
        [
            'href' => route('aais.admin.reports'),
            'label' => 'Generate Report',
            'variant' => 'outline',
            'icon' => '<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 8V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-2"/><path d="M9 14h6M9 18h4"/></svg>',
        ],
    ];
@endphp

@extends('layouts.admin')

@section('content')
<div x-data="dashboardApp()" x-cloak @keydown.escape.window="closeAllModals()">

    <x-aais.dashboard.greeting
        :greeting="$greeting"
        name="Admin"
        subtitle="Here's what's happening with your documents today."
    />

    <x-aais.dashboard.kpi-grid :kpis="$kpis" />

    <x-aais.dashboard.charts-row />

    <x-aais.dashboard.recent-transactions-card :docs="$docs" :show-count="8" />

    <div class="card" style="overflow:hidden;">
        <x-aais.ui.card-header
            title="Kiosk Intake (Staff-Accepted Only)"
            icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z'/><path d='M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3'/></svg>"
        >
            <x-slot:actions>
                <div style="display:flex;gap:8px;align-items:center;">
                    <span class="badge badge-gold" x-text="kioskPendingCount + ' pending'" title="Submitted via kiosk but not yet accepted"></span>
                    <span class="badge badge-green" x-text="kioskAccepted.length + ' accepted'"></span>
                </div>
            </x-slot:actions>
        </x-aais.ui.card-header>

        <div class="card-body" style="padding-top:16px;">
            <p style="font-size:12px;color:var(--color-muted);margin-bottom:14px;line-height:1.7;">
                Kiosk submissions do not enter dashboard monitoring until staff confirms receipt at Scan and Receive.
            </p>

            <div class="scroll-x">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref Code</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Accepted At</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="kioskAccepted.length === 0">
                            <tr>
                                <td colspan="5" class="dashboard-kiosk-empty-cell">
                                    <div class="dashboard-kiosk-empty-state">
                                        <svg class="dashboard-kiosk-empty-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M8 9h8M8 13h5"/></svg>
                                        <p class="dashboard-kiosk-empty-title">No kiosk records accepted yet</p>
                                        <p class="dashboard-kiosk-empty-copy">This list stays empty until staff accepts a kiosk submission in Scan &amp; Receive.</p>
                                        <button type="button" class="btn btn-outline btn-sm" @click="goToScanReceive()">Accept first kiosk submission</button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <template x-for="row in kioskAccepted" :key="row.ref + row.acceptedAt">
                            <tr>
                                <td><span class="ref-code" x-text="row.ref"></span></td>
                                <td style="font-weight:600;" x-text="row.name"></td>
                                <td x-text="row.type"></td>
                                <td class="text-muted" x-text="row.acceptedAt"></td>
                                <td>
                                    <x-aais.ui.status-badge-dynamic
                                        class-expr="'status-' + row.status"
                                        text-expr="labels[row.status] || row.status"
                                    />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-aais.dashboard.secondary-row
        :activities="$activities"
        :status-labels="$statusLabels"
        :quick-actions="$quickActions"
        :breakdown="$breakdown"
        :activity-log-url="route('aais.admin.transactions')"
    />

    <x-aais.dashboard.modals />

    <x-aais.ui.toast-stack items="notifications" />
</div>
@endsection

@push('scripts')
<script>
function dashboardApp() {
    const docs = @json($docs);
    const labels = @json($statusLabels);
    const filterMap = { 'All':'all','Logged':'logged','In Process':'process','Approved':'approved','For Pickup':'pickup','Completed':'complete','Voided':'void' };

    return {
        filters: ['All','Logged','In Process','Approved','For Pickup','Completed','Voided'],
        activeFilter: 'All',
        searchQuery: '',
        docs,
        labels,
        activities: @json($activities),
        activityRange: 'today',
        viewModalOpen: false,
        editModalOpen: false,
        confirmModalOpen: false,
        activeDoc: null,
        activeIdx: -1,
        editStatusValue: 'logged',
        notifications: [],
        kioskAccepted: [],
        kioskPendingCount: 0,
        lastRefreshedAt: Date.now(),
        recentTableUpdatedAt: Date.now(),
        relativeTick: Date.now(),
        volumeChart: null,
        statusChart: null,

        setActivityRange(range) {
            this.activityRange = range;
        },

        relativeFrom(timestamp) {
            const diffMs = Math.max(0, this.relativeTick - Number(timestamp || 0));
            const seconds = Math.floor(diffMs / 1000);
            if (seconds < 60) {
                return `${seconds} seconds ago`;
            }

            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) {
                return minutes === 1 ? '1 minute ago' : `${minutes} minutes ago`;
            }

            const hours = Math.floor(minutes / 60);
            return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
        },

        refreshRecentTransactions(manual = false) {
            this.recentTableUpdatedAt = Date.now();
            if (manual) {
                this.notify('Table refreshed', 'Recent transactions view was refreshed.', 'success');
            }
        },

        refreshDashboardData(manual = false) {
            this.lastRefreshedAt = Date.now();
            this.relativeTick = Date.now();
            this.refreshKioskIntake();
            this.refreshRecentTransactions(false);
            this.initCharts();

            if (manual) {
                this.notify('Dashboard refreshed', 'Live dashboard widgets have been refreshed.', 'success');
            }
        },

        goToScanReceive() {
            window.location.href = @json(route('aais.admin.portal'));
        },

        applyStatusFilter(statusLabel) {
            const normalized = String(statusLabel || '').trim();
            if (!normalized || normalized === 'Total') {
                this.activeFilter = 'All';
                this.refreshRecentTransactions(false);
                return;
            }

            this.activeFilter = this.activeFilter === normalized ? 'All' : normalized;
            this.refreshRecentTransactions(false);
        },

        refreshKioskIntake() {
            const store = window.AAISDemoStore;
            if (!store) {
                this.kioskAccepted = [];
                this.kioskPendingCount = 0;
                return;
            }

            this.kioskPendingCount = store.listPending().length;
            this.kioskAccepted = store.getRecentAccepted(6).map((record) => ({
                ref: record.ref,
                name: record.name,
                type: record.type,
                status: record.status,
                acceptedAt: store.formatDateTime(record.acceptedAt || record.receivedAt || record.updatedAt),
            }));
        },

        matchesFilter(doc) {
            const fKey = filterMap[this.activeFilter] || 'all';
            return fKey === 'all' || doc.status === fKey;
        },

        matchesSearch(doc) {
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase();
            return doc.ref.toLowerCase().includes(q) || doc.name.toLowerCase().includes(q);
        },

        get visibleCount() {
            return this.docs.filter((doc) => this.matchesFilter(doc) && this.matchesSearch(doc)).length;
        },

        isVisible(idx) {
            const doc = this.docs[idx];
            return this.matchesFilter(doc) && this.matchesSearch(doc);
        },

        closeViewModal() {
            this.viewModalOpen = false;
            // Delay clearing activeDoc so the transition can finish animating
            setTimeout(() => {
                if (!this.viewModalOpen) {
                    this.activeDoc = null;
                    this.activeIdx = -1;
                }
            }, 300);
        },

        closeEditModal() {
            this.editModalOpen = false;
            setTimeout(() => {
                if (!this.editModalOpen) {
                    this.activeDoc = null;
                    this.activeIdx = -1;
                    this.editStatusValue = 'logged';
                }
            }, 300);
        },

        closeConfirmModal() {
            this.confirmModalOpen = false;
            setTimeout(() => {
                if (!this.confirmModalOpen) {
                    this.activeDoc = null;
                    this.activeIdx = -1;
                }
            }, 300);
        },

        closeAllModals() {
            this.closeViewModal();
            this.closeEditModal();
            this.closeConfirmModal();
        },

        viewDoc(idx) {
            this.activeIdx = idx;
            this.activeDoc = this.docs[idx];
            this.viewModalOpen = true;
        },

        editDoc(idx) {
            this.activeIdx = idx;
            this.activeDoc = this.docs[idx];
            this.editStatusValue = this.activeDoc.status;
            this.editModalOpen = true;
        },

        saveEdit() {
            if (this.activeIdx < 0) return;
            const ref = this.docs[this.activeIdx].ref;
            this.docs[this.activeIdx].status = this.editStatusValue;
            this.closeEditModal();
            this.refreshRecentTransactions(false);
            this.notify('Updated!', `${ref} is now "${this.labels[this.editStatusValue]}"`, 'success');
        },

        voidDoc(idx) {
            this.activeIdx = idx;
            this.activeDoc = this.docs[idx];
            this.confirmModalOpen = true;
        },

        confirmVoid() {
            if (this.activeIdx < 0) return;
            const ref = this.docs[this.activeIdx].ref;
            this.docs[this.activeIdx].status = 'void';
            this.closeConfirmModal();
            this.refreshRecentTransactions(false);
            this.notify('Voided', `${ref} has been voided.`, 'success');
        },

        notify(title, message, type = 'success') {
            const id = Date.now();
            this.notifications.push({ id, title, message, type });
            setTimeout(() => {
                this.notifications = this.notifications.filter((n) => n.id !== id);
            }, 2800);
        },

        initCharts() {
            const volumeCanvas = document.getElementById('volumeChart');
            const statusCanvas = document.getElementById('statusChart');
            if (!volumeCanvas || !statusCanvas) {
                return;
            }

            if (this.volumeChart) {
                this.volumeChart.destroy();
            }
            if (this.statusChart) {
                this.statusChart.destroy();
            }

            const ctxVol = volumeCanvas.getContext('2d');
            this.volumeChart = new Chart(ctxVol, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Transactions',
                        data: [42, 58, 64, 49, 72, 38, 51],
                        borderColor: '#1fa64a',
                        backgroundColor: 'rgba(31,166,74,0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        pointBorderColor: '#1fa64a',
                        pointBackgroundColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.parsed.y} transactions`
                            }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false } }
                    }
                }
            });

            const statusSeries = [
                { label: 'Logged', key: 'logged', color: '#10b981' },
                { label: 'In Process', key: 'process', color: '#f59e0b' },
                { label: 'Approved', key: 'approved', color: '#3b82f6' },
                { label: 'For Pickup', key: 'pickup', color: '#f97316' },
                { label: 'Completed', key: 'complete', color: '#22c55e' },
                { label: 'Voided', key: 'void', color: '#ef4444' },
            ];
            const statusValues = statusSeries.map((item) => this.docs.filter((doc) => doc.status === item.key).length);
            const totalStatusCount = statusValues.reduce((sum, value) => sum + value, 0);

            const centerTextPlugin = {
                id: 'dashboardDonutCenterText',
                afterDraw(chart, args, options) {
                    const chartArea = chart.chartArea;
                    if (!chartArea) {
                        return;
                    }

                    const { ctx } = chart;
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = '#111827';
                    ctx.font = '700 24px Inter, sans-serif';
                    ctx.fillText(String(options.total || 0), centerX, centerY - 7);
                    ctx.fillStyle = '#6b7280';
                    ctx.font = '600 11px Inter, sans-serif';
                    ctx.fillText(options.caption || 'Total', centerX, centerY + 11);
                    ctx.restore();
                }
            };

            const ctxStatus = statusCanvas.getContext('2d');
            this.statusChart = new Chart(ctxStatus, {
                type: 'doughnut',
                plugins: [centerTextPlugin],
                data: {
                    labels: statusSeries.map((item) => item.label),
                    datasets: [{
                        data: statusValues,
                        backgroundColor: statusSeries.map((item) => item.color),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    onClick: (_, elements) => {
                        if (!elements.length) {
                            return;
                        }
                        const element = elements[0];
                        const label = this.statusChart.data.labels[element.index];
                        this.applyStatusFilter(label);
                    },
                    plugins: { 
                        legend: {
                            position: 'right',
                            labels: { usePointStyle: true, padding: 18 },
                            onClick: (_, legendItem) => {
                                this.applyStatusFilter(legendItem.text);
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => `${context.label}: ${context.parsed} transactions`
                            }
                        },
                        dashboardDonutCenterText: {
                            total: totalStatusCount,
                            caption: 'Total'
                        }
                    }
                }
            });
        },
        
        init() {
            this.refreshDashboardData(false);
            window.addEventListener('aais-demo-store-updated', () => this.refreshDashboardData(false));
            setInterval(() => {
                this.relativeTick = Date.now();
            }, 1000);
        }
    };
}
</script>
@endpush
