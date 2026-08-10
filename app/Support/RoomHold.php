<?php

namespace App\Support;

use App\Models\Booking;

/**
 * A room can be blocked for two very different reasons, and the console used
 * to render them identically.
 *
 * A *settled* hold is money in the bank: the guest has paid, or is already in
 * the room. A *pending* hold is a claim — the booking exists and the room is
 * correctly unavailable to anyone else, but nobody has been paid yet and the
 * reservation may still expire or be cancelled. Front desk needs to tell those
 * apart at a glance, and a guest looking at the room picker deserves a truer
 * word than "booked".
 *
 * `rooms.status` deliberately stays a *housekeeping* column (available /
 * cleaning / maintenance / occupied) that staff own. This hold state is
 * derived from bookings on read, so it can never go stale when a booking
 * expires and it can never be clobbered by someone setting a room to cleaning.
 */
class RoomHold
{
    /**
     * Booking statuses that hold a room without the money having landed.
     * A subset of Booking::BLOCKING_STATUSES — the rest are settled.
     */
    public const PENDING_STATUSES = ['pending_payment', 'pending_discount'];

    /**
     * What a room board shows on the tile. The first three are housekeeping
     * facts staff own; 'reserved' and 'pending' are derived from bookings and
     * exist only here — `rooms.status` never holds either.
     */
    public const DISPLAY_STATUSES = [
        'available',
        'occupied',
        'reserved',
        'pending',
        'cleaning',
        'maintenance',
    ];

    public static function isPending(?string $bookingStatus): bool
    {
        return in_array($bookingStatus, self::PENDING_STATUSES, true);
    }

    /**
     * Resolve the housekeeping column and a booking's hold into the one state
     * a board should show.
     *
     * Every room board used to render `rooms.status` raw, which meant a room
     * a guest had paid for and was sleeping in tonight still read AVAILABLE
     * until someone pressed check-in. The hold is the thing that decides
     * whether the desk can sell the room, so it has to reach the badge.
     *
     * Housekeeping wins where it is a harder fact than the booking: a room
     * under maintenance cannot be given to the guest who reserved it, and
     * `occupied` already means a guest was checked in.
     *
     * @param  string|null  $housekeeping  the `rooms.status` column
     * @param  string|null  $holdStatus    booking status of the hold the board
     *                                     is reporting on, or null for none.
     *                                     Which hold that is belongs to the
     *                                     caller: the admin room board answers
     *                                     "can I give this room out tonight",
     *                                     so it passes the stay covering today;
     *                                     the dashboard map also counts the
     *                                     next arrival.
     */
    public static function displayStatus(?string $housekeeping, ?string $holdStatus): string
    {
        if (in_array($housekeeping, ['maintenance', 'cleaning', 'occupied'], true)) {
            return $housekeeping;
        }

        if ($holdStatus === null) {
            return $housekeeping ?: 'available';
        }

        return self::isPending($holdStatus) ? 'pending' : 'reserved';
    }

    /**
     * The one-line stay descriptor shown on a room card and kept in sync by
     * the status feed. Returns the kind (a JS hook), an icon, a colour class,
     * the visible label, and the guest the hold belongs to.
     *
     * `guest` used to exist only inside `title` — a hover tooltip, which is
     * unreachable on the touch devices the desk actually carries around, and
     * invisible to a screen reader. Whoever is holding a room is the first
     * thing someone asks when they look at this board, so it is now its own
     * field and the card renders it as text.
     *
     * @param  array|null  $current  ['guest','until','status'] — a stay spanning today
     * @param  array|null  $next     ['guest','from','status']  — the soonest arrival
     */
    public static function stayLine(?array $current, ?array $next): array
    {
        if ($current) {
            if (self::isPending($current['status'] ?? null)) {
                return [
                    'kind'  => 'current-pending',
                    'icon'  => 'clock',
                    'class' => 'font-semibold text-palay-700',
                    'label' => 'Reserved · unpaid · until ' . $current['until'],
                    'guest' => $current['guest'],
                    'title' => $current['guest'] . ' · unpaid · until ' . $current['until'],
                ];
            }

            return [
                'kind'  => 'current',
                'icon'  => 'clock',
                'class' => 'font-semibold text-clsu-700',
                'label' => 'In use · until ' . $current['until'],
                'guest' => $current['guest'],
                'title' => $current['guest'] . ' · until ' . $current['until'],
            ];
        }

        if ($next) {
            if (self::isPending($next['status'] ?? null)) {
                return [
                    'kind'  => 'next-pending',
                    'icon'  => 'arrival',
                    'class' => 'font-semibold text-palay-700',
                    'label' => 'Reserved · ' . $next['from'] . ' · unpaid',
                    'guest' => $next['guest'],
                    'title' => $next['guest'] . ' arrives ' . $next['from'] . ' — payment not yet verified',
                ];
            }

            return [
                'kind'  => 'next',
                'icon'  => 'arrival',
                'class' => 'font-semibold text-palay-700',
                'label' => 'Next stay · ' . $next['from'],
                'guest' => $next['guest'],
                'title' => $next['guest'] . ' arrives ' . $next['from'],
            ];
        }

        return [
            'kind'  => 'none',
            'icon'  => 'check',
            'class' => 'font-medium text-faint',
            'label' => 'No upcoming stays',
            'guest' => null,
            'title' => '',
        ];
    }

    /**
     * What the guest-facing room picker should call a tile. `reserved` is
     * still unselectable — it simply stops calling an unpaid hold "booked".
     */
    public static function pickerStatus(?string $bookingStatus): string
    {
        return self::isPending($bookingStatus) ? 'reserved' : 'booked';
    }

    /** Every status that takes a room off the market, settled or not. */
    public static function blockingStatuses(): array
    {
        return Booking::BLOCKING_STATUSES;
    }
}
