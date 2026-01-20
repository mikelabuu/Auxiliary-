@extends('layouts.booking_layout')
@section('title', 'Booking Summary')
@section('page-title', 'Booking Summary')

@section('content')
<div class="container py-4">
    <br><br>
    <h2>Booking Summary</h2>
    <div class="card mb-3">
        <div class="card-header">
            Booking #{{ $booking->id }} - {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
        </div>
        <div class="card-body">
            <p><strong>Guest Name:</strong> {{ $booking->guest_name }}</p>
            <p><strong>Contact:</strong> {{ $booking->guest_phone }}</p>
            <p><strong>Address:</strong> {{ $booking->guest_address }}</p>

            <p><strong>Expected Guests:</strong> {{ $booking->expected_guests }}</p>
            <p><strong>Senior/PWD Count:</strong> {{ $booking->num_seniors }}</p>

            <p><strong>Rooms:</strong>
                @foreach($booking->room_numbers as $room)
                    <span class="badge bg-secondary">{{ trim($room) }}</span>
                @endforeach
            </p>

            <p><strong>Check-in:</strong> {{ $booking->check_in->format('F d, Y') }}</p>
            <p><strong>Check-out:</strong> {{ $booking->check_out->format('F d, Y') }}</p>

            <p><strong>Total Price:</strong> ₱{{ number_format($booking->total_price, 2) }}</p>
            @if($booking->discount > 0)
                <p><strong>Discount:</strong> -₱{{ number_format($booking->discount, 2) }}</p>
                <p><strong>Payable Amount:</strong> <span class="text-success">₱{{ number_format($booking->payable_amount, 2) }}</span></p>
            @endif

            {{-- Discount UI --}}
            @if($booking->wants_discount)
                
                @if(!$discountRequested)
                    {{-- User hasn't requested a discount yet --}}
                    <form action="{{ route('discount.create', $booking->id) }}" method="GET">
                        <button type="submit" class="btn btn-success mt-3">Request Discount</button>
                    </form>

                @else
                    {{-- Discount request exists --}}
                    @if($discount && $discount->status === 'pending')
                        <span class="badge bg-info mt-3">Discount request submitted.</span>
                        <p><small>Please wait for approval.</small></p>

                        {{-- Cancel Discount Request button --}}
                        <form action="{{ route('discount.cancel', $booking->id) }}" method="POST" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">
                                Cancel Discount Request
                            </button>
                        </form>
                    @elseif($discount && $discount->status !== 'pending')
                        {{-- Discount already processed --}}
                        <span class="badge bg-secondary mt-3">
                            Discount {{ ucfirst($discount->status) }}
                        </span>
                    @endif
                @endif
            @endif
        </div>
    </div>

    {{-- Reservation Breakdown --}}
    <h3 class="mt-4">Reservation Details</h3>
    <div class="row">
        @foreach($booking->reservations as $reservation)
            <div class="col-md-6 mb-3">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-light">
                        <strong>Room #{{ $reservation->room_number }}</strong>
                    </div>
                    <div class="card-body">
                        <p><strong>Room Type:</strong> {{ ucfirst($reservation->room_type) }}</p>
                        <p><strong>Guests:</strong> {{ $reservation->num_guests }}</p>
                        <p><strong>Seniors/PWD:</strong> {{ $reservation->num_seniors }}</p>
                        
                        @if(!empty($reservation->meal))
                            <p><strong>Meals:</strong></p>
                            <ul class="ms-3">
                                @foreach($reservation->meal as $mealName => $qty)
                                    <li>{{ ucfirst($mealName) }}: {{ $qty }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p><em>No meals selected</em></p>
                        @endif

                        <p><strong>Price:</strong> ₱{{ number_format($reservation->price, 2) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(in_array($booking->status, ['pending_payment']))
        <a href="{{ route('bookings.pay', $booking->id) }}" class="btn btn-primary">
            Proceed to Payment
        </a>
    @endif
    <a href="{{ route('settings.bookings') }}" class="btn btn-secondary">See All Bookings</a>
</div>
@endsection
