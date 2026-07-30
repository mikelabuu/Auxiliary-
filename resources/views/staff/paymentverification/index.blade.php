@php
    // One page, two consoles. Front desk clears receipts as often as admin
    // does, so it renders inside whichever shell the viewer already lives in.
    $isFrontDesk = auth('staff')->user()?->role === 'frontdesk';
@endphp
@extends($isFrontDesk ? 'layouts.frontdesk' : 'layouts.admin')
@section('title', 'Payment Verification')
@section('page-title', 'Payment Verification')

@section('content')
@php
    $tabs = [
        'awaiting' => ['label' => 'Awaiting verification', 'count' => $counts['awaiting']],
        'verified' => ['label' => 'Verified',              'count' => $counts['verified']],
        'rejected' => ['label' => 'Rejected',              'count' => $counts['rejected']],
    ];
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    @unless($isFrontDesk)
        <x-admin.ui.page-header subtitle="Guests who paid by GCash or bank transfer and uploaded their receipt. Nothing is confirmed until a staff member has checked the image against the transfer.">
            Payment Verification
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

    <x-admin.ui.section-card icon="receipt" title="Proof of Payment Queue"
        :subtitle="$payments->total() . ' record' . ($payments->total() === 1 ? '' : 's')">

        {{-- Status filters --}}
        <div class="filter-row mb-5">
            <span class="filter-row-label">Status</span>
            @foreach ($tabs as $key => $meta)
                <a href="{{ route('staff.paymentverification.index', ['status' => $key]) }}"
                   @class(['filter-tab', '!no-underline', 'selected' => $filter === $key])>
                    {{ $meta['label'] }}
                    <span class="ft-count">{{ $meta['count'] }}</span>
                </a>
            @endforeach
        </div>

        @forelse ($payments as $payment)
            @php
                $booking = $payment->booking;
                $isPending = $payment->status === \App\Models\Payment::STATUS_AWAITING_VERIFICATION;
            @endphp

            <article class="mb-4 rounded-[var(--radius)] border border-stone-200 bg-white overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr]">

                    {{-- The receipt the guest uploaded --}}
                    <a href="{{ route('staff.paymentverification.proof', $payment->id) }}" target="_blank" rel="noopener"
                       class="group relative block bg-stone-100 min-h-[200px] overflow-hidden !no-underline"
                       aria-label="Open full-size receipt for payment #{{ $payment->id }}">
                        <img src="{{ route('staff.paymentverification.proof', $payment->id) }}"
                             alt="Proof of payment for booking #{{ $payment->booking_id }}"
                             loading="lazy"
                             class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]">
                        <span class="absolute bottom-2 right-2 inline-flex items-center gap-1.5 rounded-full bg-black/70 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-white backdrop-blur">
                            <x-admin.ui.icon name="maximize" class="w-3 h-3" stroke-width="2.5" />
                            Full size
                        </span>
                    </a>

                    {{-- What the guest claims, and what staff do about it --}}
                    <div class="p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3 pb-4 border-b border-stone-100">
                            <div class="min-w-0">
                                <p class="text-base font-bold text-stone-800 truncate">
                                    {{ $booking->guest_name ?? 'Unknown guest' }}
                                </p>
                                <p class="text-xs font-semibold text-stone-500 mt-0.5 tabnum">
                                    Booking #{{ $payment->booking_id }}
                                    @if ($booking)
                                        · {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }}
                                        → {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}
                                    @endif
                                </p>
                            </div>
                            <span class="font-display text-2xl text-clsu-800 tabnum whitespace-nowrap">
                                ₱{{ number_format($payment->amount, 2) }}
                            </span>
                        </div>

                        <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 py-4 text-xs">
                            <div>
                                <dt class="font-bold uppercase tracking-[0.14em] text-stone-400 text-[9px]">Method</dt>
                                <dd class="mt-1 font-bold text-stone-700">{{ $payment->proof_method_label }}</dd>
                            </div>
                            <div>
                                <dt class="font-bold uppercase tracking-[0.14em] text-stone-400 text-[9px]">Guest reference</dt>
                                <dd class="mt-1 font-bold text-stone-700 font-data break-all">{{ $payment->proof_reference ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-bold uppercase tracking-[0.14em] text-stone-400 text-[9px]">Submitted</dt>
                                <dd class="mt-1 font-bold text-stone-700 tabnum">
                                    {{ $payment->proof_submitted_at?->timezone('Asia/Manila')->format('M d, g:i A') ?? '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-bold uppercase tracking-[0.14em] text-stone-400 text-[9px]">Our reference</dt>
                                <dd class="mt-1 font-bold text-stone-700 font-data break-all">{{ $payment->reference_no }}</dd>
                            </div>
                        </dl>

                        @if ($isPending)
                            <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-palay-200 bg-palay-50 px-4 py-3 text-xs font-semibold text-palay-800 leading-relaxed">
                                <x-admin.ui.icon name="shield" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                                Confirm the amount, reference and date on the receipt against the actual transfer before verifying. Verifying marks the booking paid and emails the official receipt.
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2.5">
                                <form method="POST" action="{{ route('staff.paymentverification.approve', $payment->id) }}"
                                      data-confirm-verify="{{ $payment->booking_id }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <x-admin.ui.icon name="check-circle" class="w-4 h-4" stroke-width="2" />
                                        Verify &amp; mark paid
                                    </button>
                                </form>

                                <button type="button" class="btn btn-ghost btn-sm text-ember-700"
                                        onclick="openRejectModal({{ $payment->id }}, {{ $payment->booking_id }})">
                                    <x-admin.ui.icon name="block" class="w-4 h-4" stroke-width="2" />
                                    Reject
                                </button>

                                <a href="{{ route('staff.paymentverification.proof', $payment->id) }}" target="_blank" rel="noopener"
                                   class="btn btn-ghost btn-sm">
                                    <x-admin.ui.icon name="eye" class="w-4 h-4" stroke-width="2" />
                                    Open receipt
                                </a>
                            </div>
                        @else
                            @php
                                $wasVerified = $payment->status === 'success';
                            @endphp
                            <div @class([
                                'flex items-start gap-2.5 rounded-[var(--radius)] px-4 py-3 text-xs font-semibold leading-relaxed border',
                                'border-clsu-200 bg-clsu-50 text-clsu-800' => $wasVerified,
                                'border-ember-200 bg-ember-50 text-ember-700' => ! $wasVerified,
                            ])>
                                <x-admin.ui.icon :name="$wasVerified ? 'check-circle' : 'block'" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                                <div>
                                    {{ $wasVerified ? 'Verified' : 'Rejected' }}
                                    by {{ $payment->verifier->name ?? 'staff' }}
                                    on {{ $payment->verified_at?->timezone('Asia/Manila')->format('M d, Y g:i A') ?? '—' }}.
                                    @if (! $wasVerified && $payment->rejection_reason)
                                        <span class="block mt-1 font-bold">Reason: {{ $payment->rejection_reason }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <x-admin.ui.empty-state icon="receipt"
                title="Nothing here — uploaded proofs of payment land in this queue the moment a guest submits one." />
        @endforelse

        @if ($payments->hasPages())
            <div class="mt-5">{{ $payments->links() }}</div>
        @endif
    </x-admin.ui.section-card>
</div>

{{-- Reject: a reason is mandatory, because the guest is shown it and has to
     know what to correct before uploading again. --}}
<x-admin.ui.modal id="rejectProofModal" icon="block" title="Reject proof of payment" max-width="lg">
    <form method="POST" id="rejectProofForm">
        @csrf
        <div class="modal-body space-y-4">
            <p class="text-sm text-stone-600">
                Booking <strong id="rejectBookingLabel" class="text-stone-800"></strong> stays awaiting payment.
                The guest can upload a corrected receipt.
            </p>
            <div>
                <label for="rejection_reason" class="block text-[10px] font-bold uppercase tracking-[0.16em] text-stone-500 mb-1.5">
                    Reason shown to the guest <span class="text-ember-600">*</span>
                </label>
                <textarea id="rejection_reason" name="rejection_reason" rows="3" required maxlength="500"
                          placeholder="e.g. The amount on the receipt does not match the balance due."
                          class="w-full rounded-[var(--radius)] border border-stone-200 bg-stone-50/60 px-4 py-3 text-sm text-stone-800 focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow]"></textarea>
            </div>
        </div>
        <div class="flex gap-2.5 justify-end px-7 pb-7">
            <button type="button" class="btn btn-outline" data-modal-close="rejectProofModal">Cancel</button>
            <button type="submit" class="btn btn-danger">
                <x-admin.ui.icon name="block" class="w-4 h-4" stroke-width="2" />
                Reject proof
            </button>
        </div>
    </form>
</x-admin.ui.modal>

@push('scripts')
<script>
    function openRejectModal(paymentId, bookingId) {
        const form = document.getElementById('rejectProofForm');
        form.action = '{{ url('staff/payment-verification') }}/' + paymentId + '/reject';
        document.getElementById('rejectBookingLabel').textContent = '#' + bookingId;
        document.getElementById('rejection_reason').value = '';
        openModal('rejectProofModal');
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Verifying moves money and emails a receipt — make it deliberate.
        document.querySelectorAll('[data-confirm-verify]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const id = form.dataset.confirmVerify;
                if (!form.dataset.confirmed) {
                    e.preventDefault();
                    if (window.confirm('Verify this payment? Booking #' + id + ' will be marked paid and the official receipt emailed to the guest.')) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                }
            });
        });

        // The queue is worked live: a guest who just transferred money is
        // standing by. Reverb being down is harmless — the page simply does
        // not auto-refresh, exactly as before.
        if (window.Echo) {
            window.Echo.channel('payment-verifications')
                .listen('.PaymentProofSubmitted', function () {
                    window.location.reload();
                });
        }
    });
</script>
@endpush
@endsection
