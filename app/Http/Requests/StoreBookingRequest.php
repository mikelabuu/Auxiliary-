<?php

namespace App\Http\Requests;

use App\Http\Controllers\BookingController;
use App\Rules\PersonName;
use App\Rules\PsgcCode;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The guest checkout form's contract.
 *
 * Lifted out of BookingController::store(), which had grown to 462 lines with
 * the first 85 of them being this. Nothing about the rules changed in the move
 * — the point was that store() could not be read as one thought while the
 * question "what is a valid booking?" and the question "how is a booking made
 * without selling a room twice?" shared a method.
 *
 * The ceilings still live on BookingController: the checkout view and
 * RescheduleController both read them from there, and a second home for the
 * same number is how the form and the server start disagreeing.
 */
class StoreBookingRequest extends FormRequest
{
    /**
     * The route already sits behind `auth` and `verified`, and a booking is
     * made *for* whoever is signed in — there is no per-model check to do
     * here. store() reads Auth::user() itself.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // A stay has to end somewhere. `check_out > check_in` was the only
        // bound, so a booking could run to 2040 and hold every room in the
        // house for it. The horizon matches the calendar's own 365-day window
        // — offering a date the picker cannot even show is not a feature.
        $horizon = Carbon::today()->addDays(BookingController::BOOKING_HORIZON_DAYS);
        $maxStay = Carbon::parse($this->input('check_in', 'today'))
            ->addDays(BookingController::MAX_STAY_NIGHTS);

        return [
            'first_name'      => ['required', 'string', 'max:255', new PersonName],
            // Was max:10, which silently rejected perfectly ordinary middle
            // names ("Bartholomew" is 11). Matched to the other name fields.
            'middle_name'     => ['required', 'string', 'max:255', new PersonName],
            'last_name'       => ['required', 'string', 'max:255', new PersonName],
            'suffix'          => ['nullable', 'string', 'max:255', new PersonName],
            // The form has enforced this shape all along via a pattern
            // attribute; the server took any 20 characters, so anyone posting
            // around the form could store "call me maybe" as a contact number.
            'guest_phone'     => ['required', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            // Optional, but held to the same shape as the first — a fallback
            // number nobody can dial is worse than an empty field, because the
            // desk stops looking once it sees one.
            'guest_phone_alt' => ['nullable', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/', 'different:guest_phone'],
            // The reference person: who endorsed this guest, the number to
            // reach them on, and what the stay was endorsed for. All three are
            // required on this form because the desk asked for them, but the
            // columns are nullable — walk-ins are typed at a counter and every
            // booking made before these fields existed has no answer. See the
            // migrations.
            'referred_by'     => ['required', 'string', 'max:255'],
            // Deliberately looser than guest_phone: a referrer is as likely to
            // be an office landline or an extension as a mobile, and rejecting
            // "(044) 456-0688" would push the guest into typing a mobile number
            // that is not the one the desk should ring. Digits and the
            // punctuation phone numbers are actually written with, nothing else,
            // so the column cannot become a second free-text note.
            'referred_by_phone'   => ['required', 'string', 'max:30', 'regex:/^[0-9()+\-.\s]{7,30}$/'],
            'referred_by_purpose' => ['required', 'string', 'max:255'],
            'check_in'        => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . $horizon->toDateString()],
            'check_out'       => ['required', 'date', 'after:check_in', 'before_or_equal:' . $maxStay->toDateString()],
            'expected_guests' => 'required|integer|min:1|max:' . BookingController::MAX_GUESTS_PER_BOOKING,
            // Optional, but if given it has to be a real clock time — the
            // front desk plans the evening around this.
            'arrival_time'     => 'nullable|date_format:H:i',
            'special_requests' => 'nullable|string|max:500',
            // `accepted` rather than `boolean`: an unticked box posts nothing
            // at all, and `boolean` would happily pass a missing field.
            'accept_terms'     => 'accepted',
            'reservations'    => 'required|array|min:1|max:' . BookingController::MAX_ROOMS_PER_BOOKING,
            'reservations.*.room_type'       => 'required|string',
            // No room_number. A guest chooses a room *style* and how many of
            // them; which physical rooms those are is assigned here, from what
            // is actually free, inside the same locked transaction that checks
            // the dates. Letting the client name the rooms meant the booking
            // form had to publish the whole floor plan and its occupancy to
            // anyone who opened it, and gave the desk no room to group a party
            // together or hold a quiet wing back. Only staff pick numbers now
            // (manual booking and walk-in still do).
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
            // Posted as "CODE|NAME". Only the NAME half was ever read, so a
            // malformed value stored an address with a level missing and said
            // nothing about it. PsgcCode checks the shape and that the code is
            // a real place; the names below are then read from the gazetteer
            // rather than taken from the client.
            'region_code' => ['required', 'string', 'max:255', new PsgcCode('regions')],
            'province_code' => ['nullable', 'string', 'max:255', new PsgcCode('provinces')],
            'city_code' => ['required', 'string', 'max:255', new PsgcCode('cities')],
            'barangay_code' => ['required', 'string', 'max:255', new PsgcCode('barangays')],
        ];
    }

    public function messages(): array
    {
        return [
            'guest_phone.regex'        => 'Enter a Philippine mobile number as 09xxxxxxxxx or +639xxxxxxxxx.',
            'guest_phone_alt.regex'    => 'Enter the second number as 09xxxxxxxxx or +639xxxxxxxxx, or leave it blank.',
            'guest_phone_alt.different' => 'The second contact number has to be a different number from the first.',
            'referred_by.required'     => 'Tell us which office or person is endorsing this stay — or say you are booking for yourself.',
            'referred_by_phone.required' => 'Give us a number for the person or office endorsing this stay. If that is you, your own number is the right answer.',
            'referred_by_phone.regex'    => 'Enter the reference number as digits — a mobile (09xxxxxxxxx) or a landline like (044) 456-0688.',
            'referred_by_purpose.required' => 'Say what this stay is for — a seminar, an OJT deployment, official travel, a personal visit.',
            'check_in.before_or_equal' => 'We only take bookings up to ' . BookingController::BOOKING_HORIZON_DAYS . ' days ahead.',
            'check_out.before_or_equal' => 'A single stay can run at most ' . BookingController::MAX_STAY_NIGHTS . ' nights. Please contact us for longer stays.',
            'reservations.*.num_guests.required' => 'Say how many guests are staying in each room you picked.',
            'reservations.*.num_guests.min'      => 'Each room you pick needs at least one guest in it.',
            'accept_terms.accepted'    => 'Please agree to the booking terms before confirming.',
            'arrival_time.date_format' => 'Choose an arrival time from the list, or leave it as "Not sure yet".',
        ];
    }
}
