<x-admin.ui.section-card wire:poll.15s="loadActiveBookings" icon="log-in" title="Active Stays" subtitle="Guests currently checked in.">
    @if($activeBookings->isEmpty())
        <x-admin.ui.empty-state icon="log-in" title="No active bookings at the moment." />
    @else
        <div class="scroll-x -mx-6 -mb-6 border-t border-stone-100">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ref code</th>
                        <th>Guest</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Room</th>
                        <th>Room Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeBookings as $booking)
                        @foreach($booking->reservations as $res)
                            @php
                                $initials = collect(explode(' ', trim($booking->guest_name)))
                                    ->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('');
                                $initials = strtoupper($initials ?: 'G');
                            @endphp
                            <tr>
                                <td><span class="ref-code">BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                                <td>
                                    <div class="cell-name">
                                        <span class="avatar-initials">{{ $initials }}</span>
                                        <div class="cell-name-text">
                                            <p class="cell-name-primary" title="{{ $booking->guest_name }}">{{ $booking->guest_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</td>
                                <td class="font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</td>
                                <td><span class="cell-tag font-data">{{ $res->room_number }}</span></td>
                                <td class="text-faint text-[11px] font-bold uppercase tracking-wide">{{ $res->room->room_type ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.ui.section-card>
