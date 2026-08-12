<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ExpiryLog;
use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\RoomStatusChanged;
use App\Support\GuestNotice;
use App\Support\PaymentWindow;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpireBookingsCommand extends Command
{
    protected $signature = 'bookings:expire';
    protected $description = 'Expire bookings that passed their pending payment window';

    public function handle()
    {   
        $threshold = Carbon::now()->subMinutes(PaymentWindow::minutes());
        $liveFrom  = PaymentWindow::earliestLiveCheckInDate();

        // Two ways an unpaid hold dies, and this has to collect both or the
        // rooms stay blocked in the console while availability already treats
        // them as free (Booking::applyActiveHold reads the same two clocks).
        //
        //   · the payment window ran out
        //   · the guest's own arrival time came and went while still unpaid,
        //     which at a 24-hour window is the commoner of the two for
        //     anything booked for tonight or tomorrow
        $expiredBookings = Booking::where('status', 'pending_payment')
            ->where(function ($q) use ($threshold, $liveFrom) {
                $q->where(function ($q) use ($threshold) {
                    $q->whereNotNull('pending_payment_since')
                        ->where('pending_payment_since', '<=', $threshold);
                })->orWhereDate('check_in', '<', $liveFrom);
            })
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info(" No expired bookings found.");
            return;
        }

        DB::transaction(function () use ($expiredBookings, $liveFrom) {
            foreach ($expiredBookings as $booking) {
                $previousStatus = $booking->status;

                // Which clock ran out. Worth recording separately: "they never
                // paid in 24 hours" and "they were due at 2 PM today and still
                // had not paid" are the same status but different stories, and
                // the second is the one a guest will ring up about.
                $missedArrival = Carbon::parse($booking->check_in)->toDateString() < $liveFrom;

                $pendingPayment = Payment::where('booking_id', $booking->id)
                    ->where('status', 'pending')
                    ->first();

                if ($pendingPayment) {
                    $pendingPayment->update([
                        'status' => 'failed',
                    ]);
                }

                $booking->update(['status' => 'expired']);

                ExpiryLog::create([
                    'booking_id' => $booking->id,
                    'previous_status' => $previousStatus,
                    'new_status' => 'expired',
                    'reason' => $missedArrival
                        ? 'Booking was still unpaid when its check-in time arrived.'
                        : 'Booking did not complete payment before expiry window.',
                    'expired_at' => Carbon::now(config('hostel.timezone')),
                    'processed_by' => null,
                ]);
            }
        });

        // Expiry releases the booking's hold on its rooms (BLOCKING_STATUSES),
        // so both the booking panels and the room map need a push.
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        // Losing a reservation to the payment window is the change a guest is
        // least likely to be expecting, so tell whoever is sitting on the page.
        foreach ($expiredBookings as $booking) {
            if (BookingStatusChanged::shouldEmitFor($booking)) {
                Realtime::emit(new BookingStatusChanged($booking->id, 'expired'));
            }
        }

        // ...and tell the ones who are not sitting on the page, which is nearly
        // all of them. The broadcast above only reaches an open tab; before this
        // mail existed, everyone else learned that their rooms were gone by
        // coming back and finding the booking missing.
        //
        // Outside the transaction on purpose: SMTP is slow and can hang, and
        // holding a write transaction open across a network call to a mail host
        // would lock these rows for the duration.
        foreach ($expiredBookings as $booking) {
            GuestNotice::bookingExpired($booking);
        }

        $this->info(" Marked {$expiredBookings->count()} bookings as expired and logged them.");
    }
}
