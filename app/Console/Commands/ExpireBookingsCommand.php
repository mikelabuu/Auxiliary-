<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ExpiryLog;
use App\Events\BookingChanged;
use App\Events\BookingStatusChanged;
use App\Events\RoomStatusChanged;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpireBookingsCommand extends Command
{
    protected $signature = 'bookings:expire';
    protected $description = 'Expire bookings that passed their pending payment window';

    public function handle()
    {   
        $expiryMinutes = config('bookings.expiry_minutes');
        $threshold = Carbon::now()->subMinutes($expiryMinutes);

        // Find all pending_payment bookings older than expiry window
        $expiredBookings = Booking::where('status', 'pending_payment')
            ->whereNotNull('pending_payment_since')
            ->where('pending_payment_since', '<=', $threshold)
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info(" No expired bookings found.");
            return;
        }

        DB::transaction(function () use ($expiredBookings) {
            foreach ($expiredBookings as $booking) {
                $previousStatus = $booking->status;

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
                    'reason' => 'Booking did not complete payment before expiry window.',
                    'expired_at' => Carbon::now('Asia/Manila'),
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

        $this->info(" Marked {$expiredBookings->count()} bookings as expired and logged them.");
    }
}
