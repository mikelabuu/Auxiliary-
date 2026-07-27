@props(['booking', 'stays' => null])

{{--
    "Other stays by this guest" table inside the booking detail modal — guest
    history and the view modal are one surface, not two separate modals.
    Matches by account (user_id) when the guest has one, by exact guest name
    otherwise (walk-ins).

    `stays` is optional: the dossier already runs this query to build its
    lifetime-spend stats and passes the result in, so the modal doesn't run it
    twice. Left null (any standalone use) the component queries for itself.
--}}

@php
    $stays ??= \App\Models\Booking::with('reservations')
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

    $catalog = \App\Support\RoomCatalog::all();
    $completedCount = $stays->where('status', 'completed')->count();
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-3">
        <p class="flex items-center gap-2 text-xs font-bold text-stone-500 uppercase tracking-widest">
            <x-admin.ui.icon name="clipboard" class="w-3.5 h-3.5" />
            History Booking
            <span class="normal-case tracking-normal font-semibold text-stone-400">— {{ $stays->count() }} {{ Str::plural('stay', $stays->count()) }}, {{ $completedCount }} completed</span>
        </p>
    </div>

    <div class="scroll-x rounded-xl border border-stone-200">
        <table class="data-table !min-w-[720px]">
            <thead>
                <tr>
                    <th>Room</th>
                    <th>Reference</th>
                    <th>Stay</th>
                    <th>Nights</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stays as $stay)
                    @php
                        $rooms = $stay->reservations->pluck('room_number')->filter();
                        $nights = max(1, (int) $stay->check_in->diffInDays($stay->check_out));
                        $amount = $stay->payable_amount > 0 ? $stay->payable_amount : $stay->total_price;
                        $isCurrent = $stay->id === $booking->id;
                        $type = $catalog[$stay->reservations->first()?->room_type ?? ''] ?? null;
                    @endphp
                    <tr @class(['table-row-selected' => $isCurrent])>
                        <td>
                            <div class="cell-name">
                                @if($type)
                                    <img src="{{ asset($type['image']) }}" alt="" loading="lazy" decoding="async"
                                         class="w-11 h-11 shrink-0 rounded-lg object-cover border border-stone-200">
                                @else
                                    <span class="w-11 h-11 shrink-0 rounded-lg bg-stone-100 border border-stone-200 flex items-center justify-center text-stone-400">
                                        <x-admin.ui.icon name="bed" class="w-4 h-4" />
                                    </span>
                                @endif
                                <div class="cell-name-text">
                                    <p class="cell-name-primary">{{ $rooms->isNotEmpty() ? 'Room ' . $rooms->implode(', ') : 'Unassigned' }}</p>
                                    <p class="cell-name-secondary">{{ $type['title'] ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <span class="ref-code">BK-{{ str_pad($stay->id, 4, '0', STR_PAD_LEFT) }}</span>
                                @if($isCurrent)
                                    <span class="text-[10px] font-bold text-clsu-700 uppercase tracking-wide">This booking</span>
                                @endif
                            </div>
                        </td>
                        <td class="font-data tabnum">{{ $stay->check_in->format('M d') }} – {{ $stay->check_out->format('M d, Y') }}</td>
                        <td class="font-data tabnum">{{ $nights }}</td>
                        <td class="font-data tabnum">₱{{ number_format($amount, 2) }}</td>
                        <td><span class="status {{ $statusClassMap[$stay->status] ?? 'status-neutral' }}">{{ ucwords(str_replace('_', ' ', $stay->status)) }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
