/**
 * Analytics & Reporting (staff/reports/index).
 *
 * Was ~325 lines inline in the Blade view. Endpoints arrive as a JSON payload
 * in #admin-reports-data; everything else is plain DOM state.
 *
 * Depends on jQuery, window.axios (resources/js/bootstrap.js) and SweetAlert,
 * all loaded by layouts/admin ahead of the module bundle. No-ops off this page.
 *
 * Two ideas govern how state moves through here:
 *
 *   · The page runs one report on open, so it never greets anyone with an
 *     empty panel and an instruction. Changing a *filter* after that does not
 *     re-query — the criteria block is a form you compose and then submit, and
 *     re-running mid-thought would fire a query per chip. The Update button
 *     grows a marker instead, so a composed-but-unapplied change is visible
 *     rather than merely unapplied.
 *
 *   · Paging, sorting and page size re-query immediately, but against
 *     `lastPayload` — the criteria as they were when Update was last pressed,
 *     not as they are in the form now. Otherwise sorting a table would quietly
 *     apply filter edits the user had not asked for yet, and the rows would
 *     change under a click that only promised to reorder them.
 *
 * Those two together are why the criteria bar under the filters exists, and
 * why it is the one thing on the page that is allowed to act immediately: it
 * describes the report that is *on screen*, so removing a pill from it is an
 * edit to the applied query rather than to the composed one, and re-running is
 * the only honest response. When the two diverge, the bar says so — a 10px dot
 * on a button several hundred pixels from the chip that caused it was the whole
 * of that signal before.
 *
 * The applied criteria are also mirrored into the query string, so a filtered
 * report survives a reload, can be sent to someone, and can be walked back
 * through with the browser's own Back button.
 */

