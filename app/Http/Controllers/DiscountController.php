<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\BookingChanged;
use App\Events\DiscountChanged;
use App\Events\StaffNotification;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\DiscountFile;
use App\Support\Realtime;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{
    /**
     * Show the discount request form (after booking)
     */
    public function create(Booking $booking)
    {   
        $user = Auth::user();
        $username = $user->username;
        // Ensure the logged-in user owns this booking
        if (!$user || $booking->user_id !== $user->id) {
            abort(403);
        }

        // Check if discount request already exists
        if ($booking->discount()->exists()) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'You have already submitted a discount request.');
        }

        return view('public.discount.create', [
            'booking' => $booking,
            'username' => $username,
        ]);
    }

    public function store(Request $request, Booking $booking)
    {
        $user = Auth::user();
        if (!$user || $booking->user_id !== $user->id) {
            abort(403);
        }

        // Prevent duplicate requests
        if ($booking->discount()->exists()) {
            return redirect()->route('booking.show', $booking->id)
                ->with('info', 'Discount request already submitted.');
        }

        $numSeniors = $booking->num_seniors;
        if ($numSeniors <= 0) {
            return redirect()->back()->with('error', 'This booking has no seniors/PWDs, discount request is not allowed.');
        }

        // Validate uploaded files
        $request->validate([
            'discount_files' => ['required', 'array', 'min:1'],
            'discount_files.*.*' => "required|image|mimes:jpeg,png,jpg|max:2048",
        ]);

        // Create discount record
        $discount = $booking->discount()->create([
            'status'       => 'pending',
            'amount'       => 0,
            'submitted_at' => now(),
        ]);

        // Save uploaded files
        $discountFiles = $request->file('discount_files');

        // The array KEY carries the reservation id and is entirely
        // attacker-controlled — it was previously written straight to the
        // column, so a guest could file their IDs against someone else's
        // reservation. Only ids belonging to this booking are accepted.
        $ownReservationIds = $booking->reservations()->pluck('id')->all();

        if (!empty($discountFiles)) {
            foreach ($discountFiles as $reservationId => $files) {
                if (!in_array((int) $reservationId, $ownReservationIds, true)) {
                    continue;
                }

                foreach ($files as $file) {
                    if (!$file->isValid()) continue;

                    $path = $file->store('discount_temp');

                    $discount->files()->create([
                        'reservation_id' => $reservationId,
                        'file_path'      => $path,
                        'uploaded_at'    => now(),
                        'status'         => 'pending',
                    ]);
                }
            }
        }
        else{
            return redirect()->route('settings.bookings')
            ->with('success');
        }
        
        // The staff queue is worked live — a guest who has just uploaded IDs is
        // waiting on a decision — but it only refreshed on a 60s poll.
        Realtime::emit(new DiscountChanged());
        Realtime::emit(StaffNotification::discountRequested($discount->load('booking')));

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Discount request submitted successfully! Staff will review it.');
    }

   public function cancel(Booking $booking)
    {
        $user = Auth::user();

        // Ensure the logged-in user owns this booking
        if (!$user || $booking->user_id !== $user->id) {
            abort(403);
        }

        // Get the Discount model related to this booking
        $discount = $booking->discount()->with('files')->first();

        // If no discount request exists
        if (!$discount) {
            return redirect()->route('booking.show', $booking->id)
                ->with('error', 'No discount request found.');
        }

        if ($discount->status !== 'pending') {
            return redirect()->route('booking.show', $booking->id)
                ->with('error', 'You cannot cancel this discount request as it has already been reviewed.');
        }


        // Delete discount and related files in a transaction
        DB::transaction(function () use ($discount, $booking) {
            foreach ($discount->files as $file) {
                Storage::delete($file->file_path);
                $file->delete();
            }

            $discount->delete();

            // Reset booking's discount column and status.
            //
            // wants_discount goes with it. Left true, the guest would be told
            // here that they may now proceed to payment and then be turned
            // away by PaymentController, which sends every discounted booking
            // to the front desk — a dead end nothing on the page explains.
            // Withdrawing the request means they are paying the ordinary rate.
            $booking->update([
                'discount' => 0,
                'payable_amount' => $booking->total_price,
                'wants_discount' => false,
                'status' => 'pending_payment',
            ]);
        });

        // Withdrawn requests must leave the staff queue as promptly as they
        // entered it, and the booking is back to pending_payment.
        Realtime::emit(new DiscountChanged());
        Realtime::emit(new BookingChanged());

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Discount request cancelled. You may now proceed to payment.');
    }
}
