<?php

namespace App\Support;

use App\Models\Booking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * How long a booking may sit unpaid, and when that runs out.
 *
 * The number itself lives in config('bookings.expiry_minutes') and is read all
 * over: the hold guard in Booking::applyActiveHold, bookings:expire, the
 * confirmation mail, the countdown on the booking summary, the checkout copy,
 * the docs and the tests. That was fine while the window was 60 minutes,
 * because "60 minutes" is a sentence as well as a number.
 *
 * At 24 hours it stops being one. Every guest-facing string that interpolated
 * the raw integer now reads "1440 minutes", and the countdown that formatted
 * MM:SS starts printing "1439:59". The number and the way it is said to a guest
 * are two different things, so the saying belongs here rather than in each of
 * the templates that need it.
 *
 * Only bookings the guest can settle themselves are on this clock — see
 * Booking::SETTLED_BLOCKING_STATUSES for the holds that never lapse.
 */
class PaymentWindow
{
    /** The configured window, in minutes. Never read the config key directly. */
    public static function minutes(): int
    {
        return max(1, (int) config('bookings.expiry_minutes'));
    }

    /** The same window in seconds, for the countdown's progress bar. */
    public static function seconds(): int
    {
        return static::minutes() * 60;
    }

    /**
     * How the window is said out loud: "24 hours", "90 minutes", "1 hour".
     *
     * Whole hours are spoken as hours because that is how the policy is
     * written; anything else stays in minutes rather than inventing
     * "1 hour 30 minutes" for a value nobody has ever configured.
     */
    public static function label(): string
    {
        $minutes = static::minutes();

        if ($minutes % 60 !== 0) {
            return $minutes . ' ' . Str::plural('minute', $minutes);
        }

        $hours = intdiv($minutes, 60);

        return $hours . ' ' . Str::plural('hour', $hours);
    }

    /**
     * When this booking's hold lapses, or null when it is not on the clock.
     *
     * Whichever comes first: the window, or the moment the guest was due to
     * arrive. The second half is not a refinement — without it a 24-hour window
     * outlives the thing it is holding. A booking made at 10 AM for tonight
     * kept its rooms off sale until 10 AM *tomorrow*, straight through the
     * night it was booked for, and then released them for a night that was
     * already over. That never showed up at an hour-long window, because an
     * hour cannot reach past a check-in; a day can, and most of the time will.
     *
     * A booking awaiting a discount decision is waiting on staff, not on the
     * guest, so it has no deadline to state — promising it one would be a lie.
     */
    public static function deadlineFor(Booking $booking): ?CarbonInterface
    {
        if ($booking->status !== 'pending_payment' || ! $booking->pending_payment_since) {
            return null;
        }

        $window = Carbon::parse($booking->pending_payment_since)->addMinutes(static::minutes());
        $arrival = static::checkInMomentFor($booking);

        return $arrival->lt($window) ? $arrival : $window;
    }

    /**
     * The moment a booking's guests were due to start arriving: its check-in
     * date at the configured check-in time, in the hostel's timezone.
     *
     * Returned as a UTC-comparable Carbon because everything that compares
     * against it — `pending_payment_since`, `now()` — is stored in UTC.
     */
    public static function checkInMomentFor(Booking $booking): CarbonInterface
    {
        return Carbon::parse(
            Carbon::parse($booking->check_in)->toDateString() . ' ' . config('hostel.checkin_time', '14:00'),
            config('hostel.timezone')
        )->utc();
    }

    /**
     * How long this booking's hold actually runs, in seconds.
     *
     * The countdown bar fills against the *whole* window, so a hold cut short
     * by the check-in cap would start life already part-empty and drain at the
     * wrong rate. This is the length the bar should measure itself against.
     */
    public static function secondsFor(Booking $booking): int
    {
        $deadline = static::deadlineFor($booking);

        if (! $deadline) {
            return static::seconds();
        }

        $from = Carbon::parse($booking->pending_payment_since);

        return max(1, (int) $from->diffInSeconds($deadline, absolute: false));
    }

    /**
     * The earliest check-in date whose arrival moment has not already passed —
     * the SQL-side half of the cap above.
     *
     * A hold is only alive if `check_in >= ` this. Before check-in time, a stay
     * arriving today still counts; once check-in time has passed, today's
     * arrivals are gone and only tomorrow onwards is still holding.
     *
     * A date rather than an expression, because the alternative is a
     * LEAST(...)-style comparison of a DATE column against a datetime, and
     * MySQL and SQLite disagree about what that means. Returning a bare
     * `Y-m-d` keeps both sides of the comparison a date — the column side is
     * held there by Booking's setCheckInAttribute — which is what lets the
     * caller use a plain indexed `where` instead of wrapping the column in
     * `date()`.
     */
    public static function earliestLiveCheckInDate(): string
    {
        $tz = config('hostel.timezone');
        $now = Carbon::now($tz);
        $checkIn = Carbon::parse($now->toDateString() . ' ' . config('hostel.checkin_time', '14:00'), $tz);

        return $now->lt($checkIn)
            ? $now->toDateString()
            : $now->copy()->addDay()->toDateString();
    }

    /** The deadline in the hostel's own timezone, for anything a guest reads. */
    public static function localDeadlineFor(Booking $booking): ?CarbonInterface
    {
        return static::deadlineFor($booking)?->timezone(config('hostel.timezone'));
    }
}
