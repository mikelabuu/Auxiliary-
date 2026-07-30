<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to a guest's *account* — not to one booking page — when something
 * happens to any booking they own.
 *
 * BookingStatusChanged already covers the single-booking view, but the guest
 * is far more likely to be sitting on My Bookings (a list) while they wait for
 * a receipt to be verified, and that page had no way to learn the outcome
 * short of a manual refresh.
 *
 * Private channel authorised in routes/channels.php against the account id.
 * The payload is deliberately thin — booking id, new status, and a short
 * message already safe to display. No amounts, names or contact details.
 *
 * ShouldBroadcastNow => sent synchronously, so no queue worker is required.
 */
class GuestBookingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $bookingId,
        public string $status,
        public string $message,
    ) {
    }

    /** Walk-ins have no account, so there is nobody to broadcast to. */
    public static function shouldEmitFor(?Booking $booking): bool
    {
        return $booking !== null && $booking->user_id !== null;
    }

    public static function paymentVerified(Booking $booking): self
    {
        return new self(
            $booking->user_id,
            $booking->id,
            (string) $booking->status,
            "Payment accepted — booking #{$booking->id} is confirmed. Your official receipt is on its way by email.",
        );
    }

    public static function paymentRejected(Booking $booking, string $reason): self
    {
        return new self(
            $booking->user_id,
            $booking->id,
            (string) $booking->status,
            "Your proof of payment for booking #{$booking->id} was not accepted: {$reason}",
        );
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->userId . '.bookings');
    }

    public function broadcastAs(): string
    {
        return 'GuestBookingUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'bookingId' => $this->bookingId,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
