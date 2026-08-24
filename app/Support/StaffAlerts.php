<?php

namespace App\Support;

use App\Events\StaffNotification;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\Payment;
use App\Models\Room;
use Illuminate\Support\Collection;

/**
 * What the desk needs to look at right now, as one list.
 *
 * Lifted out of the topbar view composer so it has two callers instead of one:
 * the composer still backfills the bell on page load, and
 * Staff\NotificationFeedController serves the same list as JSON so a console
 * left open can pick up new alerts without anyone reloading the page.
 *
 * These are derived on each request rather than stored, so "read" cannot live
 * in a database column. Every entry carries a stable `id` — type + subject +
 * the timestamp that made it appear — and the topbar remembers which ids have
 * been opened. The id must change when the underlying thing changes (a
 * re-uploaded proof, a room list that grew) so a genuinely new alert is never
 * pre-read, and must NOT change otherwise, or one alert splits into two rows
 * and one of them can never be marked read.
 *
 * The array shape is deliberately identical to
 * App\Events\StaffNotification::broadcastWith(): the dropdown renders one list
 * from this backfill, from the poller, and from anything arriving over Reverb,
 * and it can only treat them as one list if they are the same object. `at` is a
 * unix timestamp rather than a formatted string so the row's "2 minutes ago" is
 * computed in the browser and stays true on a console left open all shift.
 */
class StaffAlerts
{
    /**
     * Newest first, capped at 8 — the number the dropdown shows.
     */
    public static function current(): Collection
    {
        $notifications = collect();

        Discount::with('booking')
            ->where('status', 'pending')
            ->latest('submitted_at')
            ->take(5)
            ->get()
            ->each(function ($d) use ($notifications) {
                $time = $d->submitted_at ?? $d->created_at;

                $notifications->push([
                    'id'    => 'discount:' . $d->id . ':' . ($time?->timestamp ?? 0),
                    'type'  => 'discount',
                    'title' => 'Discount request',
                    'text'  => 'Booking #' . $d->booking_id
                        . ($d->booking?->guest_name ? ' · ' . $d->booking->guest_name : '')
                        . ' awaits review',
                    'url'   => route('staff.discounts.show', $d, absolute: false),
                    'level' => 'info',
                    'at'    => $time?->timestamp ?? 0,
                ]);
            });

        // Guests who have paid over GCash or a bank transfer and are
        // waiting on a human. The most time-critical item in the list.
        Payment::with('booking')
            ->whereNotNull('proof_path')
            ->awaitingVerification()
            ->latest('proof_submitted_at')
            ->take(5)
            ->get()
            ->each(function ($p) use ($notifications) {
                $notifications->push([
                    'id'    => 'payment:' . $p->id . ':' . ($p->proof_submitted_at?->timestamp ?? 0),
                    'type'  => 'payment',
                    'title' => 'Proof of payment',
                    'text'  => 'Booking #' . $p->booking_id
                        . ($p->booking?->guest_name ? ' · ' . $p->booking->guest_name : '')
                        . ' awaits verification',
                    'url'   => route('staff.paymentverification.index', [], absolute: false),
                    'level' => 'warning',
                    'at'    => ($p->proof_submitted_at ?? $p->created_at)?->timestamp ?? 0,
                ]);
            });

        // Paid guests asking to move a stay. Built through the event
        // factory rather than by hand, for the reason spelled out at the
        // checkout-due block below: an id assembled even slightly
        // differently here splits one alert into two rows, one of which
        // can never be marked read.
        \App\Models\RescheduleRequest::with('booking')
            ->pending()
            ->latest('submitted_at')
            ->take(5)
            ->get()
            ->each(function ($r) use ($notifications) {
                $notifications->push(StaffNotification::rescheduleRequested($r)->broadcastWith());
            });

        Booking::where('created_at', '>=', now()->subDays(2))
            ->latest()
            ->take(5)
            ->get()
            ->each(function ($b) use ($notifications) {
                $notifications->push([
                    'id'    => 'booking:' . $b->id . ':' . ($b->created_at?->timestamp ?? 0),
                    'type'  => 'booking',
                    'title' => 'New booking',
                    'text'  => '#' . $b->id . ' · ' . $b->guest_name
                        . ' (' . $b->check_in->format('M d') . ' – ' . $b->check_out->format('M d') . ')',
                    'url'   => route('staff.bookings.index', ['search' => $b->id], absolute: false),
                    'level' => 'success',
                    'at'    => $b->created_at?->timestamp ?? 0,
                ]);
            });

        // Stays leaving today that are still in house.
        //
        // Built by calling the event factory and taking its payload,
        // rather than assembling the array by hand the way the four blocks
        // above do. Those predate the event and have to be kept in step
        // with it by eye — an id assembled even slightly differently here
        // would silently split one alert into two rows, one of which can
        // never be marked read. Borrowing the factory makes that class of
        // drift impossible for this type.
        //
        // Present all day, not only after the reminder has fired: the bell
        // is a standing view of what needs doing, and the scheduled
        // command is what makes it *pop*. `at` is fixed to the reminder
        // time either way, so the row's age reads the same in both.
        Booking::with('reservations')
            ->where('status', Booking::STATUS_ACTIVE)
            ->where('check_out', \Carbon\Carbon::today(config('hostel.timezone'))->toDateString())
            ->orderBy('id')
            ->take(5)
            ->get()
            ->each(function ($b) use ($notifications) {
                $notifications->push(StaffNotification::checkoutDue($b)->broadcastWith());
            });

        $maintenance = Room::where('status', 'maintenance')
            ->latest('updated_at')
            ->get();

        if ($maintenance->isNotEmpty()) {
            $latest = $maintenance->first()->updated_at;

            $notifications->push([
                // Count is part of the id: an eighth room going down is
                // news even if the previous seven were already dismissed.
                'id'    => 'maintenance:' . $maintenance->count() . ':' . ($latest?->timestamp ?? 0),
                'type'  => 'maintenance',
                'title' => 'Rooms out of service',
                'text'  => $maintenance->count() . ' ' . str('room')->plural($maintenance->count())
                    . ' under maintenance (' . $maintenance->pluck('room_number')->take(4)->implode(', ')
                    . ($maintenance->count() > 4 ? ', …' : '') . ')',
                'url'   => route('staff.rooms', [], absolute: false),
                'level' => 'error',
                'at'    => $latest?->timestamp ?? 0,
            ]);
        }

        return $notifications->sortByDesc('at')->take(8)->values();
    }
}
