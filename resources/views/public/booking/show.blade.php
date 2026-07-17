@extends('layouts.public.base')
@section('title', 'Booking Summary | Farmers Hostel')
@section('content')
<div class="min-h-screen bg-canvas pt-28 pb-24">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    @if(session('success'))
        <!-- Post-booking success hero: animated check + what happens next -->
        <div class="mb-10 animate-success-pop">
            <div class="bg-cream-warm rounded-[28px] border border-gold/25 shadow-capsule px-6 sm:px-10 py-9 text-center relative overflow-hidden">
                <div class="absolute -right-14 -top-16 w-56 h-56 rounded-full bg-emerald/10 blur-2xl pointer-events-none"></div>
                <div class="absolute -left-14 -bottom-16 w-56 h-56 rounded-full bg-gold-soft/40 blur-2xl pointer-events-none"></div>
                <div class="relative z-10">
                    <svg class="w-20 h-20 mx-auto text-emerald" viewBox="0 0 72 72" fill="none" aria-hidden="true">
                        <circle class="success-check-circle" cx="36" cy="36" r="30" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                        <path class="success-check-tick" d="M23 37.5L32 46.5L49 28" stroke="currentColor" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="mt-5 text-[10px] font-bold uppercase tracking-[0.4em] text-gold">Booking #{{ $booking->id }}</p>
                    <h2 class="text-balance font-display text-3xl sm:text-4xl text-ink tracking-tight mt-3">Your rooms are <span class="italic text-gold">on hold</span></h2>
                    <p class="text-sm font-medium text-ink/55 mt-3 max-w-md mx-auto">Here's what happens next:</p>

                    @php
                        $nextSteps = ($booking->wants_discount && $booking->status === 'pending_discount')
                            ? [
                                'Upload your Senior / PWD IDs for the 20% discount review.',
                                'Once approved, settle the discounted amount to confirm your stay.',
                            ]
                            : [
                                'Settle your payment to lock in the reservation.',
                                'Keep your receipt — you\'ll get it right after payment.',
                            ];
                        $nextSteps[] = 'Check in from 2:00 PM on ' . $booking->check_in->format('M d') . ' with a valid ID.';
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-7 max-w-3xl mx-auto text-left">
                        @foreach ($nextSteps as $step)
                            <div class="flex items-start gap-3 bg-stone-50/70 border border-stone-200/70 rounded-2xl px-4 py-3.5">
                                <span class="w-7 h-7 rounded-full {{ $loop->last ? 'bg-gold text-ink' : 'bg-emerald-deep text-cream' }} font-display italic text-xs flex items-center justify-center shrink-0">{{ $loop->iteration }}</span>
                                <p class="text-xs font-bold text-stone-700 leading-relaxed">{{ $step }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Status-driven Banner -->
    @php
        $statusColors = [
            'pending_payment'  => 'bg-palay-50 border-palay-200 text-palay-900',
            'pending_discount' => 'bg-palay-50 border-palay-200 text-palay-900',
            'active'           => 'bg-clsu-50 border-clsu-200 text-clsu-800',
            'paid'             => 'bg-clsu-50 border-clsu-200 text-clsu-800',
            'completed'        => 'bg-clsu-50 border-clsu-200 text-clsu-800',
            'cancelled'        => 'bg-ember-50 border-ember-200 text-ember-800',
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
        $bannerClass = $statusColors[$booking->status] ?? 'bg-stone-50 border-stone-200 text-stone-700';
        $bannerIcon = $statusIcons[$booking->status] ?? 'info';
    @endphp

    <div class="border rounded-3xl p-5 mb-8 flex items-center justify-between gap-4 {{ $bannerClass }}">
        <div class="flex items-center gap-3">
            <span class="w-11 h-11 rounded-2xl bg-white/70 flex items-center justify-center shrink-0">
                <span class="material-icons text-[24px]">{{ $bannerIcon }}</span>
            </span>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-widest opacity-80 leading-none">Booking #{{ $booking->id }} &middot; Status</span>
                <span class="block text-lg font-semibold tracking-tight mt-1 font-display">{{ $statusLabel }}</span>
            </div>
        </div>
        <div>
            <x-booking.ui.badge :status="$booking->status" class="!px-3 !py-1 text-xs" />
        </div>
    </div>

    <!-- 2-Column Desktop Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Left Side: Guest Info + Reservation Items Breakdown (Col-Span: 2) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Guest Details Card -->
            <x-booking.ui.card title="Guest Details" icon="badge">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm font-semibold">
                    <div>
                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-0.5">Primary Guest Name</span>
                        <span class="text-ink text-base font-bold">{{ $booking->guest_name }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-0.5">Contact Number</span>
                        <span class="text-ink text-base font-bold">{{ $booking->guest_phone }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-0.5">Address</span>
                        <span class="text-ink leading-relaxed font-bold">{{ $booking->guest_address }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-0.5">Expected Guests</span>
                        <span class="text-ink font-bold">{{ $booking->expected_guests }} Pax</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-0.5">Declared Seniors / PWD</span>
                        <span class="text-ink font-bold">{{ $booking->num_seniors }} Person(s)</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-0.5">Check-in Date</span>
                        <span class="text-ink font-bold">{{ $booking->check_in->format('F d, Y') }} (2:00 PM)</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-0.5">Check-out Date</span>
                        <span class="text-ink font-bold">{{ $booking->check_out->format('F d, Y') }} (12:00 NN)</span>
                    </div>
                </div>
            </x-booking.ui.card>

            <!-- Reservation Room Breakdown -->
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-ink tracking-tight flex items-center gap-2 px-1 font-display">
                    <span class="material-icons text-clsu-700">meeting_room</span>
                    Room Reservation Details
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    @foreach($booking->reservations as $reservation)
                        <div class="bg-white rounded-3xl border border-stone-200/70 p-5 shadow-[0_10px_30px_-18px_rgba(17,78,40,0.14)] hover:shadow-[0_16px_36px_-18px_rgba(17,78,40,0.22)] transition-shadow flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between border-b border-stone-100 pb-3 mb-4">
                                    <span class="text-sm font-bold text-ink">Room #{{ $reservation->room_number }}</span>
                                    <x-booking.ui.badge status="active">{{ ucfirst($reservation->room_type) }}</x-booking.ui.badge>
                                </div>

                                <div class="text-xs font-semibold space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-stone-400">Assigned Guests:</span>
                                        <span class="text-stone-800">{{ $reservation->num_guests }} Pax</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-stone-400">Seniors / PWD:</span>
                                        <span class="text-stone-800">{{ $reservation->num_seniors }}</span>
                                    </div>

                                    <div class="pt-2 border-t border-stone-100">
                                        <span class="block text-[10px] text-stone-400 uppercase tracking-widest mb-1.5">Free Breakfast Selection</span>
                                        @if(!empty($reservation->meal))
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($reservation->meal as $mealName => $qty)
                                                    @if($qty > 0)
                                                        <span class="px-2.5 py-1 bg-clsu-50 border border-clsu-100 rounded-lg text-[10px] text-clsu-700 font-bold uppercase tracking-wider">
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

                            <div class="mt-5 pt-3 border-t border-stone-100 flex items-center justify-between text-sm font-semibold">
                                <span class="text-stone-400">Room Price / Night:</span>
                                <span class="text-ink font-black">₱{{ number_format($reservation->price, 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Side: Billing Summary Card & Action CTAs (Col-Span: 1) -->
        <div class="space-y-6 lg:sticky lg:top-28">
            <!-- Payment Billing card -->
            <x-booking.ui.card title="Payment Summary" icon="receipt_long">
                <div class="space-y-4 text-sm font-semibold">
                    <div class="flex justify-between items-center text-stone-500">
                        <span>Subtotal Price</span>
                        <span>₱{{ number_format($booking->total_price, 2) }}</span>
                    </div>

                    @if($booking->discount > 0)
                        <div class="flex justify-between items-center text-clsu-700 bg-clsu-50 border border-clsu-100 px-3 py-2 rounded-xl text-xs">
                            <span class="flex items-center gap-1"><span class="material-icons text-[14px]">discount</span> Discount Approved</span>
                            <span>-₱{{ number_format($booking->discount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-stone-100 pt-3 text-ink">
                            <span class="text-base font-black">Payable Amount</span>
                            <span class="text-lg font-black text-clsu-800">₱{{ number_format($booking->payable_amount, 2) }}</span>
                        </div>
                    @else
                        <div class="flex justify-between items-center border-t border-stone-100 pt-3 text-ink">
                            <span class="text-base font-black">Total Price</span>
                            <span class="text-lg font-black text-clsu-800">₱{{ number_format($booking->total_price, 2) }}</span>
                        </div>
                    @endif

                    <!-- Discount Uploads Processing -->
                    @if($booking->wants_discount)
                        <div class="pt-4 border-t border-stone-100 space-y-3.5">
                            <h4 class="text-[10px] font-bold text-stone-400 uppercase tracking-widest leading-none">Senior / PWD Verification</h4>

                            @if(!$discountRequested)
                                <p class="text-xs text-stone-400 leading-relaxed font-semibold">Please upload Senior Citizen or PWD verification documents to apply your 20% discount.</p>
                                <x-booking.ui.button variant="secondary" href="{{ route('discount.create', $booking->id) }}" class="w-full py-3 flex items-center justify-center gap-1.5 text-xs">
                                    <span class="material-icons text-[16px]">upload_file</span>
                                    Request &amp; Upload IDs
                                </x-booking.ui.button>
                            @else
                                @if($discount && $discount->status === 'pending')
                                    <div class="p-3 bg-palay-50 border border-palay-200 rounded-xl text-xs space-y-3">
                                        <div class="flex gap-2 text-palay-800">
                                            <span class="material-icons text-[16px] flex-shrink-0 mt-0.5">hourglass_empty</span>
                                            <div class="font-bold leading-relaxed">Verification Request Submitted. Please wait for staff review and approval before making payments.</div>
                                        </div>
                                        <form action="{{ route('discount.cancel', $booking->id) }}" method="POST" class="w-full">
                                            @csrf
                                            <button type="submit" class="w-full py-2 bg-ember-50 hover:bg-ember-100 text-ember-700 font-bold rounded-lg text-[10px] transition-all cursor-pointer">
                                                Cancel Request
                                            </button>
                                        </form>
                                    </div>
                                @elseif($discount)
                                    <div class="flex items-center gap-2">
                                        <span class="text-stone-400 text-xs">Request Status:</span>
                                        <x-booking.ui.badge :status="$discount->status" />
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Primary Payments Button -->
                    @if($booking->status === 'pending_payment')
                        <div class="pt-4 border-t border-stone-100">
                            <x-booking.ui.button variant="primary" href="{{ route('bookings.pay', $booking->id) }}" class="w-full py-3.5 flex items-center justify-center gap-1.5 text-sm">
                                <span class="material-icons text-[18px]">payment</span>
                                Proceed to Payment
                            </x-booking.ui.button>
                        </div>
                    @endif
                </div>
            </x-booking.ui.card>

            <!-- Navigation control links -->
            <x-booking.ui.button variant="neutral" href="{{ route('settings.bookings') }}" class="w-full py-3 flex items-center justify-center gap-1.5">
                <span class="material-icons text-[18px]">arrow_back</span>
                Return to My Bookings
            </x-booking.ui.button>
        </div>
    </div>
</div>
</div>
@endsection
