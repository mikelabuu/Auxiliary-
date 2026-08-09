{{--
    Where the QR on a paid receipt lands.

    Used to be an unstyled page with a green tick and the words "Verified OK" —
    fine while the route was staff-only and only the desk ever saw it, but the
    QR is printed on the guest's copy, so this is a guest-facing page and wears
    the house style like the rest of the journey.

    Deliberately says little: receipt number, booking id, and the date. Someone
    holding the receipt already knows all three, and a stranger who guessed a
    number should not learn a name, a room or a total from here.
--}}
@extends('layouts.public.base')
@section('title', 'Receipt Verification | Farmers Hostel')

@section('content')
<div class="min-h-screen bg-canvas pt-28 pb-24">
    <div class="mx-auto max-w-2xl px-4 sm:px-6">

        <p class="font-label text-[11px] uppercase tracking-[0.22em] text-emerald">Receipt Verification</p>
        <div class="mt-5 mb-6 h-0.5 w-12 rounded-full bg-gold"></div>

        <div class="rounded-[28px] bg-cream-warm px-6 py-9 shadow-[0_18px_40px_-22px_rgba(6,40,30,0.35)] ring-1 ring-emerald-deep/10 sm:px-10">

            @if ($valid)
                {{-- role=status rather than alert: this is the expected outcome,
                     not an interruption. --}}
                <div class="flex flex-col items-center text-center" role="status">
                    {{-- clsu-50/clsu-700, not success-surface/success-ink:
                         DESIGN.md lists that pair but no stylesheet ever defined
                         it, so those classes resolve to nothing. --}}
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-clsu-50" aria-hidden="true">
                        <svg class="h-8 w-8 text-clsu-700" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m4 12.5 5 5L20 6.5" />
                        </svg>
                    </span>
                    <h1 class="mt-6 font-display text-3xl leading-tight tracking-tight text-ink">This receipt is genuine.</h1>
                    <p class="mt-3 max-w-md text-pretty text-ink/70">{{ $reason }}</p>
                </div>
            @else
                <div class="flex flex-col items-center text-center" role="alert">
                    <span class="grid h-16 w-16 place-items-center rounded-full bg-ember-50" aria-hidden="true">
                        <svg class="h-8 w-8 text-ember-600" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 8v5" /><path d="M12 16.5h.01" />
                            <path d="M10.3 3.9 1.8 18.1A2 2 0 0 0 3.5 21h17a2 2 0 0 0 1.7-2.9L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    </span>
                    <h1 class="mt-6 font-display text-3xl leading-tight tracking-tight text-ink">We can’t verify this receipt.</h1>
                    <p class="mt-3 max-w-md text-pretty text-ink/70">{{ $reason }}</p>
                    <p class="mt-3 max-w-md text-pretty text-sm text-ink/55">
                        If you believe this is our receipt, bring it to the front desk and we’ll check it against our records.
                    </p>
                </div>
            @endif

            @if ($receipt)
                <dl class="mt-9 grid gap-px overflow-hidden rounded-2xl bg-emerald-deep/10 sm:grid-cols-3">
                    <div class="bg-cream-warm px-5 py-4">
                        <dt class="font-label text-[10px] uppercase tracking-[0.2em] text-ink/55">Receipt</dt>
                        <dd class="mt-1.5 font-data text-sm text-ink">{{ $receipt->receipt_number }}</dd>
                    </div>
                    <div class="bg-cream-warm px-5 py-4">
                        <dt class="font-label text-[10px] uppercase tracking-[0.2em] text-ink/55">Booking</dt>
                        <dd class="mt-1.5 font-data text-sm text-ink">#{{ $receipt->booking_id }}</dd>
                    </div>
                    <div class="bg-cream-warm px-5 py-4">
                        <dt class="font-label text-[10px] uppercase tracking-[0.2em] text-ink/55">Issued</dt>
                        <dd class="mt-1.5 font-data text-sm text-ink">{{ $receipt->created_at->format('d M Y') }}</dd>
                    </div>
                </dl>
            @endif
        </div>

        <p class="mt-6 text-center text-sm text-ink/55">
            Checked by comparing the stored copy against its original fingerprint at the moment it was issued.
        </p>
    </div>
</div>
@endsection
