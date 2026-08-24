<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\RoomUnavailable;
use App\Http\Requests\StoreBookingRequest;
use App\Services\BookingCreator;
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

    /**
     * Ceilings on a single booking, shared with the checkout view.
     *
     * These were literals inside store()'s validation rules, which meant the
     * form could only ever discover them by being rejected: the guest count
     * input carried a silent `max="40"` and the page never said so. The
     * checkout now prints the number beside the field, so it is declared once
     * here and read from both places rather than typed out twice.
     */
    public const MAX_GUESTS_PER_BOOKING = 40;

    /** Rooms in one booking. Beyond this it is a group block, handled at the desk. */
    public const MAX_ROOMS_PER_BOOKING = 10;

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
            'holdLabel'    => \App\Support\PaymentWindow::label(),
            // Printed next to the guest stepper, and enforced by store().
            'maxGuestsPerBooking' => self::MAX_GUESTS_PER_BOOKING,
            'maxRoomsPerBooking'  => self::MAX_ROOMS_PER_BOOKING,
        ]);
    }

    /**
     * What we can honestly fill in for a returning guest.
     *
     * Two sources, in that order of trust. A previous booking is the better
     * one — it is what this guest last told the front desk, second contact
     * number included — but a first-timer has none, and used to be handed six
     * empty fields despite having typed their name into the signup form
     * minutes earlier. `users.full_name` is written at registration in the
     * same "Last, First, Middle" shape store() uses, so it is the fallback
     * rather than a second parser.
     *
     * The address comes off the account, not the booking: `guest_address` is a
     * flattened label with the PSGC codes already discarded, and the four
     * dropdowns cannot be restored from it. store() writes the codes back to
     * the user, so the second booking starts where the first one finished.
     *
     * Degrades quietly to blanks at every step — a missing piece is a field
     * the guest fills in, never an error.
     *
     * @return array{first_name:string, middle_name:string, last_name:string, suffix:string, guest_phone:string, guest_phone_alt:string, address:array<string,string>}
     */
    private function checkoutPrefill(?User $user): array
    {
        $blank = [
            'first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => '',
            'guest_phone' => '', 'guest_phone_alt' => '',
            'address' => ['region' => '', 'province' => '', 'city' => '', 'barangay' => ''],
        ];

        if (! $user) {
            return $blank;
        }

        $prefill = array_merge($blank, [
            'guest_phone' => (string) ($user->phone ?? ''),
            'address'     => $this->prefillAddress($user),
        ]);

        $last = Booking::where('user_id', $user->id)
            ->whereNotNull('guest_name')
            ->latest('id')
            ->first();

        if ($last) {
            // Only the booking carries a second number — `users` has one phone
            // column — so this one comes back from the last stay or not at all.
            $prefill['guest_phone_alt'] = (string) ($last->guest_phone_alt ?? '');

            // And the first number falls back to the last stay too. Signup
            // never asks for a phone, so `users.phone` is null for anyone who
            // has not since filled in their profile — which left the odd
            // result that a returning guest was handed their *second* contact
            // number and made to retype their first.
            if ($prefill['guest_phone'] === '') {
                $prefill['guest_phone'] = (string) ($last->guest_phone ?? '');
            }
        }

        $name = $last?->guest_name ?: $user->full_name;

        return array_merge($prefill, $this->splitGuestName((string) $name));
    }

    /**
     * "Last, First, Middle [Suffix]" back into the four fields that built it.
     *
     * Shared by both prefill sources because both write that shape: store()
     * glues it together at checkout, and AuthController does the same at
     * registration with a middle initial in place of a full middle name.
     *
     * @return array{first_name:string, middle_name:string, last_name:string, suffix:string}
     */
    private function splitGuestName(string $name): array
    {
        $split = ['first_name' => '', 'middle_name' => '', 'last_name' => '', 'suffix' => ''];

        if (trim($name) === '') {
            return $split;
        }

        $parts = array_map('trim', explode(',', $name));

        $split['last_name']  = $parts[0] ?? '';
        $split['first_name'] = $parts[1] ?? '';
        $middle              = $parts[2] ?? '';

        // Only peel a trailing token off when it is unmistakably a suffix —
        // guessing would eat the second half of a two-word middle name.
        $tokens = preg_split('/\s+/', $middle, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) > 1) {
            $tail = rtrim(strtolower(end($tokens)), '.');
            if (in_array($tail, ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)) {
                $split['suffix'] = array_pop($tokens);
            }
        }
        $split['middle_name'] = implode(' ', $tokens);

        return $split;
    }

    /**
     * The saved address as the four "CODE|NAME" values the selector expects.
     *
     * The name half is read from the gazetteer rather than stored alongside
     * the code, so a place that has been renamed since the last booking still
     * matches an option in the dropdown. A code that resolves to nothing —
     * gazetteer not synced, or a code retired outright — is dropped along with
     * everything below it, because a city with no region selected above it
     * cannot be shown either.
     *
     * @return array{region:string, province:string, city:string, barangay:string}
     */
    private function prefillAddress(User $user): array
    {
        $blank = ['region' => '', 'province' => '', 'city' => '', 'barangay' => ''];

        if (! $user->region_code || ! $user->city_code) {
            return $blank;
        }

        $psgc = app(\App\Services\PsgcDirectory::class);

        // Barangays are indexed per city, so that lookup needs the city code
        // alongside it — the same argument store() passes when it resolves the
        // posted address.
        $compose = function (string $level, ?string $code, ?string $cityCode = null) use ($psgc): string {
            if (! $code) {
                return '';
            }

            $name = $psgc->name($level, $code, $cityCode);

            return $name === '' ? '' : $code . '|' . $name;
        };

        $address = [
            'region'   => $compose('regions', $user->region_code),
            // Genuinely empty for NCR, whose cities hang off the region.
            'province' => $compose('provinces', $user->province_code),
            'city'     => $compose('cities', $user->city_code),
            'barangay' => $compose('barangays', $user->barangay_code, $user->city_code),
        ];

        if ($address['region'] === '' || $address['city'] === '') {
            return $blank;
        }

        return $address;
    }

    /**
     * Store the posted address codes on the account.
     *
     * Bare codes, stripped of the label the form posted alongside them, for
     * the reasons in the migration: the code is the stable half.
     *
     * A guest is never blocked by this. It runs mid-checkout, and a save that
     * throws — a column missing because the migration has not been run on this
     * box, say — would take a confirmed-in-all-but-name booking down with it.
     * Losing the prefill is a small cost; losing the booking is not.
     */
    private function rememberAddress(?User $user, Request $request): void
    {
        if (! $user) {
            return;
        }

        try {
            $user->forceFill([
                'region_code'   => \App\Services\PsgcDirectory::code($request->region_code),
                // Blank for NCR, and stored blank rather than left at its old
                // value — a guest who has moved out of a province should not
                // keep it.
                'province_code' => \App\Services\PsgcDirectory::code($request->province_code) ?: null,
                'city_code'     => \App\Services\PsgcDirectory::code($request->city_code),
                'barangay_code' => \App\Services\PsgcDirectory::code($request->barangay_code),
            ])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    // Handle booking submission
    public function store(StoreBookingRequest $request, BookingCreator $creator)
    {

        $user = Auth::user();
        
        $guestName = implode(', ', [
            $request->last_name,
            $request->first_name,
            $request->middle_name,
        ]);
        
        if ($request->filled('suffix')) {
            $guestName .= ' ' . $request->suffix;
        }

        // Resolved from the code, not from the label the form posted, so the
        // stored address always matches the published PSGC list.
        $psgc = app(\App\Services\PsgcDirectory::class);

        $brgyName = $psgc->name('barangays', $request->barangay_code, $request->city_code);
        $cityName = $psgc->name('cities', $request->city_code);
        $provName = $request->province_code ? $psgc->name('provinces', $request->province_code) : '';

        $guest_address = collect([
            $brgyName,
            $cityName,
            $provName,
        ])
        ->filter()
        ->implode(', ');

        // Keep the codes on the account so the next booking can put the four
        // dropdowns back. $guest_address cannot do this job: it is a flattened
        // label with the codes already dropped, which is what the front desk
        // needs to read and useless for restoring a selection.
        //
        // Written here rather than after the transaction on purpose. The
        // address has been validated by this point, and the rest of store()
        // can still fail on something the guest cannot control — a room taken
        // between loading the page and confirming — which is exactly the case
        // where they should not have to pick their barangay a second time.
        //
        // Latest-wins rather than first-wins: a guest who has moved is telling
        // us so, and pinning the address to whatever the first booking said
        // would make the prefill wrong for good.
        $this->rememberAddress($user, $request);

        // What it costs, whether it fits, and which rooms are actually free —
        // all inside one locked transaction. See App\Services\BookingCreator.
        // A ValidationException from the rules in there is left to propagate:
        // Laravel turns it into the same redirect-with-errors this method used
        // to build by hand.
        try {
            $booking = $creator->create($request, $user, $guestName, $guest_address);
        }
        catch (RoomUnavailable $e) {
            // The one failure written to be read by a guest: someone took the
            // room while this form was open.
            return back()->withErrors([
                'reservations' => $e->getMessage()
            ])->withInput();
        }
        // A rule inside the creator said no. That is the guest's answer to
        // read, not an internal fault — and it must be re-thrown *before* the
        // catch-all below, which extends Throwable and would otherwise convert
        // "seniors cannot exceed expected guests" into "something went wrong".
        // Four of these paths hid that for a while because the generic message
        // is filed under the same `reservations` key they were asserting on.
        catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
        catch (\Throwable $e) {
            // Everything else is ours, not theirs. This used to echo
            // $e->getMessage() into the form, so a missing column or a
            // constraint violation was rendered verbatim to whoever was
            // booking — the schema, leaked one failure at a time.
            \Log::error('Booking store failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors([
                'reservations' => 'We could not complete that booking. Please try again, or contact the front desk if it keeps happening.'
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

    /**
     * Which rooms of a style are free over a date range.
     *
     * No longer what the booking form runs on. It used to feed the tile grid a
     * guest picked their room number from; that picker is gone and store()
     * assigns the rooms itself, so nothing on the public site calls this any
     * more. It stays because it is still the most direct answer to "is this
     * room free on these dates", and because it is the probe the hold tests
     * ask that question through.
     */
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
            ->tap(fn ($q) => Booking::applyActiveHold($q))
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
            ->tap(fn ($q) => Booking::applyActiveHold($q))
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
                Booking::applyActiveHold($q, '')
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
