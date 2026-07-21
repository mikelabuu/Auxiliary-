@extends('layouts.public.base')
@section('title', 'Booking Summary | Farmers Hostel')
@section('theme_night', '1')
@section('content')
<div class="min-h-screen bg-canvas pt-28 pb-24 relative isolate overflow-x-clip">
{{-- Ambient depth: same recipe as the checkout so the flow reads as one place --}}
<div class="night-aura night-aura--emerald" aria-hidden="true"></div>
<div class="night-aura night-aura--gold" aria-hidden="true"></div>
<div class="film-grain" aria-hidden="true"></div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    @if(session('success'))
        <!-- Post-booking success hero: animated check + what happens next -->
        <div class="mb-10 animate-success-pop">
            <div class="bg-gradient-to-b from-white/[0.06] to-white/[0.03] rounded-[28px] ring-1 ring-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,0.07),0_32px_70px_-38px_rgba(0,0,0,0.9)] px-6 sm:px-10 py-9 text-center relative overflow-hidden">
                <div class="absolute -right-14 -top-16 w-56 h-56 rounded-full bg-emerald/15 blur-2xl pointer-events-none"></div>
                <div class="absolute -left-14 -bottom-16 w-56 h-56 rounded-full bg-gold/10 blur-2xl pointer-events-none"></div>
                <div class="relative z-10">
                    <svg class="w-20 h-20 mx-auto text-gold" viewBox="0 0 72 72" fill="none" aria-hidden="true">
                        <circle class="success-check-circle" cx="36" cy="36" r="30" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <path class="success-check-tick" d="M23 37.5L32 46.5L49 28" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.4em] text-gold">Booking #{{ $booking->id }}</p>
                    <h2 class="text-balance font-display text-3xl sm:text-4xl text-ink tracking-tight mt-3">Your rooms are <span class="italic text-gold">on hold</span></h2>
                    <p class="text-sm font-medium text-ink/55 mt-3 max-w-md mx-auto">Here's what happens next:</p>

                    @php
                        // Step 1 names an action — so it IS the action: a link
                        // to the payment CTA (or the discount upload) instead of
                        // plain text that lives a scroll away from its button.
                        $nextSteps = ($booking->wants_discount && $booking->status === 'pending_discount')
                            ? [
                                ['text' => 'Upload your Senior / PWD IDs for the 20% discount review.', 'href' => $discountRequested ? null : route('discount.create', $booking->id)],
                                ['text' => 'Once approved, settle the discounted amount to confirm your stay.', 'href' => null],
                            ]
                            : [
                                ['text' => 'Settle your payment to lock in the reservation.', 'href' => $booking->status === 'pending_payment' ? '#paymentCta' : null],
                                ['text' => 'Keep your receipt — you\'ll get it right after payment.', 'href' => null],
                            ];
                        $nextSteps[] = ['text' => 'Check in from 2:00 PM on ' . $booking->check_in->format('M d') . ' with a valid ID.', 'href' => null];
                    @endphp
                    {{-- .next-step-card: cards deal out in order after the checkmark draw (app.css) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-7 max-w-3xl mx-auto text-left">
                        @foreach ($nextSteps as $step)
                            @php $tag = $step['href'] ? 'a' : 'div'; @endphp
                            <{{ $tag }}@if($step['href']) href="{{ $step['href'] }}"@endif class="next-step-card flex items-start gap-3 bg-white/5 ring-1 ring-white/10 rounded-2xl px-4 py-3.5{{ $step['href'] ? ' press !no-underline hover:bg-white/10 hover:ring-gold/40 cursor-pointer' : '' }}" style="--ns:{{ $loop->index }}">
                                <span class="w-7 h-7 rounded-full {{ $loop->last ? 'bg-gold text-night' : 'bg-bone text-night' }} font-display italic text-xs flex items-center justify-center shrink-0">{{ $loop->iteration }}</span>
                                <p class="text-xs font-bold text-ink/80 leading-relaxed">{{ $step['text'] }}@if($step['href']) <span class="material-icons text-[13px] align-middle text-gold">arrow_forward</span>@endif</p>
                            </{{ $tag }}>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Status-driven Banner -->
    @php
        $statusColors = [
            'pending_payment'  => 'bg-gold/10 ring-gold/30 text-gold',
            'pending_discount' => 'bg-gold/10 ring-gold/30 text-gold',
            'active'           => 'bg-emerald/10 ring-emerald/40 text-emerald',
            'paid'             => 'bg-emerald/10 ring-emerald/40 text-emerald',
            'completed'        => 'bg-emerald/10 ring-emerald/40 text-emerald',
            'cancelled'        => 'bg-ember-600/15 ring-ember-600/40 text-ember-200',
        ];
        $statusIcons = [
            'pending_payment'  => 'payments',
            'pending_discount' => 'upload_file',
            'active'           => 'check_circle',
            'paid'             => 'check_circle',
            'completed'        => 'task_alt',
            'cancelled'        => 'cancel',
        ];
        $statusLabel = ucwords(str_replace('_', ' ', $booking->status));
        $bannerClass = $statusColors[$booking->status] ?? 'bg-white/5 ring-white/15 text-ink/70';
        $bannerIcon = $statusIcons[$booking->status] ?? 'info';
    @endphp

    <div class="co-enter ring-1 rounded-3xl p-5 mb-8 flex items-center justify-between gap-4 {{ $bannerClass }}" style="--co:0">
        <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center shrink-0">
                <span class="material-icons text-[24px]">{{ $bannerIcon }}</span>
            </span>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest opacity-70 leading-none text-ink/70">Booking #{{ $booking->id }} &middot; Status</span>
                <span class="block text-lg font-semibold tracking-tight mt-1 font-display">{{ $statusLabel }}</span>
            </div>
        </div>
        <span class="hidden sm:inline-flex items-center rounded-full px-3 py-1 text-xs font-bold tracking-wide ring-1 ring-current/30 bg-white/5">{{ $statusLabel }}</span>
    </div>

    <!-- 2-Column Desktop Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Left Side: Guest Info + Reservation Items Breakdown (Col-Span: 2) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Guest Details Card -->
            <div class="co-enter bg-gradient-to-b from-white/[0.06] to-white/[0.03] rounded-3xl ring-1 ring-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,0.07)] p-6 sm:p-7" style="--co:1">
                <div class="flex items-center gap-2.5 mb-5 border-b border-white/10 pb-4">
                    <span class="w-9 h-9 rounded-xl bg-gold/10 text-gold ring-1 ring-gold/25 flex items-center justify-center shrink-0">
                        <span class="material-icons text-[19px]">badge</span>
                    </span>
                    <h3 class="text-lg font-semibold text-ink tracking-tight font-display">Guest Details</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm font-semibold">
                    <div>
                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-0.5">Primary Guest Name</span>
                        <span class="text-ink text-base font-bold">{{ $booking->guest_name }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-0.5">Contact Number</span>
                        <span class="text-ink text-base font-bold">{{ $booking->guest_phone }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-0.5">Address</span>
                        <span class="text-ink/85 leading-relaxed font-bold">{{ $booking->guest_address }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-0.5">Expected Guests</span>
                        <span class="text-ink font-bold">{{ $booking->expected_guests }} Pax</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-0.5">Declared Seniors / PWD</span>
                        <span class="text-ink font-bold">{{ $booking->num_seniors }} Person(s)</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-0.5">Check-in Date</span>
                        <span class="text-ink font-bold">{{ $booking->check_in->format('F d, Y') }} (2:00 PM)</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-0.5">Check-out Date</span>
                        <span class="text-ink font-bold">{{ $booking->check_out->format('F d, Y') }} (12:00 NN)</span>
                    </div>
                </div>
            </div>

            <!-- Reservation Room Breakdown -->
            <div class="co-enter space-y-4" style="--co:2">
                <h3 class="text-lg font-semibold text-ink tracking-tight flex items-center gap-2 px-1 font-display">
                    <span class="material-icons text-gold">meeting_room</span>
                    Room Reservation Details
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($booking->reservations as $reservation)
                        <div class="bg-gradient-to-b from-white/[0.06] to-white/[0.03] rounded-3xl ring-1 ring-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,0.07)] p-5 hover:ring-white/20 transition-[color,background-color,border-color,box-shadow] duration-300 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between border-b border-white/10 pb-3 mb-4">
                                    <span class="text-sm font-bold text-ink">Room #{{ $reservation->room_number }}</span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold tracking-wide bg-emerald/10 ring-1 ring-emerald/40 text-emerald">{{ ucfirst($reservation->room_type) }}</span>
                                </div>

                                <div class="text-xs font-semibold space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-ink/45">Assigned Guests:</span>
                                        <span class="text-ink/85">{{ $reservation->num_guests }} Pax</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-ink/45">Seniors / PWD:</span>
                                        <span class="text-ink/85">{{ $reservation->num_seniors }}</span>
                                    </div>

                                    <div class="pt-2 border-t border-white/10">
                                        <span class="block text-[10px] text-ink/45 uppercase tracking-widest mb-1.5">Free Breakfast Selection</span>
                                        @if(!empty($reservation->meal))
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($reservation->meal as $mealName => $qty)
                                                    @if($qty > 0)
                                                        <span class="px-2.5 py-1 bg-emerald/10 ring-1 ring-emerald/30 rounded-lg text-[10px] text-emerald font-bold uppercase tracking-wider">
                                                            {{ $mealName }}: {{ $qty }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-ink/40 italic">No breakfast choices selected</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-3 border-t border-white/10 flex items-center justify-between text-sm font-semibold">
                                <span class="text-ink/45">Room Price / Night:</span>
                                <span class="text-gold font-black tabnum">₱{{ number_format($reservation->price, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Side: Billing Summary Card & Action CTAs (Col-Span: 1) -->
        <div class="co-enter space-y-6 lg:sticky lg:top-28" style="--co:2">
            <!-- Payment Billing card -->
            <div class="bg-gradient-to-b from-white/[0.06] to-white/[0.03] rounded-3xl ring-1 ring-white/10 shadow-[inset_0_1px_0_rgba(255,255,255,0.07),0_24px_50px_-30px_rgba(0,0,0,0.8)] p-6 sm:p-7">
                <div class="flex items-center gap-2.5 mb-5 border-b border-white/10 pb-4">
                    <span class="w-9 h-9 rounded-xl bg-gold/10 text-gold ring-1 ring-gold/25 flex items-center justify-center shrink-0">
                        <span class="material-icons text-[19px]">receipt_long</span>
                    </span>
                    <h3 class="text-lg font-semibold text-ink tracking-tight font-display">Payment Summary</h3>
                </div>
                <div class="space-y-4 text-sm font-semibold">
                    <div class="flex justify-between items-center text-ink/55">
                        <span>Subtotal Price</span>
                        <span class="tabnum">₱{{ number_format($booking->total_price, 2) }}</span>
                    </div>

                    @if($booking->discount > 0)
                        <div class="flex justify-between items-center text-emerald bg-emerald/10 ring-1 ring-emerald/30 px-3 py-2 rounded-xl text-xs">
                            <span class="flex items-center gap-1"><span class="material-icons text-[14px]">discount</span> Discount Approved</span>
                            <span class="tabnum">-₱{{ number_format($booking->discount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-white/10 pt-3 text-ink">
                            <span class="text-base font-black">Payable Amount</span>
                            <span class="text-lg font-black text-gold tabnum">₱{{ number_format($booking->payable_amount, 2) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between items-center border-t border-white/10 pt-3 text-ink">
                            <span class="text-base font-black">Total Price</span>
                            <span class="text-lg font-black text-gold tabnum">₱{{ number_format($booking->total_price, 2) }}</span>
                        </div>
                    @endif

                    <!-- Discount Uploads Processing -->
                    @if($booking->wants_discount)
                        <div class="pt-4 border-t border-white/10 space-y-3.5">
                            <h4 class="text-[10px] font-bold text-ink/45 uppercase tracking-widest leading-none">Senior / PWD Verification</h4>

                            @if(!$discountRequested)
                                <p class="text-xs text-ink/50 leading-relaxed font-semibold">Please upload Senior Citizen or PWD verification documents to apply your 20% discount.</p>
                                <a href="{{ route('discount.create', $booking->id) }}" class="press !no-underline w-full py-3 rounded-full flex items-center justify-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] bg-gold/10 text-gold ring-1 ring-gold/40 hover:bg-gold hover:text-night transition-colors cursor-pointer">
                                    <span class="material-icons text-[16px]">upload_file</span>
                                    Request &amp; Upload IDs
                                </a>
                            @else
                                @if($discount && $discount->status === 'pending')
                                    <div class="p-3 bg-gold/10 ring-1 ring-gold/30 rounded-xl text-xs space-y-3">
                                        <div class="flex gap-2 text-gold">
                                            <span class="material-icons text-[16px] flex-shrink-0 mt-0.5">hourglass_empty</span>
                                            <div class="font-bold leading-relaxed">Verification Request Submitted. Please wait for staff review and approval before making payments.</div>
                                        </div>
                                        <form action="{{ route('discount.cancel', $booking->id) }}" method="POST" class="w-full">
                                            @csrf
                                            <button type="submit" class="w-full py-2 bg-ember-600/15 hover:bg-ember-600/25 text-ember-200 ring-1 ring-ember-600/40 font-bold rounded-lg text-[10px] transition-[color,background-color,border-color,box-shadow] cursor-pointer">
                                                Cancel Request
                                            </button>
                                        </form>
                                    </div>
                                @elseif($discount)
                                    <div class="flex items-center gap-2">
                                        <span class="text-ink/45 text-xs">Request Status:</span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold tracking-wide {{ $discount->status === 'approved' ? 'bg-emerald/10 ring-1 ring-emerald/40 text-emerald' : 'bg-ember-600/15 ring-1 ring-ember-600/40 text-ember-200' }}">{{ ucwords($discount->status) }}</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Primary Payments Button -->
                    @if($booking->status === 'pending_payment')
                        <div id="paymentCta" class="pt-4 border-t border-white/10 scroll-mt-28">
                            <a href="{{ route('bookings.pay', $booking->id) }}" class="press focus-ring !no-underline w-full min-h-12 py-3.5 rounded-full flex items-center justify-center gap-2 text-[12px] font-semibold uppercase tracking-[0.2em] bg-bone text-night cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)]">
                                <span class="material-icons text-[18px]">payment</span>
                                Proceed to Payment
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Navigation control links -->
            <a href="{{ route('settings.bookings') }}" class="press !no-underline w-full py-3 rounded-full flex items-center justify-center gap-1.5 text-sm font-bold text-ink/80 bg-white/5 ring-1 ring-white/15 hover:bg-white/10 hover:text-ink transition-colors cursor-pointer">
            <span class="material-icons text-[18px]">arrow_back</span>
                Return to My Bookings
            </a>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
    // Success-hero step 1 → glide to the payment CTA and flash its card
    // (same reduced-motion-aware scroll + block-flash contract as checkout).
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href="#paymentCta"]');
        if (!link) return;
        e.preventDefault();
        const target = document.getElementById('paymentCta');
        if (!target) return;
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        target.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
        const card = target.closest('.rounded-3xl') || target;
        card.classList.remove('block-flash');
        void card.offsetWidth; // restart the outline flash even on repeat clicks
        card.classList.add('block-flash');
    });
</script>
@endpush
@endsection
