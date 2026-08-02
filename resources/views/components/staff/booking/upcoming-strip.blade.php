@props(['bookings'])

{{-- Light strip of reservations already on the books for the week ahead. --}}
@if($bookings->isNotEmpty())
    <div class="card animate-in" style="animation-delay:40ms">
        <div class="card-body" style="padding:14px 20px;">
            <div class="flex flex-wrap items-center gap-2">
                <span class="filter-row-label" style="margin-right:4px;">Upcoming this week</span>
                @foreach($bookings as $booking)
                    @foreach($booking->reservations as $res)
                        <span class="cell-tag" style="gap:6px;">
                            <span style="width:6px;height:6px;border-radius:50%;background:var(--color-g-500);"></span>
                            Room {{ $res->room_number }} · {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }}-{{ \Carbon\Carbon::parse($booking->check_out)->format('M d') }}
                        </span>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
@endif
