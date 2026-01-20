<?php

namespace App\Http\Controllers\Staff\frontdesk;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\AuditLogger;

class WalkInBookingController extends Controller
{
    /**
     * Show walk-in booking form
     */
    public function create()
    {
        return view('staff.frontdesk.create');
    }

    /**
     * Store walk-in booking
     */
    public function store(Request $request)
    {
        $request->validate([
            'guest_name'      => 'required|string|max:255',
            'guest_address'   => 'required|string|max:255',
            'guest_phone'     => 'required|string|max:20',
            'check_in'        => 'required|date|after_or_equal:today',
            'check_out'       => 'required|date|after:check_in',
            'expected_guests' => 'required|integer|min:1',
            'reservations'    => 'required|array|min:1',
            'reservations.*.room_type'   => 'required|string',
            'reservations.*.room_number' => 'required|string',
            'reservations.*.price_per_night' => 'required|numeric|min:0',
            'reservations.*.num_guests'  => 'required|integer|min:1',
            'reservations.*.num_seniors' => 'nullable|integer|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $cin  = Carbon::parse($request->check_in);
        $cout = Carbon::parse($request->check_out);
        $days = max(1, $cin->diffInDays($cout));

        $allRoomNumbers = [];
        $totalPrice = 0;
        $totalSeniors = 0;
        $totalGuestsAssigned = 0;

        $capacityMap = [
            'double'     => 2,
            'triple'     => 3,
            'quadruple'  => 4,
            'deluxe'     => 2,
            'dormitory1' => 5,
            'dormitory2' => 6,
        ];

        foreach ($request->reservations as $block) {
            $roomNumbersArray = array_map('trim', explode(',', $block['room_number']));
            $roomType = $block['room_type'];
            $pricePerNight = (float) $block['price_per_night'];
            $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);
            $numGuestsBlock = (int) $block['num_guests'];

            // validate rooms exist
            $rooms = Room::whereIn('room_number', $roomNumbersArray)->get();
            if ($rooms->count() !== count($roomNumbersArray)) {
                return back()->withErrors(['reservations' => 'Some selected rooms are invalid.'])->withInput();
            }

            $blockCapacity = ($capacityMap[$roomType] ?? 1) * count($roomNumbersArray);

            if ($numSeniorsBlock > $blockCapacity || $numGuestsBlock > $blockCapacity) {
                return back()->withErrors(['reservations' => 'Guests or seniors exceed room capacity.'])->withInput();
            }

            $totalGuestsAssigned += $numGuestsBlock;
            $totalSeniors += $numSeniorsBlock;
            $totalPrice += $pricePerNight * count($roomNumbersArray) * $days;
            $allRoomNumbers = array_merge($allRoomNumbers, $roomNumbersArray);
        }

        if ($totalGuestsAssigned !== (int)$request->expected_guests) {
            return back()->withErrors(['expected_guests' => 'Total assigned guests must equal expected guests.'])->withInput();
        }

        if (count($allRoomNumbers) !== count(array_unique($allRoomNumbers))) {
            return back()->withErrors(['reservations' => 'Duplicate room numbers detected.'])->withInput();
        }

        // simulate payment success (true = paid, false = fail)
        $paymentSuccess = $request->input('simulate_payment_success', true);
        $status = $paymentSuccess ? 'paid' : 'pending_payment';

        DB::beginTransaction();
        try {
            // create booking
            $booking = Booking::create([
                'user_id'         => null, // walk-in guest has no user account
                'room_numbers'    => implode(',', $allRoomNumbers),
                'expected_guests' => $request->expected_guests,
                'guest_name'      => $request->guest_name,
                'guest_address'   => $request->guest_address,
                'guest_phone'     => $request->guest_phone,
                'check_in'        => $request->check_in,
                'check_out'       => $request->check_out,
                'discount'        => $request->input('discount_amount', 0),
                'total_price'     => $totalPrice,
                'num_seniors'     => $totalSeniors,
                'wants_discount'  => $totalSeniors > 0,
                'status'          => $status,
                'payable_amount'  => $totalPrice - ($request->input('discount_amount', 0)),
            ]);

            // create reservations
            foreach ($request->reservations as $block) {
                $roomNumbersArray = array_map('trim', explode(',', $block['room_number']));
                $roomType = $block['room_type'];
                $pricePerNight = (float) $block['price_per_night'];
                $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);
                $numGuestsBlock = (int) $block['num_guests'];
                $beds = $capacityMap[$roomType] ?? 1;

                foreach ($roomNumbersArray as $roomNumber) {
                    Reservation::create([
                        'booking_id'  => $booking->id,
                        'room_number' => $roomNumber,
                        'room_type'   => $roomType,
                        'capacity'    => $beds,
                        'price'       => $pricePerNight,
                        'num_seniors' => min($numSeniorsBlock, $beds),
                        'num_guests'  => $numGuestsBlock,
                    ]);
                    $numSeniorsBlock -= $beds;
                    if ($numSeniorsBlock < 0) $numSeniorsBlock = 0;
                }
            }

            // attach rooms
            $roomIds = Room::whereIn('room_number', $allRoomNumbers)->pluck('id')->toArray();
            if (!empty($roomIds)) {
                $booking->rooms()->attach($roomIds);
            }

            DB::commit();

            AuditLogger::log(
                'walkin_booking_created',
                $booking,
                null,
                ['status' => $status],
                "Front desk staff " . Auth::user()->name . " created walk-in booking #{$booking->id} (Status: {$status})"
            );

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Walk-in booking failed: '.$e->getMessage());
            return back()->with('error', 'Failed to create booking: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('frontdesk.walkin.show', $booking->id)
            ->with('success', "Walk-in booking created successfully. Status: {$status}");
    }

    /**
     * Show a walk-in booking details
     */
    public function show(Booking $booking)
    {
        return view('staff.frontdesk.show', compact('booking'));
    }

    /**
     * Apply manual discount (optional)
     */
    public function applyDiscount(Request $request, Booking $booking)
    {
        $request->validate([
            'discount_amount' => 'required|numeric|min:0|max:' . $booking->total_price,
        ]);

        $booking->update([
            'discount' => $request->discount_amount,
            'payable_amount' => $booking->total_price - $request->discount_amount,
        ]);

        AuditLogger::log(
            'walkin_discount_applied',
            $booking,
            null,
            ['discount' => $request->discount_amount],
            "Front desk staff " . Auth::user()->name . " applied a discount of ₱{$request->discount_amount} to walk-in booking #{$booking->id}"
        );

        return back()->with('success', 'Discount applied successfully.');
    }
}
