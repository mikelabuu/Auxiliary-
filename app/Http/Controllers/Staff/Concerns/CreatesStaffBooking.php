<?php

namespace App\Http\Controllers\Staff\Concerns;

use App\Events\BookingChanged;
use App\Events\RoomStatusChanged;
use App\Exceptions\RoomUnavailable;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Rules\PersonName;
use App\Services\AuditLogger;
use App\Support\Realtime;
use App\Support\RoomCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Staff-created bookings: the admin "Manual Booking" screen and the front
 * desk "Walk-In" screen are the same flow behind two doors, so they share one
 * implementation here.
 *
 * They used to be two copies. The copies drifted: the walk-in side kept
 * trusting the client-posted nightly rate long after the admin side started
 * recomputing it from the rooms table, and never grew the room-status guard.
 * Keeping one body means a fix can only ever land in both places at once.
 *
 * A using controller supplies only its own view/route names via
 * {@see bookingRoutes()}.
 */
trait CreatesStaffBooking
{
    /**
     * Fallback capacities for a room_type slug with no matching room_types row.
     * Capacity is normally admin-editable under Room Types & Pricing.
     */
    private const LEGACY_CAPACITIES = [
        'deluxe'     => 2,
        'double'     => 2,
        'triple'     => 3,
        'quadruple'  => 4,
        'dormitory1' => 5,
        'dormitory2' => 6,
    ];

    /**
     * The using controller's surface: which views it renders and where it
     * sends the staff member after a booking is created.
     *
     * @return array{form: string, show: string, redirect: string}
     */
    abstract protected function bookingRoutes(): array;

    public function create()
    {
        $upcomingBookings = Booking::with('reservations')
            ->whereIn('status', ['paid', 'pending_payment'])
            ->whereDate('check_out', '>=', Carbon::today())
            ->orderBy('check_in')
            ->get();

        $availableRooms = Room::where('status', 'available')->get();
        $totalAvailableRooms = $availableRooms->count();

        return view($this->bookingRoutes()['form'], compact(
            'availableRooms',
            'totalAvailableRooms',
            'upcomingBookings',
        ));
    }

    public function store(Request $request)
    {
        $typeCapacities = RoomType::pluck('capacity', 'slug');

        $request->validate([
            'guest_name'      => ['required', 'string', 'max:255', new PersonName],
            'guest_phone'     => 'required|string|max:20',
            'check_in'        => 'required|date|after_or_equal:today',
            'check_out'       => 'required|date|after:check_in',
            'expected_guests' => 'required|integer|min:1',
            'reservations'    => 'required|array|min:1',
            'reservations.*.room_type'   => 'required|string',
            'reservations.*.room_number' => 'required|string',
            // Posted for display continuity only — the nightly rate is
            // recomputed from the rooms table below, never trusted from JS.
            'reservations.*.price_per_night' => 'nullable|numeric|min:0',
            'reservations.*.num_guests'      => 'required|integer|min:1',
            'reservations.*.num_seniors'     => 'nullable|integer|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'region_code'     => 'required|string|max:255',
            'province_code'   => 'nullable|string|max:255',
            'city_code'       => 'required|string|max:255',
            'barangay_code'   => 'required|string|max:255',
        ]);

        $brgyName = explode('|', $request->barangay_code)[1] ?? '';
        $cityName = explode('|', $request->city_code)[1] ?? '';
        $provName = $request->province_code ? (explode('|', $request->province_code)[1] ?? '') : '';

        $guest_address = collect([$brgyName, $cityName, $provName])->filter()->implode(', ');

        $checkIn  = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights   = max(1, $checkIn->diffInDays($checkOut));

        $allRoomNumbers = [];
        $totalGuests    = 0;
        $totalSeniors   = 0;
        $totalPrice     = 0;
        $roomPrices     = []; // room_number => authoritative nightly rate

        // Authoritative room records — type ownership comes from here, not
        // from whatever the client posted.
        $dbRooms = Room::whereIn(
            'room_number',
            collect($request->reservations)->pluck('room_number')->all()
        )->get()->keyBy('room_number');

        // …and the nightly rate comes from the same catalog the public site
        // quotes from. This used to read `rooms.price`, while the guest path
        // read `room_types.base_price` via RoomCatalog — two columns nothing
        // kept in step, and only one of them editable from Room Types &
        // Pricing. They agree today; the first rate change would have made the
        // website and the front desk quote different money for the same night.
        $catalog = RoomCatalog::all();

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
            // Catalog first; `rooms.price` only as a fallback for a slug with
            // no room_types row behind it, which is the same case
            // LEGACY_CAPACITIES covers below.
            $price = isset($catalog[$roomType]['price'])
                ? (float) $catalog[$roomType]['price']
                : (float) $room->price;

            $roomPrices[$roomNumber] = $price;

            $capacity = $typeCapacities[$roomType] ?? self::LEGACY_CAPACITIES[$roomType] ?? 1;

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

            if (in_array($roomNumber, $allRoomNumbers)) {
                return back()->withErrors([
                    'reservations' => "Duplicate room number detected: {$roomNumber}."
                ])->withInput();
            }

            $allRoomNumbers[] = $roomNumber;
        }

