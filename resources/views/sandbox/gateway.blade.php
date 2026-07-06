@extends('layouts.public.auth')
@section('title', 'Secure Payment | Farmers Hostel')

@section('content')
@php
    $booking = $payment->booking;
    $nights = $booking ? max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out))) : 1;
@endphp

<div class="w-full max-w-4xl animate-success-pop">

    <!-- Portal masthead -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5 px-1">
        <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-2xl bg-white/10 border border-white/20 backdrop-blur flex items-center justify-center">
                <span class="material-icons text-palay-300 text-[22px]">lock</span>
            </span>
            <div>
                <p class="text-white font-display text-lg leading-tight">Secure Payment Portal</p>
                <p class="text-white/60 text-xs font-medium">Farmers Hostel · Booking #{{ $payment->booking_id }}</p>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-full border border-palay-300/40 bg-palay-400/15 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-[0.18em] text-palay-200">
            <span class="material-icons text-[13px]">science</span>
            Sandbox — no real funds move
        </span>
    </div>

    <div class="bg-white rounded-[28px] shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-5">

        <!-- ── Left: payment form ── -->
        <div class="lg:col-span-3 p-6 sm:p-8">
            @if (session('error'))
                <div class="animate-shake mb-5 flex items-start gap-2.5 rounded-2xl border border-ember-200 bg-ember-50 px-4 py-3 text-sm font-semibold text-ember-700">
                    <span class="material-icons text-[18px] mt-0.5 shrink-0">error_outline</span>
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="animate-shake mb-5 rounded-2xl border border-ember-200 bg-ember-50 px-4 py-3 text-sm font-semibold text-ember-700">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li class="flex items-start gap-2"><span class="material-icons text-[16px] mt-0.5 shrink-0">error_outline</span>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <h3 class="font-display text-2xl text-ink tracking-tight">Confirm your <span class="italic text-clsu-700">payment</span></h3>
            <p class="text-xs font-medium text-stone-400 mt-1.5 leading-relaxed">This simulated bank portal settles booking <strong class="text-stone-600">#{{ $payment->booking_id }}</strong>. Enter any card details to try the flow.</p>

            <form action="{{ route('sandbox.process', $payment->id) }}" method="POST" id="sandboxForm" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="payment_type" value="full">

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 mb-1.5">Amount to pay</label>
                    <div class="flex items-center justify-between rounded-2xl border border-palay-200 bg-palay-50/60 px-4 py-3">
                        <span class="text-xs font-bold text-stone-500 uppercase tracking-wider">Full amount</span>
                        <span class="font-display text-xl text-clsu-800 tabnum">₱{{ number_format($payment->amount, 2) }}</span>
                    </div>
                </div>

                <div>
                    <label for="card" class="block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 mb-1.5">Card / Account Number</label>
                    <div class="relative">
                        <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 text-[18px]">credit_card</span>
                        <input id="card" name="card" type="text" inputmode="numeric" autocomplete="cc-number" placeholder="1111 2222 3333 4444" maxlength="19" required
                               class="w-full pl-11 pr-4 py-3 rounded-2xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm font-bold tracking-widest tabnum focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label for="holder" class="block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 mb-1.5">Account Holder</label>
                    <div class="relative">
                        <span class="material-icons absolute left-3.5 top-1/2 -translate-y-1/2 text-stone-400 text-[18px]">person</span>
                        <input id="holder" name="holder" type="text" autocomplete="cc-name" placeholder="Juan Dela Cruz" required
                               class="w-full pl-11 pr-4 py-3 rounded-2xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm font-semibold focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all">
                    </div>
                </div>

                <!-- Simulation outcome — segmented control -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-[0.2em] text-stone-500 mb-1.5">Simulation Outcome</label>
                    <div class="grid grid-cols-2 gap-2 rounded-2xl border border-stone-200 bg-stone-50/60 p-1.5">
                        <label class="cursor-pointer">
                            <input type="radio" name="simulate" value="success" class="peer sr-only" checked>
                            <span class="flex items-center justify-center gap-1.5 rounded-xl py-2.5 text-xs font-bold text-stone-500 transition-all peer-checked:bg-clsu-700 peer-checked:text-white peer-checked:shadow-md">
                                <span class="material-icons text-[15px]">check_circle</span> Approve payment
                            </span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="simulate" value="fail" class="peer sr-only">
                            <span class="flex items-center justify-center gap-1.5 rounded-xl py-2.5 text-xs font-bold text-stone-500 transition-all peer-checked:bg-ember-600 peer-checked:text-white peer-checked:shadow-md">
                                <span class="material-icons text-[15px]">cancel</span> Decline payment
                            </span>
                        </label>
                    </div>
                </div>

                <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                    <button type="submit" id="payBtn"
                            class="w-full sm:flex-1 inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-b from-clsu-600 to-clsu-800 px-6 py-3.5 text-sm font-bold text-white shadow-[0_6px_16px_-4px_rgba(17,78,40,0.5)] transition-all hover:-translate-y-0.5 hover:shadow-[0_10px_24px_-6px_rgba(17,78,40,0.6)] focus:outline-none focus:ring-2 focus:ring-clsu-400 focus:ring-offset-2 cursor-pointer disabled:opacity-70 disabled:pointer-events-none">
                        <span class="material-icons text-[18px]">lock</span>
                        Pay ₱{{ number_format($payment->amount, 2) }}
                    </button>
                    <a href="{{ route('booking.show', $payment->booking_id) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-full border border-stone-200 bg-white px-5 py-3 text-xs font-bold text-stone-500 transition-colors hover:bg-stone-50 hover:text-stone-700 !no-underline">
                        Cancel
                    </a>
                </div>

                <div id="processing" style="display:none;" class="items-center justify-center gap-2.5 rounded-2xl border border-clsu-100 bg-clsu-50/70 px-4 py-3 text-xs font-bold text-clsu-700">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                    </svg>
                    Contacting the bank… please don't close this page.
                </div>
            </form>

            <div class="mt-6 pt-5 border-t border-stone-100 grid grid-cols-3 gap-2 text-center">
                <div class="text-[9px] font-bold text-stone-400 uppercase tracking-wider flex flex-col items-center gap-1">
                    <span class="material-icons text-[16px] text-clsu-600">lock</span> Encrypted
                </div>
                <div class="text-[9px] font-bold text-stone-400 uppercase tracking-wider flex flex-col items-center gap-1">
                    <span class="material-icons text-[16px] text-clsu-600">receipt_long</span> Instant receipt
                </div>
                <div class="text-[9px] font-bold text-stone-400 uppercase tracking-wider flex flex-col items-center gap-1">
                    <span class="material-icons text-[16px] text-clsu-600">mark_email_read</span> Email confirmation
                </div>
            </div>
        </div>

        <!-- ── Right: booking summary ── -->
        <aside class="lg:col-span-2 bg-clsu-900 text-white p-6 sm:p-8 relative overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-px bg-palay-300/40"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-20 -right-16 h-52 w-52 rounded-full bg-palay-300/10 blur-3xl"></div>

            <h4 class="font-display text-lg flex items-center gap-2 border-b border-white/10 pb-4">
                <span class="material-icons text-palay-300 text-[20px]">receipt_long</span>
                Payment Summary
            </h4>

            @if($booking)
                <div class="mt-4 space-y-1.5 text-sm">
                    <p class="font-bold truncate">{{ $booking->guest_name }}</p>
                    <p class="text-white/60 text-xs font-medium tabnum">
                        {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }} → {{ \Carbon\Carbon::parse($booking->check_out)->format('M d, Y') }}
                        · {{ $nights }} night{{ $nights === 1 ? '' : 's' }}
                    </p>
                </div>

                <div class="mt-4 space-y-2.5 border-t border-white/10 pt-4 text-sm">
                    @foreach($booking->reservations as $reservation)
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-white/90">Room {{ $reservation->room_number }} <span class="font-normal capitalize text-white/50">· {{ $reservation->room_type }}</span></p>
                                <p class="text-[11px] text-white/45">₱{{ number_format($reservation->price) }} × {{ $nights }} night{{ $nights === 1 ? '' : 's' }}</p>
                            </div>
                            <span class="whitespace-nowrap text-white/85 font-semibold tabnum">₱{{ number_format($reservation->price * $nights) }}</span>
                        </div>
                    @endforeach
                </div>

                @if($booking->discount > 0)
                    <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-4 text-sm">
                        <span class="flex items-center gap-1.5 text-palay-300"><span class="material-icons text-[15px]">discount</span> Discount</span>
                        <span class="font-semibold text-palay-300 tabnum">−₱{{ number_format($booking->discount, 2) }}</span>
                    </div>
                @endif
            @endif

            <div class="mt-4 space-y-2 border-t border-white/10 pt-4 text-xs text-white/50">
                <div class="flex justify-between"><span>Reference</span><span class="font-data text-white/80">{{ $payment->reference_no }}</span></div>
                <div class="flex justify-between"><span>Gateway</span><span class="text-white/80 capitalize">{{ $payment->gateway ?? 'sandbox' }}</span></div>
            </div>

            <div aria-hidden="true" class="mt-5 h-px w-full bg-palay-300/40"></div>
            <div class="mt-4 flex items-end justify-between">
                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-white/60">Total due</p>
                <p class="font-display text-3xl text-palay-300 tabnum leading-none">₱{{ number_format($payment->amount, 2) }}</p>
            </div>
        </aside>
    </div>

    <p class="text-center text-white/40 text-[11px] font-medium mt-5">
        Having trouble? Return to your <a href="{{ route('booking.show', $payment->booking_id) }}" class="text-palay-300 hover:text-palay-200 font-bold underline underline-offset-2">booking summary</a> and try again.
    </p>
</div>

@push('scripts')
<script>
    // Group the card number in blocks of 4 as the user types
    document.getElementById('card').addEventListener('input', function () {
        const digits = this.value.replace(/\D/g, '').slice(0, 16);
        this.value = digits.replace(/(.{4})/g, '$1 ').trim();
    });

    document.getElementById('sandboxForm').addEventListener('submit', function () {
        const btn = document.getElementById('payBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path></svg><span class="ml-2">Processing…</span>';
        document.getElementById('processing').style.display = 'flex';
    });
</script>
@endpush
@endsection
