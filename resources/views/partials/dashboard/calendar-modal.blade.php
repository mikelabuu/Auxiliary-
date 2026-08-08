{{--
    Booking Calendar modal — shared by the admin and front-desk dashboards.
    Opened by a header button with id="openCalendarBtn".
    Expects: $calendarData — { 'YYYY-MM-DD': { a:arrivals, d:departures, s:in-house, guests:[{n,r,t,k,st}] } }
--}}
<x-admin.ui.modal id="calendarModal" icon="calendar" title="Booking Calendar" max-width="2xl" scroll-body>
    <div class="modal-body">
        <div class="grid gap-5 md:grid-cols-5">
            {{-- Calendar grid --}}
            <div class="md:col-span-3">
                <div class="flex items-center justify-between mb-3">
                    <button id="calPrev" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg border border-stone-200 text-stone-500 hover:bg-stone-50 hover:text-clsu-700 transition cursor-pointer" aria-label="Previous month">
                        <x-admin.ui.icon name="chevron-left" class="w-4 h-4" stroke-width="2.5" />
                    </button>
                    <p id="calMonthYear" class="text-sm font-bold tracking-wide text-stone-900 tabnum"></p>
                    <button id="calNext" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg border border-stone-200 text-stone-500 hover:bg-stone-50 hover:text-clsu-700 transition cursor-pointer" aria-label="Next month">
                        <x-admin.ui.icon name="chevron-right" class="w-4 h-4" stroke-width="2.5" />
                    </button>
                </div>
                <div class="grid grid-cols-7 text-center text-2xs font-bold text-faint mb-1">
                    <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                </div>
                <div id="calGrid" class="grid grid-cols-7 gap-1"></div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-4 pt-3 border-t border-stone-100 text-2xs font-medium text-stone-500">
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-clsu-700"></span>Today</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-clsu-500"></span>Arrivals</span>
                    <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-palay-400"></span>Departures</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-clsu-50 border border-clsu-200"></span>In-house</span>
                </div>
            </div>
            {{-- Day detail --}}
            <div class="md:col-span-2">
                <div class="rounded-xl border border-stone-200 bg-stone-50/50 h-full p-4 min-h-[300px]">
                    <p id="calSummaryTitle" class="text-sm font-bold text-clsu-800"></p>
                    <p id="calSummarySub" class="text-[11px] text-stone-500 mt-0.5 mb-3"></p>
                    <div id="calDetail" class="space-y-2 max-h-[320px] overflow-y-auto custom-scrollbar"></div>
                </div>
            </div>
        </div>
    </div>
</x-admin.ui.modal>

