@component('mail::message')
# {{ $isProof ? 'Proof of payment to verify' : 'New booking received' }}

@if ($isProof)
**{{ $booking->guest_name }}** has uploaded a receipt for booking **#{{ $booking->id }}** and is waiting on verification.
@else
**{{ $booking->guest_name }}** has just booked. The room is held, and the booking stays pending until payment clears.
@endif

**Booking #{{ $booking->id }}**
- Guest: {{ $booking->guest_name }}
- Contact: {{ $booking->guest_phone ?: '—' }}
- Check-in: {{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}
- Check-out: {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}
- Room{{ str_contains($rooms, ',') ? 's' : '' }}: {{ $rooms ?: '—' }}
- Guests: {{ $booking->expected_guests }}
- Amount: ₱{{ number_format($amount, 2) }}

@if ($isProof && $payment)
**What the guest says they sent**
- Method: {{ $payment->proof_method_label }}
- Their reference: {{ $payment->proof_reference ?: '—' }}
- Submitted: {{ $payment->proof_submitted_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? '—' }}

Check the amount, reference and date on the receipt against the actual transfer before verifying. Verifying marks the booking paid and emails the guest their official receipt.
@endif

@component('mail::button', ['url' => $actionUrl])
{{ $isProof ? 'Open verification queue' : 'Open booking' }}
@endcomponent

@if (! $isProof)
No action is needed yet — this is a heads-up that a room is now held.
@endif

**– {{ config('app.name') }} system alert**
@endcomponent
