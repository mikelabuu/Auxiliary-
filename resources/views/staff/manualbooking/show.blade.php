@extends('layouts.admin')
@section('title', 'Admin - Manual Booking')
@section('page-title', 'Manual Booking')
@section('content')

<div class="p-6 bg-white rounded shadow space-y-4">

    <h1 class="text-2xl font-bold mb-4">Booking #{{ $booking->id }} Details</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <p><strong>Guest Name:</strong> {{ $booking->guest_name }}</p>
            <p><strong>Phone:</strong> {{ $booking->guest_phone }}</p>
            <p><strong>Address:</strong> {{ $booking->guest_address }}</p>
            <p><strong>Expected Guests:</strong> {{ $booking->expected_guests }}</p>
            <p><strong>Number of Seniors:</strong> {{ $booking->num_seniors }}</p>
        </div>
        <div>
            <p><strong>Check-In:</strong> {{ $booking->check_in }}</p>
            <p><strong>Check-Out:</strong> {{ $booking->check_out }}</p>
            <p><strong>Booking Method:</strong> {{ ucfirst($booking->payment_mode) }}</p>
            <p><strong>Status:</strong> {{ ucfirst($booking->status) }}</p>
            <p><strong>Total Price:</strong> ₱{{ number_format($booking->total_price, 2) }}</p>
            <p><strong>Discount:</strong> ₱{{ number_format($booking->discount, 2) }}</p>
            <p><strong>Payable:</strong> ₱{{ number_format($booking->payable_amount, 2) }}</p>
        </div>
    </div>

    <h2 class="text-xl font-bold mt-4 mb-2">Room Reservations</h2>
    <table class="w-full border rounded">
        <thead>
            <tr class="bg-gray-100">
                <th class="p-2 border">Room Number</th>
                <th class="p-2 border">Room Type</th>
                <th class="p-2 border">Guests</th>
                <th class="p-2 border">Seniors</th>
                <th class="p-2 border">Price/Night</th>
            </tr>
        </thead>
        <tbody>
            @foreach($booking->reservations as $res)
            <tr>
                <td class="p-2 border">{{ $res->room_number }}</td>
                <td class="p-2 border">{{ ucfirst($res->room_type) }}</td>
                <td class="p-2 border">{{ $res->num_guests }}</td>
                <td class="p-2 border">{{ $res->num_seniors }}</td>
                <td class="p-2 border">₱{{ number_format($res->price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

</div>

@endsection
