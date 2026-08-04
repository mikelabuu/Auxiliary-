@extends('layouts.admin')

@section('title', 'Admin - Reports')
@section('page-title', 'Reports')

@php
    /**
     * The chip lists, in one place.
     *
     * These were seven, four and four hand-written <button>s, each carrying the
     * same 140-character class string. Two statuses had gone missing in the
     * copying and nothing could have caught it: a status with no chip is not a
     * broken filter, it is a filter that silently cannot be asked for. The
     * booking list had no 'active', so in-house stays were unreportable, and
     * the payment list had no 'awaiting_verification' — the queue this hostel's
     * whole manual-settlement flow runs on.
     *
     * Booking statuses come from the model constant that already declares
     * itself the single source of truth, so that half can no longer drift at
     * all. Payment statuses have no such constant, so they are spelled out —
     * but spelled out once, next to the list they have to agree with.
     */
    $bookingStatusChips = collect(\App\Models\Booking::STATUSES)
        ->mapWithKeys(fn ($s) => [$s => \Illuminate\Support\Str::of($s)->replace('_', ' ')->title()->toString()])
        ->all();

    $paymentStatusChips = [
        'pending'               => 'Pending',
        'awaiting_verification' => 'Awaiting Verification',
        'success'               => 'Successful',
        'failed'                => 'Failed',
        'rejected'              => 'Rejected',
    ];

    // 'sandbox' is deliberately absent: the simulated card gateway was removed
    // when settlement became upload-a-receipt, and the only rows still holding
    // it are historical. 'All Gateways' still includes them.
    $gatewayChips = [
        'gcash'         => 'GCash',
        'bank_transfer' => 'Bank Transfer',
        'manual'        => 'Manual / Walk-in',
    ];

    $chipClass = 'filter-chip text-xs font-semibold px-3 py-1.5 rounded-full border border-stone-200 bg-white text-stone-500 hover:bg-stone-50 hover:text-stone-700 transition-colors cursor-pointer';
@endphp

@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header subtitle="Generate, filter, and export booking and payment data.">
        Analytics &amp; Reporting
        <x-slot:actions>
            {{-- Two buttons rather than a format dropdown: the choice is not a
                 setting to configure, it is which of two jobs you are doing —
                 keep working on the numbers, or hand the report to someone. --}}
            <x-admin.ui.button variant="secondary" type="button" id="exportPdfBtn" disabled>
                <x-admin.ui.icon name="file" class="w-4 h-4" stroke-width="2" />
                PDF
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" type="button" id="exportBtn" disabled>
                <x-admin.ui.icon name="download" class="w-4 h-4" stroke-width="2" />
                Excel
            </x-admin.ui.button>
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
                    <button type="button" class="{{ $chipClass }}" data-filter-group="booking_status" data-filter-value="all">All Statuses</button>
                    @foreach ($bookingStatusChips as $value => $label)
                        <button type="button" class="{{ $chipClass }}" data-filter-group="booking_status" data-filter-value="{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div id="group_payment_status" class="filter-group">
                <p class="text-[10px] font-bold text-stone-500 tracking-widest uppercase mb-2">Payment Status</p>
                <div class="flex flex-wrap gap-1.5" data-filter-chips="payment_status">
                    <button type="button" class="{{ $chipClass }}" data-filter-group="payment_status" data-filter-value="all">All Statuses</button>
                    @foreach ($paymentStatusChips as $value => $label)
                        <button type="button" class="{{ $chipClass }}" data-filter-group="payment_status" data-filter-value="{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div id="group_gateway" class="filter-group">
                <p class="text-[10px] font-bold text-stone-500 tracking-widest uppercase mb-2">Gateway</p>
                <div class="flex flex-wrap gap-1.5" data-filter-chips="gateway">
                    <button type="button" class="{{ $chipClass }}" data-filter-group="gateway" data-filter-value="all">All Gateways</button>
                    @foreach ($gatewayChips as $value => $label)
                        <button type="button" class="{{ $chipClass }}" data-filter-group="gateway" data-filter-value="{{ $value }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-4 border-t border-stone-100">
                <div id="reportSummary" class="hidden flex-wrap items-center gap-1.5"></div>
                <div class="flex items-center gap-2.5 sm:ml-auto">
                    <x-admin.ui.button variant="secondary" type="button" id="resetBtn">Reset</x-admin.ui.button>
                    {{-- The dot is the only thing telling you a chip you just
                         clicked has not been applied yet. Filters compose
                         without querying, so without it the table simply does
                         not move and that reads as a broken filter. --}}
                    <x-admin.ui.button variant="primary" type="button" id="generateBtn" class="relative">
                        <span id="dirtyDot" class="hidden absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-palay-400 ring-2 ring-white"></span>
                        <x-admin.ui.icon name="refresh" class="w-4 h-4" stroke-width="2" />
                        Update Results
                    </x-admin.ui.button>
                </div>
            </div>
        </div>
    </x-admin.ui.section-card>

    {{-- Totals, above the table.
         Without these the only way to answer "how much did we take in March"
         was to export and sum a column in Excel — the report could show you
         every row of the answer and not the answer. --}}
    <div id="reportTotals" class="grid grid-cols-1 sm:grid-cols-3 gap-3"></div>

    <!-- Results -->
    <x-admin.ui.section-card icon="chart-bar" title="Report Results" :delay="80">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <label for="perPage" class="text-[10px] font-bold text-stone-500 tracking-widest uppercase">Rows</label>
                <select id="perPage" class="px-2.5 py-1.5 rounded-lg border border-stone-200 bg-white text-stone-700 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors">
                    @foreach (\App\Services\ReportService::PAGE_SIZES as $size)
                        <option value="{{ $size }}">{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot:actions>

        <div id="reportTableContainer" class="-mx-6 -mb-6 border-t border-stone-100">
            <div id="reportTable"></div>
        </div>
    </x-admin.ui.section-card>
</div>
@endsection

{{-- Behaviour: resources/js/pages/admin-reports.js (bundled via admin.js) --}}
@push('scripts')
<script type="application/json" id="admin-reports-data">@json([
    'generate' => route('reports.generate'),
    'export'   => route('reports.export'),
])</script>
@endpush
