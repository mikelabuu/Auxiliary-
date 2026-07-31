<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A staff-facing alert that arrives *with its contents* — the thing the topbar
 * bell shows and the console pops up, delivered the moment it happens.
 *
 * Why this event is different from the five beside it
 * ---------------------------------------------------
 * BookingChanged, RoomStatusChanged, DiscountChanged and PaymentProofSubmitted
 * all ride `new Channel(...)`, which is a **public** Reverb channel: the app
 * key is in the JS bundle, so anyone at all can subscribe. That is exactly why
 * those events are payload-free — they say "something moved", the console
 * re-queries behind its own session, and no guest data ever crosses the wire.
 *
 * A notification cannot work that way. "New booking #418 · Maria Santos" *is*
 * the payload; there is nothing left if you strip it. So this one rides a
 * PrivateChannel authorised in routes/channels.php against the `staff` guard,
 * which is the same audience that already reads these names in the bell
 * dropdown. The channel is the control, not the emptiness of the message.
 *
 * The `id` format is deliberately identical to the one the topbar's view
 * composer derives (see AppServiceProvider) — type:subject:timestamp — so an
 * alert that arrives live and the same alert re-rendered on the next page load
 * are one entry, and marking it read in either place marks it read in both.
 *
 * Every `url` here is a PATH, not an absolute URL (`route(..., absolute: false)`).
 * These links are only ever clicked from inside the console, so the browser's
 * own origin is the right one by definition — and an absolute URL would be
 * built from APP_URL whenever the emitting code has no request to infer a host
 * from (the artisan demo command, the scheduler, a queue worker). On a machine
 * where APP_URL does not match where the app is actually served — a dev box
 * running `artisan serve` on a high port, or an Apache whose default vhost
 * belongs to a different project — that produces a link to somewhere else
 * entirely, which 404s.
 *
 * ShouldBroadcastNow => sent synchronously, so no queue worker is required.
 */
class StaffNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public string $text,
        public string $url,
        public string $level = 'info',
        public ?int $at = null,
    ) {
        $this->at ??= now()->timestamp;
    }

    public static function newBooking(Booking $booking): self
    {
        return new self(
            id: 'booking:' . $booking->id . ':' . ($booking->created_at?->timestamp ?? 0),
            type: 'booking',
            title: 'New booking',
            text: '#' . $booking->id . ' · ' . $booking->guest_name
                . ' (' . $booking->check_in->format('M d') . ' – ' . $booking->check_out->format('M d') . ')',
            url: route('staff.bookings.index', ['search' => $booking->id], absolute: false),
            level: 'success',
            at: $booking->created_at?->timestamp,
        );
    }

    public static function proofSubmitted(Payment $payment): self
    {
        return new self(
            id: 'payment:' . $payment->id . ':' . ($payment->proof_submitted_at?->timestamp ?? 0),
            type: 'payment',
            title: 'Proof of payment',
            text: 'Booking #' . $payment->booking_id
                . ($payment->booking?->guest_name ? ' · ' . $payment->booking->guest_name : '')
                . ' awaits verification',
            url: route('staff.paymentverification.index', [], absolute: false),
            // A guest who has just transferred money is standing at the desk.
            level: 'warning',
            at: $payment->proof_submitted_at?->timestamp,
        );
    }

    public static function discountRequested(Discount $discount): self
    {
        $at = $discount->submitted_at ?? $discount->created_at;

        return new self(
            id: 'discount:' . $discount->id . ':' . ($at?->timestamp ?? 0),
            type: 'discount',
            title: 'Discount request',
            text: 'Booking #' . $discount->booking_id
                . ($discount->booking?->guest_name ? ' · ' . $discount->booking->guest_name : '')
                . ' awaits review',
            url: route('staff.discounts.show', $discount, absolute: false),
            level: 'info',
            at: $at?->timestamp,
        );
    }

    public static function roomMaintenance(Room $room): self
    {
        return new self(
            id: 'maintenance:' . $room->id . ':' . ($room->updated_at?->timestamp ?? 0),
            type: 'maintenance',
            title: 'Room out of service',
            text: 'Room ' . $room->room_number . ' moved to maintenance',
            url: route('staff.rooms', [], absolute: false),
            level: 'error',
            at: $room->updated_at?->timestamp,
        );
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('staff.alerts');
    }

    public function broadcastAs(): string
    {
        return 'StaffNotification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'text' => $this->text,
            'url' => $this->url,
            'level' => $this->level,
            'at' => $this->at,
        ];
    }
}
