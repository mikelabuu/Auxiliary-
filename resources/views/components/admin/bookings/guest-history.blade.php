@props(['booking'])

{{--
    "Other stays by this guest" section inside the booking detail modal —
    guest history and the view modal are one surface, not two separate
    modals. Matches by account (user_id) when the guest has one, by exact
    guest name otherwise (walk-ins).
--}}

@php
    $stays = \App\Models\Booking::with('reservations')
        ->where(function ($q) use ($booking) {
            $q->where('guest_name', $booking->guest_name);
            if ($booking->user_id) {
                $q->orWhere('user_id', $booking->user_id);
            }
        })
        ->orderByDesc('check_in')
        ->get();

    $statusClassMap = [
        'paid' => 'status-paid', 'active' => 'status-active', 'completed' => 'status-completed',
        'pending_payment' => 'status-pending_payment', 'pending_discount' => 'status-pending_discount',
        'cancelled' => 'status-cancelled', 'expired' => 'status-expired', 'no_show' => 'status-cancelled',
    ];
    $completedCount = $stays->where('status', 'completed')->count();
@endphp

<div>
    <p class="flex items-center gap-2 text-xs font-bold text-stone-500 uppercase tracking-widest mb-2.5">
        <x-admin.ui.icon name="users" class="w-3.5 h-3.5" />
        Guest History
        <span class="normal-case tracking-normal font-semibold text-stone-400">— {{ $stays->count() }} {{ Str::plural('booking', $stays->count()) }} · {{ $completedCount }} completed · {{ $booking->user_id ? 'has account' : 'walk-in / no account' }}</span>
    </p>
    <div class="space-y-2">
        @foreach($stays as $stay)
            @php
                $rooms = $stay->reservations->pluck('room_number')->filter()->implode(', ');
                $nights = max(1, $stay->check_in->diffInDays($stay->check_out));
            @endphp
            <div class="rounded-xl border p-3.5 {{ $stay->id === $booking->id ? 'border-clsu-300 bg-clsu-50/40' : 'border-stone-200' }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span class="ref-code">BK-{{ str_pad($stay->id, 4, '0', STR_PAD_LEFT) }}</span>
                        @if($stay->id === $booking->id)
                            <span class="text-[10px] font-bold text-clsu-700 uppercase tracking-wide">This booking</span>
                        @endif
                    </div>
                    <span class="status {{ $statusClassMap[$stay->status] ?? 'status-neutral' }}">{{ ucwords(str_replace('_', ' ', $stay->status)) }}</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2.5 text-xs text-stone-500">
                    <p>Stay <span class="block font-semibold text-stone-700 font-data tabnum">{{ $stay->check_in->format('M d') }} - {{ $stay->check_out->format('M d, Y') }}</span></p>
                    <p>{{ Str::plural('Night', $nights) }} <span class="block font-semibold text-stone-700 font-data tabnum">{{ $nights }}</span></p>
                    <p>Rooms <span class="block font-semibold text-stone-700 font-data">{{ $rooms ?: '—' }}</span></p>
                </div>
            </div>
        @endforeach
    </div>
</div>
