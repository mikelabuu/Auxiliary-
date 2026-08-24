<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\NoShowLog;
use App\Events\BookingChanged;
use App\Events\RoomStatusChanged;
use App\Support\GuestNotice;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarkNoShowBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:mark-no-show';

    /**
     * The console command description.
     */
    protected $description = 'Forfeit paid bookings whose check-in day passed with nobody arriving (runs just after midnight Manila).';

    /**
     * The enforcement end of the house policy: a paid booking cannot be
     * cancelled, only moved, and only if the guest asks before check-in time on
     * their check-in (App\Models\RescheduleRequest::deadlineFor). This is
     * what happens when they do neither — the booking is forfeited with no
     * refund and the rooms go back on sale.
     *
     * Runs after midnight rather than at the check-in deadline, deliberately.
     * Check-in time is when the desk *starts* admitting guests, not when it
     * stops: someone arriving at 9 PM is late, not absent. The reschedule
     * deadline and the forfeiture are two different moments on the same day.
     */

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = Carbon::now(config('hostel.timezone'));
        $this->info("🔍 Checking for no-show bookings at {$now->format('Y-m-d H:i:s')}...");

        $bookings = Booking::where('status', Booking::STATUS_PAID)
            ->where('check_in', '<', $now->toDateString())
            // A guest who asked to move the stay in time has done the one thing
            // the policy asks of them. Forfeiting them because nobody at the
            // desk has answered yet would punish them for our own queue, and it
            // is the exact case the rule was written to protect. The hold stays
            // until a person decides — approve and the dates move, decline and
            // this sweep collects it the following night.
            ->whereDoesntHave('rescheduleRequests', fn ($q) => $q->pending())
            ->get();

        if ($bookings->isEmpty()) {
            $this->info(' No eligible bookings found.');
            return;
        }

        DB::transaction(function () use ($bookings, $now) {
            foreach ($bookings as $booking) {
                $previousStatus = $booking->status;

                $booking->update(['status' => Booking::STATUS_NO_SHOW]);

                NoShowLog::create([
                    'booking_id' => $booking->id,
                    'previous_status' => $previousStatus,
                    'new_status' => Booking::STATUS_NO_SHOW,
                    // Was "did not check in by 11:00 PM", a time this command
                    // never enforced — it runs after midnight and looks at the
                    // whole day. The reason now states what actually happened
                    // and what it costs, which is the same sentence the guest
                    // agreed to at checkout and reads again in the no-show mail.
                    'reason' => 'Guest did not check in on their arrival day and did not request a reschedule before check-in time. Booking forfeited, no refund.',
                    'marked_at' => $now,
                    'processed_by' => null,
                ]);
            }
        });

        // A no-show stops blocking its rooms, so panels and the map both care.
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        // Every booking in this batch is one the guest paid for, and this runs
        // at 00:05 — so without a mail the first they would know is a paid stay
        // marked against them, discovered at some unrelated later date. Sent
        // after the transaction commits so a slow mail host cannot hold the
        // rows locked.
        foreach ($bookings as $booking) {
            GuestNotice::bookingNoShow($booking);
        }

        $this->info(" Marked {$bookings->count()} bookings as no_show.");
    }
}
