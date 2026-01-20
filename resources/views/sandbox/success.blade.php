@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sandbox.css') }}">

<div class="container">
  <div class="card result-card">
    <div style="font-size:44px; margin-bottom:8px;">✅</div>
    <h2>Payment Successful</h2>
    <p class="result-meta">Your payment for booking <strong>#{{ $payment->booking_id }}</strong> has been processed.</p>
    <p class="result-meta"><small>Your E-mail Confirmation and Receipt Has been sent to your user email.</small></p>

    <div style="margin-top:18px; padding:14px; border-radius:10px; border:1px solid #eef2f6; display:inline-block; background:#f8fafc;">
      <div><strong>Reference:</strong> {{ $payment->reference_no }}</div>
      <div style="margin-top:6px;"><strong>Transaction ID:</strong> {{ $payment->landbank_transaction_id ?? 'N/A' }}</div>
      <div style="margin-top:6px;"><strong>Status:</strong> {{ ucfirst($payment->status) }}</div>
    </div>

    <div style="margin-top:22px;">
      <a href="{{ route('booking.show', $payment->booking_id) }}" class="btn btn-primary">Back to Booking</a>
    </div>
  </div>
</div>
@endsection
