@props([
    'docs' => [],
])

<div class="card transactions-log-card">
    <x-aais.ui.card-header
        class="transactions-log-header"
        title="Complete Document Log"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z'/><path d='M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3'/></svg>"
    >
        <x-slot:actions>
            <button class="btn btn-outline btn-sm">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4m14-7l-5 5l-5-5m5 5V3"/></svg>
                Export Log
            </button>
        </x-slot:actions>
    </x-aais.ui.card-header>

    <x-aais.transactions.filter-bar />

    <div class="scroll-x transactions-table-wrap">
        <table class="data-table transactions-table">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Client Name</th>
                    <th>Document Type</th>
                    <th>Office</th>
                    <th>Staff</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($docs as $idx => $doc)
                    <x-aais.transactions.table-row :doc="$doc" :idx="$idx" />
                @endforeach
            </tbody>
        </table>

        <x-aais.transactions.empty-state />
    </div>

    <div class="card-footer transactions-footer">
        <span class="transactions-footer-note">Showing <strong x-text="visibleCount"></strong> of <strong x-text="docs.length"></strong> transactions</span>
        <div class="transactions-pagination">
            <button class="btn btn-outline btn-sm" disabled>&larr; Prev</button>
            <button class="btn btn-primary btn-sm">1</button>
            <button class="btn btn-outline btn-sm">2</button>
            <button class="btn btn-outline btn-sm">3</button>
            <button class="btn btn-outline btn-sm">Next &rarr;</button>
        </div>
    </div>
</div>
