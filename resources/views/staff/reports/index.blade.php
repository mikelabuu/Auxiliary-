@extends('layouts.admin')

@section('title', 'Admin - Reports')
@section('page-title', 'Reports')

@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header subtitle="Generate, filter, and export booking and payment data.">
        Analytics <span class="text-clsu-700">&amp; Reporting</span>
        <x-slot:actions>
            <button type="button" id="exportBtn" disabled class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white disabled:active:scale-100 cursor-pointer">
                <x-admin.ui.icon name="download" class="w-4 h-4" stroke-width="2" />
                Export Excel
            </button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    <!-- Filters -->
    <x-admin.ui.section-card icon="filter" title="Report Filters" subtitle="Choose a category and timeframe, refine with status filters, then generate." :delay="40">
        <div class="space-y-5">
            <!-- Report category -->
            <div>
                <p class="text-[10px] font-bold text-stone-500 tracking-widest uppercase mb-2">Report Category</p>
                <div id="reportTypeGroup" class="flex flex-wrap gap-2">
                    <button type="button" class="report-type-btn text-sm font-medium px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 transition-colors cursor-pointer" data-report-type="booking">Booking Report</button>
                    <button type="button" class="report-type-btn text-sm font-medium px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 transition-colors cursor-pointer" data-report-type="payment">Financial Report</button>
                    <button type="button" class="report-type-btn text-sm font-medium px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 transition-colors cursor-pointer" data-report-type="combined">Combined Overview</button>
                </div>
            </div>

            <!-- Timeframe -->
            <div>
                <p class="text-[10px] font-bold text-stone-500 tracking-widest uppercase mb-2">Timeframe</p>
                <div id="dateTypeGroup" class="flex flex-wrap gap-2 mb-3">
                    <button type="button" class="date-type-btn text-sm font-medium px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 transition-colors cursor-pointer" data-date-type="monthly">Monthly</button>
                    <button type="button" class="date-type-btn text-sm font-medium px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 transition-colors cursor-pointer" data-date-type="yearly">Yearly</button>
                    <button type="button" class="date-type-btn text-sm font-medium px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 transition-colors cursor-pointer" data-date-type="range">Custom Range</button>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="month" id="date_month" class="w-full sm:w-48 px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
                    <select id="date_year" class="hidden w-full sm:w-40 px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors"></select>
                    <div id="dateRangeInputs" class="hidden flex-wrap items-center gap-2">
                        <input type="date" id="date_from" class="px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
                        <span class="text-xs text-stone-400 font-medium">to</span>
                        <input type="date" id="date_to" class="px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 text-stone-800 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
                    </div>
                </div>
            </div>

            <!-- Status filter chip groups -->
            <div id="group_booking_status" class="filter-group">
                <p class="text-[10px] font-bold text-stone-500 tracking-widest uppercase mb-2">Booking Status</p>
                <div class="flex flex-wrap gap-1.5" data-filter-chips="booking_status">
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="all">All Statuses</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="pending_discount">Pending Discount</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="pending_payment">Pending Payment</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="paid">Paid</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="completed">Completed</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="cancelled">Cancelled</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="no_show">No Show</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="booking_status" data-filter-value="expired">Expired</button>
                </div>
            </div>

            <div id="group_payment_status" class="filter-group">
                <p class="text-[10px] font-bold text-stone-500 tracking-widest uppercase mb-2">Payment Status</p>
                <div class="flex flex-wrap gap-1.5" data-filter-chips="payment_status">
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="payment_status" data-filter-value="all">All Statuses</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="payment_status" data-filter-value="pending">Pending</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="payment_status" data-filter-value="success">Successful</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="payment_status" data-filter-value="failed">Failed</button>
                </div>
            </div>

            <div id="group_gateway" class="filter-group">
                <p class="text-[10px] font-bold text-stone-500 tracking-widest uppercase mb-2">Gateway</p>
                <div class="flex flex-wrap gap-1.5" data-filter-chips="gateway">
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="gateway" data-filter-value="all">All Gateways</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="gateway" data-filter-value="sandbox">Sandbox</button>
                    <button type="button" class="filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer" data-filter-group="gateway" data-filter-value="manual">Manual / Walk-in</button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-stone-100">
                <div id="reportSummary" class="hidden flex-wrap items-center gap-1.5"></div>
                <div class="flex items-center gap-2.5 sm:ml-auto">
                    <button type="button" id="resetBtn" class="text-sm font-medium text-stone-600 border border-stone-200 bg-white rounded-xl px-4 py-2.5 hover:bg-stone-50 transition-colors cursor-pointer">Reset</button>
                    <button type="button" id="generateBtn" class="flex items-center gap-2 text-sm font-semibold text-white bg-clsu-700 rounded-xl px-4 py-2.5 shadow-card hover:shadow-card-lg hover:bg-clsu-800 active:scale-[0.98] transition-all cursor-pointer">
                        <x-admin.ui.icon name="refresh" class="w-4 h-4" stroke-width="2" />
                        Update Results
                    </button>
                </div>
            </div>
        </div>
    </x-admin.ui.section-card>

    <!-- Results -->
    <x-admin.ui.section-card icon="chart-bar" title="Report Results" subtitle="Results are paginated, 10 rows per page." :delay="80">
        <div id="reportTableContainer" class="-mx-6 -mb-6 border-t border-stone-100">
            <div id="reportTable">
                <div class="flex flex-col items-center text-center py-14 px-6">
                    <div class="w-10 h-10 rounded-full bg-clsu-50 text-clsu-700 flex items-center justify-center mb-3">
                        <x-admin.ui.icon name="filter" class="w-3.5 h-3.5" stroke-width="2" />
                    </div>
                    <p class="text-sm font-semibold text-stone-700">Select your criteria above and click Update Results.</p>
                </div>
            </div>
        </div>
    </x-admin.ui.section-card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
