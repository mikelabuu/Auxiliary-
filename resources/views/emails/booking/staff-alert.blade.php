@component('mail::message')
# {{ $isProof ? 'Proof of payment to verify' : ($isReschedule ? 'Reschedule request' : 'New booking received') }}

@if ($isProof)
**{{ $booking->guest_name }}** has uploaded a receipt for booking **#{{ $booking->id }}** and is waiting on verification.
@elseif ($isReschedule)
**{{ $booking->guest_name }}** cannot make booking **#{{ $booking->id }}** and has asked to move it. This is a paid stay, so moving it is the only option they have — a paid booking cannot be cancelled.
@else
**{{ $booking->guest_name }}** has just booked. The room is held, and the booking stays pending until payment clears.
@endif

**Booking #{{ $booking->id }}**
- Guest: {{ $booking->guest_name }}
- Contact: {{ $booking->guest_phone ?: '—' }}
@if ($booking->guest_phone_alt)
- Second contact: {{ $booking->guest_phone_alt }}
@endif
@if ($booking->referred_by)
- Reference person: {{ $booking->referred_by }}@if ($booking->referred_by_phone) ({{ $booking->referred_by_phone }})@endif

@endif
@if ($booking->referred_by_purpose)
- Purpose: {{ $booking->referred_by_purpose }}
@endif
@if ($booking->arrival_time && \App\Support\StaySchedule::isEarlyArrival(\Carbon\Carbon::parse($booking->arrival_time)->format('H:i')))
- **Early check-in requested: {{ \Carbon\Carbon::parse($booking->arrival_time)->format('g:i A') }}** (before the usual {{ \App\Support\StaySchedule::checkinLabel() }} — needs the room turned over)
@endif
- Check-in: {{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}
- Check-out: {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}
- Room{{ str_contains($rooms, ',') ? 's' : '' }}: {{ $rooms ?: '—' }}
- Guests: {{ $booking->expected_guests }}
- Amount: ₱{{ number_format($amount, 2) }}

@if ($isProof && $payment)
**What the guest says they sent**
- Method: {{ $payment->proof_method_label }}
- Their reference: {{ $payment->proof_reference ?: '—' }}
- Submitted: {{ $payment->proof_submitted_at?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') ?? '—' }}

Check the amount, reference and date on the receipt against the actual transfer before verifying. Verifying marks the booking paid and emails the guest their official receipt.
@endif

@if ($isReschedule && $reschedule)
**The dates they are asking for**
- New check-in: {{ $reschedule->requested_check_in->format('M d, Y') }}
- New check-out: {{ $reschedule->requested_check_out->format('M d, Y') }}
- Nights: {{ $reschedule->original_nights }} → {{ $reschedule->requested_nights }}
- Their reason: {{ $reschedule->reason }}

Approving moves the booking to the new dates and keeps the same rooms, so it will be refused if any of them is taken over that range. The stay is re-priced at the same nightly rate, and any difference is settled at the desk.

**The guest asked in time — they had until {{ \App\Models\RescheduleRequest::deadlineFor($booking)->format('g:i A, M d') }}, 24 hours before their check-in.** Answer before they arrive on {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }}; a booking nobody checks in to is forfeited with no refund.
@endif

@component('mail::button', ['url' => $actionUrl])
{{ $isProof ? 'Open verification queue' : ($isReschedule ? 'Open reschedule queue' : 'Open booking') }}
@endcomponent

@if (! $isProof && ! $isReschedule)
No action is needed yet — this is a heads-up that a room is now held.
@endif

**– {{ config('app.name') }} system alert**
@endcomponent
