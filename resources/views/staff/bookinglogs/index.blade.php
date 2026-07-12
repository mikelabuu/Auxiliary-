@extends('layouts.admin')

@section('title', 'Admin - Booking Logs')
@section('page-title', 'Booking Logs')

@section('content')
@php
    $tabs = [
        'checkins' => ['label' => 'Check-ins', 'icon' => 'log-in'],
        'checkouts' => ['label' => 'Check-outs', 'icon' => 'log-out'],
        'noshow' => ['label' => 'No Shows', 'icon' => 'block'],
        'expiry' => ['label' => 'Expiries', 'icon' => 'clock'],
        'cancellations' => ['label' => 'Cancellations', 'icon' => 'x'],
    ];
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">
    <x-admin.ui.page-header subtitle="A history of every check-in, check-out, no-show, expiry, and cancellation.">
        Booking <span class="text-clsu-700">Logs</span>
    </x-admin.ui.page-header>

    <x-admin.ui.section-card icon="clock" title="Activity Log" :subtitle="$tabs[$tab]['label'] . ' · ' . $logs->total() . ' records'" :delay="40">
        {{-- View tabs --}}
        <div class="filter-row mb-4">
            <span class="filter-row-label">View</span>
            @foreach ($tabs as $key => $meta)
                <a href="{{ route('staff.bookinglogs.index', ['tab' => $key, 'search' => $search]) }}"
                   @class(['filter-tab', 'selected' => $tab === $key]) style="text-decoration:none;">
                    <x-admin.ui.icon :name="$meta['icon']" class="w-4 h-4" stroke-width="2" />
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" class="filter-toolbar">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="filter-search">
                <x-admin.ui.icon name="search" class="w-4 h-4" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Search booking ID or guest name…" aria-label="Search logs">
            </div>
            <button type="submit" class="btn btn-outline btn-sm">Search</button>
            <div class="filter-toolbar-spacer"></div>
            @if($search)
                <a href="{{ route('staff.bookinglogs.index', ['tab' => $tab]) }}" class="filter-clear" style="text-decoration:none;">
                    <x-admin.ui.icon name="x" class="w-3 h-3" stroke-width="2.5" /> Clear
                </a>
            @endif
        </form>

        @if($logs->isEmpty())
            <x-admin.ui.empty-state icon="clock" title="No logs found for this tab." />
        @else
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

                                <td class="text-faint text-[11px] font-bold uppercase tracking-wide">{{ $log->staff->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $logs->appends(['tab' => $tab, 'search' => $search])->links() }}
            </div>
        @endif
    </x-admin.ui.section-card>
</div>
@endsection
