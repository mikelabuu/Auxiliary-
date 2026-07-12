@extends('layouts.admin')
@section('title', 'Admin - Payment Hub')
@section('page-title', 'Payment Hub')
@section('content')
@php
    $statusMeta = [
        'success' => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200',   'dot' => 'bg-clsu-500',  'label' => 'Success'],
        'failed'  => ['badge' => 'bg-ember-50 text-ember-700 border-ember-200', 'dot' => 'bg-ember-500', 'label' => 'Failed'],
        'pending' => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200', 'dot' => 'bg-palay-500', 'label' => 'Pending'],
    ];
    $statusTabs = [
        'all'     => ['label' => 'All payments', 'count' => $stats['success'] + $stats['failed'] + $stats['pending']],
        'success' => ['label' => 'Success',      'count' => $stats['success']],
        'pending' => ['label' => 'Pending',      'count' => $stats['pending']],
        'failed'  => ['label' => 'Failed',       'count' => $stats['failed']],
    ];
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    <x-admin.ui.page-header subtitle="Every peso that moved through the system — online, sandbox, and manual payments.">
        Payment <span class="text-clsu-700">Hub</span>
        <x-slot:actions>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.all')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                All
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.cash')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                Cash
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" :href="route('reports.payments.sandbox')">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                Sandbox
            </x-admin.ui.button>
        </x-slot:actions>
    </x-admin.ui.page-header>

    <!-- Ledger stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-admin.ui.stat-card icon="credit-card" badge="ALL TIME" label="Total Collected" :delay="40" dark>
            ₱{{ number_format($stats['collected'], 2) }}
            <x-slot:footnote><p class="text-xs text-clsu-300">Across {{ $stats['success'] }} successful payments</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="check-circle" badge="TODAY" label="Collected Today" :delay="80">
            ₱{{ number_format($stats['collected_today'], 2) }}
            <x-slot:footnote><p class="text-xs text-stone-400">{{ now('Asia/Manila')->format('M d, Y') }}</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="clock" color="palay" badge="AWAITING" label="Pending" :delay="120">
            {{ $stats['pending'] }}
            <x-slot:footnote><p class="text-xs text-stone-400">Started but not settled</p></x-slot:footnote>
        </x-admin.ui.stat-card>

        <x-admin.ui.stat-card icon="block" color="ember" badge="DECLINED" label="Failed" :delay="160">
            {{ $stats['failed'] }}
            <x-slot:footnote><p class="text-xs text-stone-400">Rejected or errored attempts</p></x-slot:footnote>
        </x-admin.ui.stat-card>
    </div>

    <x-admin.ui.section-card icon="receipt" title="Payment Ledger" :subtitle="$payments->total() . ' record' . ($payments->total() === 1 ? '' : 's') . ($search ? ' matching “' . $search . '”' : '')" :delay="200">

        {{-- Status filters --}}
        <div class="filter-row mb-4">
            <span class="filter-row-label">Status</span>
            @foreach ($statusTabs as $key => $meta)
                <a href="{{ route('staff.paymentlogs.index', array_filter(['status' => $key, 'search' => $search, 'sort' => $sort])) }}"
                   @class(['filter-tab', 'selected' => $status === $key]) style="text-decoration:none;">
                    {{ $meta['label'] }}
                    <span class="ft-count">{{ $meta['count'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Search + sort --}}
        <form method="GET" class="filter-toolbar">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Payment ID, booking, or reference…" aria-label="Search payments">
            </div>
            <select name="sort" class="filter-select" aria-label="Sort order">
                <option value="latest" @selected($sort === 'latest')>Newest first</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
            </select>
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
            <div class="filter-toolbar-spacer"></div>
            @if($search || $status !== 'all' || $sort !== 'latest')
                <a href="{{ route('staff.paymentlogs.index') }}" class="filter-clear" style="text-decoration:none;">
                    <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
                </a>
            @endif
        </form>

        @if($payments->isEmpty())
            <x-admin.ui.empty-state icon="credit-card" title="No payments match this view." />
        @else
            <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Payment</th>
                            <th>Booking</th>
                            <th class="text-right">Amount</th>
                            <th>Status</th>
                            <th>Gateway</th>
                            <th>Reference</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $statusClassMap = ['success' => 'status-success', 'failed' => 'status-failed', 'pending' => 'status-pending']; @endphp
                        @foreach ($payments as $payment)
                            @php
                                $sClass = $statusClassMap[$payment->status] ?? 'status-neutral';
                                $sLabel = $statusMeta[$payment->status]['label'] ?? ucfirst($payment->status);
                            @endphp
                            <tr>
                                <td><span class="ref-code">PMT-{{ str_pad($payment->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                <td class="font-data tabnum text-muted">#{{ $payment->booking_id }}</td>
                                <td class="text-right font-data tabnum font-semibold">₱{{ number_format($payment->amount, 2) }}</td>
                                <td><span class="status {{ $sClass }}">{{ $sLabel }}</span></td>
                                <td class="text-faint text-[11px] font-bold uppercase tracking-wide">{{ $payment->gateway ?? '—' }}</td>
                                <td class="font-data tabnum text-muted">{{ $payment->reference_no ?? '—' }}</td>
                                <td class="font-data tabnum text-xs text-faint">{{ $payment->landbank_transaction_id ?? '—' }}</td>
                                <td class="font-data tabnum whitespace-nowrap text-muted">{{ \Carbon\Carbon::parse($payment->created_at)->timezone('Asia/Manila')->format('M d, Y · h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $payments->links() }}
            </div>
        @endif
    </x-admin.ui.section-card>
</div>
@endsection
