<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the guest who owns a booking when its status changes under
 * them — a discount approved or rejected, a payment confirmed, a staff
 * cancellation.
 *
 * The other events in this app are payload-free signals on public channels
 * because every subscriber is a staff console. This one reaches a guest's
 * browser, so it rides a *private* channel authorised in routes/channels.php
 * against the booking's owner, and carries only the booking id plus the new
 * status — never a name, amount, or contact detail.
 *
 * Walk-in bookings have no user_id and therefore no channel to receive on;
 * emitting for one is harmless (nobody can subscribe) but pointless, so
 * callers should skip it via `shouldEmitFor()`.
 *
 * ShouldBroadcastNow => sent synchronously, so no queue worker is required.
 */
class BookingStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $bookingId,
        public string $status,
    ) {
    }

    /** Only account-holders can subscribe, so walk-ins have nobody to notify. */
    public static function shouldEmitFor(?Booking $booking): bool
    {
        return $booking !== null && $booking->user_id !== null;
    }

    public static function for(Booking $booking): self
    {
        return new self($booking->id, (string) $booking->status);
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('booking.' . $this->bookingId);
    }

    public function broadcastAs(): string
    {
        return 'BookingStatusChanged';
    }

    public function broadcastWith(): array
    {
        return ['status' => $this->status];
    }
}
