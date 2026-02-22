@extends('layouts.frontdesk')
@section('title', 'Front Desk - Bookings')
@section('content')

<div class="walkin-container p-6 bg-white rounded-lg shadow-sm border border-gray-100 mb-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <h1 class="text-2xl font-bold text-gray-800">Bookings</h1>

        <form method="GET" class="flex flex-col md:flex-row items-center gap-2">
            <input type="text" name="search" placeholder="Search ID" 
                   value="{{ request('search') }}" 
                   class="border rounded p-2 w-full md:w-64">

            <select name="sort" class="border rounded p-2">
                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest</option>
                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Guest Name</option>
                <option value="check_in" {{ request('sort') == 'check_in' ? 'selected' : '' }}>Check-In</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                Apply
            </button>
        </form>
    </div>

    @if($bookings->count())
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 border-b text-left">Guest Name</th>
                        <th class="px-4 py-2 border-b text-left">Phone</th>
                        <th class="px-4 py-2 border-b text-left">Check-In</th>
                        <th class="px-4 py-2 border-b text-left">Check-Out</th>
                        <th class="px-4 py-2 border-b text-left">Rooms</th>
                        <th class="px-4 py-2 border-b text-left">Status</th>
                        <th class="px-4 py-2 border-b text-left">Total Guests</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($bookings as $booking)
                        <tr>
                            <td class="px-4 py-2">{{ $booking->guest_name }}</td>
                            <td class="px-4 py-2">{{ $booking->guest_phone }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</td>
                            <td class="px-4 py-2">
                                @foreach($booking->reservations as $res)
                                    <span class="inline-block bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm mb-1">
                                        {{ $res->room_number }} ({{ ucfirst($res->room_type) }})
                                    </span>
                                @endforeach
                            </td>
                            <td class="px-4 py-2">{{ ucfirst($booking->status) }}</td>
                            <td class="px-4 py-2">{{ $booking->expected_guests ?? $booking->reservations->sum('num_guests') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $bookings->links() }}
        </div>
    @else
        <div class="p-4 text-gray-600 bg-gray-50 rounded">
            No bookings found.
        </div>
    @endif

</div>

@endsection
