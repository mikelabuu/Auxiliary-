<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast on any change that affects room availability (status flips,
 * check-ins/outs, new/edited/deleted rooms). Carries no payload on purpose —
 * it's a lightweight "something changed" signal; the Room Management page
 * responds by refetching the public /staff/rooms/status-feed endpoint and
 * patching only the cards that actually changed. That keeps guest data off
 * the wire and reuses the existing polling code path.
 *
 * ShouldBroadcastNow => sent synchronously, so no queue worker is required.
 */
class RoomStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastOn(): Channel
    {
        return new Channel('rooms');
    }

    public function broadcastAs(): string
    {
        return 'RoomStatusChanged';
    }
}
