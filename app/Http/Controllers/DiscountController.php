<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Booking;
use App\Models\Discount;
use App\Models\DiscountFile;
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

        return view('discount.create', [
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

        if (!empty($discountFiles)) {
            foreach ($discountFiles as $reservationId => $files) {
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

            // Reset booking's discount column and status
            $booking->update([
                'discount' => 0, 
                'payable_amount' => $booking->total_price,
                'status' => 'pending_payment',
            ]);
        });

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Discount request cancelled. You may now proceed to payment.');
    }
}
