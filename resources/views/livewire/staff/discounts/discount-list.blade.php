<div wire:poll.60s>
    @php
        $statusMeta = [
            'pending'  => ['badge' => 'bg-palay-100 text-palay-800 border-palay-200', 'dot' => 'bg-palay-500'],
            'approved' => ['badge' => 'bg-clsu-50 text-clsu-700 border-clsu-200',     'dot' => 'bg-clsu-500'],
            'rejected' => ['badge' => 'bg-ember-50 text-ember-700 border-ember-200',  'dot' => 'bg-ember-500'],
        ];
        $tabs = [
            ''         => 'All requests',
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
    @endphp

    <x-admin.ui.section-card icon="tag" title="Verification Queue" :subtitle="$discounts->total() . ' request' . ($discounts->total() === 1 ? '' : 's') . ' · refreshes automatically'" :delay="40">
        {{-- Exports live on the queue they export, not in the page header —
             four full-size buttons up there outweighed the page title.

             data-no-loader: these serve a spreadsheet download and leave the
             page where it is, so the navigation curtain
             (partials/page-loader) must not be raised for them. --}}
        <x-slot:actions>
            <span class="section-label hidden sm:inline">Export</span>
            <x-admin.ui.button variant="secondary" size="sm" data-no-loader :href="route('reports.discounts.all')">
                <x-admin.ui.icon name="download" class="w-3.5 h-3.5" />
                All
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" size="sm" data-no-loader :href="route('reports.discounts.pending')">
                <x-admin.ui.icon name="download" class="w-3.5 h-3.5" />
                Pending
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" size="sm" data-no-loader :href="route('reports.discounts.approved')">
                <x-admin.ui.icon name="download" class="w-3.5 h-3.5" />
                Approved
            </x-admin.ui.button>
            <x-admin.ui.button variant="secondary" size="sm" data-no-loader :href="route('reports.discounts.rejected')">
                <x-admin.ui.icon name="download" class="w-3.5 h-3.5" />
                Rejected
            </x-admin.ui.button>
        </x-slot:actions>


        {{-- Status pills + sort --}}
        <div class="filter-row mb-4">
            <span class="filter-row-label">Status</span>
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="$set('status', '{{ $key }}')"
                        @class(['filter-tab', 'selected' => $status === $key])>
                    {{ $label }}
                    <span class="ft-count">{{ $counts[$key] ?? 0 }}</span>
                </button>
            @endforeach
            <div class="filter-toolbar-spacer"></div>
            <x-admin.ui.density-switch />
            <span class="refresh-chip" wire:loading.delay.flex wire:target="status, sort, gotoPage, previousPage, nextPage">
                <svg class="spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
                Updating
            </span>
            <select wire:model.live="sort" class="filter-select" aria-label="Sort order">
                <option value="">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="checkin">Soonest check-in</option>
            </select>
        </div>

        @if($discounts->isEmpty())
            <x-admin.ui.empty-state icon="tag" title="No discount requests in this view." />
        @else
            <div class="wire-panel" wire:loading.delay.class="is-refreshing" wire:target="status, sort, gotoPage, previousPage, nextPage">
            <div class="scroll-x -mx-6 border-t border-stone-100">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Request</th>
                            <th>Guest</th>
                            <th>Booking</th>
                            <th>Check-in</th>
                            <th>IDs</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $statusClassMap = ['pending' => 'status-pending', 'approved' => 'status-completed', 'rejected' => 'status-rejected']; @endphp
                        @foreach($discounts as $discount)
                            @php
                                $sClass = $statusClassMap[$discount->status] ?? 'status-neutral';
                            @endphp
                            <tr>
                                <td><span class="ref-code">DR-{{ str_pad($discount->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                <td>
                                    <div class="cell-name">
                                        <x-admin.ui.avatar />
                                        <div class="cell-name-text">
                                            <p class="cell-name-primary">{{ $discount->booking->guest_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-data tabnum text-muted">#{{ $discount->booking->id }}</td>
                                <td class="font-data tabnum whitespace-nowrap">{{ \Carbon\Carbon::parse($discount->booking->check_in)->format('M d, Y') }}</td>
                                <td class="font-data tabnum text-muted">{{ $discount->files->count() }}</td>
                                <td class="font-data tabnum whitespace-nowrap text-faint">{{ $discount->created_at->format('M d, Y · h:i A') }}</td>
                                <td><span class="status {{ $sClass }}">{{ ucfirst($discount->status) }}</span></td>
                                <td class="text-right">
                                    <div class="table-actions justify-end">
                                        <a href="{{ route('staff.discounts.show', $discount->id) }}"
                                           class="review-discount btn btn-outline btn-sm"
                                           data-discount-id="{{ $discount->id }}">
                                            <x-admin.ui.icon name="eye" class="w-3.5 h-3.5" />
                                            Review
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $discounts->links('vendor.pagination.admin') }}
            </div>
            </div>
        @endif
    </x-admin.ui.section-card>
</div>
