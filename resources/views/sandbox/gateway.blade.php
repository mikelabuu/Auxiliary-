@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/sandbox.css') }}">

<div class="container">
  <div class="card">
    <div class="sandbox-header">
      <div class="sandbox-logo">LB</div>
      <div>
        <div class="sandbox-title">Bank — Sandbox Payment Portal</div>
        <div class="sandbox-sub">Simulated payment page (for demo only)</div>
      </div>
    </div>
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    <div class="grid">
      {{-- Left: Payment form --}}
      <div class="form-section">
        <h3>Confirm Payment</h3>
        <p class="small">You are about to pay for booking <strong>#{{ $payment->booking_id }}</strong>. This portal is a sandbox simulation and will not move real funds.</p>

        <form action="{{ route('sandbox.process', $payment->id) }}" method="POST" id="sandboxForm">
          @csrf

          <div class="field">
            <label>Payment Option</label>
            <select class="input" name="payment_type" required>
              <option value="full">Pay Full Amount</option>
            </select>
          </div>

          <div class="field">
            <label>Total Amount</label>
            <input class="input" type="text" readonly value="₱{{ number_format($payment->amount, 2) }}">
          </div>

          <div class="field">
            <label>Card / Account Number</label>
            <input class="input" name="card" type="text" placeholder="1111-2222-3333-4444" required>
          </div>

          <div class="field">
            <label>Account Holder</label>
            <input class="input" name="holder" type="text" placeholder="Juan Dela Cruz" required>
          </div>

          <div class="field">
            <label>Simulation Mode</label>
            <select class="input" name="simulate">
              <option value="success">Simulate Success</option>
              <option value="fail">Simulate Failure</option>
            </select>
          </div>

          <div style="margin-top:12px;" class="row">
            <button type="submit" class="btn btn-primary" id="payBtn">Pay Now</button>
            <a href="{{ route('booking.show', $payment->booking_id) }}" class="btn btn-ghost">Cancel</a>
          </div>

          <div id="processing" style="display:none; margin-top:12px;" class="processing">
            <div class="spinner"></div>
            <div>Processing payment…</div>
          </div>
        </form>
      </div>

      {{-- Right: Summary --}}
      <aside class="summary">
        <h4>Payment Summary</h4>
        <div class="summary-row"><div>Booking</div><div>#{{ $payment->booking_id }}</div></div>
        <div class="summary-row"><div>Reference</div><div>{{ $payment->reference_no }}</div></div>
        <div class="summary-row"><div>Gateway</div><div>{{ ucfirst($payment->gateway ?? 'sandbox') }}</div></div>
        <hr style="margin:12px 0; border:none; border-top:1px solid #f1f5f9;">
        <div class="summary-row amount"><div>Total</div><div>₱{{ number_format($payment->amount, 2) }}</div></div>
        <p class="small" style="margin-top:12px;">You can either process a full payment, or pay the reservation fee of ₱500.</p>
      </aside>
    </div>
  </div>
</div>

<script>
document.getElementById('sandboxForm').addEventListener('submit', function(e){
  document.getElementById('payBtn').disabled = true;
  document.getElementById('processing').style.display = 'flex';
});
</script>
@endsection
