<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Models\Checkout;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoCheckoutBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:autocheckout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically checkout bookings whose checkout date is today at 2:30 PM.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now('Asia/Manila');
        $targetTime = Carbon::today('Asia/Manila')->setTime(14, 0, 0);

        // Safeguard: only run if current time >= 14:30 UTC
        if ($now->lessThan($targetTime)) {
            $this->info("It's not yet 2:30pm. Skipping auto-checkout.");
            return;
        }

        $bookings = Booking::where('status', 'active')
            ->whereDate('check_out', $now->toDateString())
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings eligible for auto-checkout.');
            return;
        }

        foreach ($bookings as $booking) {
            $booking->update(['status' => 'completed']);

            Checkout::create([
                'booking_id' => $booking->id,
                'auto'       => true,
                'created_at' => now('UTC'),
                'updated_at' => now('UTC'),
            ]);

            $this->info("Booking #{$booking->id} auto-checked out.");
        }

        $this->info("Auto-checkout process finished. Total processed: " . $bookings->count());
    }
}