$(function () {
    // Active/inactive appearance is applied as full class-string swaps (not a
    // semantic "is-active" class backed by CSS) because this <script> block
    // is shipped to the browser as-is — it never passes through the Tailwind/
    // PostCSS build, so a Tailwind-only construct like @apply in a <style>
    // tag here would just be invalid CSS the browser ignores.
    function setToggleActive(el, markerClass, active) {
        const base = markerClass + ' text-sm font-medium px-4 py-2.5 rounded-xl border transition-colors cursor-pointer';
        const appearance = active
            ? 'bg-clsu-700 border-clsu-800 text-white shadow-card'
            : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50';
        el.attr('class', base + ' ' + appearance);
    }
    function setChipActive(el, active) {
        const base = 'filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border transition-colors cursor-pointer';
        const appearance = active
            ? 'bg-clsu-50 border-clsu-200 text-clsu-700'
            : 'bg-white border-stone-200 text-stone-500 hover:bg-stone-50 hover:text-stone-700';
        el.attr('class', base + ' ' + appearance);
    }

    const STATUS_COLOR = {
        paid: 'clsu', success: 'clsu', completed: 'clsu',
        pending: 'palay', pending_payment: 'palay', pending_discount: 'palay',
        cancelled: 'ember', failed: 'ember', expired: 'ember',
        no_show: 'stone',
    };
    const BADGE_CLASS = {
        clsu:  'bg-clsu-50 text-clsu-700 border-clsu-200',
        palay: 'bg-palay-100 text-palay-800 border-palay-200',
        ember: 'bg-ember-50 text-ember-700 border-ember-200',
        stone: 'bg-stone-100 text-stone-600 border-stone-200',
    };
    const ICON = {
        chevronLeft: '<svg class="icon w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>',
        chevronRight: '<svg class="icon w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>',
        alert: '<svg class="icon w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        filter: '<svg class="icon w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>',
    };

    const REPORT_FILTER_GROUPS = {
        booking: ['booking_status'],
        payment: ['payment_status', 'gateway'],
        combined: ['booking_status', 'payment_status', 'gateway'],
    };

    const state = {
        reportType: 'booking',
        dateType: 'monthly',
        filters: { booking_status: ['all'], payment_status: ['all'], gateway: ['all'] },
    };

    /* ------------------- REPORT TYPE ------------------- */
    function renderReportType() {
        $('.report-type-btn').each(function () {
            setToggleActive($(this), 'report-type-btn', $(this).data('report-type') === state.reportType);
        });
        $('.filter-group').addClass('hidden');
        (REPORT_FILTER_GROUPS[state.reportType] || []).forEach(group => {
            $('#group_' + group).removeClass('hidden');
        });
    }

    $('#reportTypeGroup').on('click', '.report-type-btn', function () {
        state.reportType = $(this).data('report-type');
        state.filters = { booking_status: ['all'], payment_status: ['all'], gateway: ['all'] };
        renderReportType();
        renderAllFilterChips();
        clearResults();
    });

    /* ------------------- DATE TYPE ------------------- */
    function renderDateType() {
        $('.date-type-btn').each(function () {
            setToggleActive($(this), 'date-type-btn', $(this).data('date-type') === state.dateType);
        });
        $('#date_month').toggleClass('hidden', state.dateType !== 'monthly');
        $('#date_year').toggleClass('hidden', state.dateType !== 'yearly');
        $('#dateRangeInputs').toggleClass('hidden', state.dateType !== 'range').toggleClass('flex', state.dateType === 'range');
    }

    $('#dateTypeGroup').on('click', '.date-type-btn', function () {
        state.dateType = $(this).data('date-type');
        renderDateType();
    });

    /* ------------------- STATUS FILTER CHIPS ------------------- */
    function renderFilterChips(group) {
        const active = state.filters[group];
        $(`[data-filter-chips="${group}"] .filter-chip`).each(function () {
            setChipActive($(this), active.includes($(this).data('filter-value').toString()));
        });
    }
    function renderAllFilterChips() {
        Object.keys(state.filters).forEach(renderFilterChips);
    }

    $('.filter-group').on('click', '.filter-chip', function () {
        const group = $(this).data('filter-group');
        const value = $(this).data('filter-value').toString();
        let current = state.filters[group];

        if (value === 'all') {
            current = ['all'];
        } else {
            current = current.filter(v => v !== 'all');
            if (current.includes(value)) {
                current = current.filter(v => v !== value);
            } else {
                current.push(value);
            }
            if (current.length === 0) current = ['all'];
        }

        state.filters[group] = current;
        renderFilterChips(group);
    });

    /* ------------------- PAYLOAD / SUMMARY ------------------- */
    function cleanFilter(group) {
        const values = state.filters[group];
        return (!values || values.includes('all')) ? null : values;
    }

    function buildPayload() {
        const filters = {};
        const booking = cleanFilter('booking_status');
        const payment = cleanFilter('payment_status');
        const gateway = cleanFilter('gateway');
        if (booking) filters.booking_status = booking;
        if (payment) filters.payment_status = payment;
        if (gateway) filters.gateway = gateway;

        const columnSetMap = { booking: 'booking_summary', payment: 'financial', combined: 'combined' };

        let dateValue = null;
        if (state.dateType === 'monthly') dateValue = $('#date_month').val();
        if (state.dateType === 'yearly') dateValue = $('#date_year').val();
        if (state.dateType === 'range') dateValue = { from: $('#date_from').val(), to: $('#date_to').val() };

        return {
            report_type: state.reportType,
            column_set: columnSetMap[state.reportType],
            date_range: { type: state.dateType, value: dateValue },
            filters,
        };
    }

    function humanize(str) {
        return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function renderSummary(payload) {
        let html = `<span class="text-[10px] font-bold text-stone-400 tracking-widest uppercase flex items-center gap-1">${ICON.filter} Active</span>`;
        html += `<span class="text-[10px] font-bold text-clsu-700 bg-clsu-50 rounded-full px-2.5 py-1 tracking-wide">${payload.report_type.toUpperCase()}</span>`;

        const dateVal = typeof payload.date_range.value === 'object'
            ? (payload.date_range.value.from && payload.date_range.value.to ? `${payload.date_range.value.from} → ${payload.date_range.value.to}` : 'All Time')
            : (payload.date_range.value || 'All Time');
        html += `<span class="text-[10px] font-bold text-palay-800 bg-palay-100 rounded-full px-2.5 py-1 tracking-wide">${dateVal}</span>`;

        Object.keys(payload.filters || {}).forEach(key => {
            const values = payload.filters[key];
            if (values && values.length) {
                const label = humanize(key.replace('_status', ''));
                const text = values.map(humanize).join(', ');
                html += `<span class="text-[10px] font-bold text-stone-600 bg-stone-100 rounded-full px-2.5 py-1 tracking-wide">${label}: ${text}</span>`;
            }
        });

        $('#reportSummary').html(html).removeClass('hidden').addClass('flex');
    }

    /* ------------------- TABLE / SKELETON / EMPTY STATE ------------------- */
    function showSkeleton() {
        const rows = Array(5).fill(`
            <tr class="border-b border-stone-100">
                <td colspan="6" class="px-6 py-4"><div class="h-3.5 rounded-full bg-stone-100 animate-pulse" style="width:${40 + Math.random() * 40}%"></div></td>
            </tr>
        `).join('');
        $('#reportTable').html(`<table class="w-full"><tbody>${rows}</tbody></table>`);
    }

    function renderEmptyResults() {
        $('#reportTable').html(`
            <div class="flex flex-col items-center text-center py-14 px-6">
                <div class="w-10 h-10 rounded-full bg-ember-50 text-ember-600 flex items-center justify-center mb-3">${ICON.alert}</div>
                <p class="text-sm font-semibold text-stone-700">No matching records found</p>
                <p class="text-xs text-stone-400 mt-1 max-w-xs">Try expanding the date range, removing some filters, or switching the report type.</p>
            </div>
        `);
        $('#reportPagination').remove();
    }

    function renderTable(data) {
        const rows = data.data;
        if (!rows.length) { renderEmptyResults(); toggleExport(false); return; }

        toggleExport(true);
        const columns = Object.keys(rows[0]);

        let html = '<table class="w-full text-sm"><thead><tr class="bg-stone-50/70 border-b border-stone-100">';
        columns.forEach(col => {
            html += `<th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">${humanize(col)}</th>`;
        });
        html += '</tr></thead><tbody>';

        rows.forEach(row => {
            html += '<tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">';
            columns.forEach(col => {
                let val = row[col] ?? '—';
                const key = String(val).toLowerCase().trim();
                if (STATUS_COLOR.hasOwnProperty(key)) {
                    const color = STATUS_COLOR[key];
                    val = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border ${BADGE_CLASS[color]}">${humanize(key)}</span>`;
                }
                html += `<td class="px-6 py-3 text-stone-700 font-data tabnum">${val}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table>';

        html += `
            <div id="reportPagination" class="flex items-center justify-between px-6 py-4 border-t border-stone-100">
                <p class="text-xs text-stone-400">Page <span class="font-bold text-stone-700">${data.current_page}</span> of ${data.last_page}</p>
                <div class="flex items-center gap-2">
                    <button type="button" class="page-btn flex items-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-1.5 hover:bg-stone-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-page="${data.current_page - 1}" ${data.current_page <= 1 ? 'disabled' : ''}>${ICON.chevronLeft} Previous</button>
                    <button type="button" class="page-btn flex items-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-1.5 hover:bg-stone-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-page="${data.current_page + 1}" ${data.current_page >= data.last_page ? 'disabled' : ''}>Next ${ICON.chevronRight}</button>
                </div>
            </div>`;

        $('#reportTable').html(html);
    }

    $(document).on('click', '.page-btn:not(:disabled)', function () {
        generateReport(Number($(this).data('page')));
    });

    function clearResults() {
        $('#reportTable').html(`
            <div class="flex flex-col items-center text-center py-14 px-6">
                <div class="w-10 h-10 rounded-full bg-clsu-50 text-clsu-700 flex items-center justify-center mb-3">${ICON.filter}</div>
                <p class="text-sm font-semibold text-stone-700">Select your criteria above and click Update Results.</p>
            </div>
        `);
        $('#reportSummary').addClass('hidden').removeClass('flex').html('');
        toggleExport(false);
    }

    function toggleExport(enabled) {
        $('#exportBtn').prop('disabled', !enabled);
    }

    /* ------------------- GENERATE / EXPORT ------------------- */
    window.generateReport = function (page = 1) {
        const payload = buildPayload();
        renderSummary(payload);
        showSkeleton();

        axios.post('{{ route('reports.generate') }}?page=' + page, payload)
            .then(res => renderTable(res.data))
            .catch(err => {
                console.error(err);
                $('#reportTable').html('<div class="p-6 text-sm text-ember-600">Error loading report. Please try again.</div>');
            });
    };

    $('#generateBtn').on('click', () => generateReport(1));

    $('#exportBtn').on('click', function () {
        const payload = buildPayload();

        axios.post('{{ route('reports.export') }}', payload, { responseType: 'blob' })
            .then(response => {
                const blob = new Blob([response.data]);
                const link = document.createElement('a');
                let filename = 'report.xlsx';

                const disposition = response.headers['content-disposition'];
                if (disposition && disposition.includes('filename=')) {
                    const match = disposition.match(/filename="?([^"]+)"?/);
                    if (match && match[1]) filename = match[1];
                }

                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                link.click();
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Export failed', text: 'Please try again.', timer: 2000, showConfirmButton: false });
            });
    });

    /* ------------------- RESET ------------------- */
    $('#resetBtn').on('click', function () {
        state.reportType = 'booking';
        state.dateType = 'monthly';
        state.filters = { booking_status: ['all'], payment_status: ['all'], gateway: ['all'] };

        $('#date_month').val('');
        $('#date_year').val('');
        $('#date_from, #date_to').val('');

        renderReportType();
        renderDateType();
        renderAllFilterChips();
        clearResults();
    });

    /* ------------------- INIT ------------------- */
    const currentYear = new Date().getFullYear();
    const startYear = 2025;
    const yearSelect = $('#date_year');
    for (let y = currentYear; y >= startYear; y--) {
        yearSelect.append(`<option value="${y}">${y}</option>`);
    }

    renderReportType();
    renderDateType();
    renderAllFilterChips();
    toggleExport(false);
});
</script>
@endpush
