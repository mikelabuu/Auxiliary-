@php
    $isFinancialAlert = $isProof || $isVerified;
    $eyebrow = $isProof
        ? 'Action required · Payment proof'
        : ($isVerified ? 'Payment verified · Cashier' : ($isReschedule ? 'Action required · Reschedule' : 'New booking'));
    $statusTone = ($isProof || $isReschedule)
        ? 'warning'
        : ($isVerified ? 'success' : 'neutral');
    $title = $isProof
        ? 'Review payment proof'
        : ($isVerified ? 'Cashier verified payment' : ($isReschedule ? 'Review reschedule request' : 'New booking received'));
@endphp

@component('mail::message', [
    'preheader' => $preheader,
])
@component('mail::status', ['tone' => $statusTone])
{{ $eyebrow }}
@endcomponent

# {{ $title }}

@if ($isProof)
**{{ $booking->guest_name }}** submitted proof of payment for booking **#{{ $booking->id }}**. Check the claim against the actual transfer before marking the booking paid.
@elseif ($isVerified)
**{{ $payment->verifier->name ?? 'The cashier' }}** verified the payment for booking **#{{ $booking->id }}**. The payment is already marked paid; this notice is for administrative oversight.
@elseif ($isReschedule)
**{{ $booking->guest_name }}** asked to move paid booking **#{{ $booking->id }}**. Review the requested dates and room availability before deciding.
@else
**{{ $booking->guest_name }}** completed the booking flow for booking **#{{ $booking->id }}**. The room is being held while payment is pending.
@endif

@if ($isProof && $payment)
<table class="alert-card alert-card-attention" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#FFFAEB">
<tr>
<td class="alert-card-cell" style="background-color: #FFFAEB; border: 1px solid #FEDF89; border-left: 4px solid #F79009; padding: 22px 24px;">
<p class="alert-section-label" style="margin: 0 0 5px; color: #93370D; font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-weight: 700; line-height: 1.3; text-transform: uppercase;">Booking amount</p>
<p class="alert-amount" style="margin: 0 0 18px; color: #14201A; font-family: Georgia, 'Times New Roman', Times, serif; font-size: 30px; font-weight: 700; line-height: 1.15;">₱{{ number_format($amount, 2) }}</p>

<table class="alert-detail-table" width="100%" cellpadding="0" cellspacing="0">
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #FEDF89; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Method</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #FEDF89; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ $payment->proof_method_label }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #FEDF89; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Guest reference</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #FEDF89; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere;">{{ $payment->proof_reference ?: '—' }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #FEDF89; padding: 11px 12px 0 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Submitted</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #FEDF89; padding: 11px 0 0; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $payment->proof_submitted_at?->timezone(config('hostel.timezone'))->format('M d, Y · g:i A') ?? '—' }}</td>
</tr>
</table>

<p class="alert-guidance" style="margin: 18px 0 0; color: #39463E; font-size: 13px; line-height: 1.6;">Cross-check the amount, reference, and date on the uploaded receipt against the actual GCash or bank transfer. Verification marks the booking paid and emails the guest an official receipt.</p>
</td>
</tr>
</table>

@endif

@if ($isVerified && $payment)
<table class="alert-card" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#EDFDF3">
<tr>
<td class="alert-card-cell" style="background-color: #EDFDF3; border: 1px solid #AAF0C4; border-left: 4px solid #0F8F51; padding: 22px 24px;">
<p class="alert-section-label" style="margin: 0 0 5px; color: #087443; font-family: Arial, Helvetica, sans-serif; font-size: 12px; font-weight: 700; line-height: 1.3; text-transform: uppercase;">Payment received</p>
<p class="alert-amount" style="margin: 0 0 18px; color: #14201A; font-family: Georgia, 'Times New Roman', Times, serif; font-size: 30px; font-weight: 700; line-height: 1.15;">₱{{ number_format($amount, 2) }}</p>

<table class="alert-detail-table" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #D3F8E0; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Verified by</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #D3F8E0; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ $payment->verifier->name ?? 'Cashier' }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #D3F8E0; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Method</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #D3F8E0; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ $payment->proof_method_label }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #D3F8E0; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Guest reference</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #D3F8E0; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45; word-break: break-word; overflow-wrap: anywhere;">{{ $payment->proof_reference ?: '—' }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #D3F8E0; padding: 11px 12px 0 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Verified</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #D3F8E0; padding: 11px 0 0; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $payment->verified_at?->timezone(config('hostel.timezone'))->format('M d, Y · g:i A') ?? '—' }}</td>
</tr>
</table>

<p class="alert-guidance" style="margin: 18px 0 0; color: #39463E; font-size: 13px; line-height: 1.6;"><strong>No second payment approval is required.</strong> The cashier has confirmed the bank transaction, the booking is paid, and the guest’s official receipt is being issued.</p>
</td>
</tr>
</table>

@endif

@if ($isReschedule && $reschedule)
<table class="alert-card alert-card-attention" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#FFFAEB">
<tr>
<td class="alert-card-cell" style="background-color: #FFFAEB; border: 1px solid #FEDF89; border-left: 4px solid #F79009; padding: 22px 24px;">
<p class="alert-section-title" style="margin: 0 0 13px; color: #14201A; font-family: Georgia, 'Times New Roman', Times, serif; font-size: 17px; font-weight: 700; line-height: 1.35;">Requested dates</p>
<table class="alert-detail-table" width="100%" cellpadding="0" cellspacing="0">
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #FEDF89; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">New check-in</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #FEDF89; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ $reschedule->requested_check_in->format('M d, Y') }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #FEDF89; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">New check-out</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #FEDF89; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ $reschedule->requested_check_out->format('M d, Y') }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #FEDF89; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Nights</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #FEDF89; padding: 11px 0; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $reschedule->original_nights }} → {{ $reschedule->requested_nights }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #FEDF89; padding: 11px 12px 0 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Guest’s reason</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #FEDF89; padding: 11px 0 0; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $reschedule->reason }}</td>
</tr>
</table>

