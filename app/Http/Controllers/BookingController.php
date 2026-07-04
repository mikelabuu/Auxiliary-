<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class BookingController extends Controller
{
    // Show welcome landing page
    public function welcome()
    {
        $user = Auth::user();
        $username = $user ? $user->username : null;
        $roomTypes = config('room_types', []);
        return view('welcome', compact('username', 'roomTypes'));
    }

    // Show checkout form
    public function showCheckoutForm(Request $request)
    {
        $user = Auth::user();
        $username = $user->username;
        $roomTypes = config('room_types', []);
        
        $roomTypeKey = $request->query('room_type');
        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');
        $guests = $request->query('guests', 1);

        $selectedRoomType = null;
        if ($roomTypeKey && isset($roomTypes[$roomTypeKey])) {
            $selectedRoomType = $roomTypes[$roomTypeKey];
            $selectedRoomType['id'] = $roomTypeKey;
        }
        
        return view('checkout', compact('username', 'roomTypes', 'selectedRoomType', 'checkIn', 'checkOut', 'guests'));
    }

    // Handle booking submission
    public function store(Request $request)
    {
        $request->validate([
            'first_name'      => 'required|string|max:255',
            'middle_name'     => 'required|string|max:10',
            'last_name'       => 'required|string|max:255',
            'suffix'          => 'nullable|string|max:255',
            'guest_phone'     => 'required|string|max:20',
            'check_in'        => 'required|date|after_or_equal:today',
            'check_out'       => 'required|date|after:check_in',
            'expected_guests' => 'required|integer|min:1',
            'reservations'    => 'required|array|min:1',
            'reservations.*.room_type'       => 'required|string',
            'reservations.*.room_number'     => 'required|string', // CSV per block
            'reservations.*.price_per_night' => 'required|numeric|min:0',
            'reservations.*.beds'            => 'required|integer|min:1',
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

        // safer: central capacity map
        $capacityMap = [
            'double'      => 2,
            'triple'      => 3,
            'quadruple'   => 4,
            'deluxe'      => 2,
            'dormitory1'  => 5,
            'dormitory2'  => 6,
        ];

        foreach ($request->reservations as $block) {
            $roomNumbersArray = array_map('trim', explode(',', $block['room_number']));
            $roomType = $block['room_type'];
            $pricePerNight = (float) $block['price_per_night'];
            $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);

            // validate rooms exist
            $rooms = Room::whereIn('room_number', $roomNumbersArray)->get();
            if ($rooms->count() !== count($roomNumbersArray)) {
                return back()->withErrors(['reservations' => 'Some selected rooms are invalid.'])->withInput();
            }

            // capacity: trust backend map, not frontend "beds"
            $beds = $capacityMap[$roomType] ?? 1;
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

            $meals = $block['meal'] ?? [];
            $mealTotal = array_sum($meals);

            if ($mealTotal !== $blockGuests) {
                return back()->withErrors([
                    'reservations' => "Total meals selected ({$mealTotal}) must equal number of guests ({$blockGuests}) for {$roomType}."
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
            $booking = DB::transaction(function() use ($request, $user, $allRoomNumbers, $guestName, $guest_address, $totalPrice, $totalSeniors, $status, $capacityMap) {
                // lock rooms
                $lockedRooms = Room::whereIn('room_number', $allRoomNumbers)
                        ->lockForUpdate()
                        ->get();

                $overlappingRooms = $lockedRooms->filter(function($room) use ($request) {
                    return $room->bookings()->where(function($q) use ($request) {
                            $q->whereDate('check_in', '<', $request->check_out)
                            ->whereDate('check_out', '>', $request->check_in);
                        })
                        ->whereIn('status', ['pending_payment', 'checked_in', 'pending_discount'])
                        ->exists();
                })->pluck('room_number')->toArray();

                if (!empty($overlappingRooms)) {
                    throw new \Exception('The following rooms are already booked: ' . implode(', ', $overlappingRooms));
                }
                
                //Begin Booking 

                $booking = Booking::create([
                    'user_id'         => $user->id,
                    'room_numbers'    => implode(',', $allRoomNumbers),
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
                    $pricePerNight = (float) $block['price_per_night'];
                    $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);
                    $meals = $block['meal'] ?? null;
                    $beds = $capacityMap[$roomType] ?? 1;

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

        return view('show', [
            'username' => $username,
            'booking' => $booking,
            'discountRequested' => $discountRequested,
            'discount' => $discount,
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

        // Get bookings that overlap with the given range AND block availability
        $bookings = Booking::whereIn('status', [
                'pending_discount',
                'pending_payment',
                'paid',
                'confirmed',
                'active',
            ])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->get();

        // Collect booked room numbers
        $bookedRoomNumbers = $bookings->flatMap(function ($b) {
            if (is_array($b->room_numbers)) {
                return $b->room_numbers;
            }
            return array_map('trim', explode(',', (string) $b->room_numbers));
        })->toArray();

        // Map rooms to availability
        $result = $rooms->map(function ($r) use ($bookedRoomNumbers) {
            if (in_array(trim($r->room_number), $bookedRoomNumbers)) {
                $status = 'booked'; // from booking overlap
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
}