        if ($totalGuests !== (int) $request->expected_guests) {
            return back()->withErrors([
                'expected_guests' => "Total assigned guests ({$totalGuests}) must equal expected guests ({$request->expected_guests})."
            ])->withInput();
        }

        $status = 'paid';
        $discount = (float) $request->input('discount_amount', 0);

        // A discount may not exceed what the stay actually costs. `min:0` was
        // the only bound, so a posted discount_amount larger than the total
        // drove payable_amount negative — a "paid" booking worth less than
        // nothing, with a successful Payment row to match.
        //
        // Rejected rather than clamped: a fat-fingered 99999 that silently
        // became "the whole stay is free" is exactly the mistake nobody would
        // catch until the books did. The ceiling is the server-computed total,
        // never anything the form claimed it was.
        if ($discount > $totalPrice) {
            return back()->withErrors([
                'discount_amount' => 'The discount (₱' . number_format($discount, 2) . ') cannot exceed the total for this stay (₱' . number_format($totalPrice, 2) . ').'
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Rooms already spoken for over the selected range, re-checked
            // INSIDE the transaction and behind a row lock.
            //
            // This pair of guards used to run before the transaction opened,
            // with no lock at all — so two staff assigning the same room at
            // once both passed, and a walk-in could not serialise against a
            // guest booking online (that path locks `rooms`, this one never
            // touched them). BookingController::store has locked correctly for
            // a while; this is the same guarantee, finally on the same terms.
            $lockedRooms = Room::whereIn('room_number', $allRoomNumbers)
                ->lockForUpdate()
                ->get();

            // BLOCKING_STATUSES is the shared list: a hand-rolled one here once
            // omitted pending_discount, letting a discount-pending room be
            // double-booked.
            $overlappingRooms = Reservation::whereIn('room_number', $allRoomNumbers)
                ->whereHas('booking', function ($q) use ($request) {
                    Booking::applyActiveHold($q, '')
                        ->where('check_in', '<', $request->check_out)
                        ->where('check_out', '>', $request->check_in);
                })
                ->pluck('room_number')
                ->unique()
                ->values()
                ->all();

            if (!empty($overlappingRooms)) {
                throw new RoomUnavailable(
                    'The following rooms are already booked for the selected dates: ' . implode(', ', $overlappingRooms)
                );
            }

            // Authoritative status guard: reject rooms the front desk just closed
            // (maintenance/cleaning/occupied) even if this page still showed them as
            // open. The live board is a convenience; this is the guarantee.
            $unavailableRooms = $lockedRooms
                ->filter(fn ($room) => $room->status !== 'available')
                ->pluck('room_number')
                ->all();

            if (!empty($unavailableRooms)) {
                throw new RoomUnavailable(
                    'The following rooms are no longer available: ' . implode(', ', $unavailableRooms)
                );
            }

            $booking = Booking::create([
                'user_id'         => null,
                'expected_guests' => $request->expected_guests,
                'guest_name'      => $request->guest_name,
                'guest_address'   => $guest_address,
                'guest_phone'     => $request->guest_phone,
                'check_in'        => $request->check_in,
                'check_out'       => $request->check_out,
                'discount'        => $discount,
                'total_price'     => $totalPrice,
                'num_seniors'     => $totalSeniors,
                'wants_discount'  => $discount > 0,
                'status'          => $status,
                'payable_amount'  => $totalPrice - $discount,
                'payment_mode'    => 'manual',
            ]);

            foreach ($request->reservations as $block) {
                $roomType = strtolower($block['room_type']);

                Reservation::create([
                    'booking_id'  => $booking->id,
                    'room_number' => $block['room_number'],
                    'room_type'   => $block['room_type'],
                    'capacity'    => $typeCapacities[$roomType] ?? self::LEGACY_CAPACITIES[$roomType] ?? 1,
                    'price'       => $roomPrices[$block['room_number']] ?? 0,
                    'num_guests'  => (int) $block['num_guests'],
                    'num_seniors' => (int) ($block['num_seniors'] ?? 0),
                ]);
            }

            $payment = Payment::where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->first();

            if (!$payment) {
                Payment::create([
                    'booking_id'   => $booking->id,
                    'user_id'      => $booking->user_id,
                    'amount'       => $booking->payable_amount ?? $booking->total_price,
                    'status'       => 'success',
                    'payment_type' => 'manual',
                    'reference_no' => strtoupper(Str::random(10)),
                    'gateway'      => 'manual',
                ]);
            }

            // Off the locked read, not the unlocked one taken before the
            // transaction opened — same rows the guards above just cleared.
            $roomIds = $lockedRooms->pluck('id')->all();

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
        } catch (RoomUnavailable $e) {
            // A room conflict is an ordinary outcome at a busy desk, not a
            // failure: someone else took the room first. It belongs on the
            // reservations field where the staff member is looking, in the
            // same shape the pre-transaction checks used to return.
            DB::rollBack();

            return back()->withErrors(['reservations' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Walk-in booking failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to create booking: ' . $e->getMessage())->withInput();
        }

        // Post-commit, and only on the success path — a rolled-back booking
        // must never announce itself. Without these, rooms taken at the desk
        // stay "available" on every other console until its next poll.
        Realtime::emit(new BookingChanged());
        Realtime::emit(new RoomStatusChanged());

        return redirect()->route($this->bookingRoutes()['redirect'], $booking->id)
            ->with('success', "Walk-in booking created successfully. Status: {$status}");
    }

    public function show(Booking $booking)
    {
        $booking->load('reservations', 'payments');

        return view($this->bookingRoutes()['show'], compact('booking'));
    }

    public function getAvailableRoomsAjax(Request $request)
    {
        $request->validate([
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');

        $rooms = Room::orderBy('room_number')->get();

        $bookedRoomNumbers = Reservation::whereHas('booking', function ($q) use ($checkIn, $checkOut) {
            Booking::applyActiveHold($q, '')
                ->where('check_in', '<', $checkOut)
                ->where('check_out', '>', $checkIn);
        })->pluck('room_number')->all();

        $typeCapacities = RoomType::pluck('capacity', 'slug');
        $typeNames      = RoomType::pluck('name', 'slug');

        $result = $rooms->map(function ($room) use ($bookedRoomNumbers, $typeCapacities, $typeNames) {
            if (in_array($room->room_number, $bookedRoomNumbers)) {
                $status = 'booked';
            } elseif ($room->status !== 'available') {
                $status = $room->status;
            } else {
                $status = 'available';
            }

            $slug = strtolower($room->room_type);

            return [
                'id'          => $room->room_number, // the board keys off room_number, not id
                'room_number' => $room->room_number,
                'room_type'   => $room->room_type,
                'type_name'   => $typeNames[$slug] ?? ucfirst($room->room_type),
                'capacity'    => (int) ($typeCapacities[$slug] ?? self::LEGACY_CAPACITIES[$slug] ?? 1),
                'wing'        => $room->wing,
                'price'       => $room->price,
                'status'      => $status,
            ];
        });

        return response()->json(['rooms' => $result]);
    }
}
