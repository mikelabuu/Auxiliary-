@php
    $bookingDetails = [
        'Check-in' => \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') . " ({$checkinTime})",
        'Check-out' => \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') . " ({$checkoutTime})",
    ];

    if ($booking->reservations->isNotEmpty()) {
        $bookingDetails[\Illuminate\Support\Str::plural('Room', $booking->reservations->count())]
            = $booking->reservations->pluck('room_number')->implode(', ');
    }

    $bookingDetails['Total paid'] = '₱' . number_format(
        $booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price,
        2
    );
@endphp

@component('mail::message', ['preheader' => "Booking #{$booking->id} is confirmed. Your official receipt is attached."])
@component('mail::status', ['tone' => 'success'])
Payment received · Booking confirmed
@endcomponent

# Your booking is confirmed

Dear {{ $booking->guest_name }},

We received your payment and confirmed your stay at **{{ config('app.name') }}**.

@component('mail::details', ['title' => "Booking #{$booking->id}", 'rows' => $bookingDetails])
@endcomponent

@component('mail::panel')
Your **official receipt is attached** to this email. Keep it with your booking details for check-in.
@endcomponent

@component('mail::button', ['url' => route('booking.show', $booking->id)])
View booking
@endcomponent

We look forward to welcoming you.

**– The {{ config('app.name') }} Team**
@endcomponent
