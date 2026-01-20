<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\NoShowLog;
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
    protected $description = 'Mark paid bookings as no_show if they did not check in by 11 PM PH time.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $now = Carbon::now('Asia/Manila');
        $cutoff = Carbon::today('Asia/Manila')->setTime(23, 0, 0); // 11:00 PM

        // Skip if not yet 11:00 PM
        if ($now->lessThan($cutoff)) {
            $this->info(" It’s not yet 11:00 PM — skipping check.");
            return;
        }

        $this->info("🔍 Checking for no-show bookings at {$now->format('Y-m-d H:i:s')}...");

        $bookings = Booking::where('status', 'paid')
            ->whereDate('check_in', $now->toDateString())
            ->where('status', '!=', 'active')
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

        $this->info(" Marked {$bookings->count()} bookings as no_show.");
    }
}
