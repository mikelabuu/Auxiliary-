@php
    $title     = 'Reports & Exports';
    $role      = 'Admin';
    $topbarSub = 'Daily and weekly transaction reports — Admin access only';

    $docs = [
        ['ref'=>'TL-2026-0412','name'=>'Maria Santos','type'=>'Transcript of Records','received'=>'Apr 1, 2026','released'=>'—','office'=>'Registrar','status'=>'process'],
        ['ref'=>'TL-2026-0411','name'=>'Juan Dela Cruz','type'=>'Certificate of Enrollment','received'=>'Apr 1, 2026','released'=>'—','office'=>'Admissions','status'=>'pickup'],
        ['ref'=>'TL-2026-0410','name'=>'Ana Reyes','type'=>'Good Moral Certificate','received'=>'Mar 31, 2026','released'=>'—','office'=>'OSAS','status'=>'approved'],
        ['ref'=>'TL-2026-0409','name'=>'Carlo Mendoza','type'=>'Diploma Authentication','received'=>'Mar 31, 2026','released'=>'Apr 1, 2026','office'=>'Registrar','status'=>'complete'],
        ['ref'=>'TL-2026-0408','name'=>'Rosa Garcia','type'=>'CAV Document','received'=>'Mar 30, 2026','released'=>'—','office'=>'Records','status'=>'logged'],
        ['ref'=>'TL-2026-0407','name'=>'Kevin Lim','type'=>'Transfer Credentials','received'=>'Mar 30, 2026','released'=>'Mar 30, 2026','office'=>'Registrar','status'=>'void'],
        ['ref'=>'TL-2026-0406','name'=>'Liza Bautista','type'=>'Honorable Dismissal','received'=>'Mar 29, 2026','released'=>'Mar 31, 2026','office'=>'OSAS','status'=>'complete'],
        ['ref'=>'TL-2026-0405','name'=>'Mark Cruz','type'=>'Transcript of Records','received'=>'Mar 29, 2026','released'=>'—','office'=>'Registrar','status'=>'process'],
        ['ref'=>'TL-2026-0404','name'=>'Donna Valdez','type'=>'Certificate of Graduation','received'=>'Mar 28, 2026','released'=>'Mar 29, 2026','office'=>'Admissions','status'=>'complete'],
        ['ref'=>'TL-2026-0403','name'=>'Paolo Reyes','type'=>'Good Moral Certificate','received'=>'Mar 28, 2026','released'=>'—','office'=>'OSAS','status'=>'process'],
    ];
    $statusLabels = ['logged'=>'Logged','process'=>'In Process','approved'=>'Approved','pickup'=>'For Pickup','complete'=>'Completed','void'=>'Voided'];

    $kpis = [
        ['label'=>'Total Transactions','value'=>'48','period'=>'This Week','icon'=>'stat-icon-green'],
        ['label'=>'Completed','value'=>'38','period'=>'This Week','icon'=>'stat-icon-gold'],
        ['label'=>'Still Processing','value'=>'7','period'=>'Current','icon'=>'stat-icon-blue'],
        ['label'=>'For Pickup','value'=>'3','period'=>'Current','icon'=>'stat-icon-green'],
        ['label'=>'Voided','value'=>'1','period'=>'This Week','icon'=>'stat-icon-rose'],
        ['label'=>'SLA Compliance','value'=>'79%','period'=>'This Week','icon'=>'stat-icon-gold'],
    ];

    $officeBreakdown = [
        ['name' => 'Registrar', 'count' => 16, 'percent' => 38],
        ['name' => 'Admissions', 'count' => 12, 'percent' => 28],
        ['name' => 'OSAS', 'count' => 9, 'percent' => 21],
        ['name' => 'Records', 'count' => 5, 'percent' => 12],
    ];
@endphp

@extends('layouts.admin')

@section('content')
<div x-data="reportsApp()" x-cloak class="reports-root" @keydown.escape.window="closeAllModals()">
    <x-aais.reports.kpi-grid :kpis="$kpis" />
    <x-aais.reports.transaction-report-card :docs="$docs" :status-labels="$statusLabels" />
    <x-aais.reports.insights-section :office-breakdown="$officeBreakdown" />
    <x-aais.reports.modals />

