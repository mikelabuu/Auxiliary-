{{--
    Booking Insights modal — shared by the admin and front-desk dashboards.
    Opened by a header button with id="openInsightsBtn".
    Expects: $labels, $values, $revenueValues (all 12-length, Jan..Dec),
             $peakMonthName, $peakMonthCount.

    The four KPIs are an expandable bento grid (vengenceui expandable-bento-grid,
    ported via resources/js/expandable-bento.js): each card morphs into a large
    centered card revealing a per-month breakdown. The combo chart stays a
    large, always-rendered cell below (its canvas must never be display:none or
    Chart.js can't size it).
--}}
@php
    $totalBookings = array_sum($values);
    $totalRevenue = array_sum($revenueValues);
    $activeMonths = collect($values)->filter(fn ($v) => $v > 0)->count();
    $avgPerMonth = $activeMonths ? round($totalBookings / $activeMonths, 1) : 0;

    $maxBookings = max(1, max($values));
    $maxRevenue = max(1, max($revenueValues));
    // Months with data, ranked by bookings (for the Peak breakdown).
    $ranked = collect($labels)
        ->map(fn ($lbl, $i) => ['label' => $lbl, 'b' => $values[$i], 'r' => $revenueValues[$i]])
        ->filter(fn ($m) => $m['b'] > 0)
        ->sortByDesc('b')
        ->values();
@endphp
<x-admin.ui.modal id="bookingInsightsModal" icon="chart-bar" title="Booking Insights" max-width="2xl" scroll-body>
    <div class="modal-body">
        <div class="flex items-center justify-between gap-3 mb-4">
            <p class="text-xs text-stone-500">Tap a metric to expand · bookings &amp; revenue by month</p>
            <span class="text-xs font-semibold text-stone-500 bg-stone-50 border border-stone-200 rounded-full px-3 py-1.5">{{ date('Y') }}</span>
        </div>

        {{-- KPI bento grid — each card expands to a month breakdown --}}
        <div data-bento class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
            {{-- Total Bookings --}}
            <div data-bento-item role="button" tabindex="0" aria-expanded="false" aria-label="Total bookings — monthly breakdown"
                 class="card bento-item overflow-hidden">
                <div data-bento-summary>
                    <div class="px-3.5 py-3">
                        <p class="text-2xs font-bold uppercase tracking-wider text-faint">Total Bookings</p>
                        <p class="text-xl font-bold text-stone-900 tabnum mt-0.5">{{ number_format($totalBookings) }}</p>
                    </div>
                </div>
                <div data-bento-detail>
                    <p class="text-xs text-stone-500 mb-3">Bookings per month · {{ date('Y') }}</p>
                    <ul class="space-y-1.5">
                        @foreach ($labels as $i => $lbl)
                            <li class="flex items-center gap-3">
                                <span class="w-8 text-[11px] font-semibold text-stone-500 tabnum">{{ $lbl }}</span>
                                <span class="flex-1 h-2 rounded-full bg-stone-100 overflow-hidden"><span class="block h-full rounded-full bg-clsu-500" style="width:{{ $values[$i] ? max(4, round($values[$i] / $maxBookings * 100)) : 0 }}%"></span></span>
                                <span class="w-8 text-right text-xs font-bold text-stone-800 tabnum">{{ $values[$i] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Total Revenue --}}
            <div data-bento-item role="button" tabindex="0" aria-expanded="false" aria-label="Total revenue — monthly breakdown"
                 class="card bento-item overflow-hidden">
                <div data-bento-summary>
                    <div class="px-3.5 py-3">
                        <p class="text-2xs font-bold uppercase tracking-wider text-faint">Total Revenue</p>
                        <p class="text-xl font-bold text-clsu-700 tabnum mt-0.5">₱{{ number_format($totalRevenue) }}</p>
                    </div>
                </div>
                <div data-bento-detail>
                    <p class="text-xs text-stone-500 mb-3">Revenue per month · {{ date('Y') }}</p>
                    <ul class="space-y-1.5">
                        @foreach ($labels as $i => $lbl)
                            <li class="flex items-center gap-3">
                                <span class="w-8 text-[11px] font-semibold text-stone-500 tabnum">{{ $lbl }}</span>
                                <span class="flex-1 h-2 rounded-full bg-stone-100 overflow-hidden"><span class="block h-full rounded-full bg-palay-400" style="width:{{ $revenueValues[$i] ? max(4, round($revenueValues[$i] / $maxRevenue * 100)) : 0 }}%"></span></span>
                                <span class="w-20 text-right text-xs font-bold text-stone-800 tabnum">₱{{ number_format($revenueValues[$i]) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Peak Month --}}
            <div data-bento-item role="button" tabindex="0" aria-expanded="false" aria-label="Peak month — ranking"
                 class="card bento-item overflow-hidden">
                <div data-bento-summary>
                    <div class="px-3.5 py-3">
                        <p class="text-2xs font-bold uppercase tracking-wider text-faint">Peak Month</p>
                        <p class="text-sm font-bold text-palay-700 mt-1.5">{{ $peakMonthName }} <span class="text-faint font-medium">· {{ $peakMonthCount }}</span></p>
                    </div>
                </div>
                <div data-bento-detail>
                    <p class="text-xs text-stone-500 mb-3">Busiest months by bookings</p>
                    @if ($ranked->isEmpty())
                        <p class="text-[13px] text-faint">No bookings recorded yet this year.</p>
                    @else
                        <ol class="space-y-1.5">
                            @foreach ($ranked as $idx => $m)
                                <li class="flex items-center gap-3 rounded-lg px-2.5 py-1.5 {{ $idx === 0 ? 'bg-palay-50 border border-palay-200' : '' }}">
                                    <span class="w-5 text-xs font-bold {{ $idx === 0 ? 'text-palay-700' : 'text-faint' }} tabnum">{{ $idx + 1 }}</span>
                                    <span class="flex-1 text-sm font-semibold text-stone-800">{{ $m['label'] }}</span>
                                    <span class="text-xs font-bold text-stone-700 tabnum">{{ $m['b'] }} <span class="text-faint font-medium">bookings</span></span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>

            {{-- Avg / Active Month --}}
            <div data-bento-item role="button" tabindex="0" aria-expanded="false" aria-label="Average per active month"
                 class="card bento-item overflow-hidden">
                <div data-bento-summary>
                    <div class="px-3.5 py-3">
                        <p class="text-2xs font-bold uppercase tracking-wider text-faint">Avg / Active Month</p>
                        <p class="text-xl font-bold text-stone-900 tabnum mt-0.5">{{ $avgPerMonth }}</p>
                    </div>
                </div>
                <div data-bento-detail>
                    <p class="text-xs text-stone-500 mb-3">How the average is derived</p>
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <div class="rounded-xl border border-stone-200 bg-stone-50/60 px-3 py-2.5 text-center">
                            <p class="text-lg font-bold text-stone-900 tabnum">{{ number_format($totalBookings) }}</p>
                            <p class="text-2xs font-semibold uppercase tracking-wide text-faint">Bookings</p>
                        </div>
                        <div class="rounded-xl border border-stone-200 bg-stone-50/60 px-3 py-2.5 text-center">
                            <p class="text-lg font-bold text-stone-900 tabnum">{{ $activeMonths }}</p>
                            <p class="text-2xs font-semibold uppercase tracking-wide text-faint">Active mo.</p>
                        </div>
                        <div class="rounded-xl border border-clsu-200 bg-clsu-50 px-3 py-2.5 text-center">
                            <p class="text-lg font-bold text-clsu-700 tabnum">{{ $avgPerMonth }}</p>
                            <p class="text-2xs font-semibold uppercase tracking-wide text-clsu-600/70">Average</p>
                        </div>
                    </div>
                    <p class="text-[13px] text-stone-500 leading-relaxed">Across <span class="font-semibold text-stone-700">{{ $activeMonths }}</span> {{ $activeMonths === 1 ? 'month' : 'months' }} with at least one booking, the hostel averaged <span class="font-semibold text-stone-700">{{ $avgPerMonth }}</span> bookings per active month.</p>
                </div>
            </div>
        </div>

        {{-- Combo chart: bookings (bars) + revenue (line) — always rendered --}}
        <div class="card px-4 py-3.5">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-sm font-bold text-stone-800">Bookings &amp; Revenue</p>
                    <p class="text-xs text-stone-500">Monthly combo · {{ date('Y') }}</p>
                </div>
            </div>
            <div class="relative h-72">
                <canvas id="insightsChart"></canvas>
            </div>
        </div>
    </div>
</x-admin.ui.modal>

@push('scripts')
<script src="{{ asset('vendor/chart.js/chart.umd.min.js') }}"></script>
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
