<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast whenever a Senior/PWD discount request moves: a guest submits or
 * cancels one, staff approve or reject a file or the request itself.
 *
 * The staff discount queue is worked in real time — a guest uploads IDs and
 * then waits for a decision — but it only ever refreshed on a 60s wire:poll,
 * so a request could sit invisible for a full minute. Payload-free like the
 * other two events: the list simply re-queries itself, so no ID document or
 * guest detail crosses the wire.
 *
 * ShouldBroadcastNow => sent synchronously, so no queue worker is required.
 */
class DiscountChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastOn(): Channel
    {
        return new Channel('discounts');
    }

    public function broadcastAs(): string
    {
        return 'DiscountChanged';
    }
}