</div>
@endsection

@push('scripts')
<script>
function reportsApp() {
    const defaultDocs = @json($docs);
    const officeMap = { 'All Offices':'all','Registrar':'Registrar','Admissions':'Admissions','OSAS':'OSAS','Records':'Records' };
    const statusLabels = @json($statusLabels);

    return {
        // Data & Filters
        docs: JSON.parse(JSON.stringify(defaultDocs)),
        labels: statusLabels,
        officeFilters: ['All Offices','Registrar','Admissions','OSAS','Records'],
        activeOffice: 'All Offices',
        searchQuery: '', 
        quickDateRange: 'this_week',
        dateRange: 'this_week', dateFrom: '', dateTo: '',
        selectedRows: [],
        lastUpdatedAt: Date.now(),
        relativeTick: Date.now(),
        
        // Modals state
        viewModalOpen: false, editModalOpen: false, 
        confirmModalOpen: false, exportModalOpen: false,
        activeDoc: null, activeIdx: -1, editStatusValue: '',
        
        // Confirmation configuration
        confirmAction: null,
        confirmData: { title:'', message:'', btnText:'', isDanger:false },
        
        // Export Configuration
        exporting: false,
        exportConfig: { scope: 'all', dateRange: 'this_week', office: 'All Offices', format: 'csv' },
        
        // Notifications
        notifications: [],

        init() {
            this.$watch('activeOffice', val => {
                this.exportConfig.office = val;
                this.touchUpdated();
            });
            this.$watch('searchQuery', () => this.touchUpdated());
            this.$watch('dateFrom', () => this.touchUpdated());
            this.$watch('dateTo', () => this.touchUpdated());
            this.applyDatePreset(this.dateRange, false);
            setInterval(() => {
                this.relativeTick = Date.now();
            }, 60000);
        },

        get visibleCount() { return this.docs.filter((_,i) => this.isVisible(i)).length; },

        get showingRangeText() {
            if (this.visibleCount <= 0) {
                return `Showing 0 of ${this.docs.length} transactions`;
            }
            return `Showing 1-${this.visibleCount} of ${this.docs.length} transactions`;
        },

        get selectedStatusSummary() {
            if (this.selectedRows.length <= 0) {
                return '';
            }
            const breakdown = this.selectedStatusBreakdown();
            if (!breakdown) {
                return `${this.selectedRows.length} selected`;
            }
            return `${this.selectedRows.length} selected - ${breakdown}`;
        },

        touchUpdated() {
            this.lastUpdatedAt = Date.now();
            this.relativeTick = Date.now();
        },

        relativeLastUpdated() {
            const diffMs = Math.max(0, this.relativeTick - this.lastUpdatedAt);
            const mins = Math.floor(diffMs / 60000);
            if (mins <= 0) return 'just now';
            if (mins === 1) return '1 minute ago';
            if (mins < 60) return `${mins} minutes ago`;
            const hrs = Math.floor(mins / 60);
            return hrs === 1 ? '1 hour ago' : `${hrs} hours ago`;
        },

        formatInputDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        startOfWeek(date) {
            const clone = new Date(date);
            const day = clone.getDay();
            const diff = (day === 0 ? -6 : 1) - day;
            clone.setDate(clone.getDate() + diff);
            clone.setHours(0, 0, 0, 0);
            return clone;
        },

        applyDatePreset(preset, syncQuick = true) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (preset === 'this_week') {
                const start = this.startOfWeek(today);
                this.dateFrom = this.formatInputDate(start);
                this.dateTo = this.formatInputDate(today);
                if (syncQuick) this.quickDateRange = 'this_week';
            } else if (preset === 'this_month') {
                const start = new Date(today.getFullYear(), today.getMonth(), 1);
                this.dateFrom = this.formatInputDate(start);
                this.dateTo = this.formatInputDate(today);
                if (syncQuick) this.quickDateRange = 'custom';
            } else if (preset === 'last_month') {
                const start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const end = new Date(today.getFullYear(), today.getMonth(), 0);
                this.dateFrom = this.formatInputDate(start);
                this.dateTo = this.formatInputDate(end);
                if (syncQuick) this.quickDateRange = 'custom';
            } else if (preset === 'all_time') {
                this.dateFrom = '';
                this.dateTo = '';
                if (syncQuick) this.quickDateRange = 'any';
            } else if (preset === 'custom') {
                if (!this.dateFrom || !this.dateTo) {
                    const start = this.startOfWeek(today);
                    this.dateFrom = this.formatInputDate(start);
                    this.dateTo = this.formatInputDate(today);
                }
                if (syncQuick) this.quickDateRange = 'custom';
            }

            this.touchUpdated();
        },

        setQuickDateRange(range) {
            this.quickDateRange = range;
            if (range === 'any') {
                this.dateRange = 'all_time';
                this.applyDatePreset('all_time', false);
                return;
            }
            if (range === 'this_week') {
                this.dateRange = 'this_week';
                this.applyDatePreset('this_week', false);
                return;
            }
            if (range === 'last_30') {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const start = new Date(today);
                start.setDate(today.getDate() - 29);
                this.dateRange = 'custom';
                this.dateFrom = this.formatInputDate(start);
                this.dateTo = this.formatInputDate(today);
                this.touchUpdated();
                return;
            }
            this.dateRange = 'custom';
            this.applyDatePreset('custom', false);
            this.$nextTick(() => {
                const fromInput = document.querySelector('.reports-date-input');
                if (fromInput) fromInput.focus();
            });
        },

        parseDocDate(value) {
            const parsed = new Date(value);
            if (Number.isNaN(parsed.getTime())) {
                return null;
            }
            parsed.setHours(0, 0, 0, 0);
            return parsed;
        },

        matchesDateWindow(doc) {
            if (!this.dateFrom && !this.dateTo) {
                return true;
            }

            const docDate = this.parseDocDate(doc.received);
            if (!docDate) {
                return false;
            }

            if (this.dateFrom) {
                const from = new Date(`${this.dateFrom}T00:00:00`);
                if (!Number.isNaN(from.getTime()) && docDate < from) {
                    return false;
                }
            }

            if (this.dateTo) {
                const to = new Date(`${this.dateTo}T00:00:00`);
                if (!Number.isNaN(to.getTime()) && docDate > to) {
                    return false;
                }
            }

            return true;
        },

        statusTooltip(doc) {
            if (!doc) return '';
            const office = String(doc.office || '').trim();
            const officeLabel = office ? (office.toLowerCase().includes('office') ? office : `${office} Office`) : 'assigned office';
            if (doc.status === 'process') return `Currently with ${officeLabel}`;
            if (doc.status === 'approved') return `Approved by ${officeLabel}`;
            if (doc.status === 'pickup') return 'Ready for pickup release';
            if (doc.status === 'logged') return 'Logged and queued for processing';
            if (doc.status === 'complete') return 'Completed and released';
            if (doc.status === 'void') return 'Voided by admin staff';
            return this.labels[doc.status] || 'Status unavailable';
        },

        isVisible(idx) {
            const d = this.docs[idx];
            const oKey = officeMap[this.activeOffice] || 'all';
            if (oKey !== 'all' && d.office !== oKey) return false;
            if (this.searchQuery.trim()) {
                const q = this.searchQuery.toLowerCase();
                if (!d.ref.toLowerCase().includes(q) && !d.name.toLowerCase().includes(q)) return false;
            }
            if (!this.matchesDateWindow(d)) return false;
            return true;
        },

        rowHasZebra(idx) {
            if (this.selectedRows.includes(idx) || !this.isVisible(idx)) {
                return false;
            }

            let visiblePosition = 0;
            for (let i = 0; i < this.docs.length; i++) {
                if (!this.isVisible(i)) {
                    continue;
                }

                if (i === idx) {
                    return visiblePosition % 2 === 1;
                }

                visiblePosition++;
            }

            return false;
        },

        selectedStatusBreakdown() {
            if (this.selectedRows.length <= 0) {
                return '';
            }

            const counts = {};
            this.selectedRows.forEach((idx) => {
                const status = this.docs[idx]?.status || 'unknown';
                counts[status] = (counts[status] || 0) + 1;
            });

            const order = ['process', 'pickup', 'approved', 'logged', 'complete', 'void'];
            const parts = [];

            order.forEach((status) => {
                if (counts[status]) {
                    parts.push(`${counts[status]} ${this.labels[status] || status}`);
                    delete counts[status];
                }
            });

            Object.keys(counts).forEach((status) => {
                parts.push(`${counts[status]} ${this.labels[status] || status}`);
            });

            return parts.join(', ');
        },

        toggleAll(checked) { 
            if (checked) {
                // Select only visible rows
                this.selectedRows = this.docs.map((_,i) => i).filter(i => this.isVisible(i));
            } else {
                this.selectedRows = []; 
            }
            this.touchUpdated();
        },
        toggleRow(idx, checked) {
            if (checked) { if (!this.selectedRows.includes(idx)) this.selectedRows.push(idx); }
            else { this.selectedRows = this.selectedRows.filter(i => i !== idx); }
            this.touchUpdated();
        },

        // Single Actions
        openViewModal(idx) {
            this.activeIdx = idx;
            this.activeDoc = this.docs[idx];
            this.viewModalOpen = true;
        },
        
        openEditModal(idx) {
            this.activeIdx = idx;
            this.activeDoc = this.docs[idx];
            this.editStatusValue = this.activeDoc.status;
            this.editModalOpen = true;
        },
        closeViewModal() {
            this.viewModalOpen = false;
            this.activeIdx = -1;
            this.activeDoc = null;
        },

        closeEditModal() {
            this.editModalOpen = false;
            this.activeIdx = -1;
            this.activeDoc = null;
            this.editStatusValue = '';
        },

        closeConfirmModal() {
            this.confirmModalOpen = false;
            this.confirmAction = null;
            this.confirmData = { title:'', message:'', btnText:'', isDanger:false };
        },

        closeExportModal() {
            this.exportModalOpen = false;
        },

        closeAllModals() {
            this.closeViewModal();
            this.closeEditModal();
            this.closeConfirmModal();
            this.closeExportModal();
        },

        confirmEdit() {
            const ref = this.activeDoc.ref;
            this.docs[this.activeIdx].status = this.editStatusValue;
            this.closeEditModal();
            this.touchUpdated();
            this.notify('Updated Status!', `${ref} changed to ${this.labels[this.editStatusValue]}.`, 'success');
        },

        requestVoid(idx) {
            const d = this.docs[idx];
            this.confirmData = { 
                title: 'Void Transaction?', 
                message: `Are you sure you want to void the transaction ${d.ref} for ${d.name}? This action cannot be undone.`,
                btnText: 'Yes, Void It', 
                isDanger: true 
            };
            this.confirmAction = () => {
                this.docs[idx].status = 'void';
                this.touchUpdated();
                this.notify('Transaction Voided', `${d.ref} has been voided.`, 'success');
            };
            this.confirmModalOpen = true;
        },

        bulkExportSelected() {
            if (this.selectedRows.length <= 0) return;
            this.openExportModal('csv');
            this.exportConfig.scope = 'selected';
        },

        // Bulk Actions
        bulkComplete() {
            this.confirmData = {
                title: 'Mark as Complete',
                message: `Are you sure you want to mark ${this.selectedRows.length} selected transactions as complete?`,
                btnText: 'Confirm Completion',
                isDanger: false
            };
            this.confirmAction = () => {
                let count = 0;
                this.selectedRows.forEach(idx => { 
                    if (this.docs[idx].status !== 'void' && this.docs[idx].status !== 'complete') {
                        this.docs[idx].status = 'complete'; 
                        count++;
                    }
                });
                this.selectedRows = [];
                this.touchUpdated();
                this.notify('Bulk Update', `${count} transactions marked as complete.`, 'success');
            };
            this.confirmModalOpen = true;
        },
        
        bulkVoid() {
            if (this.selectedRows.length <= 0) {
                this.notify('No Selection', 'Select at least one transaction to void.', 'error');
                return;
            }

            const count = this.selectedRows.length;
            const suffix = count === 1 ? '' : 's';
            const summary = this.selectedStatusBreakdown();
            this.confirmData = {
                title: 'Confirm Bulk Void',
                message: `You are about to void ${count} transaction${suffix}${summary ? ` - ${summary}` : ''}. This action cannot be undone.`,
                btnText: 'Void Selected',
                isDanger: true
            };
            this.confirmAction = () => {
                let count = 0;
                this.selectedRows.forEach(idx => { 
                    if (this.docs[idx].status !== 'void' && this.docs[idx].status !== 'complete') {
                        this.docs[idx].status = 'void';
                        count++;
                    }
                });
                this.selectedRows = [];
                this.touchUpdated();
                this.notify('Bulk Voided', `${count} transactions have been voided.`, 'success');
            };
            this.confirmModalOpen = true;
        },

        executeConfirm() {
            if (this.confirmAction) this.confirmAction();
            this.closeConfirmModal();
        },

        // Export System
        openExportModal(format = 'csv') {
            this.exportConfig.format = format;
            if (this.selectedRows.length > 0) {
                this.exportConfig.scope = 'selected';
            } else {
                this.exportConfig.scope = 'all';
            }
            this.exportModalOpen = true;
            this.touchUpdated();
        },
        
        executeExport() {
            this.exporting = true;
            setTimeout(() => {
                this.exporting = false;
                this.closeExportModal();
                const ext = this.exportConfig.format === 'pdf' ? 'pdf' : 'csv';
                const items = this.exportConfig.scope === 'selected' ? this.selectedRows.length : this.visibleCount;
                this.touchUpdated();
                this.notify('Export Successful', `aais_report_${Date.now()}.${ext} generated with ${items} items.`, 'success');
            }, 1200);
        },

        quickExportThisWeek() {
            if (this.exporting) {
                return;
            }

            this.exportConfig.dateRange = 'this_week';
            this.exportConfig.scope = this.selectedRows.length > 0 ? 'selected' : 'all';
            this.executeExport();
        },
        
        // Notifications Helper
        notify(title, message, type = 'success') {
            const id = Date.now();
            this.notifications.push({ id, title, message, type });
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, 3000);
        }
    };
}

