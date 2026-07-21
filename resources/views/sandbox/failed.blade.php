@extends('layouts.public.auth')
@section('title', 'Payment Failed | Farmers Hostel')

@section('content')
<div class="w-full max-w-lg animate-success-pop">
    <div class="bg-white rounded-[28px] shadow-2xl overflow-hidden text-center">
        <div class="bg-ember-700 relative px-8 pt-10 pb-16 overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute -top-16 -right-14 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
            <span class="animate-shake inline-flex w-20 h-20 items-center justify-center rounded-full border-4 border-white/80 text-white">
                <span class="material-icons text-[40px]">priority_high</span>
            </span>
            <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.4em] text-white/70">Booking #{{ $payment->booking_id }}</p>
            <h2 class="font-display text-3xl text-white tracking-tight mt-2">Payment <span class="italic">declined</span></h2>
        </div>

        <div class="px-6 sm:px-10 pb-8 -mt-8 relative">
            <div class="bg-white rounded-2xl border border-stone-100 shadow-lg px-5 py-4">
                <p class="font-display text-3xl text-stone-700 tabnum">₱{{ number_format($payment->amount, 2) }}</p>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 mt-1">Amount not charged</p>
            </div>

            <p class="text-xs font-medium text-stone-400 leading-relaxed mt-5">
                The bank could not process this payment. No money left your account — your rooms remain on hold, so you can safely try again.
            </p>

            <div class="mt-5 rounded-2xl border border-stone-100 bg-stone-50/70 divide-y divide-stone-100 text-left text-xs">
                <div class="flex items-center justify-between px-4 py-2.5">
                    <span class="font-bold text-stone-400 uppercase tracking-wider text-[10px]">Reference</span>
                    <span class="font-data font-bold text-stone-700 tabnum">{{ $payment->reference_no }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-2.5">
                    <span class="font-bold text-stone-400 uppercase tracking-wider text-[10px]">Transaction ID</span>
                    <span class="font-data font-bold text-stone-700 tabnum">{{ $payment->landbank_transaction_id ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between px-4 py-2.5">
                    <span class="font-bold text-stone-400 uppercase tracking-wider text-[10px]">Status</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-ember-200 bg-ember-50 px-2.5 py-1 text-[10px] font-bold text-ember-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-ember-500"></span>
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-2.5">
                <a href="{{ route('bookings.pay', $payment->booking_id) }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-b from-clsu-600 to-clsu-800 px-6 py-3 text-sm font-bold text-white shadow-[0_6px_16px_-4px_rgba(17,78,40,0.5)] transition-[transform,color,background-color,border-color,box-shadow] hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-6px_rgba(17,78,40,0.6)] !no-underline">
                    <span class="material-icons text-[18px]">refresh</span>
                    Try again
                </a>
                <a href="{{ route('booking.show', $payment->booking_id) }}"
                   class="inline-flex items-center justify-center gap-1.5 rounded-full border border-stone-200 bg-white px-5 py-3 text-xs font-bold text-stone-500 transition-colors hover:bg-stone-50 hover:text-stone-700 !no-underline">
                    Back to booking
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
