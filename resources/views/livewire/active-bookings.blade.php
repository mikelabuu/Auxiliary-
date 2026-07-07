<x-admin.section-card wire:poll.15s="loadActiveBookings" icon="log-in" title="Active Stays" subtitle="Guests currently checked in.">
    @if($activeBookings->isEmpty())
        <x-admin.empty-state icon="log-in" title="No active bookings at the moment." />
    @else
        <div class="-mx-6 -mb-6 border-t border-stone-100 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-stone-50/70 border-b border-stone-100">
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">ID</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Guest Name</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Check-in</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Check-out</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Room</th>
                        <th class="text-left font-bold text-[10px] text-stone-500 tracking-widest uppercase px-6 py-3">Room Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeBookings as $booking)
                        @foreach($booking->reservations as $res)
                            <tr class="border-b border-stone-100 hover:bg-clsu-50/40 transition-colors">
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">#{{ $booking->id }}</td>
                                <td class="px-6 py-3 text-stone-800 font-medium">{{ $booking->guest_name }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $booking->check_in->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $booking->check_out->format('M d, Y') }}</td>
                                <td class="px-6 py-3 text-stone-700 font-data tabnum">{{ $res->room_number }}</td>
                                <td class="px-6 py-3 text-stone-500 text-[11px] font-bold uppercase tracking-wide">{{ $res->room->room_type ?? '—' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-admin.section-card>
