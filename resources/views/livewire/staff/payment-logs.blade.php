<div>
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

    <!-- Ledger stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
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
                <button type="button" wire:click="$set('statusFilter', '{{ $key }}')"
                   @class(['filter-tab', 'selected' => $statusFilter === $key])>
                    {{ $meta['label'] }}
                    <span class="ft-count">{{ $meta['count'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- Search + sort --}}
        <div class="filter-toolbar">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Payment ID, booking, guest, or reference…" aria-label="Search payments">
            </div>
            <select wire:model.live="sort" class="filter-select" aria-label="Sort order">
                <option value="latest">Newest first</option>
                <option value="oldest">Oldest first</option>
            </select>
            <div class="filter-toolbar-spacer"></div>
            <span class="refresh-chip" wire:loading.delay.flex wire:target="search, statusFilter, sort, resetFilters, gotoPage, previousPage, nextPage">
                <svg class="spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
                Updating
            </span>
            @if($search || $statusFilter !== 'all' || $sort !== 'latest')
                <button type="button" wire:click="resetFilters" class="filter-clear">
                    <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
                </button>
            @endif
        </div>

        @if($payments->isEmpty())
            <x-admin.ui.empty-state icon="credit-card" title="No payments match this view." />
        @else
            <div class="wire-panel" wire:loading.delay.class="is-refreshing" wire:target="search, statusFilter, sort, resetFilters, gotoPage, previousPage, nextPage">
            <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
                <table class="data-table" data-server-sort>
                    <thead>
                        <tr>
                            <x-admin.ui.sort-th field="id" :active="$sortField" :dir="$sortDirection">Payment</x-admin.ui.sort-th>
                            <x-admin.ui.sort-th field="booking_id" :active="$sortField" :dir="$sortDirection">Booking</x-admin.ui.sort-th>
                            <th>Payer</th>
                            <x-admin.ui.sort-th field="amount" :active="$sortField" :dir="$sortDirection" class="text-right">Amount</x-admin.ui.sort-th>
                            <x-admin.ui.sort-th field="status" :active="$sortField" :dir="$sortDirection">Status</x-admin.ui.sort-th>
                            <x-admin.ui.sort-th field="gateway" :active="$sortField" :dir="$sortDirection">Gateway</x-admin.ui.sort-th>
                            <x-admin.ui.sort-th field="reference_no" :active="$sortField" :dir="$sortDirection">Reference</x-admin.ui.sort-th>
                            <x-admin.ui.sort-th field="landbank_transaction_id" :active="$sortField" :dir="$sortDirection">Transaction ID</x-admin.ui.sort-th>
                            <x-admin.ui.sort-th field="created_at" :active="$sortField" :dir="$sortDirection">Date</x-admin.ui.sort-th>
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
                                <td class="font-medium capitalize">{{ $payment->booking?->guest_name ?? '—' }}</td>
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
                {{ $payments->links('vendor.pagination.admin') }}
            </div>
            </div>
        @endif
    </x-admin.ui.section-card>
</div>
