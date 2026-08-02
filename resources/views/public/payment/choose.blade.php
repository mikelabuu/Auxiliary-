@extends('layouts.public.base')
@section('title', 'Choose Payment Method | Farmers Hostel')
@section('content')
@php
    $nights = max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)));
@endphp

<div class="min-h-screen bg-canvas pt-28 pb-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald mb-3">
                <span class="h-px w-8 bg-emerald/50"></span> Payment
            </span>
            <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">How would you like to <span class="italic text-gold">settle</span>?</h1>
            <p class="text-sm font-medium text-ink/55 mt-3 max-w-xl">Booking #{{ $booking->id }} · {{ $booking->check_in->format('M d') }} → {{ $booking->check_out->format('M d, Y') }} · {{ $nights }} night{{ $nights === 1 ? '' : 's' }}</p>
        </div>

        @if ($lastRejected)
            <div class="mb-8 flex items-start gap-2.5 rounded-2xl border border-ember-300/50 bg-ember-50 px-5 py-4 text-xs font-bold leading-relaxed text-ember-700">
                <i class="fa-solid fa-circle-exclamation text-[18px] shrink-0"></i>
                <div>
                    Your previous proof of payment was not accepted.
                    @if ($lastRejected->rejection_reason)
                        <span class="block mt-1 font-semibold text-ember-600">Reason: {{ $lastRejected->rejection_reason }}</span>
                    @endif
                    <span class="block mt-1 font-semibold text-ember-600">You can upload a corrected receipt below.</span>
                </div>
            </div>
        @endif

        <!-- Amount due -->
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4 rounded-3xl bg-emerald-deep px-7 py-6 text-cream shadow-[0_14px_34px_-26px_rgba(6,40,30,0.5)]">
            <div>
                <span class="block text-[9px] font-bold uppercase tracking-[0.28em] text-cream/60">Amount due</span>
                <span class="block font-display text-4xl text-gold mt-1 tabnum leading-none">₱{{ number_format($amount, 2) }}</span>
            </div>
            @if ($booking->discount > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full border border-gold/40 bg-gold/10 px-3.5 py-1.5 text-[11px] font-bold text-gold">
                    <i class="fa-solid fa-tag text-[14px]"></i>
                    ₱{{ number_format($booking->discount, 2) }} discount applied
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

            <!-- Manual: GCash / bank transfer, verified by staff -->
            <div class="flex flex-col bg-cream-warm rounded-3xl p-7 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                    <i class="fa-solid fa-receipt text-[24px]"></i>
                </span>
                <h2 class="mt-4 font-display text-2xl text-ink tracking-tight">GCash or Bank Transfer</h2>
                <p class="mt-2 text-xs font-medium text-stone-500 leading-relaxed">Send the payment yourself, then upload the receipt you were given. Our front desk checks it against the transfer and confirms your booking.</p>

                <ol class="mt-5 space-y-2.5">
                    <li class="flex items-start gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-emerald-deep text-cream font-display italic text-[11px] flex items-center justify-center shrink-0">1</span>
                        <p class="text-xs font-bold text-stone-700 leading-relaxed">Send ₱{{ number_format($amount, 2) }} to the hostel account.</p>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-emerald-deep text-cream font-display italic text-[11px] flex items-center justify-center shrink-0">2</span>
                        <p class="text-xs font-bold text-stone-700 leading-relaxed">Upload a photo of your receipt with its reference number.</p>
                    </li>
                    <li class="flex items-start gap-2.5">
                        <span class="w-6 h-6 rounded-full bg-gold text-ink font-display italic text-[11px] flex items-center justify-center shrink-0">3</span>
                        <p class="text-xs font-bold text-stone-700 leading-relaxed">Staff verify it and your official receipt is emailed to you.</p>
                    </li>
                </ol>

                <a href="{{ route('bookings.pay.proof', $booking->id) }}"
                   class="press focus-ring mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-emerald-deep px-6 py-3.5 text-[12px] font-semibold uppercase tracking-[0.2em] text-cream !no-underline cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_25%,transparent)]">
                    <i class="fa-solid fa-file-arrow-up text-[18px]"></i>
                    Upload proof of payment
                </a>
            </div>

            <!-- Sandbox card gateway -->
            <div class="flex flex-col bg-cream-warm rounded-3xl p-7 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)]">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                    <i class="fa-solid fa-credit-card text-[24px]"></i>
                </span>
                <h2 class="mt-4 font-display text-2xl text-ink tracking-tight">Pay online by card</h2>
                <p class="mt-2 text-xs font-medium text-stone-500 leading-relaxed">Settle instantly through the bank portal. No staff review is needed. Your booking is confirmed the moment the payment clears.</p>

                <span class="mt-5 inline-flex w-fit items-center gap-1.5 rounded-full border border-gold/40 bg-gold-soft/30 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-[0.16em] text-ink/70">
                    <i class="fa-solid fa-flask text-[13px] text-gold"></i>
                    Sandbox, no real funds move
                </span>

                <div class="flex-1"></div>

                <a href="{{ route('bookings.pay.sandbox', $booking->id) }}"
                   class="press focus-ring mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full border border-emerald-deep/20 bg-cream px-6 py-3.5 text-[12px] font-semibold uppercase tracking-[0.2em] text-emerald-deep !no-underline cursor-pointer transition-colors hover:bg-emerald-deep hover:text-cream">
                    <i class="fa-solid fa-lock text-[18px]"></i>
                    Continue to bank portal
                </a>
            </div>
        </div>

        <div class="mt-8 text-center">
            <a href="{{ route('booking.show', $booking->id) }}" class="press inline-flex items-center justify-center gap-1.5 rounded-full border border-emerald-deep/20 bg-cream px-6 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-deep !no-underline transition-colors hover:bg-emerald-deep hover:text-cream">
                <i class="fa-solid fa-arrow-left text-[16px]"></i>
                Back to booking
            </a>
        </div>
    </div>
</div>
@endsection
