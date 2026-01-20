@component('mail::message')
# Booking Confirmed!

Dear {{ $booking->guest_name }},

We’re happy to confirm your booking at **{{ config('app.name') }}**.

**Booking Details:**
- Booking ID: **#{{ $booking->id }}**
- Check-in: {{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}
- Check-out: {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}
- Total Paid: @if ($booking->payable_amount > 0) 
                    ₱{{ number_format($booking->payable_amount, 2) }}
                @else 
                    ₱{{ number_format($booking->total_price, 2) }}
                @endif

You can find your **Official Receipt** attached to this email.

@component('mail::button', ['url' => route('booking.show', $booking->id)])
View Booking
@endcomponent

Thank you for choosing us!  
**– The {{ config('app.name') }} Team**
@endcomponent
