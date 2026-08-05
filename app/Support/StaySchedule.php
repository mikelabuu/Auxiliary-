<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The clock a stay runs on: when guests may arrive, when they must leave, and
 * when the desk is warned about it.
 *
 * Was CheckoutSchedule. Renamed when check-in moved in — the class covers both
 * ends of a stay now, and a name that says "checkout" would have sent the next
 * person looking for a second class that should not exist.
 *
 * Check-out has real derivation to own. Three layers need the same answer and
 * must agree:
 *
 *   · App\Console\Kernel                 — what time the reminder is scheduled
 *   · App\Console\Commands\AutoCheckOut… — the deadline it refuses to run before
 *   · App\Events\StaffNotification       — the alert's `at`, which decides
 *                                          whether a morning "mark all read"
 *                                          has already swallowed it
 *
 * Each used to compute its own answer from config. That works exactly as long
 * as all three keep computing it the same way, and the failure when they stop
 * is quiet: the alert still fires, still looks right in the log, and is simply
 * greyed out before anybody sees it.
 *
 * Check-in needs none of that. It is a single configured time that ten pieces
 * of guest-facing copy state out loud, so what it needs is one formatting rule
 * rather than ten literals.
 */
class StaySchedule
{
    public static function timezone(): string
    {
        return config('hostel.timezone');
    }

    // ── Check-in ─────────────────────────────────────────────────────────────

    /** Display form for guest-facing copy, e.g. "2:00 PM". */
    public static function checkinLabel(): string
    {
        return static::parseTime(config('hostel.checkin_time', '14:00'))->format('g:i A');
    }

    /**
     * The booking form's "estimated arrival" options: hourly from check-in
     * until 11 PM, then a catch-all for later.
     *
     * Generated rather than listed so the first slot is always check-in.
     * Written out, the list began at 2:00 PM regardless — move check-in later
     * and the form would have gone on offering arrival slots before the desk
     * would admit anyone.
     *
     * `arrival_time` is validated as `date_format:H:i` and not against this
     * list, so generating it cannot put the form out of step with the
     * controller.
     *
     * @return array<string, string>  'HH:MM' => 'g:i A'
     */
    public static function arrivalSlots(): array
    {
        $slot = static::parseTime(config('hostel.checkin_time', '14:00'));
        $slots = [];

        while ($slot->hour <= 23) {
            $slots[$slot->format('H:i')] = $slot->format('g:i A');

            if ($slot->hour === 23) {
                break;
            }

            $slot = $slot->addHour();
        }

        $slots['00:00'] = 'After midnight';

        return $slots;
    }

    // ── Check-out ────────────────────────────────────────────────────────────

    /** Display form for staff copy and console output, e.g. "2:00 PM". */
    public static function checkoutLabel(): string
    {
        return static::deadlineOn()->format('g:i A');
    }

    /**
     * The check-out deadline on a given date (default: today), in the hostel's
     * timezone.
     */
    public static function deadlineOn(?string $date = null): CarbonInterface
    {
        $tz = static::timezone();
        $date ??= Carbon::today($tz)->toDateString();

        return Carbon::parse($date . ' ' . config('hostel.checkout_time', '14:00'), $tz);
    }

    // ── Check-out reminder ───────────────────────────────────────────────────

    public static function reminderEnabled(): bool
    {
        return (bool) config('hostel.checkout_reminder.enabled', true);
    }

    /**
     * When the desk is warned, on a given date: the deadline minus the lead.
     *
     * Clamped to the same day. A lead long enough to cross midnight would put
     * the reminder on the date *before* the stay ends, where the query that
     * finds "stays leaving today" would not match it — the reminder would
     * simply never appear, with nothing logged to say why.
     */
    public static function reminderOn(?string $date = null): CarbonInterface
    {
        $deadline = static::deadlineOn($date);
        $lead = max(0, (int) config('hostel.checkout_reminder.lead_hours', 2));

        $reminder = $deadline->copy()->subHours($lead);

        return $reminder->isSameDay($deadline)
            ? $reminder
            : $deadline->copy()->startOfDay();
    }

    /**
     * 'H:i' for Kernel's dailyAt(), which takes a clock time rather than a
     * moment. Derived from today so it tracks any change to either key.
     */
    public static function reminderTimeOfDay(): string
    {
        return static::reminderOn()->format('H:i');
    }

    /**
     * Parse an 'HH:MM' config value against today, in the hostel's timezone.
     * Kept private so a malformed key fails in one place rather than wherever
     * it happened to be read.
     */
    private static function parseTime(string $time): CarbonInterface
    {
        return Carbon::parse(
            Carbon::today(static::timezone())->toDateString() . ' ' . $time,
            static::timezone()
        );
    }
}