const officeBreakdownData = @json($officeBreakdown);

/* Reports Charts */
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') {
        return;
    }

    const trendCanvas = document.getElementById('trendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Received',
                        data: [12, 8, 14, 10, 4, 0, 0],
                        borderColor: 'var(--color-g-500)',
                        backgroundColor: 'rgba(31,166,74,.14)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: 'var(--color-g-500)',
                    },
                    {
                        label: 'Completed',
                        data: [9, 7, 11, 8, 3, 0, 0],
                        borderColor: 'var(--color-au-500)',
                        backgroundColor: 'rgba(242,195,0,.12)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBackgroundColor: 'var(--color-au-500)',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11, weight: 600 },
                            padding: 18,
                            usePointStyle: true,
                            pointStyle: 'circle',
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 11 } },
                        grid: { color: '#eef2f7' },
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false },
                    },
                },
            },
        });
    }

    const officeChartCanvas = document.getElementById('officeBreakdownChart');
    if (officeChartCanvas && Array.isArray(officeBreakdownData) && officeBreakdownData.length > 0) {
        const labels = officeBreakdownData.map((item) => item.name);
        const counts = officeBreakdownData.map((item) => Number(item.count) || 0);

        new Chart(officeChartCanvas, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [
                    {
                        data: counts,
                        backgroundColor: ['#0f5f2a', '#1f7a3f', '#2ea043', '#5bbf73', '#9ee1b3'],
                        borderColor: '#ffffff',
                        borderWidth: 2,
                        hoverOffset: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '58%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 11, weight: 600 },
                            padding: 14,
                            boxWidth: 12,
                            boxHeight: 12,
                        },
                    },
                },
            },
        });
    }
});
</script>
@endpush