@push('scripts')
<script>
(function () {
    // Per-date occupancy: { 'YYYY-MM-DD': { a:arrivals, d:departures, s:in-house, guests:[{n,r,t,k,st}] } }
    const calData = @json($calendarData);
    const monthsLong = ["January","February","March","April","May","June","July","August","September","October","November","December"];

    let calDate = new Date();
    let selected = null;

    const el = (id) => document.getElementById(id);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const ymd = (y, m, d) => `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;

    function fillerCell(n) {
        return `<div class="flex items-start justify-center pt-1.5 h-14 rounded-lg text-xs text-stone-300 select-none"><span class="w-6 h-6 flex items-center justify-center">${n}</span></div>`;
    }

    function dayCell(y, m, d) {
        const ds = ymd(y, m, d);
        const info = calData[ds];
        const t = new Date();
        const isToday = d === t.getDate() && m === t.getMonth() && y === t.getFullYear();
        const isSel = ds === selected;

        let cls = 'relative flex flex-col items-center justify-start pt-1.5 pb-1 h-14 rounded-lg text-xs select-none transition text-stone-700 ';
        if (info && info.s > 0) cls += 'bg-clsu-50 ';
        if (info) cls += 'cursor-pointer hover:bg-clsu-100 ';
        if (isSel) cls += 'ring-2 ring-clsu-500 ';

        const numCls = isToday
            ? 'w-6 h-6 flex items-center justify-center rounded-full bg-clsu-700 text-white font-bold'
            : 'w-6 h-6 flex items-center justify-center font-medium';

        let dots = '';
        if (info) {
            const a = info.a > 0 ? `<span class="w-1.5 h-1.5 rounded-full bg-clsu-500"></span>${info.a > 1 ? `<span class="text-2xs font-bold text-clsu-600 leading-none">${info.a}</span>` : ''}` : '';
            const dep = info.d > 0 ? `<span class="w-1.5 h-1.5 rounded-full bg-palay-400"></span>${info.d > 1 ? `<span class="text-2xs font-bold text-palay-600 leading-none">${info.d}</span>` : ''}` : '';
            if (a || dep) dots = `<span class="flex items-center gap-1 mt-1 h-2">${a}${dep}</span>`;
        }
        const attr = info ? ` data-date="${ds}"` : '';
        return `<div class="${cls}"${attr}><span class="${numCls}">${d}</span>${dots}</div>`;
    }

    function showMonthSummary(y, m) {
        const mm = String(m + 1).padStart(2, '0');
        let daysWith = 0;
        Object.keys(calData).forEach(k => { if (k.startsWith(`${y}-${mm}-`)) daysWith++; });
        el('calSummaryTitle').textContent = daysWith > 0 ? `${daysWith} ${daysWith === 1 ? 'day' : 'days'} with guests` : 'No booked dates';
        el('calSummarySub').textContent = `${monthsLong[m]} ${y} · active & upcoming stays`;
        el('calDetail').innerHTML = '<p class="text-[11px] text-faint">Tap a highlighted day to see its guests.</p>';
    }

    function showDayDetail(ds) {
        const info = calData[ds];
        if (!info) return;
        const dObj = new Date(ds + 'T00:00:00');
        el('calSummaryTitle').textContent = dObj.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
        const bits = [];
        if (info.a) bits.push(`${info.a} arrival${info.a > 1 ? 's' : ''}`);
        if (info.d) bits.push(`${info.d} departure${info.d > 1 ? 's' : ''}`);
        bits.push(`${info.guests.length} guest${info.guests.length > 1 ? 's' : ''}`);
        el('calSummarySub').textContent = bits.join(' · ');

        const chip = (k) => k === 'arrival'
            ? '<span class="text-2xs font-bold uppercase tracking-wide text-clsu-700 bg-clsu-50 border border-clsu-200 rounded-full px-1.5 py-0.5">Arrival</span>'
            : k === 'departure'
                ? '<span class="text-2xs font-bold uppercase tracking-wide text-palay-800 bg-palay-100 border border-palay-200 rounded-full px-1.5 py-0.5">Departure</span>'
                : '<span class="text-2xs font-bold uppercase tracking-wide text-stone-500 bg-stone-100 border border-stone-200 rounded-full px-1.5 py-0.5">In-house</span>';

        el('calDetail').innerHTML = info.guests.map(g => `
            <div class="rounded-lg border border-stone-200 bg-white px-3 py-2">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-stone-800 truncate">${esc(g.n)}</p>
                    ${chip(g.k)}
                </div>
                <p class="text-[11px] text-stone-500 mt-0.5">Room ${esc(g.r)} · ${esc(g.t)}</p>
            </div>`).join('');
    }

    function render() {
        if (!el('calGrid')) return;
        const y = calDate.getFullYear();
        const m = calDate.getMonth();
        el('calMonthYear').textContent = `${monthsLong[m]} ${y}`;

        const firstDay = new Date(y, m, 1).getDay();
        const lastDate = new Date(y, m + 1, 0).getDate();
        const prevLast = new Date(y, m, 0).getDate();

        let html = '';
        for (let i = firstDay; i > 0; i--) html += fillerCell(prevLast - i + 1);
        for (let d = 1; d <= lastDate; d++) html += dayCell(y, m, d);
        const nextDays = (7 - ((firstDay + lastDate) % 7)) % 7;
        for (let i = 1; i <= nextDays; i++) html += fillerCell(i);
        el('calGrid').innerHTML = html;

        const mm = String(m + 1).padStart(2, '0');
        if (selected && selected.startsWith(`${y}-${mm}-`)) showDayDetail(selected);
        else showMonthSummary(y, m);
    }

    // Expose so the header button can (re)render on open.
    window.renderDashCalendar = render;

    // Delegated handlers work regardless of when this script runs.
    if (window.jQuery) {
        jQuery(function ($) {
            $(document).on('click', '#calGrid [data-date]', function () { selected = this.getAttribute('data-date'); render(); });
            $(document).on('click', '#calPrev', function () { calDate.setMonth(calDate.getMonth() - 1); selected = null; render(); });
            $(document).on('click', '#calNext', function () { calDate.setMonth(calDate.getMonth() + 1); selected = null; render(); });
            $(document).on('click', '#openCalendarBtn', function () { window.openModal('calendarModal'); requestAnimationFrame(render); });
            $('#calendarModal').on('click', '[data-modal-close]', function () { window.closeModal('calendarModal'); });
            $(document).on('keydown', function (e) { if (e.key === 'Escape' && $('#calendarModal').hasClass('flex')) window.closeModal('calendarModal'); });
            render();
        });
    }
})();
</script>
@endpush
