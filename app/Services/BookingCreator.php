<?php

namespace App\Services;

use App\Exceptions\RoomUnavailable;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Support\RoomCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns a validated checkout submission into a booking, without selling a room
 * twice.
 *
 * This is the second half of what used to be BookingController::store() — 462
 * lines that answered three separate questions at once. StoreBookingRequest
 * now answers "is this submission well-formed?"; this answers "what does it
 * cost, does it fit, and which rooms are actually free right now?"; the
 * controller is left with the part that is genuinely HTTP: who is signed in,
 * what to tell them, and where to send them next.
 *
 * Nothing about the arithmetic or the locking changed in the move. The one
 * deliberate difference is how a broken booking is reported: the rules below
 * used to `return back()->withErrors(...)`, which only a controller can do, and
 * now throw ValidationException. For a form POST the guest sees exactly the
 * same thing — Laravel turns it into the same redirect, with the same errors
 * and the same flashed input.
 */
class BookingCreator
{
    /**
     * @param  string  $guestName  "Last, First, Middle [Suffix]", assembled by the caller.
     * @param  string  $guestAddress  The flattened PSGC label, resolved from codes by the caller.
     *
     * @throws ValidationException The submission is internally inconsistent.
     * @throws RoomUnavailable Someone took the room while the form was open.
     */
    public function create(
        StoreBookingRequest $request,
        User $user,
        string $guestName,
        string $guestAddress
    ): Booking {
        // Named to match the code moved from store(), which reads this as a
        // plain column value rather than as a parameter.
        $guest_address = $guestAddress;
        $cin  = Carbon::parse($request->check_in);
        $cout = Carbon::parse($request->check_out);
        $days = max(1, $cin->diffInDays($cout));

        $totalPrice = 0;
        $totalSeniors = 0;
        $totalGuestsAssigned = 0;
        // How many rooms of each style this booking is asking for. The
        // transaction below turns these counts into actual room numbers.
        $wantedByType = [];

        // authoritative catalog: capacity AND nightly rate come from here,
        // never from the submitted form
        $catalog = RoomCatalog::all();

        foreach ($request->reservations as $block) {
            $roomType = $block['room_type'];
            $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);

            $catalogType = $catalog[$roomType] ?? null;
            if (! $catalogType) {
                throw ValidationException::withMessages(['reservations' => "Unknown room type: {$roomType}."]);
            }
            $pricePerNight = (float) $catalogType['price'];

            // One block is one room. It always was on this form — the picker
            // only ever let a guest select a single tile per block — and now
            // that nobody is naming rooms, saying so plainly is what lets the
            // counts below be counts.
            $blockCapacity = (int) $catalogType['beds'];
            $wantedByType[$roomType] = ($wantedByType[$roomType] ?? 0) + 1;

            // seniors per block
            if ($numSeniorsBlock > $blockCapacity) {
                throw ValidationException::withMessages(['reservations' => 'Senior count cannot exceed block capacity.']);
            }
            $totalSeniors += $numSeniorsBlock;

            // guests assigned to this block
            $blockGuests = (int) ($block['num_guests'] ?? 0);
            if ($blockGuests > $blockCapacity) {
                throw ValidationException::withMessages([
                    'reservations' => "Guests assigned ({$blockGuests}) exceed capacity ({$blockCapacity}) for {$roomType}.",
                ]);
            }

            // Breakfast is a complimentary extra, not part of the booking
            // contract: a guest may take none, or fewer than one each. Only
            // the upper bound is enforced — you cannot claim more breakfasts
            // than there are guests to eat them.
            $meals = $block['meal'] ?? [];
            $mealTotal = array_sum($meals);

            if ($mealTotal > $blockGuests) {
                throw ValidationException::withMessages([
                    'reservations' => "Breakfasts selected ({$mealTotal}) cannot exceed the guests in the {$roomType} room ({$blockGuests}).",
                ]);
            }

            $totalGuestsAssigned += $blockGuests;

            // price calc
            $totalPrice += $pricePerNight * $days;
        }

