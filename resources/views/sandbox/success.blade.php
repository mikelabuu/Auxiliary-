@extends('layouts.public.auth')
@section('title', 'Payment Successful | Farmers Hostel')

@section('content')
<div class="w-full max-w-lg animate-success-pop">
    <div class="bg-white rounded-[28px] shadow-2xl overflow-hidden text-center">
        <div class="bg-clsu-900 relative px-8 pt-10 pb-16 overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute -top-16 -right-14 h-48 w-48 rounded-full bg-palay-300/10 blur-3xl"></div>
            <svg class="w-20 h-20 mx-auto text-palay-300" viewBox="0 0 72 72" fill="none" aria-hidden="true">
                <circle class="success-check-circle" cx="36" cy="36" r="30" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                <path class="success-check-tick" d="M23 37.5L32 46.5L49 28" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.4em] text-palay-300">Booking #{{ $payment->booking_id }}</p>
            <h2 class="font-display text-3xl text-white tracking-tight mt-2">Payment <span class="italic text-palay-300">successful</span></h2>
        </div>

        <div class="px-6 sm:px-10 pb-8 -mt-8 relative">
            <div class="bg-white rounded-2xl border border-stone-100 shadow-lg px-5 py-4">
                <p class="font-display text-3xl text-clsu-800 tabnum">₱{{ number_format($payment->amount, 2) }}</p>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400 mt-1">Amount paid</p>
            </div>

            <p class="text-xs font-medium text-stone-400 leading-relaxed mt-5">
                Your reservation is confirmed. A receipt and confirmation email are on their way to your inbox.
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
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-clsu-200 bg-clsu-50 px-2.5 py-1 text-[10px] font-bold text-clsu-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-clsu-500"></span>
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row gap-2.5">
                <a href="{{ route('booking.show', $payment->booking_id) }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-b from-clsu-600 to-clsu-800 px-6 py-3 text-sm font-bold text-white shadow-[0_6px_16px_-4px_rgba(17,78,40,0.5)] transition-[transform,color,background-color,border-color,box-shadow] hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-6px_rgba(17,78,40,0.6)] !no-underline">
                    <i class="fa-solid fa-receipt text-[18px]"></i>
                    View my booking
                </a>
                <a href="{{ route('settings.bookings') }}"
                   class="inline-flex items-center justify-center gap-1.5 rounded-full border border-stone-200 bg-white px-5 py-3 text-xs font-bold text-stone-500 transition-colors hover:bg-stone-50 hover:text-stone-700 !no-underline">
                    All bookings
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
