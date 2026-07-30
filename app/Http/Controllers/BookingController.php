<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Reservation;
use App\Support\RoomCatalog;
use App\Support\RoomHold;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class BookingController extends Controller
{
    // Show welcome landing page
    public function welcome()
    {
        $user = Auth::user();
        $username = $user ? $user->username : null;
        $roomTypes = RoomCatalog::all();
        $minPrice = RoomCatalog::minPrice();
        return view('public.home', compact('username', 'roomTypes', 'minPrice'));
    }

    // Show one room type's dedicated detail page (/rooms/{slug})
    public function showRoomType(string $slug)
    {
        $roomType = RoomCatalog::find($slug);

        abort_if($roomType === null, 404);

        $user = Auth::user();

        // Sibling types power the "other rooms" strip at the foot of the page.
        $otherTypes = collect(RoomCatalog::all())
            ->except($slug)
            ->values()
            ->all();

        return view('public.rooms.show', [
            'username'   => $user ? $user->username : null,
            'roomType'   => $roomType,
            'otherTypes' => $otherTypes,
        ]);
    }

    // Show checkout form
    public function showCheckoutForm(Request $request)
    {
        $user = Auth::user();
        $username = $user->username;
        $roomTypes = RoomCatalog::all();

        $roomTypeKey = $request->query('room_type');
        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');
        $guests = $request->query('guests', 1);

        $selectedRoomType = RoomCatalog::find($roomTypeKey);
        
        return view('public.booking.checkout', compact('username', 'roomTypes', 'selectedRoomType', 'checkIn', 'checkOut', 'guests'));
    }

    // Handle booking submission
    public function store(Request $request)
    {
        $request->validate([
            'first_name'      => ['required', 'string', 'max:255', new \App\Rules\PersonName],
            'middle_name'     => ['required', 'string', 'max:10', new \App\Rules\PersonName],
            'last_name'       => ['required', 'string', 'max:255', new \App\Rules\PersonName],
            'suffix'          => ['nullable', 'string', 'max:255', new \App\Rules\PersonName],
            'guest_phone'     => 'required|string|max:20',
            'check_in'        => 'required|date|after_or_equal:today',
            'check_out'       => 'required|date|after:check_in',
            'expected_guests' => 'required|integer|min:1',
            'reservations'    => 'required|array|min:1',
            'reservations.*.room_type'       => 'required|string',
            'reservations.*.room_number'     => 'required|string', // CSV per block
            // price/beds are posted for display continuity but the backend
            // recomputes both from RoomCatalog — never trust client pricing.
            'reservations.*.price_per_night' => 'nullable|numeric',
            'reservations.*.beds'            => 'nullable|integer',
            'reservations.*.num_seniors'     => 'nullable|integer|min:0',
            'reservations.*.meal' => 'nullable|array',
            'reservations.*.meal.*' => 'integer|min:0',
            'region_code' => 'required|string|max:255',
            'province_code' => 'nullable|string|max:255',
            'city_code' => 'required|string|max:255',
            'barangay_code' => 'required|string|max:255',
        ]);

        $user = Auth::user();
        
        $guestName = implode(', ', [
            $request->last_name,
            $request->first_name,
            $request->middle_name,
        ]);
        
        if ($request->filled('suffix')) {
            $guestName .= ' ' . $request->suffix;
        }

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


        $cin  = Carbon::parse($request->check_in);
        $cout = Carbon::parse($request->check_out);
        $days = max(1, $cin->diffInDays($cout));

        $allRoomNumbers = [];
        $totalPrice = 0;
        $totalCapacity = 0;
        $totalSeniors = 0;
        $totalGuestsAssigned = 0;
        $cdate = Carbon::parse($request->check_in, 'Asia/Manila');
        $now = Carbon::now('Asia/Manila');

        // authoritative catalog: capacity AND nightly rate come from here,
        // never from the submitted form
        $catalog = RoomCatalog::all();

        foreach ($request->reservations as $block) {
            $roomNumbersArray = array_map('trim', explode(',', $block['room_number']));
            $roomType = $block['room_type'];
            $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);

            $catalogType = $catalog[$roomType] ?? null;
            if (!$catalogType) {
                return back()->withErrors(['reservations' => "Unknown room type: {$roomType}."])->withInput();
            }
            $pricePerNight = (float) $catalogType['price'];

            // validate rooms exist AND actually belong to the claimed type
            $rooms = Room::whereIn('room_number', $roomNumbersArray)
                ->where('room_type', $roomType)
                ->get();
            if ($rooms->count() !== count($roomNumbersArray)) {
                return back()->withErrors(['reservations' => 'Some selected rooms are invalid for the chosen room type.'])->withInput();
            }

            // capacity: trust backend catalog, not frontend "beds"
            $beds = (int) $catalogType['beds'];
            $blockCapacity = $beds * count($roomNumbersArray);
            $totalCapacity += $blockCapacity;

            // seniors per block
            if ($numSeniorsBlock > $blockCapacity) {
                return back()->withErrors(['reservations' => 'Senior count cannot exceed block capacity.'])->withInput();
            }
            $totalSeniors += $numSeniorsBlock;

            // guests assigned to this block
            $blockGuests = (int)($block['num_guests'] ?? 0);
            if ($blockGuests > $blockCapacity) {
                return back()->withErrors([
                    'reservations' => "Guests assigned ({$blockGuests}) exceed capacity ({$blockCapacity}) for {$roomType}."
                ])->withInput();
            }

            // Breakfast is a complimentary extra, not part of the booking
            // contract: a guest may take none, or fewer than one each. Only
            // the upper bound is enforced — you cannot claim more breakfasts
            // than there are guests to eat them.
            $meals = $block['meal'] ?? [];
            $mealTotal = array_sum($meals);

            if ($mealTotal > $blockGuests) {
                return back()->withErrors([
                    'reservations' => "Breakfasts selected ({$mealTotal}) cannot exceed the guests in the {$roomType} room ({$blockGuests})."
                ])->withInput();
            }

            $totalGuestsAssigned += $blockGuests;

            // price calc
            $totalPrice += $pricePerNight * count($roomNumbersArray) * $days;

            // collect all room numbers
            $allRoomNumbers = array_merge($allRoomNumbers, $roomNumbersArray);
        }

        // prevent duplicate room numbers across blocks
        if (count($allRoomNumbers) !== count(array_unique($allRoomNumbers))) {
            return back()->withErrors(['reservations' => 'Duplicate room numbers detected.'])->withInput();
        }

        // validate overall guest count (NEW FINAL CHECK)
        if ($totalGuestsAssigned !== (int)$request->expected_guests) {
            return back()->withErrors([
                'expected_guests' => "Guests assigned to rooms ({$totalGuestsAssigned}) must equal expected guests ({$request->expected_guests})."
            ])->withInput();
        }

        // seniors cannot exceed guests
        if ($totalSeniors > $request->expected_guests) {
            return back()->withErrors([
                'num_seniors' => 'Senior count cannot exceed expected guests.'
            ])->withInput();
        }

        // status
        $status = $request->boolean('request_discount') && $totalSeniors > 0
            ? 'pending_discount'
            : 'pending_payment';

        if ($totalSeniors !== array_sum(array_column($request->reservations, 'num_seniors'))) {
            return back()->withErrors([
                'reservations' => 'Mismatch: total seniors in reservations must equal the total seniors for this booking.'
            ])->withInput();
        }
        //                                          //
        //                                          //
        // PREVENT DOUBLE BOOKING WHILE TRANSACTION //
        //                                          //
        //                                          //
        try{
            $booking = DB::transaction(function() use ($request, $user, $allRoomNumbers, $guestName, $guest_address, $totalPrice, $totalSeniors, $status, $catalog) {
                // lock rooms
                $lockedRooms = Room::whereIn('room_number', $allRoomNumbers)
                        ->lockForUpdate()
                        ->get();

                $overlappingRooms = $lockedRooms->filter(function($room) use ($request) {
                    return $room->bookings()->where(function($q) use ($request) {
                            $q->whereDate('check_in', '<', $request->check_out)
                            ->whereDate('check_out', '>', $request->check_in);
                        })
                        ->whereIn('status', Booking::BLOCKING_STATUSES)
                        ->exists();
                })->pluck('room_number')->toArray();

                if (!empty($overlappingRooms)) {
                    throw new \Exception('The following rooms are already booked: ' . implode(', ', $overlappingRooms));
                }

                // Authoritative status guard: a room the front desk just set to
                // maintenance/cleaning/occupied must not be bookable, even if the
                // guest's page still shows it as open (stale tab, no JS, etc.).
                // The real-time UI is only a convenience — this is the guarantee.
                $unavailableRooms = $lockedRooms->filter(fn($room) => $room->status !== 'available')
                    ->pluck('room_number')->toArray();

                if (!empty($unavailableRooms)) {
                    throw new \Exception('The following rooms are no longer available: ' . implode(', ', $unavailableRooms));
                }
                
                //Begin Booking 

                $booking = Booking::create([
                    'user_id'         => $user->id,
                    'expected_guests' => $request->expected_guests,
                    'guest_name'      => $guestName,
                    'guest_address'   => $guest_address,
                    'guest_phone'     => $request->guest_phone,
                    'check_in'        => $request->check_in,
                    'check_out'       => $request->check_out,
                    'discount'        => 0,
                    'total_price'     => $totalPrice,
                    'num_seniors'     => $totalSeniors,
                    'wants_discount'  => $request->boolean('request_discount'),
                    'status'          => $status,
                    'payment_mode'    => 'system',
                ]);

                foreach ($request->reservations as $block) {
                    $roomNumbersArray = array_map('trim', explode(',', $block['room_number']));
                    $roomType = $block['room_type'];
                    $pricePerNight = (float) ($catalog[$roomType]['price'] ?? 0);
                    $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);
                    $meals = $block['meal'] ?? null;
                    $beds = (int) ($catalog[$roomType]['beds'] ?? 1);

                    foreach ($roomNumbersArray as $roomNumber) {
                        $room = Room::where('room_number', $roomNumber)->first();

                        Reservation::create([
                            'booking_id'      => $booking->id,
                            'room_number'     => $roomNumber,
                            'room_type'       => $roomType,
                            'capacity'        => $beds,
                            'price' => $pricePerNight,
                            'num_seniors'     => min($numSeniorsBlock, $beds),
                            'num_guests'  => (int) ($block['num_guests'] ?? 0),
                            'meal'        => $meals,
                        ]);

                        $numSeniorsBlock -= $beds;
                        if ($numSeniorsBlock < 0) {
                            $numSeniorsBlock = 0;
                        }
                    }
                }
                
                return $booking;
            });
        }
        catch (\Throwable $e) {
            \Log::error('Booking store failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'reservations' => $e->getMessage()
            ])->withInput();
        }

        // pivot attach
        $roomIds = Room::whereIn('room_number', $allRoomNumbers)->pluck('id')->toArray();
        if (!empty($roomIds)) {
            $booking->rooms()->attach($roomIds);
        }

        \App\Support\Realtime::emit(new \App\Events\BookingChanged());
        \App\Support\Realtime::emit(new \App\Events\RoomStatusChanged());

        // Tell the desk a room is now held. Wrapped internally — a mail
        // failure must never cost the guest the booking they just made.
        \App\Support\StaffAlert::newBooking($booking);

        return redirect()->route('booking.show', $booking->id)
            ->with('success', 'Booking submitted! Review your Booking.');
    }


    //show booking summary after booking
    public function show(Booking $booking)
    {   
        $user = Auth::user();
        $username = $user->username;
        // Ensure the logged-in user owns this booking
        if (!$user || $booking->user_id !== $user->id) {
            abort(403);
        }

        $booking->load('reservations');
        $discount = $booking->discount()->with('files')->first();
        // Check if a discount request already exists
        $discountRequested = $booking->discount()->exists();

        // Drives the payment panel: a guest who has uploaded a receipt is
        // waiting on staff and must not be shown "Proceed to Payment" again,
        // and one whose proof was rejected needs to see why.
        $latestPayment = \App\Models\Payment::where('booking_id', $booking->id)
            ->whereNotNull('proof_path')
            ->latest('id')
            ->first();

        return view('public.booking.show', [
            'username' => $username,
            'booking' => $booking,
            'discountRequested' => $discountRequested,
            'discount' => $discount,
            'latestPayment' => $latestPayment,
        ]);
    }

    // get available rooms
    public function getAvailableRooms(Request $request)
    {
        $request->validate([
            'room_type' => 'required|string',
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');
        $roomType = $request->input('room_type');

        // Get all rooms of this type
        $rooms = Room::where('room_type', $roomType)
            ->orderBy('room_number')
            ->get();

        // Room numbers held by any blocking booking that overlaps the range,
        // read from reservations (the authoritative per-room source). The
        // holding booking's status comes along so an unpaid hold can be named
        // honestly — it is every bit as unselectable, just not yet money.
        $holds = Reservation::query()
            ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->where('bookings.check_in', '<', $checkOut)
            ->where('bookings.check_out', '>', $checkIn)
            ->get(['reservations.room_number', 'bookings.status as booking_status']);

        // A room can carry several overlapping holds; a settled one wins, so
        // a paid stay is never softened to "reserved" by a pending neighbour.
        $holdByRoom = [];
        foreach ($holds as $hold) {
            $number = trim($hold->room_number);
            if (! isset($holdByRoom[$number]) || ! RoomHold::isPending($hold->booking_status)) {
                $holdByRoom[$number] = $hold->booking_status;
            }
        }

        // Map rooms to availability
        $result = $rooms->map(function ($r) use ($holdByRoom) {
            $number = trim($r->room_number);

            if (array_key_exists($number, $holdByRoom)) {
                // 'booked' (settled) or 'reserved' (awaiting payment).
                $status = RoomHold::pickerStatus($holdByRoom[$number]);
            } elseif ($r->status !== 'available') {
                $status = $r->status; // maintenance, cleaning, occupied
            } else {
                $status = 'available'; // truly free
            }

            return [
                'id'          => $r->id,
                'room_number' => $r->room_number,
                'status'      => $status,
            ];
        });

        return response()->json(['rooms' => $result]);
    }

    /**
     * Live availability summary for the landing-page search widget.
     * Returns, per room type, how many rooms are open for the date range.
     */
    public function availabilitySummary(Request $request)
    {
        $request->validate([
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn  = $request->input('check_in');
        $checkOut = $request->input('check_out');

        // Room numbers held by any blocking booking that overlaps the range,
        // read from reservations (the authoritative per-room source).
        $bookedRoomNumbers = Reservation::whereHas('booking', fn ($q) =>
                $q->whereIn('status', Booking::BLOCKING_STATUSES)
                  ->where('check_in', '<', $checkOut)
                  ->where('check_out', '>', $checkIn))
            ->pluck('room_number')->map(fn ($n) => trim($n))->filter()->unique()->all();

        $rooms   = Room::all();
        $catalog = RoomCatalog::all();

        $summary = [];
        foreach ($catalog as $slug => $type) {
            $ofType = $rooms->where('room_type', $slug);

            $available = $ofType->filter(function ($r) use ($bookedRoomNumbers) {
                return $r->status === 'available'
                    && !in_array(trim($r->room_number), $bookedRoomNumbers);
            })->count();

            $summary[] = [
                'room_type' => $slug,
                'title'     => $type['title'],
                'price'     => (float) $type['price'],
                'beds'      => (int) $type['beds'],
                'total'     => $ofType->count(),
                'available' => $available,
            ];
        }

        $nights = max(1, Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut)));

        return response()->json([
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
            'nights'    => $nights,
            'summary'   => $summary,
        ]);
    }
}
