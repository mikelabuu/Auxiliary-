<?php

namespace App\Console\Commands;

use App\Events\StaffNotification;
use App\Models\Booking;
use App\Support\CheckoutSchedule;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Tells the desk which stays are leaving today, before the 2 PM deadline
 * rather than after it.
 *
 * The gap this fills: every other signal about check-out is retrospective.
 * bookings:autocheckout closes the stay at 2 PM, and the dashboard's Needs
 * Attention panel plus the `overdue` KPI only light up the day *after* a
 * check-out was missed. Nothing spoke up while the guest was still in the room
 * and something could still be done about it — chasing them, briefing
 * housekeeping, or writing up an extension.
 *
 * Today only, deliberately. A stay whose date has already passed is overdue,
 * not "about to", and the overdue panel already owns it. Re-alerting those
 * every day is how a bell becomes background noise.
 *
 * The alerts ride the existing StaffNotification pipeline (private Reverb
 * channel + topbar bell), so this command adds no infrastructure of its own —
 * and, like every other emitter, it goes through Realtime::emit(), which means
 * a Reverb that happens to be down cannot make the scheduled run fail.
 */
class RemindCheckoutsDue extends Command
{
    protected $signature = 'bookings:checkout-reminder
                            {--dry : List the stays that would be alerted, without emitting anything}';

    protected $description = 'Alert the desk about stays due to check out today (runs ahead of the configured check-out time).';

    public function handle(): int
    {
        if (! CheckoutSchedule::enabled()) {
            $this->info('Checkout reminders are disabled (hostel.checkout_reminder.enabled).');

            return self::SUCCESS;
        }

        $today = Carbon::today(CheckoutSchedule::timezone())->toDateString();

        $due = Booking::with('reservations')
            ->where('status', 'active')
            ->whereDate('check_out', $today)
            ->orderBy('id')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No stays are due to check out today.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry');

        $this->info(($dry ? 'Would alert ' : 'Alerting the desk about ')
            . $due->count() . ' ' . str('stay')->plural($due->count()) . ' leaving today:');

        foreach ($due as $booking) {
            $rooms = $booking->reservations->pluck('room_number')->filter()->unique()->implode(', ');

            $this->line('  #' . $booking->id . '  ' . $booking->guest_name
                . ($rooms ? '  (room ' . $rooms . ')' : '  (no room assigned)'));

            if (! $dry) {
                Realtime::emit(StaffNotification::checkoutDue($booking));
            }
        }

        if ($dry) {
            $this->comment('Dry run — nothing was sent.');
        }

        return self::SUCCESS;
    }
}
