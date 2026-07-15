<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Checkout;
use App\Events\BookingChanged;
use App\Events\RoomStatusChanged;
use App\Support\Realtime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AutoCheckOutBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:autocheckout {--force : Process regardless of the 2 PM time guard}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically check out active bookings past their checkout date (runs after 2 PM Manila).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Manila');
        $targetTime = Carbon::today('Asia/Manila')->setTime(14, 0, 0);

        // Checkout time is 2 PM; scheduled runs before that just skip.
        if (!$this->option('force') && $now->lessThan($targetTime)) {
            $this->info("It's not yet 2:00 PM in Manila. Skipping auto-checkout.");
            return;
        }

        // <= today (not = today) so a day the scheduler was down doesn't leave
        // bookings stranded in 'active' forever.
        $bookings = Booking::with('reservations.room')
            ->where('status', 'active')
            ->whereDate('check_out', '<=', $now->toDateString())
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings eligible for auto-checkout.');
            return;
        }

        DB::transaction(function () use ($bookings) {
            foreach ($bookings as $booking) {
                $booking->update(['status' => 'completed']);

                // The stay is over, so the rooms go back on the board. Without
                // this, rooms stayed 'occupied' forever with no booking behind
                // them. Guard: when catching up on a backlog, the room may
                // already host a NEWER active stay — leave those alone.
                foreach ($booking->reservations as $reservation) {
                    $heldByAnother = \App\Models\Reservation::where('room_number', $reservation->room_number)
                        ->where('booking_id', '!=', $booking->id)
                        ->whereHas('booking', fn ($q) => $q->where('status', 'active'))
                        ->exists();

                    if (!$heldByAnother) {
                        $reservation->room?->update(['status' => 'available']);
                    }
                }

                Checkout::create([
                    'booking_id'     => $booking->id,
                    'checked_out_at' => Carbon::now('Asia/Manila'),
                    'method'         => 'auto',
                    'processed_by'   => null,
                ]);

                $this->info("Booking #{$booking->id} auto-checked out.");
            }
        });

        // One push for the batch; dashboards patch themselves.
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        $this->info('Auto-checkout process finished. Total processed: ' . $bookings->count());
    }
}
