@extends('layouts.public.base')
@section('title', 'Booking Summary | Farmers Hostel')
{{-- Cream Boutique, not Night Estate. This was the last page in the booking
     journey still on the dark canvas: checkout, discount and payment are all
     cream, so confirming a booking dipped into a dark room and came back out.
     The night aura and film grain that dressed that canvas came out with it,
     same as they did on the checkout. --}}
@section('content')
<div class="min-h-screen bg-canvas pt-28 pb-24 relative isolate overflow-x-clip">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    @if(session('success'))
        <!-- Post-booking success hero: animated check + what happens next -->
        <div class="mb-8 animate-success-pop">
            <div class="bg-cream-warm rounded-[28px] ring-1 ring-emerald-deep/8 shadow-[0_20px_50px_-32px_rgba(6,40,30,0.4)] px-6 sm:px-10 py-9 text-center relative overflow-hidden">
                <div class="absolute -right-14 -top-16 w-56 h-56 rounded-full bg-emerald/10 blur-2xl pointer-events-none"></div>
                <div class="absolute -left-14 -bottom-16 w-56 h-56 rounded-full bg-gold/10 blur-2xl pointer-events-none"></div>
                <div class="relative z-10">
                    <svg class="w-20 h-20 mx-auto text-emerald-deep" viewBox="0 0 72 72" fill="none" aria-hidden="true">
                        <circle class="success-check-circle" cx="36" cy="36" r="30" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <path class="success-check-tick" d="M23 37.5L32 46.5L49 28" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.4em] text-palay-800">Booking #{{ $booking->id }}</p>
                    @php
                        // This hero shows on any success flash, not only the one
                        // that follows creating the booking — submitting a
                        // reschedule or a proof of payment lands here too. The
                        // headline was hardcoded to "on hold", so a booking that
                        // was already paid announced itself as unpaid directly
                        // above a status band reading "Paid".
                        [$heroLead, $heroWord] = match ($booking->status) {
                            'pending_payment', 'pending_discount' => ['Your rooms are ', 'on hold'],
                            'paid'      => ['Your booking is ', 'confirmed'],
                            'active'    => ['You are ', 'checked in'],
                            'completed' => ['Your stay is ', 'complete'],
                            'cancelled' => ['This booking was ', 'cancelled'],
                            'expired'   => ['This booking has ', 'expired'],
                            'no_show'   => ['This booking was ', 'not claimed'],
                            default     => ['Your booking has been ', 'updated'],
                        };
                    @endphp
                    <h2 class="text-balance font-display text-3xl sm:text-4xl text-ink tracking-tight mt-3">{{ $heroLead }}<span class="italic text-gold">{{ $heroWord }}</span></h2>
                    <p class="text-sm font-medium text-stone-600 mt-3 max-w-md mx-auto">Here's what happens next:</p>

                    @php
                        // Step 1 names an action — so it IS the action: a link
                        // to the payment CTA (or the discount upload) instead of
                        // plain text that lives a scroll away from its button.
                        $nextSteps = match (true) {
                            $booking->wants_discount && $booking->status === 'pending_discount' => [
                                ['text' => 'Upload your Senior / PWD IDs for the 20% discount review.', 'href' => $discountRequested ? null : route('discount.create', $booking->id)],
                                ['text' => 'Once approved, pay the discounted amount at our front desk with the original IDs.', 'href' => null],
                            ],
                            // Approved and now owed — but a discounted rate is
                            // settled in person, so the step must not point at a
                            // payment page PaymentController will turn them away from.
                            (bool) $booking->wants_discount => [
                                ['text' => 'Pay at our front desk — bring the original Senior / PWD ID for every discounted guest.', 'href' => '#paymentCta'],
                                ['text' => 'Keep your receipt. You\'ll get it right after payment.', 'href' => null],
                            ],
                            default => [
                                ['text' => 'Settle your payment to lock in the reservation.', 'href' => $booking->status === 'pending_payment' ? '#paymentCta' : null],
                                ['text' => 'Keep your receipt. You\'ll get it right after payment.', 'href' => null],
                            ],
                        };
                        $nextSteps[] = ['text' => 'Check in from ' . $checkinTime . ' on ' . $booking->check_in->format('M d') . ' with a valid ID.', 'href' => null];
                    @endphp
                    {{-- .next-step-card: cards deal out in order after the checkmark draw (app.css) --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-7 max-w-3xl mx-auto text-left">
                        @foreach ($nextSteps as $step)
                            @php $tag = $step['href'] ? 'a' : 'div'; @endphp
                            <{{ $tag }}@if($step['href']) href="{{ $step['href'] }}"@endif class="next-step-card flex items-start gap-3 bg-white/70 ring-1 ring-emerald-deep/8 rounded-2xl px-4 py-3.5{{ $step['href'] ? ' press !no-underline hover:bg-white hover:ring-gold/45 cursor-pointer' : '' }}" style="--ns:{{ $loop->index }}">
                                <span class="w-7 h-7 rounded-full {{ $loop->last ? 'bg-gold text-night' : 'bg-emerald-deep text-cream' }} font-display italic text-xs flex items-center justify-center shrink-0">{{ $loop->iteration }}</span>
                                <p class="text-xs font-bold text-stone-700 leading-relaxed">{{ $step['text'] }}@if($step['href']) <x-booking.ui.icon-solid name="arrow-right" class="text-[13px] align-middle text-palay-800" />@endif</p>
                            </{{ $tag }}>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Hold countdown ──────────────────────────────────────────────
         The page has always said the rooms are "on hold" without saying
         for how long, while bookings:expire silently cancels the booking
         after the payment window. A guest could sit on this screen and watch
         the reservation die with no warning. The window is real, so state it
         and count it down. --}}
    @php
        $holdEndsAt = \App\Support\PaymentWindow::deadlineFor($booking);
        // Per booking, not the configured window: a hold cut short by the
        // check-in cap is genuinely shorter, and a bar measured against the
        // full 24 hours would open two-thirds empty and drain at the wrong rate.
        $holdWindow = \App\Support\PaymentWindow::secondsFor($booking);
        $paysAtDesk = (bool) $booking->wants_discount;
        // Whether the cap is what is ending this hold, rather than the window.
        // "Pay within 24 hours" is not the instruction when the real deadline
        // is this afternoon.
        $holdEndsAtArrival = $holdEndsAt
            && $holdEndsAt->equalTo(\App\Support\PaymentWindow::checkInMomentFor($booking));
    @endphp

    @if($holdEndsAt && $holdEndsAt->isFuture())
        <div class="co-enter mb-8 hold-bar" style="--co:0"
             data-hold-ends="{{ $holdEndsAt->timestamp }}"
             data-hold-window="{{ $holdWindow }}">
            <div class="hold-bar__row">
                <span class="hold-bar__icon" aria-hidden="true"><x-booking.ui.icon-solid name="hourglass-half" /></span>
                <div class="hold-bar__copy">
                    <p class="hold-bar__label">Rooms held for you</p>
                    <p class="hold-bar__note">
                        @if($holdEndsAtArrival)
                            {{-- The cap, not the window. Say which deadline this
                                 is, because "you have 24 hours" would be a lie
                                 on a stay that starts this afternoon. --}}
                            Your stay starts {{ $booking->check_in->isToday() ? 'today' : $booking->check_in->format('M d') }},
                            so this must be settled {{ $paysAtDesk ? 'at our front desk ' : '' }}by check-in at {{ $checkinTime }} — not the usual {{ \App\Support\PaymentWindow::label() }}.
                        @elseif($paysAtDesk)
                            Settle at our front desk before the timer runs out or these rooms go back on sale.
                        @else
                            Settle the payment before the timer runs out or these rooms go back on sale.
                        @endif
                    </p>
                </div>
                {{-- aria-live on the wrapper, not the digits: announcing every
                     tick would make a screen reader unusable. The value is
                     polite and only re-read when the minute changes. --}}
                <p class="hold-bar__clock tabnum" data-hold-clock
                   role="timer" aria-live="polite"
                   aria-label="Time left to pay">--:--</p>
            </div>
            <div class="hold-bar__track" aria-hidden="true">
                <span class="hold-bar__fill" data-hold-fill></span>
            </div>
        </div>
    @endif

    <!-- Status-driven Banner -->
    @php
        $statusColors = [
            'pending_payment'  => 'bg-gold/12 ring-gold/35 text-palay-800',
            'pending_discount' => 'bg-gold/12 ring-gold/35 text-palay-800',
            'active'           => 'bg-emerald/10 ring-emerald/35 text-emerald-deep',
            'paid'             => 'bg-emerald/10 ring-emerald/35 text-emerald-deep',
            'completed'        => 'bg-emerald/10 ring-emerald/35 text-emerald-deep',
            'cancelled'        => 'bg-ember-600/10 ring-ember-600/35 text-ember-700',
        ];
        $statusIcons = [
            'pending_payment'  => 'money-bill-wave',
            'pending_discount' => 'file-arrow-up',
            'active'           => 'circle-check',
            'paid'             => 'circle-check',
            'completed'        => 'circle-check',
            'cancelled'        => 'circle-xmark',
        ];
        $statusLabel = ucwords(str_replace('_', ' ', $booking->status));
        $bannerClass = $statusColors[$booking->status] ?? 'bg-white/70 ring-emerald-deep/10 text-stone-600';
        $bannerIcon = $statusIcons[$booking->status] ?? 'circle-info';
    @endphp

    <div class="co-enter ring-1 rounded-3xl p-5 mb-8 flex items-center justify-between gap-4 {{ $bannerClass }}" style="--co:0">
        <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-2xl bg-white/70 flex items-center justify-center shrink-0">
                <x-booking.ui.icon-solid :name="$bannerIcon" class="text-[24px]" />
            </span>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest opacity-70 leading-none">Booking #{{ $booking->id }} &middot; Status</span>
                <span class="block text-lg font-semibold tracking-tight mt-1 font-display">{{ $statusLabel }}</span>
            </div>
        </div>
        <span class="hidden sm:inline-flex items-center rounded-full px-3 py-1 text-xs font-bold tracking-wide ring-1 ring-current/30 bg-white/60">{{ $statusLabel }}</span>
    </div>

    <!-- 2-Column Desktop Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Left Side: Guest Info + Reservation Items Breakdown (Col-Span: 2) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Guest Details Card -->
            <div class="co-enter bg-cream-warm rounded-3xl ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] p-6 sm:p-7" style="--co:1">
                <div class="flex items-center gap-2.5 mb-5 border-b border-emerald-deep/10 pb-4">
                    <span class="w-9 h-9 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0">
                        <x-booking.ui.icon-solid name="id-badge" class="text-[19px]" />
                    </span>
                    <h3 class="text-lg font-semibold text-ink tracking-tight font-display">Guest Details</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm font-semibold">
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Primary Guest Name</span>
                        <span class="text-ink text-base font-bold">{{ $booking->guest_name }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Contact Number</span>
                        <span class="text-ink text-base font-bold">{{ $booking->guest_phone }}</span>
                        @if($booking->guest_phone_alt)
                            <span class="block text-stone-700 text-sm font-bold mt-0.5">{{ $booking->guest_phone_alt }}</span>
                        @endif
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Address</span>
                        <span class="text-stone-700 leading-relaxed font-bold">{{ $booking->guest_address }}</span>
                    </div>
                    @if ($booking->referred_by)
                        <div>
                            <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Reference Person</span>
                            <span class="text-stone-700 font-bold">{{ $booking->referred_by }}</span>
                            @if ($booking->referred_by_phone)
                                <span class="block text-stone-700 text-sm font-bold mt-0.5">{{ $booking->referred_by_phone }}</span>
                            @endif
                        </div>
                    @endif
                    @if ($booking->referred_by_purpose)
                        <div>
                            <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Purpose</span>
                            <span class="text-stone-700 font-bold">{{ $booking->referred_by_purpose }}</span>
                        </div>
                    @endif
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Expected Guests</span>
                        <span class="text-ink font-bold">{{ $booking->expected_guests }} Pax</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Declared Seniors / PWD</span>
                        <span class="text-ink font-bold">{{ $booking->num_seniors }} Person(s)</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Check-in Date</span>
                        <span class="text-ink font-bold">{{ $booking->check_in->format('F d, Y') }} ({{ $checkinTime }})</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Check-out Date</span>
                        <span class="text-ink font-bold">{{ $booking->check_out->format('F d, Y') }} ({{ $checkoutTime }})</span>
                    </div>
                    {{-- Only shown when the guest actually told us. "Not sure
                         yet" is a real answer, and printing "—" against a bold
                         label reads like something went missing. --}}
                    @if ($booking->arrival_time)
                        <div>
                            <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">Estimated Arrival</span>
                            <span class="text-ink font-bold">{{ \Carbon\Carbon::parse($booking->arrival_time)->format('g:i A') }}</span>
                        </div>
                    @endif
                </div>

                @if ($booking->special_requests)
                    <div class="mt-5 pt-5 border-t border-emerald-deep/10">
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-1.5">Your Requests</span>
                        <p class="text-sm font-medium text-stone-700 leading-relaxed whitespace-pre-line">{{ $booking->special_requests }}</p>
                        <p class="text-[11px] font-medium text-stone-500 mt-2">We'll do our best, though requests aren't guaranteed.</p>
                    </div>
                @endif
            </div>

            <!-- Reservation Room Breakdown -->
            <div class="co-enter space-y-4" style="--co:2">
                <h3 class="text-lg font-semibold text-ink tracking-tight flex items-center gap-2 px-1 font-display">
                    <x-booking.ui.icon-solid name="door-open" class="text-palay-800" />
                    Room Reservation Details
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($booking->reservations as $reservation)
                        <div class="bg-cream-warm rounded-3xl ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] p-5 hover:ring-emerald-deep/15 transition-[color,background-color,border-color,box-shadow] duration-300 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between border-b border-emerald-deep/10 pb-3 mb-4">
                                    <span class="text-sm font-bold text-ink">Room #{{ $reservation->room_number }}</span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold tracking-wide bg-emerald/10 ring-1 ring-emerald/35 text-emerald-deep">{{ ucfirst($reservation->room_type) }}</span>
                                </div>

                                <div class="text-xs font-semibold space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-stone-500">Assigned Guests:</span>
                                        <span class="text-stone-700">{{ $reservation->num_guests }} Pax</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-stone-500">Seniors / PWD:</span>
                                        <span class="text-stone-700">{{ $reservation->num_seniors }}</span>
                                    </div>

                                    <div class="pt-2 border-t border-emerald-deep/10">
                                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-1.5">Free Breakfast Selection</span>
                                        @if(!empty($reservation->meal))
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($reservation->meal as $mealName => $qty)
                                                    @if($qty > 0)
                                                        <span class="px-2.5 py-1 bg-emerald/10 ring-1 ring-emerald/30 rounded-lg text-[10px] text-emerald-deep font-bold uppercase tracking-wider">
                                                            {{ $mealName }}: {{ $qty }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-stone-400 italic">No breakfast choices selected</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 pt-3 border-t border-emerald-deep/10 flex items-center justify-between text-sm font-semibold">
                                <span class="text-stone-500">Room Price / Night:</span>
                                <span class="text-palay-800 font-black tabnum">₱{{ number_format($reservation->price, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Side: Billing Summary Card & Action CTAs (Col-Span: 1) -->
        <div class="co-enter space-y-6 lg:sticky lg:top-28" style="--co:2">
            <!-- Payment Billing card -->
            <div class="bg-cream-warm rounded-3xl ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] p-6 sm:p-7">
                <div class="flex items-center gap-2.5 mb-5 border-b border-emerald-deep/10 pb-4">
                    <span class="w-9 h-9 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0">
                        <x-booking.ui.icon-solid name="receipt" class="text-[19px]" />
                    </span>
                    <h3 class="text-lg font-semibold text-ink tracking-tight font-display">Payment Summary</h3>
                </div>
                <div class="space-y-4 text-sm font-semibold">
                    <div class="flex justify-between items-center text-stone-600">
                        <span>Subtotal Price</span>
                        <span class="tabnum">₱{{ number_format($booking->total_price, 2) }}</span>
                    </div>

                    @if($booking->discount > 0)
                        <div class="flex justify-between items-center text-emerald-deep bg-emerald/10 ring-1 ring-emerald/30 px-3 py-2 rounded-xl text-xs">
                            <span class="flex items-center gap-1"><x-booking.ui.icon-solid name="tag" class="text-[14px]" /> Discount Approved</span>
                            <span class="tabnum">-₱{{ number_format($booking->discount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-emerald-deep/10 pt-3 text-ink">
                            <span class="text-base font-black">Payable Amount</span>
                            <span class="text-lg font-black text-palay-800 tabnum">₱{{ number_format($booking->payable_amount, 2) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between items-center border-t border-emerald-deep/10 pt-3 text-ink">
                            <span class="text-base font-black">Total Price</span>
                            <span class="text-lg font-black text-palay-800 tabnum">₱{{ number_format($booking->total_price, 2) }}</span>
                        </div>
                    @endif

                    <!-- Discount Uploads Processing -->
                    @if($booking->wants_discount)
                        <div class="pt-4 border-t border-emerald-deep/10 space-y-3.5">
                            <h4 class="text-[10px] font-bold text-stone-500 uppercase tracking-widest leading-none">Senior / PWD Verification</h4>

                            @if(!$discountRequested)
                                <p class="text-xs text-stone-600 leading-relaxed font-semibold">Please upload Senior Citizen or PWD verification documents to apply your 20% discount.</p>
                                <a href="{{ route('discount.create', $booking->id) }}" class="press !no-underline w-full py-3 rounded-full flex items-center justify-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] bg-gold/12 text-palay-800 ring-1 ring-gold/40 hover:bg-gold hover:text-night transition-colors cursor-pointer">
                                    <x-booking.ui.icon-solid name="file-arrow-up" class="text-[16px]" />
                                    Request &amp; Upload IDs
                                </a>
                            @else
                                @if($discount && $discount->status === 'pending')
                                    <div class="p-3 bg-gold/12 ring-1 ring-gold/30 rounded-xl text-xs space-y-3">
                                        <div class="flex gap-2 text-palay-800">
                                            <x-booking.ui.icon-solid name="hourglass" class="text-[16px] flex-shrink-0 mt-0.5" />
                                            <div class="font-bold leading-relaxed">Verification Request Submitted. Please wait for staff review and approval before making payments.</div>
                                        </div>
                                        <form action="{{ route('discount.cancel', $booking->id) }}" method="POST" class="w-full" data-busy-form>
                                            @csrf
                                            <button type="submit" class="w-full py-2 bg-ember-600/10 hover:bg-ember-600/20 text-ember-700 ring-1 ring-ember-600/35 font-bold rounded-lg text-[10px] transition-[color,background-color,border-color,box-shadow] cursor-pointer">
                                                Cancel Request
                                            </button>
                                        </form>
                                    </div>
                                @elseif($discount)
                                    <div class="flex items-center gap-2">
                                        <span class="text-stone-500 text-xs">Request Status:</span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold tracking-wide {{ $discount->status === 'approved' ? 'bg-emerald/10 ring-1 ring-emerald/35 text-emerald-deep' : 'bg-ember-600/10 ring-1 ring-ember-600/35 text-ember-700' }}">{{ ucwords($discount->status) }}</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Primary Payments Button -->
                    @if($booking->status === 'pending_payment')
                        @php
                            $awaitingProof = ($latestPayment ?? null)
                                && $latestPayment->status === \App\Models\Payment::STATUS_AWAITING_VERIFICATION;
                            $proofRejected = ($latestPayment ?? null)
                                && $latestPayment->status === \App\Models\Payment::STATUS_REJECTED;
                        @endphp

                        <div id="paymentCta" class="pt-4 border-t border-emerald-deep/10 scroll-mt-28">
                            @if($paysAtDesk)
                                {{-- A discounted rate is granted against an original
                                     ID, which cannot be handed over through a form.
                                     PaymentController refuses this booking's online
                                     route, so the page must not offer it — it says
                                     where to go and what to bring instead. --}}
                                <div class="flex flex-col gap-3 rounded-2xl bg-gold/12 ring-1 ring-gold/30 px-4 py-3.5 text-xs">
                                    <div class="flex gap-2 text-palay-800">
                                        <x-booking.ui.icon-solid name="building-columns" class="text-[16px] flex-shrink-0 mt-0.5" />
                                        <div class="font-bold leading-relaxed">
                                            Settle this booking at our front desk. A Senior Citizen / PWD discount cannot be paid online — bring the original ID for every discounted guest and we will take payment in person.
                                        </div>
                                    </div>
                                    @if($holdEndsAt)
                                        <p class="text-[11px] font-semibold text-stone-500">
                                            Come in before <span class="tabnum">{{ $holdEndsAt->timezone(config('hostel.timezone'))->format('g:i A, M d') }}</span> or the rooms go back on sale.
                                        </p>
                                    @endif
                                </div>
                            @elseif($awaitingProof)
                                {{-- The guest has paid and proved it; the ball is with the front
                                     desk now, so the pay button would only invite a double payment. --}}
                                <div class="flex flex-col gap-3 rounded-2xl bg-gold/12 ring-1 ring-gold/30 px-4 py-3.5 text-xs">
                                    <div class="flex gap-2 text-palay-800">
                                        <x-booking.ui.icon-solid name="hourglass" class="text-[16px] flex-shrink-0 mt-0.5" />
                                        <div class="font-bold leading-relaxed">
                                            Proof of payment received. Our front desk is verifying it against the transfer. Your official receipt is emailed as soon as it clears.
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-stone-500">
                                        <span>{{ $latestPayment->proof_method_label }}</span>
                                        <span class="font-data">Ref {{ $latestPayment->proof_reference }}</span>
                                        <span class="tabnum">Sent {{ $latestPayment->proof_submitted_at?->timezone(config('hostel.timezone'))->format('M d, g:i A') }}</span>
                                    </div>
                                </div>
                            @else
                                @if($proofRejected)
                                    <div class="mb-3 flex gap-2 rounded-2xl bg-ember-600/10 ring-1 ring-ember-600/35 px-4 py-3.5 text-xs text-ember-700">
                                        <x-booking.ui.icon-solid name="circle-exclamation" class="text-[16px] flex-shrink-0 mt-0.5" />
                                        <div class="font-bold leading-relaxed">
                                            Your proof of payment was not accepted.
                                            @if($latestPayment->rejection_reason)
                                                <span class="block mt-1 font-semibold">Reason: {{ $latestPayment->rejection_reason }}</span>
                                            @endif
                                            <span class="block mt-1 font-semibold">Please upload a corrected receipt.</span>
                                        </div>
                                    </div>
                                @endif

                                <a href="{{ route('bookings.pay', $booking->id) }}" class="press focus-ring !no-underline w-full min-h-12 py-3.5 rounded-full flex items-center justify-center gap-2 text-[12px] font-semibold uppercase tracking-[0.2em] bg-emerald-deep text-cream cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)]">
                                    <x-booking.ui.icon-solid name="credit-card" class="text-[18px]" />
                                    {{ $proofRejected ? 'Try payment again' : 'Proceed to Payment' }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Changing a paid stay ─────────────────────────────────
                 There is no Cancel here, and that is the policy rather than an
                 omission: the rooms came off sale when the money landed. What a
                 guest can do is ask to move the stay, and only with a full
                 day's notice before check-in — so the card says the deadline
                 out loud and disappears once it has passed, because a button
                 the server would refuse is worse than no button at all. --}}
            @if(in_array($booking->status, \App\Models\RescheduleRequest::RESCHEDULABLE_STATUSES, true))
                @php
                    $openReschedule = \App\Models\RescheduleRequest::openFor($booking);
                    $rescheduleDeadline = \App\Models\RescheduleRequest::deadlineFor($booking);
                @endphp

                <div class="bg-cream-warm rounded-3xl ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] p-6">
                    <div class="flex items-center gap-2.5 mb-4 border-b border-emerald-deep/10 pb-4">
                        <span class="w-9 h-9 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0">
                            <x-booking.ui.icon-solid name="calendar-days" class="text-[18px]" />
                        </span>
                        <h3 class="text-lg font-semibold text-ink tracking-tight font-display">Can't make it?</h3>
                    </div>

                    @if($openReschedule)
                        <p class="text-xs font-semibold text-stone-600 leading-relaxed">
                            You asked to move this stay to
                            <strong class="text-ink">{{ $openReschedule->requested_check_in->format('M d') }} – {{ $openReschedule->requested_check_out->format('M d, Y') }}</strong>.
                            Our front desk is checking whether your rooms are free and will email you the answer.
                        </p>
                        <a href="{{ route('booking.reschedule.create', $booking->id) }}" class="press !no-underline mt-4 w-full py-2.5 rounded-full flex items-center justify-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] bg-gold/12 text-palay-800 ring-1 ring-gold/40 hover:bg-gold hover:text-night transition-colors cursor-pointer">
                            View request
                        </a>
                    @elseif($rescheduleDeadline->isFuture())
                        <p class="text-xs font-semibold text-stone-600 leading-relaxed">
                            A paid booking can't be cancelled, but we can move it. Tell us by
                            <strong class="text-ink">{{ $rescheduleDeadline->format('g:i A') }} on {{ $rescheduleDeadline->format('M d') }}</strong>
                            — 24 hours before your {{ $booking->check_in->format('M d') }} check-in — and we'll try to find you new dates.
                        </p>
                        <p class="text-[11px] font-medium text-stone-500 leading-relaxed mt-2">
                            After that we can't move it, and a booking nobody checks in to is forfeited — no refund.
                        </p>
                        <a href="{{ route('booking.reschedule.create', $booking->id) }}" class="press !no-underline mt-4 w-full py-2.5 rounded-full flex items-center justify-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] bg-gold/12 text-palay-800 ring-1 ring-gold/40 hover:bg-gold hover:text-night transition-colors cursor-pointer">
                            <x-booking.ui.icon-solid name="calendar-days" class="text-[15px]" />
                            Request a reschedule
                        </a>
                    @else
                        <p class="text-xs font-semibold text-stone-600 leading-relaxed">
                            The deadline to move this stay has passed — we needed 24 hours' notice, so it closed {{ $rescheduleDeadline->format('g:i A, M d') }}. Please call our front desk.
                        </p>
                    @endif
                </div>
            @endif

            <!-- Navigation control links -->
            <a href="{{ route('settings.bookings') }}" class="press !no-underline w-full py-3 rounded-full flex items-center justify-center gap-1.5 text-sm font-bold text-stone-700 bg-white/70 ring-1 ring-emerald-deep/10 hover:bg-white hover:text-ink transition-colors cursor-pointer">
            <x-booking.ui.icon-solid name="arrow-left" class="text-[18px]" />
                Return to My Bookings
            </a>
        </div>
    </div>
</div>
</div>

@push('scripts')
@auth
<script>
    // Real-time push: this page is where a guest waits on a decision they
    // can't make themselves — a Senior/PWD discount being reviewed, or a
    // payment being confirmed. Until now the only way to learn the outcome was
    // to refresh by hand.
    //
    // Private channel: unlike the staff-facing broadcasts (payload-free, public
    // channels), this one reaches a guest's browser, so subscription is
    // authorised in routes/channels.php against the booking's owner and the
    // payload carries only the new status.
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Echo) return;

        window.Echo.private('booking.{{ $booking->id }}')
            .listen('.BookingStatusChanged', function (payload) {
                if (payload && payload.status === '{{ $booking->status }}') return;

                // Re-render server-side: the status banner, payable amount,
                // discount panel and payment CTA are all computed in Blade, so
                // reloading is both the simplest and the only consistent way
                // to show the new state.
                window.location.reload();
            });
    });

    // ...and the same outcome without a WebSocket, which is what actually runs.
    //
    // The listener above has never fired on this page: window.Echo is built in
    // resources/js/echo.js, which only resources/js/admin.js imports, so it does
    // not exist in the public bundle a guest loads. Polling a status-only
    // endpoint needs no Reverb daemon and no WebSocket proxy. If Reverb is ever
    // running, the listener simply gets there first and this never fires.
    document.addEventListener('DOMContentLoaded', function () {
        const ENDPOINT = @json(route('bookings.status.feed'));
        const ID       = @json((string) $booking->id);
        const RENDERED = @json($booking->status);
        const INTERVAL = 20000;

        let inFlight = false;

        async function check() {
            // Nothing to watch for on a tab nobody is looking at; the
            // visibility handler below catches up the moment they return.
            if (inFlight || document.hidden) return;
            inFlight = true;

            try {
                const res = await fetch(ENDPOINT, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;

                const data = await res.json();
                const now = (data.bookings || {})[ID];

                if (now && now.status && now.status !== RENDERED) window.location.reload();
            } catch (e) {
                // Offline, or a blip — the next tick tries again. A guest
                // waiting on a decision must never be shown a network error.
            } finally {
                inFlight = false;
            }
        }

        setInterval(check, INTERVAL);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) check();
        });
    });
</script>
@endauth
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

    // ── Hold countdown ──────────────────────────────────────────────────
    // Deadline comes from the server as a unix timestamp, so a wrong clock on
    // the guest's device shifts the reading but never invents time they don't
    // have — the expiry itself is decided by bookings:expire, not by this.
    (function () {
        const bar = document.querySelector('.hold-bar[data-hold-ends]');
        if (!bar) return;

        const endsAt = Number(bar.dataset.holdEnds) * 1000;
        const window_ = Number(bar.dataset.holdWindow) * 1000;
        const clock = bar.querySelector('[data-hold-clock]');
        const fill = bar.querySelector('[data-hold-fill]');
        const pad = (n) => String(n).padStart(2, '0');
        let lastMinute = null;

        function tick() {
            const left = endsAt - Date.now();

            if (left <= 0) {
                clock.textContent = '00:00';
                fill.style.transform = 'scaleX(0)';
                bar.classList.add('is-expired');
                clock.removeAttribute('aria-live');
                // The room is only actually released by the scheduler, so ask
                // the server what happened rather than asserting it here.
                setTimeout(() => window.location.reload(), 2000);
                return;
            }

            // HH:MM:SS once the window is long enough to need it. The payment
            // window is 24 hours, and MM:SS rendered that as "1439:59" — a
            // number nobody reads as "a day".
            const total = Math.ceil(left / 1000);
            const hours = Math.floor(total / 3600);
            const mins = Math.floor(total / 60) % 60;
            const secs = total % 60;
            clock.textContent = hours > 0
                ? pad(hours) + ':' + pad(mins) + ':' + pad(secs)
                : pad(mins) + ':' + pad(secs);

            // Re-announce on the minute only — a per-second live region is
            // unusable with a screen reader.
            const totalMins = Math.floor(total / 60);
            if (totalMins !== lastMinute) {
                clock.setAttribute('aria-label', hours > 0
                    ? hours + ' hours ' + mins + ' minutes left to pay'
                    : totalMins + ' minutes left to pay');
                lastMinute = totalMins;
            }

            fill.style.transform = 'scaleX(' + Math.max(0, Math.min(1, left / window_)) + ')';
            // Urgent in the last hour, or the last tenth of a shorter window —
            // ten minutes out of twenty-four hours is a warning nobody is
            // still on the page to see.
            bar.classList.toggle('is-urgent', left <= Math.min(60 * 60 * 1000, window_ / 6));

            requestAnimationFrame(() => setTimeout(tick, 1000));
        }

        tick();
    })();
</script>
@endpush
@endsection
