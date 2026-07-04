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
    <x-admin.page-header subtitle="A history of every check-in, check-out, no-show, expiry, and cancellation.">
        Booking <span class="font-display italic font-medium text-clsu-800">Logs</span>
    </x-admin.page-header>

    <x-admin.section-card icon="clock" title="Activity Log" :subtitle="$tabs[$tab]['label'] . ' · ' . $logs->total() . ' records'" :delay="40">
        <div class="flex flex-wrap gap-2 mb-5">
            @foreach ($tabs as $key => $meta)
                <a href="{{ route('staff.bookinglogs.index', ['tab' => $key, 'search' => $search]) }}"
                   class="flex items-center gap-1.5 text-sm font-medium px-4 py-2.5 rounded-xl border transition-colors {{ $tab === $key ? 'bg-gradient-to-b from-clsu-600 to-clsu-800 border-clsu-800 text-white shadow-card' : 'border-stone-200 bg-white text-stone-600 hover:bg-stone-50' }}">
                    <x-admin.icon :name="$meta['icon']" class="w-4 h-4" stroke-width="2" />
                    {{ $meta['label'] }}
                </a>
            @endforeach
        </div>

        <form method="GET" class="flex flex-col sm:flex-row gap-3 mb-6">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative flex-1 max-w-xs">
                <x-admin.icon name="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400" stroke-width="2" />
                <input type="text" name="search" value="{{ $search }}" placeholder="Search booking ID or guest name…" class="w-full text-sm bg-stone-50 border border-stone-200 rounded-full pl-10 pr-4 py-2.5 text-stone-700 placeholder:text-stone-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-palay-300 focus:border-palay-300 transition-colors">
            </div>
            <button type="submit" class="text-sm font-medium text-clsu-700 border border-clsu-200 bg-white rounded-xl px-4 py-2.5 hover:bg-clsu-50 hover:border-clsu-300 transition-colors cursor-pointer">Search</button>
        </form>

        @if($logs->isEmpty())
            <x-admin.empty-state icon="clock" title="No logs found for this tab." />
        @else
            <div class="-mx-6 -mb-6 border-t border-stone-100 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-stone-50/70 border-b border-stone-100">
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Booking ID</th>
                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Guest</th>

                            @if($tab === 'checkins')
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Checked In At</th>
                            @elseif($tab === 'checkouts')
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Checked Out At</th>
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Method</th>
                            @elseif($tab === 'noshow' || $tab === 'expiry')
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Previous Status</th>
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">New Status</th>
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Reason</th>
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">{{ $tab === 'noshow' ? 'Marked At' : 'Expired At' }}</th>
                            @elseif($tab === 'cancellations')
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Cancelled By</th>
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Reason</th>
                                <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Cancelled At</th>
                            @endif

                            <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $log->booking->id ?? 'N/A' }}</td>
                                <td class="px-6 py-3 text-stone-800 font-medium">{{ $log->booking->guest_name ?? 'Unknown' }}</td>

                                @if($tab === 'checkins')
                                    <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $log->checked_in_at }}</td>
                                @elseif($tab === 'checkouts')
                                    <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $log->checked_out_at }}</td>
                                    <td class="px-6 py-3 text-stone-500 text-[11px] font-bold uppercase tracking-wide">{{ ucfirst($log->method ?? '-') }}</td>
                                @elseif($tab === 'noshow' || $tab === 'expiry')
                                    <td class="px-6 py-3 text-stone-600 capitalize">{{ str_replace('_', ' ', $log->previous_status) }}</td>
                                    <td class="px-6 py-3 text-stone-600 capitalize">{{ str_replace('_', ' ', $log->new_status) }}</td>
                                    <td class="px-6 py-3 text-stone-500">{{ $log->reason ?? 'N/A' }}</td>
                                    <td class="px-6 py-3 text-stone-700 font-data tabnum">
                                        {{ $tab === 'noshow'
                                            ? ($log->marked_at ?? 'N/A')
                                            : ($log->expired_at ?? 'N/A')
                                        }}
                                    </td>
                                @elseif($tab === 'cancellations')
                                    <td class="px-6 py-3 text-stone-600">{{ ucfirst($log->cancelled_by) }}</td>
                                    <td class="px-6 py-3 text-stone-500">{{ $log->reason ?? 'N/A' }}</td>
                                    <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $log->cancelled_at ?? 'N/A' }}</td>
                                @endif

                                <td class="px-6 py-3 text-stone-500 text-[11px] font-bold uppercase tracking-wide">{{ $log->staff->name ?? 'System' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $logs->appends(['tab' => $tab, 'search' => $search])->links() }}
            </div>
        @endif
    </x-admin.section-card>
</div>
@endsection
