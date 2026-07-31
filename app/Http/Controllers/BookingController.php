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
use Illuminate\Validation\Rule;
use Carbon\Carbon;


class BookingController extends Controller
{
    /**
     * How far ahead a stay may start. Kept equal to the calendar endpoint's
     * own window so the picker never offers a date store() would reject.
     */
    public const BOOKING_HORIZON_DAYS = 365;

    /** Longest single stay. Anything beyond this is a conversation, not a form. */
    public const MAX_STAY_NIGHTS = 30;

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

        return view('public.booking.checkout', compact('username', 'roomTypes', 'selectedRoomType', 'checkIn', 'checkOut', 'guests') + [
            'prefill'      => $this->checkoutPrefill($user),
            'holdMinutes'  => (int) config('bookings.expiry_minutes'),
        ]);
    }

    /**
     * What we can honestly fill in for a returning guest.
     *
     * `users` stores a username, an email and a phone — no name at all — so
     * the only place this person's name has ever been written is their last
     * booking. That is the source, and it degrades quietly to blanks for a
     * first-timer.
     *
     * @return array{first_name:string, middle_name:string, last_name:string, suffix:string, guest_phone:string}
     */
    private function checkoutPrefill(?User $user): array
    {
        $blank = ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => '', 'guest_phone' => ''];

        if (! $user) {
            return $blank;
        }

        $prefill = array_merge($blank, ['guest_phone' => (string) ($user->phone ?? '')]);

        $last = Booking::where('user_id', $user->id)
            ->whereNotNull('guest_name')
            ->latest('id')
            ->first();

        if (! $last) {
            return $prefill;
        }

        // store() writes "Last, First, Middle" and glues any suffix onto the
        // end, so unpick it the same way round.
        $parts = array_map('trim', explode(',', (string) $last->guest_name));

        $prefill['last_name']   = $parts[0] ?? '';
        $prefill['first_name']  = $parts[1] ?? '';
        $middle                 = $parts[2] ?? '';

        // Only peel a trailing token off when it is unmistakably a suffix —
        // guessing would eat the second half of a two-word middle name.
        $tokens = preg_split('/\s+/', $middle, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) > 1) {
            $tail = rtrim(strtolower(end($tokens)), '.');
            if (in_array($tail, ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)) {
                $prefill['suffix'] = array_pop($tokens);
            }
        }
        $prefill['middle_name'] = implode(' ', $tokens);

        return $prefill;
    }

    // Handle booking submission
    public function store(Request $request)
    {
        // A stay has to end somewhere. `check_out > check_in` was the only
        // bound, so a booking could run to 2040 and hold every room in the
        // house for it. The horizon matches the calendar's own 365-day window
        // — offering a date the picker cannot even show is not a feature.
        $horizon  = Carbon::today()->addDays(self::BOOKING_HORIZON_DAYS);
        $maxStay  = Carbon::parse($request->input('check_in', 'today'))
            ->addDays(self::MAX_STAY_NIGHTS);

        $request->validate([
            'first_name'      => ['required', 'string', 'max:255', new \App\Rules\PersonName],
            // Was max:10, which silently rejected perfectly ordinary middle
            // names ("Bartholomew" is 11). Matched to the other name fields.
            'middle_name'     => ['required', 'string', 'max:255', new \App\Rules\PersonName],
            'last_name'       => ['required', 'string', 'max:255', new \App\Rules\PersonName],
            'suffix'          => ['nullable', 'string', 'max:255', new \App\Rules\PersonName],
            // The form has enforced this shape all along via a pattern
            // attribute; the server took any 20 characters, so anyone posting
            // around the form could store "call me maybe" as a contact number.
            'guest_phone'     => ['required', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'check_in'        => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $horizon->toDateString()],
            'check_out'       => ['required', 'date', 'after:check_in', 'before_or_equal:' . $maxStay->toDateString()],
            'expected_guests' => 'required|integer|min:1|max:40',
            // Optional, but if given it has to be a real clock time — the
            // front desk plans the evening around this.
            'arrival_time'     => 'nullable|date_format:H:i',
            'special_requests' => 'nullable|string|max:500',
            // `accepted` rather than `boolean`: an unticked box posts nothing
            // at all, and `boolean` would happily pass a missing field.
            'accept_terms'     => 'accepted',
            'reservations'    => 'required|array|min:1|max:10',
            'reservations.*.room_type'       => 'required|string',
            'reservations.*.room_number'     => 'required|string', // CSV per block
            // Never validated, only read as `$block['num_guests'] ?? 0`. An
            // omitted value quietly became zero guests, and every per-block
            // capacity check below compares against zero and passes.
            'reservations.*.num_guests'      => 'required|integer|min:1',
            // price/beds are posted for display continuity but the backend
            // recomputes both from RoomCatalog — never trust client pricing.
            'reservations.*.price_per_night' => 'nullable|numeric',
            'reservations.*.beds'            => 'nullable|integer',
            'reservations.*.num_seniors'     => 'nullable|integer|min:0',
            'reservations.*.meal' => 'nullable|array',
            'reservations.*.meal.*' => 'integer|min:0|max:40',
            'region_code' => 'required|string|max:255',
            'province_code' => 'nullable|string|max:255',
            'city_code' => 'required|string|max:255',
            'barangay_code' => 'required|string|max:255',
        ], [
            'guest_phone.regex'        => 'Enter a Philippine mobile number as 09xxxxxxxxx or +639xxxxxxxxx.',
            'check_in.before_or_equal' => 'We only take bookings up to ' . self::BOOKING_HORIZON_DAYS . ' days ahead.',
            'check_out.before_or_equal' => 'A single stay can run at most ' . self::MAX_STAY_NIGHTS . ' nights. Please contact us for longer stays.',
            'reservations.*.num_guests.required' => 'Say how many guests are staying in each room you picked.',
            'reservations.*.num_guests.min'      => 'Each room you pick needs at least one guest in it.',
            'accept_terms.accepted'    => 'Please agree to the booking terms before confirming.',
            'arrival_time.date_format' => 'Choose an arrival time from the list, or leave it as "Not sure yet".',
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

                // Read the hold from RESERVATIONS, not the booking_room pivot.
                //
                // This guard used to ask `$room->bookings()`, which is the
                // belongsToMany over booking_room — and that pivot is attached
                // *after* this transaction commits. Every booking therefore
                // spent the window between COMMIT and attach() invisible to
                // this check, and `lockForUpdate` below hands the row to the
                // next waiter at exactly that moment: two guests racing for
                // room 112 both passed, and the room was sold twice. A pivot
                // attach that ever failed left the room permanently rebookable.
                //
                // Reservations are written inside this same transaction, and
                // are already the authoritative per-room source everywhere else
                // — the room grid, availabilitySummary, calendarAvailability,
                // manual booking and walk-in all read them. This is the last
                // guard that disagreed.
                // whereDate, not a plain comparison: bookings.check_in is a
                // DATE column, but a driver that stores it with a midnight
                // time component makes `'…12 00:00:00' > '…12'` true and
                // rejects a legitimate back-to-back stay. A stay is
                // [check_in, check_out), so the turnover day belongs to the
                // arriving guest — getting this wrong costs a night's revenue
                // on every changeover.
                $overlappingRooms = Reservation::query()
                    ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
                    ->whereIn('reservations.room_number', $allRoomNumbers)
                    ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
                    ->whereDate('bookings.check_in', '<', $request->check_out)
                    ->whereDate('bookings.check_out', '>', $request->check_in)
                    ->pluck('reservations.room_number')
                    ->map(fn ($number) => trim($number))
                    ->unique()
                    ->values()
                    ->all();

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
                    // Blank means "not sure yet", which is a real answer and
                    // must not be stored as midnight. Normalised to H:i:s on
                    // the way in: the form posts "22:00", MySQL's TIME reads
                    // back "22:00:00" and SQLite hands back whatever it was
                    // given, so pinning the shape here keeps every reader —
                    // views, tests, the front desk — looking at one format.
                    'arrival_time'      => $request->filled('arrival_time')
                        ? Carbon::createFromFormat('H:i', $request->input('arrival_time'))->format('H:i:s')
                        : null,
                    'special_requests'  => $request->filled('special_requests') ? trim($request->input('special_requests')) : null,
                    // Stamped server-side. The checkbox only proves a box was
                    // ticked; this records when the agreement actually happened.
                    'accepted_terms_at' => now(),
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

                // Inside the transaction, not after it. The room board
                // (App\Support\RoomBoard) reads this pivot, so a booking that
                // committed and then failed to attach would hold rooms the
                // board showed as free.
                $roomIds = $lockedRooms->pluck('id')->all();
                if (!empty($roomIds)) {
                    $booking->rooms()->attach($roomIds);
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

        \App\Support\Realtime::emit(new \App\Events\BookingChanged());
        \App\Support\Realtime::emit(new \App\Events\RoomStatusChanged());
        // …and the same news in a form a human can read, straight into the
        // console's bell and a popup (private staff channel — see the event).
        \App\Support\Realtime::emit(\App\Events\StaffNotification::newBooking($booking));

        // Tell the desk a room is now held. Wrapped internally — a mail
        // failure must never cost the guest the booking they just made.
        \App\Support\StaffAlert::newBooking($booking);

        // …and tell the guest, which nothing did until now: they placed a
        // booking, the payment window started counting down, and their inbox
        // stayed empty until they either paid or the booking expired.
        \App\Support\GuestNotice::bookingReceived($booking);

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
    /**
     * Which nights are already sold out, for the checkout date pickers.
     *
     * The calendar previously knew nothing about bookings: it enforced
     * `minDate: today` and check-out > check-in, and that was all. A guest
     * could pick a sold-out week, fill in the whole form, and only discover
     * the problem at the room grid — "All rooms of this type are booked" —
     * with no hint which dates would have worked.
     *
     * A night is full when every sellable room is spoken for. Sellable means
     * `rooms.status = 'available'`: a room in maintenance is not inventory,
     * so counting it would leave nights looking bookable that aren't.
     *
     * Nights, not days. A stay covers [check_in, check_out), so checking OUT
     * on a full date is fine — only the nights in between have to be free.
     * The client uses that: full nights are struck off the check-in picker,
     * and the first full night after a chosen check-in becomes the check-out
     * picker's maxDate.
     *
     * Optionally scoped to one `room_type`. Property-wide numbers are close to
     * useless for a guest who has already chosen a style: room 112 is one of
     * three doubles, so a hold on it leaves 21 of 22 rooms free and the night
     * reads wide open — while the type the guest is actually shopping for is
     * down to its last two. Scoped, that same night correctly reports "2 left",
     * and goes fully struck-through once all three doubles are gone.
     */
    public function calendarAvailability(Request $request)
    {
        $request->validate([
            'room_type' => ['nullable', 'string', Rule::in(array_keys(RoomCatalog::all()))],
        ]);

        $days = (int) $request->integer('days', 365);
        $days = max(30, min(365, $days));

        $roomType = $request->query('room_type') ?: null;

        $start = Carbon::today();
        $end   = $start->copy()->addDays($days);

        // The sellable set, not just its size. Two reasons it has to be the
        // set: the type filter needs to know *which* rooms count, and a
        // reservation against a non-sellable room (one pulled into
        // maintenance after it was booked) must not be subtracted from a
        // total that never included it — that used to undercount what was
        // left and black out nights that were genuinely free.
        $sellableRooms = Room::where('status', 'available')
            ->when($roomType, fn ($q) => $q->where('room_type', $roomType))
            ->pluck('room_number')
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique();

        $sellable    = $sellableRooms->count();
        $sellableSet = $sellableRooms->flip(); // O(1) membership below

        // One pass over the overlapping reservations, then fan each one out
        // across the nights it covers — cheaper than a query per date, and the
        // window is bounded so the loop is too.
        $rows = Reservation::query()
            ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
            ->whereIn('bookings.status', Booking::BLOCKING_STATUSES)
            ->where('bookings.check_in', '<', $end)
            ->where('bookings.check_out', '>', $start)
            ->get(['reservations.room_number', 'bookings.check_in', 'bookings.check_out']);

        /** @var array<string, array<string, true>> $taken night => set of room numbers */
        $taken = [];

        foreach ($rows as $row) {
            $roomNumber = trim((string) $row->room_number);
            // Membership is judged against `rooms`, never reservations.room_type
            // — the latter is whatever the form posted, the former is what the
            // room actually is.
            if ($roomNumber === '' || ! $sellableSet->has($roomNumber)) {
                continue;
            }

            $from = Carbon::parse($row->check_in)->startOfDay()->max($start);
            $to   = Carbon::parse($row->check_out)->startOfDay()->min($end);

            for ($night = $from->copy(); $night->lt($to); $night->addDay()) {
                // A set, not a counter: two reservations can name the same
                // room across overlapping ranges, and double-counting would
                // black out nights that are actually free.
                $taken[$night->toDateString()][$roomNumber] = true;
            }
        }

        $full = [];
        $remaining = [];

        if ($sellable === 0) {
            // Nothing of this type is sellable at all (every room of it is in
            // maintenance, or the type has no rooms). No booking is needed to
            // make those nights unavailable — the whole window is off.
            for ($night = $start->copy(); $night->lt($end); $night->addDay()) {
                $date = $night->toDateString();
                $remaining[$date] = 0;
                $full[] = $date;
            }
        } else {
            foreach ($taken as $date => $rooms) {
                $left = max(0, $sellable - count($rooms));
                $remaining[$date] = $left;

                if ($left === 0) {
                    $full[] = $date;
                }
            }
        }

        sort($full);

        return response()->json([
            // Echoed back so a slow response for a type the guest has already
            // moved on from can be dropped instead of painting the calendar
            // with the wrong inventory.
            'room_type' => $roomType,
            'sellable'  => $sellable,
            'from'      => $start->toDateString(),
            'to'        => $end->toDateString(),
            // Nights with no rooms left — struck off the picker entirely.
            'full'      => $full,
            // Only nights with at least one booking appear here; anything
            // absent is wide open, which keeps the payload small.
            'remaining' => $remaining,
        ]);
    }

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
