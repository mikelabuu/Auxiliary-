@php
    // One review page, two staff shells. Cashiers can decide; admins see the
    // same evidence and audit result without receiving financial authority.
    $isCashier = auth('staff')->user()?->role === 'cashier';
    $booking = $payment->booking;
    $isAwaiting = $payment->status === \App\Models\Payment::STATUS_AWAITING_VERIFICATION;
    $isActionable = $isAwaiting && $booking?->status === \App\Models\Booking::STATUS_PENDING_PAYMENT;
@endphp
@extends($isCashier ? 'layouts.frontdesk' : 'layouts.admin')
@section('title', 'Review Payment')
@section('page-title', 'Review Payment')

@section('content')
<div class="space-y-6 max-w-[1180px] mx-auto">
    <div>
        <a href="{{ route('staff.paymentverification.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-clsu-700 hover:text-clsu-900 !no-underline">
            <span aria-hidden="true">&larr;</span>
            Back to payment verification
        </a>
    </div>

    @unless($isCashier)
        <x-admin.ui.page-header subtitle="Read-only payment oversight. The cashier compares this receipt with the bank account and records the decision.">
            Payment #{{ $payment->id }}
        </x-admin.ui.page-header>
    @endunless

    @if ($errors->any())
        <div class="rounded-[var(--radius)] border border-ember-200 bg-ember-50 px-4 py-3">
            <ul class="space-y-1 text-sm font-medium text-ember-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-admin.ui.section-card icon="receipt" title="Payment proof"
        subtitle="Booking #{{ $payment->booking_id }}">
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(300px,0.9fr)_minmax(0,1.1fr)] gap-6">
            <a href="{{ route('staff.paymentverification.proof', $payment) }}" target="_blank" rel="noopener"
               class="group relative block min-h-[320px] overflow-hidden rounded-[var(--radius)] border border-stone-200 bg-stone-100 !no-underline"
               aria-label="Open full-size receipt for payment #{{ $payment->id }}">
                <img src="{{ route('staff.paymentverification.proof', $payment) }}"
                     alt="Proof of payment for booking #{{ $payment->booking_id }}"
                     class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.02]">
                <span class="absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-black/70 px-3 py-1.5 text-2xs font-bold uppercase tracking-wider text-white backdrop-blur">
                    <x-admin.ui.icon name="maximize" class="w-3 h-3" stroke-width="2.5" />
                    Full size
                </span>
            </a>

            <div class="min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 pb-5 border-b border-stone-100">
                    <div>
                        <p class="text-lg font-bold text-stone-800">{{ $booking->guest_name ?? 'Unknown guest' }}</p>
                        <p class="mt-1 text-xs font-semibold text-muted tabnum">
                            @if ($booking)
                                {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }}
                                &rarr; {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}
                            @else
                                Booking record unavailable
                            @endif
                        </p>
                    </div>
                    <span class="font-display text-3xl text-clsu-800 tabnum whitespace-nowrap">
                        ₱{{ number_format($payment->amount, 2) }}
                    </span>
                </div>

                <dl class="grid grid-cols-2 gap-4 py-5 text-xs">
                    <div>
                        <dt class="font-bold uppercase tracking-[0.14em] text-faint text-2xs">Method</dt>
                        <dd class="mt-1 font-bold text-stone-700">{{ $payment->proof_method_label }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold uppercase tracking-[0.14em] text-faint text-2xs">Payment reference</dt>
                        <dd class="mt-1 font-bold text-stone-700 font-data break-all">{{ $payment->proof_reference ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-bold uppercase tracking-[0.14em] text-faint text-2xs">Submitted</dt>
                        <dd class="mt-1 font-bold text-stone-700 tabnum">
                            {{ $payment->proof_submitted_at?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') ?? '—' }}
                        </dd>
                    </div>
                </dl>

                @if ($isActionable && $isCashier)
                    <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-palay-200 bg-palay-50 px-4 py-3 text-xs font-semibold text-palay-800 leading-relaxed">
                        <x-admin.ui.icon name="shield" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                        Confirm the amount, reference and date against the actual transfer. Opening this email link has not approved anything.
                    </div>

                    <form method="POST" action="{{ route('staff.paymentverification.approve', $payment) }}"
                          class="mt-5" data-busy-form
                          data-confirm-title="Verify this payment?"
                          data-confirm="Booking #{{ $payment->booking_id }} will be marked paid and the official receipt emailed to the guest."
                          data-confirm-action="Yes, verify">
                        @csrf
                        <button type="submit" data-busy-btn class="btn btn-primary">
                            <x-admin.ui.icon name="check-circle" class="w-4 h-4" stroke-width="2" />
                            Verify &amp; mark paid
                        </button>
                    </form>

                    <form method="POST" action="{{ route('staff.paymentverification.reject', $payment) }}"
                          class="mt-6 border-t border-stone-100 pt-5" data-busy-form
                          data-confirm-title="Reject this payment proof?"
                          data-confirm="The booking will stay unpaid and the guest will be shown your reason."
                          data-confirm-action="Reject proof">
                        @csrf
                        <label for="rejection_reason" class="block text-2xs font-bold uppercase tracking-[0.16em] text-muted mb-1.5">
                            Reason shown to the guest <span class="text-ember-600">*</span>
                        </label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" required maxlength="500"
                                  placeholder="e.g. The amount on the receipt does not match the balance due."
                                  class="w-full rounded-[var(--radius)] border border-stone-200 bg-stone-50/60 px-4 py-3 text-sm text-stone-800 focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow]">{{ old('rejection_reason') }}</textarea>
                        <button type="submit" data-busy-btn class="btn btn-danger mt-3">
                            <x-admin.ui.icon name="block" class="w-4 h-4" stroke-width="2" />
                            Reject proof
                        </button>
                    </form>
                @elseif ($isActionable)
                    <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-palay-200 bg-palay-50 px-4 py-3 text-xs font-semibold text-palay-800 leading-relaxed">
                        <x-admin.ui.icon name="shield" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                        Awaiting cashier verification. Admins can inspect the proof, but only a cashier can verify or reject it.
                    </div>
                @elseif ($payment->status === 'success')
                    <a download href="{{ route('receipts.download', $payment->booking_id) }}" class="btn btn-outline mb-3">Download official receipt</a>
                    <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-clsu-200 bg-clsu-50 px-4 py-3 text-xs font-semibold text-clsu-800 leading-relaxed">
                        <x-admin.ui.icon name="check-circle" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                        <div>
                            Verified by {{ $payment->verifier->name ?? 'staff' }}
                            on {{ $payment->verified_at?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') ?? '—' }}.
                        </div>
                    </div>
                @elseif ($payment->status === \App\Models\Payment::STATUS_REJECTED)
                    <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-ember-200 bg-ember-50 px-4 py-3 text-xs font-semibold text-ember-700 leading-relaxed">
                        <x-admin.ui.icon name="block" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                        <div>
                            Rejected by {{ $payment->verifier->name ?? 'staff' }}
                            on {{ $payment->verified_at?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') ?? '—' }}.
                            @if ($payment->rejection_reason)
                                <span class="block mt-1">Reason: {{ $payment->rejection_reason }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-ember-200 bg-ember-50 px-4 py-3 text-xs font-semibold text-ember-700 leading-relaxed">
                        <x-admin.ui.icon name="alert" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                        This claim can no longer be decided because the booking is {{ str_replace('_', ' ', $booking?->status ?? 'unavailable') }}. No payment state was changed by opening this page.
                    </div>
                @endif
            </div>
        </div>
    </x-admin.ui.section-card>
</div>
@endsection
