<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast whenever the payment verification queue moves: a guest uploads a
 * receipt, or staff clear or reject one.
 *
 * A guest who has just transferred money is standing at the desk waiting to be
 * let in, so this queue cannot wait on a poll interval. Payload-free like the
 * other staff-facing events — the list re-queries itself, so no receipt image
 * or guest detail crosses the wire.
 *
 * ShouldBroadcastNow => sent synchronously, so no queue worker is required.
 */
class PaymentProofSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastOn(): Channel
    {
        return new Channel('payment-verifications');
    }

    public function broadcastAs(): string
    {
        return 'PaymentProofSubmitted';
    }
}
