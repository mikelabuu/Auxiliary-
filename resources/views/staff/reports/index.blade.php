@extends('layouts.admin')

@section('title', 'Admin - Reports')
@section('page-title', 'Reports')

@section('content')
<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header subtitle="Generate, filter, and export booking and payment data.">
        Analytics &amp; Reporting
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" type="button" id="exportBtn" disabled>
                <x-admin.ui.icon name="download" class="w-4 h-4" stroke-width="2" />
                Export Excel
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
                    <x-admin.ui.button variant="secondary" type="button" id="resetBtn">Reset</x-admin.ui.button>
                    <x-admin.ui.button variant="primary" type="button" id="generateBtn">
                        <x-admin.ui.icon name="refresh" class="w-4 h-4" stroke-width="2" />
                        Update Results
                    </x-admin.ui.button>
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

{{-- Behaviour: resources/js/pages/admin-reports.js (bundled via admin.js) --}}
@push('scripts')
<script type="application/json" id="admin-reports-data">@json([
    'generate' => route('reports.generate'),
    'export'   => route('reports.export'),
])</script>
@endpush