        // validate overall guest count (NEW FINAL CHECK)
        if ($totalGuestsAssigned !== (int) $request->expected_guests) {
            throw ValidationException::withMessages([
                'expected_guests' => "Guests assigned to rooms ({$totalGuestsAssigned}) must equal expected guests ({$request->expected_guests}).",
            ]);
        }

        // seniors cannot exceed guests
        if ($totalSeniors > $request->expected_guests) {
            throw ValidationException::withMessages([
                'num_seniors' => 'Senior count cannot exceed expected guests.',
            ]);
        }

        // A discount is only wanted if there is somebody to discount. Ticking
        // the box with zero seniors declared used to store wants_discount=true
        // against a booking that could never receive one — harmless while the
        // flag only chose a starting status, but it now also decides whether
        // the booking may be paid online, and that booking would have been
        // sent to the front desk to prove an entitlement it never claimed.
        $wantsDiscount = $request->boolean('request_discount') && $totalSeniors > 0;

        // status
        $status = $wantsDiscount ? 'pending_discount' : 'pending_payment';

        if ($totalSeniors !== array_sum(array_column($request->reservations, 'num_seniors'))) {
            throw ValidationException::withMessages([
                'reservations' => 'Mismatch: total seniors in reservations must equal the total seniors for this booking.',
            ]);
        }

