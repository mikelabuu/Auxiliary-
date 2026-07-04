@php
    $statusColorMap = [
        'paid' => 'clsu', 'active' => 'clsu', 'completed' => 'clsu',
        'pending_payment' => 'palay', 'pending_discount' => 'palay',
        'cancelled' => 'ember', 'expired' => 'ember', 'no_show' => 'ember',
    ];
    $badgeClassMap = [
        'clsu'  => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'palay' => 'bg-palay-100 text-palay-800 border-palay-200',
        'ember' => 'bg-ember-50 text-ember-700 border-ember-200',
    ];
    $statusColor = $statusColorMap[$booking->status] ?? 'stone';
    $badgeClass = $badgeClassMap[$statusColor] ?? 'bg-stone-100 text-stone-600 border-stone-200';
    $statusLabel = ucwords(str_replace('_', ' ', $booking->status));

    $payment = $booking->payments instanceof \Illuminate\Support\Collection ? $booking->payments->first() : $booking->payments;
    $paymentStatusColor = ['success' => 'clsu', 'pending' => 'palay'][$payment->status ?? ''] ?? 'ember';
@endphp

<div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
    <div class="flex items-center justify-between">
        <p class="text-sm font-semibold text-stone-900">Booking #{{ $booking->id }}</p>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeClass }}">{{ $statusLabel }}</span>
    </div>

    <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm bg-stone-50/70 rounded-xl border border-stone-100 p-4">
        <div>
            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Guest</p>
            <p class="text-stone-800 font-medium mt-0.5">{{ $booking->guest_name }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Phone</p>
            <p class="text-stone-800 font-medium mt-0.5">{{ $booking->guest_phone ?: '—' }}</p>
        </div>
        <div class="col-span-2">
            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Address</p>
            <p class="text-stone-800 font-medium mt-0.5">{{ $booking->guest_address ?: '—' }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Check-in</p>
            <p class="text-stone-800 font-medium mt-0.5 font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Check-out</p>
            <p class="text-stone-800 font-medium mt-0.5 font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Expected Guests</p>
            <p class="text-stone-800 font-medium mt-0.5">{{ $booking->expected_guests }}</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Booked At</p>
            <p class="text-stone-800 font-medium mt-0.5 font-data tabnum">{{ $booking->updated_at->format('M d, Y g:i A') }}</p>
        </div>
    </div>

    <div>
        <p class="flex items-center gap-2 text-xs font-bold text-stone-500 uppercase tracking-widest mb-2.5">
            <x-admin.icon name="receipt" class="w-3.5 h-3.5" />
            Pricing Summary
        </p>
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-stone-200/70 p-3">
                <p class="text-[10px] text-stone-400 uppercase tracking-wide">Total Price</p>
                <p class="text-sm font-bold text-stone-800 font-data tabnum mt-0.5">₱{{ number_format($booking->total_price, 2) }}</p>
            </div>
            <div class="rounded-xl border border-stone-200/70 p-3">
                <p class="text-[10px] text-stone-400 uppercase tracking-wide">Discount</p>
                <p class="text-sm font-bold text-stone-800 font-data tabnum mt-0.5">₱{{ number_format($booking->discount, 2) }}</p>
            </div>
            <div class="rounded-xl border border-clsu-200 bg-clsu-50/60 p-3">
                <p class="text-[10px] text-clsu-700 uppercase tracking-wide font-semibold">Payable</p>
                <p class="text-sm font-bold text-clsu-800 font-data tabnum mt-0.5">₱{{ number_format($booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price, 2) }}</p>
            </div>
        </div>
    </div>

    <div>
        <p class="flex items-center gap-2 text-xs font-bold text-stone-500 uppercase tracking-widest mb-2.5">
            <x-admin.icon name="bed" class="w-3.5 h-3.5" />
            Room Details
        </p>
        <div class="space-y-2">
            @foreach ($booking->reservations as $res)
                <div class="rounded-xl border border-stone-200/70 p-3.5">
                    <p class="text-sm font-semibold text-stone-800">Room {{ $res->room_number }} <span class="text-stone-400 font-normal">({{ $res->room->room_type ?? '—' }})</span></p>
                    <div class="grid grid-cols-3 gap-2 mt-1.5 text-xs text-stone-500">
                        <p>Guests: <span class="font-semibold text-stone-700">{{ $res->num_guests }}</span></p>
                        <p>Seniors/PWD: <span class="font-semibold text-stone-700">{{ $res->num_seniors }}</span></p>
                        <p>Meals: <span class="font-semibold text-stone-700">{{ collect($res->meal)->filter(fn($v) => $v == '1')->keys()->implode(', ') ?: 'None' }}</span></p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if ($payment)
        <div>
            <p class="flex items-center gap-2 text-xs font-bold text-stone-500 uppercase tracking-widest mb-2.5">
                <x-admin.icon name="credit-card" class="w-3.5 h-3.5" />
                Payment Details
            </p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-2 rounded-xl border border-stone-200/70 p-3.5 text-sm">
                <p class="text-stone-500">Reference # <span class="block text-stone-800 font-semibold font-data">{{ $payment->reference_no ?? 'N/A' }}</span></p>
                <p class="text-stone-500">Amount Paid <span class="block text-stone-800 font-semibold font-data tabnum">₱{{ number_format($payment->amount, 2) }}</span></p>
                <p class="text-stone-500">Status
                    <span class="block mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeClassMap[$paymentStatusColor] ?? 'bg-stone-100 text-stone-600 border-stone-200' }}">{{ ucfirst($payment->status) }}</span>
                    </span>
                </p>
            </div>
        </div>
    @endif
</div>
