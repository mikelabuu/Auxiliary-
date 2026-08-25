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

    /*
     * Quick ranges, resolved in the browser against the user's own clock and
     * submitted as an ordinary custom range — so they need no new date_range
     * type on the server and export/validation keep working unchanged.
     *
     * These are the four questions actually asked of this page. Reaching them
     * before meant picking Custom Range and typing two dates, which is a lot of
     * work for "last month".
     */
    $rangePresets = [
        'last7'   => 'Last 7 days',
        'last30'  => 'Last 30 days',
        'last90'  => 'Last 90 days',
        'ytd'     => 'Year to date',
    ];
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
            {{-- Every group below is a real group: role + aria-labelledby, and
                 aria-pressed on each chip. They were bare <button>s before, so
                 a screen reader announced nine unrelated buttons with no way to
                 tell which were on — which for a filter is the only thing that
                 matters. .filter-tab is the console's own chip, shared with the
                 bookings, payments and audit filter bars; these used to carry
                 their own 140-character class string and their own idea of what
                 "selected" looks like. --}}

            <!-- Report category -->
            <div>
                <p id="lbl_reportType" class="filter-row-label !mr-0 block mb-2">Report Category</p>
                <div id="reportTypeGroup" class="filter-row" role="group" aria-labelledby="lbl_reportType">
                    <button type="button" class="filter-tab report-type-btn" data-report-type="booking" aria-pressed="false">Booking Report</button>
                    <button type="button" class="filter-tab report-type-btn" data-report-type="payment" aria-pressed="false">Financial Report</button>
                    <button type="button" class="filter-tab report-type-btn" data-report-type="combined" aria-pressed="false">Combined Overview</button>
                </div>
            </div>

            <!-- Timeframe -->
            <div>
                <p id="lbl_dateType" class="filter-row-label !mr-0 block mb-2">Timeframe</p>
                <div id="dateTypeGroup" class="filter-row mb-3" role="group" aria-labelledby="lbl_dateType">
                    <button type="button" class="filter-tab date-type-btn" data-date-type="monthly" aria-pressed="false">Monthly</button>
                    <button type="button" class="filter-tab date-type-btn" data-date-type="yearly" aria-pressed="false">Yearly</button>
                    <button type="button" class="filter-tab date-type-btn" data-date-type="range" aria-pressed="false">Custom Range</button>
                </div>

                {{-- aria-label rather than a visible <label> for each: the
                     controls are mutually exclusive views of one choice and
                     share the "Timeframe" caption above, so several visible
                     labels would be noise on screen. Without them a screen
                     reader announces an unnamed date field, and only the month
                     picker showed up in an audit at all — the others start
                     hidden, so they were never even flagged. --}}
                <div class="flex flex-wrap items-center gap-3">
                    {{-- Steppers. "Last month" was previously a trip into the
                         native month picker; it is now one click, and holding
                         the period fixed while flipping report type is the
                         other half of how this page is actually used. --}}
                    <div id="monthStepper" class="date-stepper">
                        <button type="button" class="pager-btn" data-step="-1" aria-label="Previous month">
                            <x-admin.ui.icon name="chevron-left" />
                        </button>
                        <input type="month" id="date_month" aria-label="Report month" class="date-stepper-field w-full sm:w-48">
                        <button type="button" class="pager-btn" data-step="1" aria-label="Next month">
                            <x-admin.ui.icon name="chevron-right" />
                        </button>
                    </div>

                    <div id="yearStepper" class="date-stepper hidden">
                        <button type="button" class="pager-btn" data-step="-1" aria-label="Previous year">
                            <x-admin.ui.icon name="chevron-left" />
                        </button>
                        <select id="date_year" aria-label="Report year" class="date-stepper-field w-full sm:w-40 cursor-pointer"></select>
                        <button type="button" class="pager-btn" data-step="1" aria-label="Next year">
                            <x-admin.ui.icon name="chevron-right" />
                        </button>
                    </div>

                    <div id="dateRangeInputs" class="hidden flex-wrap items-center gap-2">
                        <input type="date" id="date_from" aria-label="Report start date" class="date-stepper-field">
                        <span class="text-xs text-faint font-medium">to</span>
                        <input type="date" id="date_to" aria-label="Report end date" class="date-stepper-field">
                    </div>
                </div>

                {{-- Quick ranges, custom-range mode only. --}}
                <div id="rangePresets" class="filter-row mt-2.5 hidden" role="group" aria-label="Quick ranges">
                    @foreach ($rangePresets as $key => $label)
                        <button type="button" class="filter-tab filter-tab-sm range-preset-btn" data-range-preset="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>

                {{-- A backwards range used to reach the server and come back as
                     a 422 under the table, several seconds and one wasted query
                     later. It is knowable here. --}}
                <p id="dateError" class="filter-inline-error hidden" role="alert"></p>
            </div>

            <!-- Status filter chip groups -->
            <div id="group_booking_status" class="filter-group">
                <p id="lbl_booking_status" class="filter-row-label !mr-0 block mb-2">Booking Status</p>
                <div class="filter-row" data-filter-chips="booking_status" role="group" aria-labelledby="lbl_booking_status">
                    <button type="button" class="filter-tab filter-tab-sm" data-filter-group="booking_status" data-filter-value="all" aria-pressed="false">All Statuses</button>
                    @foreach ($bookingStatusChips as $value => $label)
                        <button type="button" class="filter-tab filter-tab-sm" data-filter-group="booking_status" data-filter-value="{{ $value }}" aria-pressed="false">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div id="group_payment_status" class="filter-group">
                <p id="lbl_payment_status" class="filter-row-label !mr-0 block mb-2">Payment Status</p>
                <div class="filter-row" data-filter-chips="payment_status" role="group" aria-labelledby="lbl_payment_status">
                    <button type="button" class="filter-tab filter-tab-sm" data-filter-group="payment_status" data-filter-value="all" aria-pressed="false">All Statuses</button>
                    @foreach ($paymentStatusChips as $value => $label)
                        <button type="button" class="filter-tab filter-tab-sm" data-filter-group="payment_status" data-filter-value="{{ $value }}" aria-pressed="false">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div id="group_gateway" class="filter-group">
                <p id="lbl_gateway" class="filter-row-label !mr-0 block mb-2">Gateway</p>
                <div class="filter-row" data-filter-chips="gateway" role="group" aria-labelledby="lbl_gateway">
                    <button type="button" class="filter-tab filter-tab-sm" data-filter-group="gateway" data-filter-value="all" aria-pressed="false">All Gateways</button>
                    @foreach ($gatewayChips as $value => $label)
                        <button type="button" class="filter-tab filter-tab-sm" data-filter-group="gateway" data-filter-value="{{ $value }}" aria-pressed="false">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-3 pt-4 border-t border-stone-100">
                {{-- What is actually on screen, and a way out of each part of
                     it. The summary used to be a read-only restatement that
                     also lagged: it described the last applied query while the
                     chips above showed the composed one, with nothing saying
                     which was which. Now it says when it is stale, and every
                     applied filter can be dropped from here without hunting
                     for the chip that set it. --}}
                <div id="reportSummary" class="report-summary hidden"></div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <p class="text-2xs text-faint sm:mr-auto">Filters compose here and apply on <b class="text-muted">Update Results</b>.</p>
                    <div class="flex items-center gap-2.5">
                        <x-admin.ui.button variant="secondary" type="button" id="resetBtn">Reset all</x-admin.ui.button>
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
                <label for="perPage" class="text-2xs font-bold text-muted tracking-widest uppercase">Rows</label>
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
