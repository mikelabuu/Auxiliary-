@props([
    'docs' => [],
    'showCount' => 5,
    'transactionsUrl' => null,
])

@php
    $visibleDocs = array_slice($docs, 0, $showCount);
    $allTransactionsUrl = $transactionsUrl ?? route('aais.admin.transactions');
@endphp

<div class="card" style="overflow:hidden;">
    <x-aais.ui.card-header
        title="Recent Live Transactions"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z'/><path d='M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3'/></svg>"
    >
        <x-slot:actions>
            <div class="dashboard-recent-actions">
                <div class="dashboard-live-meta">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9"/></svg>
                    <span x-text="'Last updated ' + relativeFrom(recentTableUpdatedAt)"></span>
                    <button type="button" class="btn btn-ghost btn-sm btn-icon" aria-label="Refresh recent transactions" @click="refreshRecentTransactions(true)">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 11a8.1 8.1 0 0 0-15.5-2M4 5v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2M20 19v-4h-4"/></svg>
                    </button>
                </div>
                <a href="{{ $allTransactionsUrl }}" class="btn btn-outline btn-sm">
                    View All Transactions &rarr;
                </a>
            </div>
        </x-slot:actions>
    </x-aais.ui.card-header>

    <div class="scroll-x">
        <table class="data-table dashboard-recent-table">
            <thead>
                <tr>
                    <th>Ref Code</th><th>Client Name</th><th>Document Type</th>
                    <th>Office</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($visibleDocs as $idx => $doc)
                    <tr x-show="isVisible({{ $idx }})" x-transition>
                        <td>
                            <button type="button" class="dashboard-ref-link" @click="viewDoc({{ $idx }})" title="Open transaction details">
                                <span class="ref-code">{{ $doc['ref'] }}</span>
                            </button>
                        </td>
                        <td style="font-weight:600;">{{ $doc['name'] }}</td>
                        <td>{{ $doc['type'] }}</td>
                        <td><span class="chip chip-muted">{{ $doc['office'] }}</span></td>
                        <td>
                            <x-aais.ui.status-badge-dynamic
                                class-expr="'status-' + docs[{{ $idx }}].status"
                                text-expr="labels[docs[{{ $idx }}].status]"
                            />
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:4px;">
                                <button class="btn btn-ghost btn-sm btn-icon" title="View Details" @click="viewDoc({{ $idx }})">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
