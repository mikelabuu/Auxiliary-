@php
    // Must be defined before the component tag: :subtitle is evaluated when
    // the tag opens, so defining $tabs later leaves it undefined there.
    $tabs = [
        'checkins' => ['label' => 'Check-ins', 'icon' => 'log-in'],
        'checkouts' => ['label' => 'Check-outs', 'icon' => 'log-out'],
        'noshow' => ['label' => 'No Shows', 'icon' => 'block'],
        'expiry' => ['label' => 'Expiries', 'icon' => 'clock'],
        'cancellations' => ['label' => 'Cancellations', 'icon' => 'x'],
    ];
@endphp
<x-admin.ui.section-card icon="clock" title="Activity Log" :subtitle="($tabs[$tab]['label'] ?? 'Logs') . ' · ' . $logs->total() . ' records'" :delay="40">

    {{-- View tabs --}}
    <div class="filter-row mb-4">
        <span class="filter-row-label">View</span>
        @foreach ($tabs as $key => $meta)
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
               @class(['filter-tab', 'selected' => $tab === $key])>
                <x-admin.ui.icon :name="$meta['icon']" class="w-4 h-4" stroke-width="2" />
                {{ $meta['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="filter-toolbar">
        <div class="filter-search">
            <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search booking ID or guest name…" aria-label="Search logs">
        </div>
        <div class="filter-toolbar-spacer"></div>
        <x-admin.ui.density-switch />
        <span class="refresh-chip" wire:loading.delay.flex wire:target="search, tab, resetFilters, gotoPage, previousPage, nextPage">
            <svg class="spinner-inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9" class="opacity-20"/><path d="M21 12a9 9 0 0 0-9-9"/></svg>
            Updating
        </span>
        @if($search)
            <button type="button" wire:click="resetFilters" class="filter-clear">
                <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
            </button>
        @endif
    </div>

    @if($logs->isEmpty())
        <x-admin.ui.empty-state icon="clock" title="No logs found for this tab." />
    @else
        <div class="wire-panel" wire:loading.delay.class="is-refreshing" wire:target="search, tab, resetFilters, gotoPage, previousPage, nextPage">
        <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Guest</th>

                        @if($tab === 'checkins')
                            <th>Checked In At</th>
                        @elseif($tab === 'checkouts')
                            <th>Checked Out At</th>
                            <th>Method</th>
                        @elseif($tab === 'noshow' || $tab === 'expiry')
                            <th>Previous Status</th>
                            <th>New Status</th>
                            <th>Reason</th>
                            <th>{{ $tab === 'noshow' ? 'Marked At' : 'Expired At' }}</th>
                        @elseif($tab === 'cancellations')
                            <th>Cancelled By</th>
                            <th>Reason</th>
                            <th>Cancelled At</th>
                        @endif

                        <th>Processed By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td><span class="ref-code">BK-{{ $log->booking ? str_pad($log->booking->id, 4, '0', STR_PAD_LEFT) : 'N/A' }}</span></td>
                            <td class="cell-strong">{{ $log->booking->guest_name ?? 'Unknown' }}</td>

                            @if($tab === 'checkins')
                                <td class="font-data tabnum text-muted">{{ $log->checked_in_at }}</td>
                            @elseif($tab === 'checkouts')
                                <td class="font-data tabnum text-muted">{{ $log->checked_out_at }}</td>
                                <td><span class="cell-tag">{{ ucfirst($log->method ?? '-') }}</span></td>
                            @elseif($tab === 'noshow' || $tab === 'expiry')
                                <td class="capitalize text-muted">{{ str_replace('_', ' ', $log->previous_status) }}</td>
                                <td class="capitalize text-muted">{{ str_replace('_', ' ', $log->new_status) }}</td>
                                <td class="text-faint">{{ $log->reason ?? 'N/A' }}</td>
                                <td class="font-data tabnum text-muted">
                                    {{ $tab === 'noshow'
                                        ? ($log->marked_at ?? 'N/A')
                                        : ($log->expired_at ?? 'N/A')
                                    }}
                                </td>
                            @elseif($tab === 'cancellations')
                                <td class="text-muted">{{ ucfirst($log->cancelled_by) }}</td>
                                <td class="text-faint">{{ $log->reason ?? 'N/A' }}</td>
                                <td class="font-data tabnum text-muted">{{ $log->cancelled_at ?? 'N/A' }}</td>
                            @endif

                            <td class="text-faint text-2xs font-bold uppercase tracking-wide">{{ $log->staff->name ?? 'System' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $logs->links('vendor.pagination.admin') }}
        </div>
        </div>
    @endif
</x-admin.ui.section-card>
