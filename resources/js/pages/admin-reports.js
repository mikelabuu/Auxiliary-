/**
 * Analytics & Reporting (staff/reports/index).
 *
 * Was ~325 lines inline in the Blade view. Endpoints arrive as a JSON payload
 * in #admin-reports-data; everything else is plain DOM state.
 *
 * Depends on jQuery, window.axios (resources/js/bootstrap.js) and SweetAlert,
 * all loaded by layouts/admin ahead of the module bundle. No-ops off this page.
 */

function initAdminReports() {
    const dataEl = document.getElementById('admin-reports-data');
    if (!dataEl) return;

    const ROUTES = JSON.parse(dataEl.textContent);

    // Active/inactive appearance is applied as full class-string swaps rather
    // than a semantic "is-active" class, so the two states live next to each
    // other here instead of being split across a stylesheet. Tailwind scans
    // this file (@source '../**/*.js' in admin.css), so the classes below are
    // generated normally.
    function setToggleActive(el, markerClass, active) {
        const base = markerClass + ' text-sm font-medium px-4 py-2.5 rounded-xl border transition-colors cursor-pointer';
        const appearance = active
            ? 'bg-clsu-600 border-clsu-700 text-white shadow-card'
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

        let html = '<div class="scroll-x"><table class="data-table"><thead><tr>';
        columns.forEach(col => {
            html += `<th>${humanize(col)}</th>`;
        });
        html += '</tr></thead><tbody>';

        rows.forEach(row => {
            html += '<tr>';
            columns.forEach(col => {
                let val = row[col] ?? '—';
                const key = String(val).toLowerCase().trim();
                if (Object.prototype.hasOwnProperty.call(STATUS_COLOR, key)) {
                    const color = STATUS_COLOR[key];
                    val = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border ${BADGE_CLASS[color]}">${humanize(key)}</span>`;
                }
                html += `<td class="font-data tabnum">${val}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

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
    function generateReport(page = 1) {
        const payload = buildPayload();
        renderSummary(payload);
        showSkeleton();

        window.axios.post(ROUTES.generate + '?page=' + page, payload)
            .then(res => renderTable(res.data))
            .catch(err => {
                console.error(err);
                $('#reportTable').html('<div class="p-6 text-sm text-ember-600">Error loading report. Please try again.</div>');
            });
    }

    $('#generateBtn').on('click', () => generateReport(1));

    $('#exportBtn').on('click', function () {
        const payload = buildPayload();

        window.axios.post(ROUTES.export, payload, { responseType: 'blob' })
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
                window.Swal.fire({ icon: 'error', title: 'Export failed', text: 'Please try again.', timer: 2000, showConfirmButton: false });
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
}

$(initAdminReports);
