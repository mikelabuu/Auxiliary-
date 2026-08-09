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
    protected $description = 'Mark paid bookings as no_show when their check-in day has passed (runs just after midnight Manila).';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = Carbon::now(config('hostel.timezone'));
        $this->info("🔍 Checking for no-show bookings at {$now->format('Y-m-d H:i:s')}...");

        $bookings = Booking::where('status', 'paid')
            ->whereDate('check_in', '<', $now->toDateString())
            ->get();

        if ($bookings->isEmpty()) {
            $this->info(' No eligible bookings found.');
            return;
        }

        DB::transaction(function () use ($bookings, $now) {
            foreach ($bookings as $booking) {
                $previousStatus = $booking->status;

                $booking->update(['status' => 'no_show']);

                NoShowLog::create([
                    'booking_id' => $booking->id,
                    'previous_status' => $previousStatus,
                    'new_status' => 'no_show',
                    'reason' => 'Guest did not check in by 11:00 PM.',
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
