@extends('layouts.admin')
@section('title', 'Admin - Booking Logs')
@section('page-title', 'Booking Logs')

@section('content')
<div class="space-y-6">
    <!-- Tabs -->
    <div class="flex space-x-2">
        @php
            $tabs = [
                'checkins' => 'Check-ins',
                'checkouts' => 'Check-outs',
                'noshow' => 'No Shows',
                'expiry' => 'Expiries',
                'cancellations' => 'Cancellations',
            ];
        @endphp
        @foreach ($tabs as $key => $label)
            <a href="{{ route('staff.bookinglogs.index', ['tab' => $key, 'search' => $search]) }}"
                class="px-4 py-2 rounded-lg font-medium text-sm
                {{ $tab === $key ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Search -->
    <form method="GET" class="flex items-center space-x-3 mb-4">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="text" name="search" value="{{ $search }}" placeholder="Search by Booking ID or Guest Name..."
            class="border rounded-lg px-3 py-2 w-1/3">
        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">Search</button>
    </form>

    <!-- Logs Table -->
    <!-- Logs Table -->
    <div class="bg-white rounded-xl shadow p-4">
        <table class="min-w-full border rounded-lg">
            <thead class="bg-gray-100 text-left">
                <tr>
                    <th class="p-3">Booking ID</th>
                    <th class="p-3">Guest</th>

                    @if($tab === 'checkins')
                        <th class="p-3">Checked In At</th>
                    @elseif($tab === 'checkouts')
                        <th class="p-3">Checked Out At</th>
                        <th class="p-3">Method</th>
                    @elseif($tab === 'noshow' || $tab === 'expiry')
                        <th class="p-3">Previous Status</th>
                        <th class="p-3">New Status</th>
                        <th class="p-3">Reason</th>
                        <th class="p-3">{{ $tab === 'noshow' ? 'Marked At' : 'Expired At' }}</th>
                    @elseif($tab === 'cancellations')
                        <th class="p-3">Cancelled By</th>
                        <th class="p-3">Reason</th>
                        <th class="p-3">Cancelled At</th>
                    @endif

                    <th class="p-3">Processed By</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-t hover:bg-gray-50 transition">
                        <td class="p-3">#{{ $log->booking->id ?? 'N/A' }}</td>
                        <td class="p-3">{{ $log->booking->guest_name ?? 'Unknown' }}</td>

                        @if($tab === 'checkins')
                            <td class="p-3">{{ $log->checked_in_at }}</td>
                        @elseif($tab === 'checkouts')
                            <td class="p-3">{{ $log->checked_out_at }}</td>
                            <td class="p-3">{{ ucfirst($log->method ?? '-') }}</td>
                        @elseif($tab === 'noshow' || $tab === 'expiry')
                            <td class="p-3 capitalize">{{ str_replace('_', ' ', $log->previous_status) }}</td>
                            <td class="p-3 capitalize">{{ str_replace('_', ' ', $log->new_status) }}</td>
                            <td class="p-3">{{ $log->reason ?? 'N/A' }}</td>
                            <td class="p-3">
                                {{ $tab === 'noshow' 
                                    ? ($log->marked_at ?? 'N/A')
                                    : ($log->expired_at ?? 'N/A')
                                }}
                            </td>
                        @elseif($tab === 'cancellations')
                            <td class="p-3">{{ ucfirst($log->cancelled_by) }}</td>
                            <td class="p-3">{{ $log->reason ?? 'N/A' }}</td>
                            <td class="p-3">{{ $log->cancelled_at ?? 'N/A' }}</td>
                        @endif

                        <td class="p-3">{{ $log->staff->name ?? 'System'}}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $tab === 'cancellations' ? 6 : 7 }}" class="text-center text-gray-500 p-4">No logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $logs->appends(['tab' => $tab, 'search' => $search])->links() }}
        </div>
    </div>

</div>
@endsection
