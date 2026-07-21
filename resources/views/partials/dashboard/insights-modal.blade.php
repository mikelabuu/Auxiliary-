{{--
    Booking Insights modal — shared by the admin and front-desk dashboards.
    Opened by a header button with id="openInsightsBtn".
    Expects: $labels, $values, $revenueValues (all 12-length, Jan..Dec),
             $peakMonthName, $peakMonthCount.
--}}
@php
    $totalBookings = array_sum($values);
    $totalRevenue = array_sum($revenueValues);
    $activeMonths = collect($values)->filter(fn ($v) => $v > 0)->count();
    $avgPerMonth = $activeMonths ? round($totalBookings / $activeMonths, 1) : 0;
@endphp
<x-admin.ui.modal id="bookingInsightsModal" icon="chart-bar" title="Booking Insights" max-width="2xl" scroll-body>
    <div class="modal-body">
        <div class="flex items-center justify-between gap-3 mb-4">
            <p class="text-xs text-stone-500">Bookings &amp; revenue by month</p>
            <span class="text-xs font-semibold text-stone-500 bg-stone-50 border border-stone-200 rounded-full px-3 py-1.5">{{ date('Y') }}</span>
        </div>

        {{-- KPI strip --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            <div class="rounded-xl border border-stone-200 bg-stone-50/60 px-3.5 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400">Total Bookings</p>
                <p class="text-xl font-bold text-stone-900 tabnum mt-0.5">{{ number_format($totalBookings) }}</p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-stone-50/60 px-3.5 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400">Total Revenue</p>
                <p class="text-xl font-bold text-clsu-700 tabnum mt-0.5">₱{{ number_format($totalRevenue) }}</p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-stone-50/60 px-3.5 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400">Peak Month</p>
                <p class="text-sm font-bold text-palay-700 mt-1.5">{{ $peakMonthName }} <span class="text-stone-400 font-medium">· {{ $peakMonthCount }}</span></p>
            </div>
            <div class="rounded-xl border border-stone-200 bg-stone-50/60 px-3.5 py-3">
                <p class="text-[10px] font-bold uppercase tracking-wider text-stone-400">Avg / Active Month</p>
                <p class="text-xl font-bold text-stone-900 tabnum mt-0.5">{{ $avgPerMonth }}</p>
            </div>
        </div>

        {{-- Combo chart: bookings (bars) + revenue (line) --}}
        <div class="relative h-72">
            <canvas id="insightsChart"></canvas>
        </div>
    </div>
</x-admin.ui.modal>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Booking Insights combo chart — lazy-initialised on first modal open
// (a hidden canvas sizes to 0). Shared partial: admin + front desk.
window.initInsightsChart = (function () {
    let chart = null;
    return function () {
        const el = document.getElementById('insightsChart');
        if (!el || typeof Chart === 'undefined') return;
        if (chart) { chart.resize(); return; }
        chart = new Chart(el.getContext('2d'), {
            data: {
                labels: @json($labels),
                datasets: [
                    {
                        type: 'bar', label: 'Bookings', data: @json($values), yAxisID: 'yB', order: 2,
                        backgroundColor: 'rgba(22,179,100,0.75)', hoverBackgroundColor: 'rgba(9,146,80,0.95)',
                        borderColor: 'rgba(8,116,67,1)', borderWidth: 1, borderRadius: 6, maxBarThickness: 34
                    },
                    {
                        type: 'line', label: 'Revenue', data: @json($revenueValues), yAxisID: 'yR', order: 1,
                        borderColor: 'rgba(214,158,46,1)', backgroundColor: 'rgba(214,158,46,0.12)',
                        borderWidth: 2, tension: 0.35, fill: true, pointRadius: 3, pointHoverRadius: 5,
                        pointBackgroundColor: 'rgba(214,158,46,1)'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, font: { family: "'Geist', sans-serif" }, color: '#51655a' } },
                    tooltip: {
                        callbacks: {
                            label: (c) => c.dataset.yAxisID === 'yR'
                                ? ' Revenue: ₱' + Number(c.parsed.y).toLocaleString()
                                : ' Bookings: ' + c.parsed.y
                        }
                    }
                },
                scales: {
                    yB: { position: 'left', beginAtZero: true, ticks: { stepSize: 1, precision: 0, color: '#8ba295', font: { family: "'Geist', sans-serif" } }, grid: { color: 'rgba(20,32,26,0.06)' } },
                    yR: { position: 'right', beginAtZero: true, ticks: { color: '#c99a2e', font: { family: "'Geist', sans-serif" }, callback: (v) => '₱' + Number(v).toLocaleString() }, grid: { display: false } },
                    x: { ticks: { color: '#51655a', font: { family: "'Geist', sans-serif" } }, grid: { display: false } }
                }
            }
        });
    };
})();

// Open/close wiring (self-contained so it works under either layout).
(function () {
    if (!window.jQuery) return;
    jQuery(function ($) {
        $(document).on('click', '#openInsightsBtn', function () {
            window.openModal('bookingInsightsModal');
            if (window.initInsightsChart) requestAnimationFrame(window.initInsightsChart);
        });
        $('#bookingInsightsModal').on('click', '[data-modal-close]', function () { window.closeModal('bookingInsightsModal'); });
        $(document).on('keydown', function (e) { if (e.key === 'Escape' && $('#bookingInsightsModal').hasClass('flex')) window.closeModal('bookingInsightsModal'); });
    });
})();
</script>
@endpush