<p class="alert-guidance" style="margin: 18px 0 0; color: #39463E; font-size: 13px; line-height: 1.6;">Approval keeps the same rooms and rechecks availability for the requested dates. Any price difference is settled at the desk.</p>
</td>
</tr>
</table>

@endif

<table class="alert-card alert-booking-card" width="100%" cellpadding="0" cellspacing="0" role="presentation" bgcolor="#F6F9F6">
<tr>
<td class="alert-card-cell" style="background-color: #F6F9F6; border: 1px solid #E2EBE4; padding: 22px 24px;">
<p class="alert-section-title" style="margin: 0 0 13px; color: #14201A; font-family: Georgia, 'Times New Roman', Times, serif; font-size: 17px; font-weight: 700; line-height: 1.35;">Booking #{{ $booking->id }}</p>
<table class="alert-detail-table" width="100%" cellpadding="0" cellspacing="0">
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Guest</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ $booking->guest_name }}</td>
</tr>
@unless ($isFinancialAlert)
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Contact</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $booking->guest_phone ?: '—' }}@if ($booking->guest_phone_alt)<br>
{{ $booking->guest_phone_alt }}@endif</td>
</tr>
@if ($booking->referred_by)
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Reference person</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $booking->referred_by }}@if ($booking->referred_by_phone)<br>
{{ $booking->referred_by_phone }}@endif</td>
</tr>
@endif
@if ($booking->referred_by_purpose)
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Purpose</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $booking->referred_by_purpose }}</td>
</tr>
@endif
@endunless
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Check-in</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ \Carbon\Carbon::parse($booking->check_in)->format('M d, Y') }}@if ($booking->arrival_time && \App\Support\StaySchedule::isEarlyArrival(\Carbon\Carbon::parse($booking->arrival_time)->format('H:i')))<br>
<span style="color: #B91C1C; font-size: 12px; font-weight: 600;">Early arrival · {{ \Carbon\Carbon::parse($booking->arrival_time)->format('g:i A') }}</span>@endif</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Check-out</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0; color: #14201A; font-size: 14px; font-weight: 700; line-height: 1.45;">{{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 11px 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Room{{ str_contains($rooms, ',') ? 's' : '' }}</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0; color: #087443; font-size: 14px; font-weight: 700; line-height: 1.45; word-break: break-word;">{{ $rooms ?: '—' }}</td>
</tr>
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px {{ $isFinancialAlert ? '0' : '11px' }} 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Guests</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0 {{ $isFinancialAlert ? '0' : '11px' }}; color: #14201A; font-size: 14px; line-height: 1.45;">{{ $booking->expected_guests }}</td>
</tr>
@unless ($isFinancialAlert)
<tr>
<th class="alert-detail-label" scope="row" align="left" valign="top" style="width: 38%; border-top: 1px solid #E2EBE4; padding: 11px 12px 0 0; color: #5C7266; font-size: 13px; font-weight: 600; line-height: 1.45;">Amount</th>
<td class="alert-detail-value" valign="top" style="border-top: 1px solid #E2EBE4; padding: 11px 0 0; color: #14201A; font-size: 15px; font-weight: 700; line-height: 1.45;">₱{{ number_format($amount, 2) }}</td>
</tr>
@endunless
</table>
</td>
</tr>
</table>

@component('mail::button', ['url' => $actionUrl])
{{ $isProof ? 'Review payment proof' : ($isVerified ? 'View verification record' : ($isReschedule ? 'Review reschedule request' : 'View booking details')) }}
@endcomponent

@if ($isProof)
<p class="alert-action-note" style="margin: 0 0 8px; color: #51655A; font-size: 13px; line-height: 1.55; text-align: center;"><strong>Staff sign-in is required.</strong> Opening the review page does not approve the payment.</p>
@elseif ($isVerified)
<p class="alert-action-note" style="margin: 0 0 8px; color: #51655A; font-size: 13px; line-height: 1.55; text-align: center;">The record is read-only for administrators. The cashier has already completed verification.</p>
@elseif ($isReschedule)
<p class="alert-action-note" style="margin: 0 0 8px; color: #51655A; font-size: 13px; line-height: 1.55; text-align: center;">The guest submitted this before the {{ \App\Models\RescheduleRequest::deadlineFor($booking)->format('M d, g:i A') }} deadline. Availability is checked again before dates change.</p>
@else
<p class="alert-action-note" style="margin: 0 0 8px; color: #51655A; font-size: 13px; line-height: 1.55; text-align: center;">No immediate action is required. This is a record that room inventory is currently held pending payment.</p>
@endif
<p class="alert-fallback" style="margin: 0; color: #5C7266; font-size: 12px; line-height: 1.5; text-align: center;">Button not working? Copy and paste this secure link:<br>
<a href="{{ $actionUrl }}" style="color: #087443; text-decoration: underline; word-break: break-all;">{{ $actionUrl }}</a></p>
@endcomponent