        // PREVENT DOUBLE BOOKING: everything below runs inside one transaction,
        // and the room rows are locked before anything is read from them.
        return DB::transaction(function () use ($request, $user, $wantedByType, $guestName, $guest_address, $totalPrice, $totalSeniors, $status, $wantsDiscount, $catalog) {
            // Lock every room of every style being asked for — not a named
            // list, because there is no longer a named list. The lock has
            // to cover the whole pool the assignment below draws from, or
            // two guests picking the same style at the same moment could
            // each be handed the same last room.
            $lockedRooms = Room::whereIn('room_type', array_keys($wantedByType))
                    ->lockForUpdate()
                    ->get();

            // Read the hold from RESERVATIONS, not the booking_room pivot.
            //
            // This guard used to ask `$room->bookings()`, which is the
            // belongsToMany over booking_room — and that pivot is attached
            // *after* this transaction commits. Every booking therefore
            // spent the window between COMMIT and attach() invisible to
            // this check, and `lockForUpdate` above hands the row to the
            // next waiter at exactly that moment: two guests racing for
            // room 112 both passed, and the room was sold twice. A pivot
            // attach that ever failed left the room permanently rebookable.
            //
            // Reservations are written inside this same transaction, and
            // are already the authoritative per-room source everywhere else
            // — availabilitySummary, calendarAvailability, manual booking
            // and walk-in all read them. This is the last guard that
            // disagreed.
            // A stay is [check_in, check_out), so the turnover day belongs
            // to the arriving guest — getting this wrong costs a night's
            // revenue on every changeover.
            //
            // This used to be whereDate(), because a driver that stored
            // the DATE column with a midnight time component made
            // `'…12 00:00:00' > '…12'` true and rejected a legitimate
            // back-to-back stay. Booking's setCheckInAttribute /
            // setCheckOutAttribute now normalise the stored value to bare
            // `Y-m-d` on every driver, so the comparison is exact without
            // wrapping the column — and `date(check_in)` was not sargable,
            // which meant idx_bookings_availability could not be used by
            // this query at all (MySQL declined it even under FORCE
            // INDEX). Both sides must stay bare dates: the columns via
            // those mutators, the bounds via toDateString() below.
            $checkInBound  = Carbon::parse($request->check_in)->toDateString();
            $checkOutBound = Carbon::parse($request->check_out)->toDateString();

            $takenRoomNumbers = Reservation::query()
                ->join('bookings', 'bookings.id', '=', 'reservations.booking_id')
                ->whereIn('reservations.room_number', $lockedRooms->pluck('room_number')->all())
                ->tap(fn ($q) => Booking::applyActiveHold($q))
                ->where('bookings.check_in', '<', $checkOutBound)
                ->where('bookings.check_out', '>', $checkInBound)
                ->pluck('reservations.room_number')
                ->map(fn ($number) => trim($number))
                ->unique()
                ->flip();

            // What is actually sellable for these dates, per style. A room
            // the front desk just moved to maintenance/cleaning/occupied is
            // not inventory, whatever a stale tab still shows — the live UI
            // is a convenience, this is the guarantee.
            $poolByType = $lockedRooms
                ->filter(fn ($room) => $room->status === 'available'
                    && ! $takenRoomNumbers->has(trim((string) $room->room_number)))
                ->groupBy('room_type');

            // Assign. Shuffled rather than taken in order, so the same low
            // room numbers are not worn out first while the far end of the
            // corridor sits unused.
            $assignments = [];   // room_type => list of Room, in the order blocks claim them

            foreach ($wantedByType as $roomType => $wanted) {
                $pool = ($poolByType[$roomType] ?? collect())->shuffle()->values();

                if ($pool->count() < $wanted) {
                    $title = $catalog[$roomType]['title'] ?? $roomType;
                    $left = $pool->count();

                    throw new RoomUnavailable(
                        $left === 0
                            ? "There are no {$title} rooms left for those dates. Try other dates or another room style."
                            : "Only {$left} " . ($left === 1 ? 'room' : 'rooms') . " of the {$title} style "
                                . ($left === 1 ? 'is' : 'are') . " left for those dates, and you asked for {$wanted}."
                    );
                }

                $assignments[$roomType] = $pool->take($wanted)->values();
            }

            // Begin Booking

            $booking = Booking::create([
                'user_id'         => $user->id,
                'expected_guests' => $request->expected_guests,
                'guest_name'      => $guestName,
                'guest_address'   => $guest_address,
                'guest_phone'     => $request->guest_phone,
                'guest_phone_alt' => $request->guest_phone_alt ?: null,
                'referred_by'     => trim((string) $request->referred_by) ?: null,
                'referred_by_phone'   => trim((string) $request->referred_by_phone) ?: null,
                'referred_by_purpose' => trim((string) $request->referred_by_purpose) ?: null,
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
                // What the guest actually owes. Left unset here for a long
                // time, so every guest booking without a discount carried
                // NULL — harmless to the payment paths, which all read
                // `payable_amount ?? total_price`, but the financial report
                // and the bookings export select the column directly and
                // showed those rows blank. Equal to total_price at
                // creation; DiscountAdminController rewrites it when a
                // discount is approved.
                'payable_amount'  => $totalPrice,
                'num_seniors'     => $totalSeniors,
                'wants_discount'  => $wantsDiscount,
                'status'          => $status,
                'payment_mode'    => 'system',
            ]);

            // Hand each block the next room assigned to its style. A
            // per-type cursor rather than a shared one, so two blocks of
            // different styles cannot take each other's rooms.
            $cursor = array_fill_keys(array_keys($assignments), 0);
            $bookedRooms = collect();

            foreach ($request->reservations as $block) {
                $roomType = $block['room_type'];
                $pricePerNight = (float) ($catalog[$roomType]['price'] ?? 0);
                $numSeniorsBlock = (int) ($block['num_seniors'] ?? 0);
                $meals = $block['meal'] ?? null;
                $beds = (int) ($catalog[$roomType]['beds'] ?? 1);

                $room = $assignments[$roomType][$cursor[$roomType]++];
                $bookedRooms->push($room);

                Reservation::create([
                    'booking_id'      => $booking->id,
                    'room_number'     => $room->room_number,
                    'room_type'       => $roomType,
                    'capacity'        => $beds,
                    'price'           => $pricePerNight,
                    'num_seniors'     => min($numSeniorsBlock, $beds),
                    'num_guests'      => (int) ($block['num_guests'] ?? 0),
                    'meal'            => $meals,
                ]);
            }

            // Inside the transaction, not after it. The room board
            // (App\Support\RoomBoard) reads this pivot, so a booking that
            // committed and then failed to attach would hold rooms the
            // board showed as free.
            //
            // Only the rooms actually assigned — `$lockedRooms` is now the
            // whole pool of every style asked for, and attaching that would
            // hold the entire floor.
            $roomIds = $bookedRooms->pluck('id')->unique()->all();
            if (! empty($roomIds)) {
                $booking->rooms()->attach($roomIds);
            }

            return $booking;
        });
    }
}
