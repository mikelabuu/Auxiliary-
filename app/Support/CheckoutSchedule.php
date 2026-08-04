<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * The check-out clock, in one place.
 *
 * Three things need to agree about when check-out happens and when the desk is
 * warned about it, and they are in three different layers:
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
 * So the derivation lives here once and the three callers ask.
 */
class CheckoutSchedule
{
    public static function timezone(): string
    {
        return config('hostel.timezone');
    }

    public static function enabled(): bool
    {
        return (bool) config('hostel.checkout_reminder.enabled', true);
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

    /** Human form for alert copy and console output, e.g. "2:00 PM". */
    public static function deadlineLabel(): string
    {
        return static::deadlineOn()->format('g:i A');
    }
}
