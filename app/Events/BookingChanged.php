<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast whenever a booking changes in a way the Bookings Hub should reflect
 * (check-in, check-out, no-show, cancellation, new manual booking). Like
 * RoomStatusChanged this is a payload-free signal — the Livewire panels on the
 * Bookings Hub simply re-query themselves, so no guest data crosses the wire.
 *
 * ShouldBroadcastNow => sent synchronously, so no queue worker is required.
 */
class BookingChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastOn(): Channel
    {
        return new Channel('bookings');
    }

    public function broadcastAs(): string
    {
        return 'BookingChanged';
    }
}
