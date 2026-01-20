@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sandbox.css') }}">

<div class="container">
  <div class="card result-card">
    <div style="font-size:44px; margin-bottom:8px;">❌</div>
    <h2>Payment Failed</h2>
    <p class="result-meta">We were unable to process your payment for booking <strong>#{{ $payment->booking_id }}</strong>.</p>

    <div style="margin-top:18px; padding:14px; border-radius:10px; border:1px solid #fff2f2; display:inline-block; background:#fff7f7;">
      <div><strong>Reference:</strong> {{ $payment->reference_no }}</div>
      <div style="margin-top:6px;"><strong>Transaction ID:</strong> {{ $payment->landbank_transaction_id ?? 'N/A' }}</div>
      <div style="margin-top:6px;"><strong>Status:</strong> {{ ucfirst($payment->status) }}</div>
    </div>

    <div style="margin-top:22px;">
      <a href="{{ route('booking.show', $payment->booking_id) }}" class="btn btn-ghost">Back to Booking</a>
      <a href="{{ route('sandbox.pay', $payment->id) }}" class="btn btn-primary" style="margin-left:8px;">Try Again</a>
    </div>
  </div>
</div>
@endsection
