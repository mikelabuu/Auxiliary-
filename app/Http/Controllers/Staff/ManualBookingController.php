<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Services\AuditLogger;


class ManualBookingController extends Controller
{
    public function create()
    {
        $today = Carbon::today();

        $upcomingBookings = Booking::with('reservations')
        ->whereIn('status', ['paid', 'pending_payment'])
        ->whereDate('check_out', '>=', $today)
        ->orderBy('check_in')
        ->get();

        $availableRooms = Room::where('status', 'available')->get();
        $totalAvailableRooms = $availableRooms->count();

        return view('staff.manualbooking.index', compact(
            'availableRooms',
            'totalAvailableRooms',
            'upcomingBookings',
        ));
    }

    public function store(Request $request)
    {
        // Room capacity now lives on room_types (admin-editable via the Room
        // Types & Pricing UI). Fall back to these legacy defaults only for a
        // room_type slug that somehow has no matching room_types row.
        $roomCapacityMap = [
            'deluxe'     => 2,
            'double'     => 2,
            'triple'     => 3,
            'quadruple'  => 4,
            'dormitory1' => 5,
            'dormitory2' => 6,
        ];
        $typeCapacities = RoomType::pluck('capacity', 'slug');

        // Validate request
        $request->validate([
            'guest_name'      => ['required', 'string', 'max:255', new \App\Rules\PersonName],
            'guest_phone'     => 'required|string|max:20',
            'check_in'        => 'required|date|after_or_equal:today',
            'check_out'       => 'required|date|after:check_in',
            'expected_guests' => 'required|integer|min:1',
            'reservations'    => 'required|array|min:1',
            'reservations.*.room_type'     => 'required|string',
            'reservations.*.room_number'   => 'required|string',
            // posted for display continuity only — the nightly rate is
            // recomputed from the rooms table below, never trusted from JS
            'reservations.*.price_per_night' => 'nullable|numeric|min:0',
            'reservations.*.num_guests'    => 'required|integer|min:1',
            'reservations.*.num_seniors'   => 'nullable|integer|min:0',
            'discount_amount'               => 'nullable|numeric|min:0',
            'region_code' => 'required|string|max:255',
            'province_code' => 'nullable|string|max:255',
            'city_code' => 'required|string|max:255',
            'barangay_code' => 'required|string|max:255',
        ]);

        $brgyName = explode('|', $request->barangay_code)[1] ?? '';
        $cityName = explode('|', $request->city_code)[1] ?? '';
        $provName = $request->province_code ? (explode('|', $request->province_code)[1] ?? '') : '';

        $guest_address = collect([
            $brgyName,
            $cityName,
            $provName,
        ])
        ->filter()
        ->implode(', ');


        $checkIn  = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights   = max(1, $checkIn->diffInDays($checkOut));

        $allRoomNumbers = [];
        $totalGuests    = 0;
        $totalSeniors   = 0;
        $totalPrice     = 0;
        $roomPrices     = []; // room_number => authoritative nightly rate

        // Authoritative room records — price and type ownership come from
        // here, not from whatever the client posted.
        $dbRooms = Room::whereIn(
            'room_number',
            collect($request->reservations)->pluck('room_number')->all()
        )->get()->keyBy('room_number');

        // Validate reservations & calculate totals
        foreach ($request->reservations as $block) {
            $roomType   = strtolower($block['room_type']);
            $roomNumber = $block['room_number'];
            $numGuests  = (int) $block['num_guests'];
            $numSeniors = (int) ($block['num_seniors'] ?? 0);

            $room = $dbRooms->get($roomNumber);
            if (!$room || strtolower($room->room_type) !== $roomType) {
                return back()->withErrors([
                    'reservations' => "Room {$roomNumber} does not exist or is not a {$roomType} room."
                ])->withInput();
            }
            $price = (float) $room->price;
            $roomPrices[$roomNumber] = $price;

            // Check room capacity
            $capacity = $typeCapacities[$roomType] ?? $roomCapacityMap[$roomType] ?? 1;

            if ($numGuests > $capacity) {
                return back()->withErrors([
                    'reservations' => "Number of guests for room {$roomNumber} exceeds its capacity ({$capacity})."
                ])->withInput();
            }

            if ($numSeniors > $numGuests) {
                return back()->withErrors([
                    'reservations' => "Number of seniors for room {$roomNumber} cannot exceed total guests."
                ])->withInput();
            }

            $totalGuests  += $numGuests;
            $totalSeniors += $numSeniors;
            $totalPrice   += $price * $nights;

            // Check for duplicate rooms
            if (in_array($roomNumber, $allRoomNumbers)) {
                return back()->withErrors([
                    'reservations' => "Duplicate room number detected: {$roomNumber}."
                ])->withInput();
            }

            $allRoomNumbers[] = $roomNumber;
        }

        // Ensure total guests match expected
        if ($totalGuests !== (int) $request->expected_guests) {
            return back()->withErrors([
                'expected_guests' => "Total assigned guests ({$totalGuests}) must equal expected guests ({$request->expected_guests})."
            ])->withInput();
        }

        $allRoomNumbers = collect($request->reservations)
            ->pluck('room_number')
            ->toArray();

        $status = 'paid';

        DB::beginTransaction();
        try {
            // Lock the room rows for the life of this transaction. The checks
            // below used to run before the transaction opened, with no lock, so
            // two concurrent bookings for the same room could both pass them
            // and both insert. Locking serialises those requests: the second
            // waits, then re-reads and sees the first booking's reservations.
            $lockedRooms = Room::whereIn('room_number', $allRoomNumbers)
                ->lockForUpdate()
                ->get();

            // Check if any of these rooms are already booked for the selected range
            $overlappingRooms = Reservation::whereIn('room_number', $allRoomNumbers)
                ->whereHas('booking', function ($q) use ($request) {
                    $q->whereIn('status', Booking::BLOCKING_STATUSES)
                    ->where('check_in', '<', $request->check_out)
                    ->where('check_out', '>', $request->check_in);
                })
                ->pluck('room_number')
                ->toArray();

            if (!empty($overlappingRooms)) {
                DB::rollBack();

                return back()->withErrors([
                    'reservations' => 'The following rooms are already booked for the selected dates: ' . implode(', ', $overlappingRooms)
                ])->withInput();
            }

            // Authoritative status guard: reject rooms the front desk just closed
            // (maintenance/cleaning/occupied) even if this page still showed them as
            // open. The live board is a convenience; this is the guarantee.
            // Read from the locked rows, not a fresh query.
            $unavailableRooms = $lockedRooms
                ->filter(fn ($room) => $room->status !== 'available')
                ->pluck('room_number')
                ->toArray();

            if (!empty($unavailableRooms)) {
                DB::rollBack();

                return back()->withErrors([
                    'reservations' => 'The following rooms are no longer available: ' . implode(', ', $unavailableRooms)
                ])->withInput();
            }

            // Create main booking
            $booking = Booking::create([
                'user_id'         => null,
                'expected_guests' => $request->expected_guests,
                'guest_name'      => $request->guest_name,
                'guest_address'   => $guest_address,
                'guest_phone'     => $request->guest_phone,
                'check_in'        => $request->check_in,
                'check_out'       => $request->check_out,
                'discount'        => $request->input('discount_amount', 0),
                'total_price'     => $totalPrice,
                'num_seniors'     => $totalSeniors,
                'wants_discount'  => $request->input('discount_amount', 0) > 0,
                'status'          => $status,
                'payable_amount'  => $totalPrice - ($request->input('discount_amount', 0)),
                'payment_mode' => 'manual',
            ]);

            // Create reservations
            foreach ($request->reservations as $block) {
                $roomType   = strtolower($block['room_type']);
                $capacity   = $typeCapacities[$roomType] ?? $roomCapacityMap[$roomType] ?? 1;

                Reservation::create([
                    'booking_id'  => $booking->id,
                    'room_number' => $block['room_number'],
                    'room_type'   => $block['room_type'],
                    'capacity'    => $capacity,
                    'price'       => $roomPrices[$block['room_number']] ?? 0,
                    'num_guests'  => (int) $block['num_guests'],
                    'num_seniors' => (int) ($block['num_seniors'] ?? 0),
                ]);
            }

            //Payment

            $payment = Payment::where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->first();

            // If none found, create a new one
            if (!$payment) {
                $payment = Payment::create([
                    'booking_id'   => $booking->id,
                    'user_id'      => $booking->user_id,
                    'amount'       => $booking->payable_amount ?? $booking->total_price,
                    'status'       => 'success',
                    'payment_type' => 'manual',
                    'reference_no' => strtoupper(Str::random(10)),
                    'gateway'      => 'manual',
                ]);
            }

            // Reuse the rows already locked above rather than re-querying each
            // room; existence was proved during validation.
            $roomIds = $lockedRooms->pluck('id')->all();

            // Attach rooms to booking (pivot)
            if (!empty($roomIds)) {
                $booking->rooms()->attach($roomIds);
            }

            DB::commit();

            AuditLogger::log(
                'manual_booking_created',
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

        \App\Support\Realtime::emit(new \App\Events\BookingChanged());
        \App\Support\Realtime::emit(new \App\Events\RoomStatusChanged());

        return redirect()->route('staff.manualbooking.show', $booking->id)
                        ->with('success', "Walk-in booking created successfully. Status: {$status}");
    }

    public function show(Booking $booking)
    {
        $booking->load('reservations', 'payments');

        return view('staff.manualbooking.show', compact('booking'));
    }

    public function getAvailableRoomsAjax(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');

        // Get all rooms
        $rooms = Room::orderBy('room_number')->get();

        // Get reservations that overlap
        $reservations = Reservation::whereHas('booking', function($q) use ($checkIn, $checkOut) {
            $q->whereIn('status', Booking::BLOCKING_STATUSES)
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn);
        })->get();

        // Collect booked room numbers
        $bookedRoomNumbers = $reservations->pluck('room_number')->toArray();

        // Staff-managed capacities per type slug (legacy fallback for slugs
        // that have no room_types row)
        $legacyCapacities = [
            'deluxe' => 2, 'double' => 2, 'triple' => 3,
            'quadruple' => 4, 'dormitory1' => 5, 'dormitory2' => 6,
        ];
        $typeCapacities = RoomType::pluck('capacity', 'slug');
        $typeNames      = RoomType::pluck('name', 'slug');

        // Map rooms to availability
        $result = $rooms->map(function ($room) use ($bookedRoomNumbers, $typeCapacities, $typeNames, $legacyCapacities) {
            if (in_array($room->room_number, $bookedRoomNumbers)) {
                $status = 'booked';
            } elseif ($room->status !== 'available') {
                $status = $room->status;
            } else {
                $status = 'available';
            }

            $slug = strtolower($room->room_type);

            return [
                'id' => $room->room_number, // this will now be room_number for AJAX
                'room_number' => $room->room_number,
                'room_type' => $room->room_type,
                'type_name' => $typeNames[$slug] ?? ucfirst($room->room_type),
                'capacity' => (int) ($typeCapacities[$slug] ?? $legacyCapacities[$slug] ?? 1),
                'wing' => $room->wing,
                'price' => $room->price,
                'status' => $status,
            ];
        });

        return response()->json(['rooms' => $result]);
    }


}
