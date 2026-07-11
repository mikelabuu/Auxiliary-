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
            <a href="{{ route('reports.payments.all') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                All
            </a>
            <a href="{{ route('reports.payments.cash') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                Cash
            </a>
            <a href="{{ route('reports.payments.sandbox') }}" class="flex items-center gap-2 text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 active:scale-[0.98] transition-all shadow-sm !no-underline">
                <x-admin.ui.icon name="download" class="w-4 h-4" />
                Sandbox
            </a>
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

        <!-- Status tabs -->
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach ($statusTabs as $key => $meta)
                <a href="{{ route('staff.paymentlogs.index', array_filter(['status' => $key, 'search' => $search, 'sort' => $sort])) }}"
                   class="flex items-center gap-1.5 text-sm font-medium px-4 py-2.5 rounded-xl border transition-colors {{ $status === $key ? 'bg-clsu-700 border-clsu-800 text-white shadow-card' : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50' }}">
                    {{ $meta['label'] }}
                    <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none {{ $status === $key ? 'bg-white/15 text-white' : 'bg-stone-100 text-stone-500' }}">{{ $meta['count'] }}</span>
                </a>
            @endforeach
        </div>

        <!-- Search + sort -->
        <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="relative flex-1 max-w-xs">
                <x-admin.ui.icon name="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Payment ID, booking, or reference…" class="w-full text-sm bg-stone-50 border border-stone-200 rounded-lg pl-10 pr-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 transition-colors">
            </div>
            <select name="sort" class="w-full sm:w-44 px-4 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-700 text-sm focus:outline-none focus:ring-2 focus:ring-clsu-500/25 focus:border-clsu-500 cursor-pointer transition-colors">
                <option value="latest" @selected($sort === 'latest')>Newest first</option>
                <option value="oldest" @selected($sort === 'oldest')>Oldest first</option>
            </select>
            <button type="submit" class="text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 transition-colors cursor-pointer">Apply</button>
            @if($search || $status !== 'all' || $sort !== 'latest')
                <a href="{{ route('staff.paymentlogs.index') }}" class="self-center text-xs font-semibold text-stone-500 hover:text-clsu-700 px-2 transition-colors !no-underline">Clear</a>
            @endif
        </form>

        @if($payments->isEmpty())
            <x-admin.ui.empty-state icon="credit-card" title="No payments match this view." />
        @else
            <div class="-mx-6 -mb-6 border-t border-stone-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-stone-50/70 border-b border-stone-100">
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Payment</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Booking</th>
                            <th class="text-right font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Amount</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Status</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Gateway</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Reference</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Transaction ID</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            @php $meta = $statusMeta[$payment->status] ?? ['badge' => 'bg-stone-100 text-stone-600 border-stone-200', 'dot' => 'bg-stone-400', 'label' => ucfirst($payment->status)]; @endphp
                            <tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $payment->id }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $payment->booking_id }}</td>
                                <td class="px-6 py-3 text-right text-stone-800 font-semibold font-data tabnum">₱{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $meta['badge'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-stone-500 text-[11px] font-bold uppercase tracking-wide">{{ $payment->gateway ?? '—' }}</td>
                                <td class="px-6 py-3 text-stone-600 font-data tabnum">{{ $payment->reference_no ?? '—' }}</td>
                                <td class="px-6 py-3 text-stone-500 font-data tabnum text-xs">{{ $payment->landbank_transaction_id ?? '—' }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum whitespace-nowrap">{{ \Carbon\Carbon::parse($payment->created_at)->timezone('Asia/Manila')->format('M d, Y · h:i A') }}</td>
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