function initAdminReports() {
    const dataEl = document.getElementById('admin-reports-data');
    if (!dataEl) return;

    const ROUTES = JSON.parse(dataEl.textContent);

    // Selected state is a class and an ARIA state, not a class-string swap.
    // These chips are .filter-tab — the same control the bookings, payments and
    // audit filter bars use — so "selected" looks the same everywhere in the
    // console instead of this page having its own two definitions of it. The
    // status chips in particular used to go a pale clsu-50 while the category
    // buttons above them went solid: the same word, two different weights, on
    // one card.
    function setSelected(el, active) {
        el.toggleClass('selected', active).attr('aria-pressed', active ? 'true' : 'false');
    }

    // Every value the status chips can now ask for needs an entry here, or the
    // rows it returns fall through to plain text while the rows beside them
    // wear a badge — which reads as a rendering fault rather than a gap in a
    // lookup table. 'active', 'awaiting_verification' and 'rejected' were the
    // three that had no chip to reach them before.
    const STATUS_COLOR = {
        paid: 'clsu', success: 'clsu', completed: 'clsu', active: 'clsu',
        pending: 'palay', pending_payment: 'palay', pending_discount: 'palay',
        awaiting_verification: 'palay',
        cancelled: 'ember', failed: 'ember', expired: 'ember', rejected: 'ember',
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
        x: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
        pending: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
        warn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    };

    // Labels for the criteria bar. Deriving them from the value would give
    // "Booking Status: Pending Payment" — the group name twice.
    const GROUP_LABEL = {
        booking_status: 'Booking',
        payment_status: 'Payment',
        gateway: 'Gateway',
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
        perPage: 10,
        sort: null,
        direction: 'desc',
    };

    // The criteria as of the last Update. Paging and sorting run against this,
    // never against `state`, for the reason in the file header.
    let lastPayload = null;

    /* ------------------- PENDING-CHANGES MARKER ------------------- */
    // Filters compose without querying, which is only tolerable if "you have
    // unapplied changes" is visible. Without it, clicking a chip and seeing
    // the table not move reads as a broken filter.
    function setDirty(dirty) {
        $('#generateBtn').toggleClass('is-dirty', dirty);
        $('#dirtyDot').toggleClass('hidden', !dirty);
        // The bar is describing the table, which is now behind the form. Said
        // where the change was made, not only as a dot on a distant button.
        $('#reportSummary').toggleClass('is-stale', dirty);
    }

    /* ------------------- REPORT TYPE ------------------- */
    function renderReportType() {
        $('.report-type-btn').each(function () {
            setSelected($(this), $(this).data('report-type') === state.reportType);
        });
        $('.filter-group').addClass('hidden');
        (REPORT_FILTER_GROUPS[state.reportType] || []).forEach(group => {
            $('#group_' + group).removeClass('hidden');
        });
    }

    $('#reportTypeGroup').on('click', '.report-type-btn', function () {
        state.reportType = $(this).data('report-type');
        state.filters = { booking_status: ['all'], payment_status: ['all'], gateway: ['all'] };
        // Columns differ per report type, so a sort held over from the previous
        // one would name a column the new report does not select. The server
        // falls back safely, but the header would still show an arrow on a
        // column that is not doing anything.
        state.sort = null;
        state.direction = 'desc';
        renderReportType();
        renderAllFilterChips();
        setDirty(true);
    });

    /* ------------------- DATE TYPE ------------------- */
    function renderDateType() {
        $('.date-type-btn').each(function () {
            setSelected($(this), $(this).data('date-type') === state.dateType);
        });
        // The steppers wrap the field, so visibility moves to the wrapper —
        // hiding the input alone left two orphan arrows on the row.
        $('#monthStepper').toggleClass('hidden', state.dateType !== 'monthly');
        $('#yearStepper').toggleClass('hidden', state.dateType !== 'yearly');
        $('#dateRangeInputs').toggleClass('hidden', state.dateType !== 'range').toggleClass('flex', state.dateType === 'range');
        $('#rangePresets').toggleClass('hidden', state.dateType !== 'range');
        validateDates();
    }

    $('#dateTypeGroup').on('click', '.date-type-btn', function () {
        state.dateType = $(this).data('date-type');
        renderDateType();
        setDirty(true);
    });

    $('#date_month, #date_year, #date_from, #date_to').on('change', () => {
        validateDates();
        setDirty(true);
    });

    /* ------------------- TIMEFRAME STEPPERS ------------------- */
    // "Last month" was a trip into the native month picker. It is one click now,
    // and stepping a period while holding the report type is most of what this
    // page gets used for.
    $('#monthStepper').on('click', '.pager-btn', function () {
        const step = Number($(this).data('step'));
        const raw = $('#date_month').val() || currentMonth();
        const [y, m] = raw.split('-').map(Number);
        // Date() does the year rollover, so December + 1 is January of the next
        // year rather than month 13.
        const d = new Date(y, (m - 1) + step, 1);
        $('#date_month').val(d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0'));
        setDirty(true);
    });

    $('#yearStepper').on('click', '.pager-btn', function () {
        const step = Number($(this).data('step'));
        const select = $('#date_year');
        const options = select.find('option').map((i, o) => o.value).get();
        // The list is newest-first, so "next year" is one step up the array.
        const i = options.indexOf(String(select.val()));
        const next = i - step;
        if (next < 0 || next >= options.length) return;
        select.val(options[next]);
        setDirty(true);
    });

    /* ------------------- QUICK RANGES ------------------- */
    // Resolved here against the user's own clock and submitted as an ordinary
    // custom range, so the server needs no new date_range type and export and
    // validation keep working unchanged.
    function iso(d) {
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    const RANGE_PRESETS = {
        last7:  () => { const t = new Date(); const f = new Date(); f.setDate(f.getDate() - 6);  return [f, t]; },
        last30: () => { const t = new Date(); const f = new Date(); f.setDate(f.getDate() - 29); return [f, t]; },
        last90: () => { const t = new Date(); const f = new Date(); f.setDate(f.getDate() - 89); return [f, t]; },
        ytd:    () => { const t = new Date(); return [new Date(t.getFullYear(), 0, 1), t]; },
    };

    $('#rangePresets').on('click', '.range-preset-btn', function () {
        const build = RANGE_PRESETS[$(this).data('range-preset')];
        if (!build) return;
        const [from, to] = build();
        $('#date_from').val(iso(from));
        $('#date_to').val(iso(to));
        $('.range-preset-btn').each((i, el) => setSelected($(el), el === this));
        validateDates();
        setDirty(true);
    });

    /* ------------------- DATE VALIDATION ------------------- */
    // A backwards or half-filled range used to reach the server and come back
    // as a 422 rendered under the table, one wasted query later. Both faults
    // are knowable here, so they are said next to the field instead.
    function dateProblem() {
        if (state.dateType === 'monthly' && !$('#date_month').val()) return 'Pick a month to report on.';
        if (state.dateType === 'yearly' && !$('#date_year').val()) return 'Pick a year to report on.';
        if (state.dateType === 'range') {
            const from = $('#date_from').val();
            const to = $('#date_to').val();
            if (!from || !to) return 'A custom range needs both a start and an end date.';
            if (from > to) return 'The start date is after the end date.';
        }
        return null;
    }

    function validateDates() {
        const problem = dateProblem();
        $('#dateError')
            .toggleClass('hidden', !problem)
            .html(problem ? ICON.warn + '<span>' + problem + '</span>' : '');
        return !problem;
    }

    /* ------------------- STATUS FILTER CHIPS ------------------- */
    function renderFilterChips(group) {
        const active = state.filters[group];
        $(`[data-filter-chips="${group}"] [data-filter-value]`).each(function () {
            setSelected($(this), active.includes($(this).data('filter-value').toString()));
        });
    }

    function renderAllFilterChips() {
        Object.keys(state.filters).forEach(renderFilterChips);
    }

    function toggleFilter(group, value) {
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
    }

    $('.filter-group').on('click', '[data-filter-value]', function () {
        toggleFilter($(this).data('filter-group'), $(this).data('filter-value').toString());
        setDirty(true);
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
            per_page: state.perPage,
            sort: state.sort,
            direction: state.direction,
        };
    }

    function humanize(str) {
        return str.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    function peso(value) {
        return '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const REPORT_LABEL = { booking: 'Booking Report', payment: 'Financial Report', combined: 'Combined Overview' };

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => (
            { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
        ));
    }

    /**
     * What the chip for this value says.
     *
     * humanize() on the raw value gets these wrong in both directions: 'gcash'
     * comes out "Gcash", and 'manual' comes out "Manual" when the chip the user
     * clicked reads "Manual / Walk-in". The chip is the label the user chose
     * from, so the summary should echo it rather than invent its own.
     */
    function valueLabel(group, value) {
        const chip = document.querySelector(
            `[data-filter-chips="${group}"] [data-filter-value="${CSS.escape(value)}"]`
        );
        return chip ? chip.textContent.trim() : humanize(value);
    }

    function periodLabel(range) {
        const v = range.value;
        if (v && typeof v === 'object') {
            return v.from && v.to ? `${v.from} → ${v.to}` : 'All time';
        }
        if (!v) return 'All time';
        // 2026-08 is a machine's way of saying August 2026, and this line is
        // read far more often than it is typed.
        const m = /^(\d{4})-(\d{2})$/.exec(String(v));
        if (!m) return String(v);
        const month = new Date(Number(m[1]), Number(m[2]) - 1, 1)
            .toLocaleString(undefined, { month: 'long' });
        return `${month} ${m[1]}`;
    }

    /**
     * What is on screen right now, and a way out of each removable part of it.
     *
     * Reads `payload` — the criteria that produced the current table — never
     * `state`, which is the composed-but-maybe-unapplied version. When the two
     * differ the bar keeps describing the table and says that it is behind.
     */
    function renderSummary(payload) {
        const $bar = $('#reportSummary');

        let html = `<span class="report-summary-label">${ICON.filter} Showing</span>`;
        html += `<span class="report-crumb report-crumb-strong">${escapeHtml(REPORT_LABEL[payload.report_type] || payload.report_type)}</span>`;
        html += `<span class="report-crumb">${escapeHtml(periodLabel(payload.date_range))}</span>`;

        let removable = 0;
        Object.keys(payload.filters || {}).forEach(group => {
            (payload.filters[group] || []).forEach(value => {
                removable++;
                html += `<span class="report-pill">`
                    + `<span class="report-pill-group">${escapeHtml(GROUP_LABEL[group] || humanize(group))}</span>`
                    + `<span>${escapeHtml(valueLabel(group, value))}</span>`
                    + `<button type="button" class="report-pill-x" data-drop-group="${escapeHtml(group)}" data-drop-value="${escapeHtml(value)}"`
                    + ` aria-label="Remove ${escapeHtml(valueLabel(group, value))} from ${escapeHtml(GROUP_LABEL[group] || group)}">${ICON.x}</button>`
                    + `</span>`;
            });
        });

        if (!removable) {
            html += `<span class="report-crumb">No status filters</span>`;
        } else {
            html += `<button type="button" id="clearFiltersBtn" class="report-summary-clear">${ICON.x} Clear ${removable} filter${removable === 1 ? '' : 's'}</button>`;
        }

        html += `<span class="report-summary-stale">${ICON.pending}`
            + `<span>Filters changed — press Update Results to apply them.</span></span>`;

        $bar.html(html).removeClass('hidden');
    }

    /* ------------------- ACTING ON THE CRITERIA BAR ------------------- */
    // These edit the *applied* report, which is the one the bar describes, so
    // they re-run rather than going dirty. Removing a pill and then having to
    // press Update would be asking twice for one thing.
    $('#reportSummary').on('click', '.report-pill-x', function () {
        toggleFilter($(this).data('drop-group'), String($(this).data('drop-value')));
        runReport(1);
    });

    $('#reportSummary').on('click', '#clearFiltersBtn', function () {
        // Only the status/gateway chips. Report type and period are what the
        // report *is*; Reset all is next to the Update button for those.
        state.filters = { booking_status: ['all'], payment_status: ['all'], gateway: ['all'] };
        renderAllFilterChips();
        runReport(1);
    });

    /* ------------------- TOTALS ------------------- */
    function renderTotals(cards) {
        if (!cards || !cards.length) { $('#reportTotals').empty(); return; }

        $('#reportTotals').html(cards.map(card => `
            <div class="card p-4">
                <p class="text-2xs font-bold text-faint tracking-widest uppercase">${humanize(card.label)}</p>
                <p class="text-2xl font-extrabold text-stone-800 tabnum mt-1">${
                    card.format === 'money' ? peso(card.value) : Number(card.value).toLocaleString('en-PH')
                }</p>
            </div>
        `).join(''));
    }

    function skeletonTotals() {
        $('#reportTotals').html(Array(3).fill(`
            <div class="card p-4">
                <div class="h-2.5 w-20 rounded-full bg-stone-100 animate-pulse"></div>
                <div class="h-6 w-28 rounded-lg bg-stone-100 animate-pulse mt-2"></div>
            </div>
        `).join(''));
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
                <p class="text-xs text-faint mt-1 max-w-xs">Try expanding the date range, removing some filters, or switching the report type.</p>
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
            // Every selected column is sortable: the server resolves the alias
            // through the same map that produced the select list, so the two
            // cannot disagree about what exists.
            const dir = state.sort === col ? (state.direction === 'asc' ? 'sort-asc' : 'sort-desc') : '';
            html += `<th class="sortable ${dir}" data-sort-col="${col}" title="Sort by ${humanize(col)}">${humanize(col)}</th>`;
        });
        html += '</tr></thead><tbody>';

        rows.forEach(row => {
            html += '<tr>';
            columns.forEach(col => {
                let val = row[col] ?? '—';
                const key = String(val).toLowerCase().trim();
                if (Object.prototype.hasOwnProperty.call(STATUS_COLOR, key)) {
                    const color = STATUS_COLOR[key];
                    val = `<span class="inline-flex items-center px-2.5 py-1 rounded-full text-2xs font-bold border ${BADGE_CLASS[color]}">${humanize(key)}</span>`;
                }
                html += `<td class="font-data tabnum">${val}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        const from = data.from || 0;
        const to = data.to || 0;
        html += `
            <div id="reportPagination" class="flex items-center justify-between px-6 py-4 border-t border-stone-100">
                <p class="text-xs text-faint">Showing <span class="font-bold text-stone-700">${from}–${to}</span> of <span class="font-bold text-stone-700">${data.total}</span></p>
                <div class="flex items-center gap-2">
                    <button type="button" class="page-btn flex items-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-1.5 hover:bg-stone-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-page="${data.current_page - 1}" ${data.current_page <= 1 ? 'disabled' : ''}>${ICON.chevronLeft} Previous</button>
                    <span class="text-xs text-faint">Page ${data.current_page} of ${data.last_page}</span>
                    <button type="button" class="page-btn flex items-center gap-1.5 text-xs font-semibold text-stone-600 border border-stone-200 bg-white rounded-lg px-3 py-1.5 hover:bg-stone-50 transition-colors disabled:opacity-40 disabled:cursor-not-allowed" data-page="${data.current_page + 1}" ${data.current_page >= data.last_page ? 'disabled' : ''}>Next ${ICON.chevronRight}</button>
                </div>
            </div>`;

        $('#reportTable').html(html);
    }

    /* ------------------- VIEW CONTROLS (re-query, no re-compose) ------------------- */
    $(document).on('click', '.page-btn:not(:disabled)', function () {
        runReport(Number($(this).data('page')), { reuse: true });
    });

    $(document).on('click', '[data-sort-col]', function () {
        const col = $(this).data('sort-col');

        if (state.sort === col) {
            state.direction = state.direction === 'asc' ? 'desc' : 'asc';
        } else {
            state.sort = col;
            state.direction = 'asc';
        }

        // Sorting is server-side, so it orders the whole result set and then
        // pages it — sorting a 200-row report from page 1 does not merely
        // rearrange the ten rows already on screen.
        runReport(1, { reuse: true });
    });

    $('#perPage').on('change', function () {
        state.perPage = Number($(this).val());
        runReport(1, { reuse: true });
    });

    function toggleExport(enabled) {
        $('#exportBtn, #exportPdfBtn').prop('disabled', !enabled);
    }

    /* ------------------- GENERATE ------------------- */
    /**
     * @param {number} page
     * @param {{reuse?: boolean}} opts  reuse: keep the criteria from the last
     *        Update and change only page/sort/size. See the file header.
     */
    function runReport(page = 1, opts = {}) {
        // Only a fresh compose can carry a bad date; a reuse runs the criteria
        // that already succeeded.
        if (!opts.reuse && !validateDates()) {
            setDirty(true);
            $('#dateError')[0].scrollIntoView({ block: 'nearest' });
            return;
        }

        const payload = opts.reuse && lastPayload
            ? Object.assign({}, lastPayload, { per_page: state.perPage, sort: state.sort, direction: state.direction })
            : buildPayload();

        lastPayload = payload;
        renderSummary(payload);
        syncUrl(payload);
        showSkeleton();
        skeletonTotals();
        if (!opts.reuse) setDirty(false);

        window.axios.post(ROUTES.generate + '?page=' + page, payload)
            .then(res => {
                renderTable(res.data.rows);
                renderTotals(res.data.summary);
            })
            .catch(err => {
                console.error(err);
                $('#reportTotals').empty();

                // A 422 knows exactly what is wrong — almost always an unset
                // month or an incomplete custom range. Showing that beats
                // "Please try again", which sends people to re-click the same
                // button and get the same nothing.
                const errors = err.response && err.response.status === 422 && err.response.data.errors;
                const message = errors
                    ? Object.values(errors).flat().join(' ')
                    : 'Could not load the report. Please try again.';

                $('#reportTable').html(`
                    <div class="flex flex-col items-center text-center py-14 px-6">
                        <div class="w-10 h-10 rounded-full bg-ember-50 text-ember-600 flex items-center justify-center mb-3">${ICON.alert}</div>
                        <p class="text-sm font-semibold text-stone-700">${errors ? 'Check your criteria' : 'Something went wrong'}</p>
                        <p class="text-xs text-muted mt-1 max-w-sm">${message}</p>
                    </div>
                `);
                toggleExport(false);
            });
    }

    $('#generateBtn').on('click', () => runReport(1));

    /* ------------------- EXPORT ------------------- */
    function download(format) {
        // The export must match what is on screen, so it goes out with the
        // criteria that produced the current table — not whatever the form has
        // been edited to since.
        const payload = Object.assign({}, lastPayload || buildPayload(), { format });

        const $btn = format === 'pdf' ? $('#exportPdfBtn') : $('#exportBtn');
        $btn.prop('disabled', true);

        window.axios.post(ROUTES.export, payload, { responseType: 'blob' })
            .then(response => {
                const blob = new Blob([response.data]);
                const link = document.createElement('a');
                let filename = 'report.' + format;

                const disposition = response.headers['content-disposition'];
                if (disposition && disposition.includes('filename=')) {
                    const match = disposition.match(/filename="?([^"]+)"?/);
                    if (match && match[1]) filename = match[1];
                }

                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                link.click();
                window.URL.revokeObjectURL(link.href);
            })
            .catch(() => {
                window.Swal.fire({
                    icon: 'error',
                    title: 'Export failed',
                    text: 'Please try again.',
                    timer: 2000,
                    showConfirmButton: false,
                });
            })
            .then(() => $btn.prop('disabled', false));
    }

    $('#exportBtn').on('click', () => download('xlsx'));
    $('#exportPdfBtn').on('click', () => download('pdf'));

    /* ------------------- RESET ------------------- */
    $('#resetBtn').on('click', function () {
        state.reportType = 'booking';
        state.dateType = 'monthly';
        state.filters = { booking_status: ['all'], payment_status: ['all'], gateway: ['all'] };
        state.sort = null;
        state.direction = 'desc';
        state.perPage = 10;

        $('#perPage').val(10);
        $('#date_month').val(currentMonth());
        $('#date_year').val(new Date().getFullYear());
        $('#date_from, #date_to').val('');

        $('.range-preset-btn').each((i, el) => setSelected($(el), false));

        renderReportType();
        renderDateType();
        renderAllFilterChips();
        runReport(1);
    });

    /* ------------------- URL <-> CRITERIA ------------------- */
    /**
     * The applied criteria live in the query string.
     *
     * Reports get sent to people ("the August financials"), and a page that
     * held its whole state in memory could only be shared as instructions for
     * rebuilding it. A reload lost it too, which on a filter panel this size is
     * a real cost. Only what Update applied is written, so the URL and the
     * table on screen always agree.
     */
    const URL_KEYS = { booking_status: 'bs', payment_status: 'ps', gateway: 'gw' };

    // The opening report is where the page already is, so it replaces. Every
    // report after it is somewhere the user asked to go, so it pushes. Keyed on
    // "have we run yet" rather than on whether the URL had a query string:
    // opening a shared link starts *with* one, and testing for that put a
    // duplicate entry in the history before the user had done anything.
    let urlPrimed = false;

    function syncUrl(payload) {
        const q = new URLSearchParams();
        q.set('type', payload.report_type);
        q.set('period', payload.date_range.type);

        const v = payload.date_range.value;
        if (v && typeof v === 'object') {
            if (v.from) q.set('from', v.from);
            if (v.to) q.set('to', v.to);
        } else if (v) {
            q.set('on', String(v));
        }

        Object.keys(URL_KEYS).forEach(group => {
            const values = (payload.filters || {})[group];
            if (values && values.length) q.set(URL_KEYS[group], values.join(','));
        });

        if (state.perPage !== 10) q.set('rows', state.perPage);
        if (payload.sort) { q.set('sort', payload.sort); q.set('dir', payload.direction); }

        const url = window.location.pathname + '?' + q.toString();
        if (urlPrimed) window.history.pushState(null, '', url);
        else window.history.replaceState(null, '', url);
        urlPrimed = true;
    }

    function hydrateFromUrl() {
        const q = new URLSearchParams(window.location.search);
        if (![...q.keys()].length) return;

        const type = q.get('type');
        if (['booking', 'payment', 'combined'].includes(type)) state.reportType = type;

        const period = q.get('period');
        if (['monthly', 'yearly', 'range'].includes(period)) state.dateType = period;

        if (q.get('on')) {
            if (state.dateType === 'monthly') $('#date_month').val(q.get('on'));
            if (state.dateType === 'yearly') $('#date_year').val(q.get('on'));
        }
        if (q.get('from')) $('#date_from').val(q.get('from'));
        if (q.get('to')) $('#date_to').val(q.get('to'));

        Object.keys(URL_KEYS).forEach(group => {
            const raw = q.get(URL_KEYS[group]);
            if (!raw) return;
            // Only values this page actually offers — a hand-edited URL should
            // not be able to put the chips and the query out of step.
            const offered = $(`[data-filter-chips="${group}"] [data-filter-value]`)
                .map((i, el) => String($(el).data('filter-value'))).get();
            const values = raw.split(',').filter(v => offered.includes(v) && v !== 'all');
            if (values.length) state.filters[group] = values;
        });

        const rows = Number(q.get('rows'));
        if ([10, 25, 50, 100].includes(rows)) { state.perPage = rows; $('#perPage').val(rows); }

        const sort = q.get('sort');
        if (sort) { state.sort = sort; state.direction = q.get('dir') === 'asc' ? 'asc' : 'desc'; }
    }

    // Back/forward should move the report, not just the address bar.
    window.addEventListener('popstate', function () {
        state.filters = { booking_status: ['all'], payment_status: ['all'], gateway: ['all'] };
        hydrateFromUrl();
        renderReportType();
        renderDateType();
        renderAllFilterChips();
        runReport(1);
    });

    /* ------------------- INIT ------------------- */
    function currentMonth() {
        const now = new Date();
        return now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    }

    const currentYear = new Date().getFullYear();
    const startYear = 2025;
    const yearSelect = $('#date_year');
    for (let y = currentYear; y >= startYear; y--) {
        yearSelect.append(`<option value="${y}">${y}</option>`);
    }

    // A default the first query can actually run with. The month input starts
    // empty, and 'monthly' with no value fails validation — so the page's own
    // opening request would have 422'd on a field the user had not touched.
    if (!$('#date_month').val()) $('#date_month').val(currentMonth());

    // Before the first render, so a shared link opens on the report it names
    // rather than on the default and then jumping.
    hydrateFromUrl();
    if (!$('#date_month').val()) $('#date_month').val(currentMonth());

    renderReportType();
    renderDateType();
    renderAllFilterChips();
    toggleExport(false);
    setDirty(false);

    runReport(1);
}

$(initAdminReports);
