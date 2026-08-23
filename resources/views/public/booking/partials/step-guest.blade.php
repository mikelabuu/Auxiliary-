{{-- Guest details fields — rendered inside the "Step 2 of 3" step-card in
     public/booking/checkout.blade.php (the card supplies the header).

     `$prefill` comes from BookingController::checkoutPrefill(): the phone off
     the account, the name off the last booking or — for a first-timer who has
     none — off the name they gave at signup, and the address off the codes
     the last booking saved back to the account. old() still wins, so a
     rejected submission shows what the guest typed rather than reverting
     under them. --}}
@php
    $prefill = $prefill ?? [];

    // Only the flat fields decide whether the banner is worth showing.
    // `address` is a nested array that is present either way, so counting it
    // would put the banner on every checkout including a first-timer's.
    $prefilled = array_filter(\Illuminate\Support\Arr::except($prefill, ['address']));
@endphp

@if ($prefilled)
    <p class="mb-4 flex items-start gap-2 rounded-2xl border border-gold/30 bg-gold/10 px-3.5 py-2.5 text-[11px] font-semibold text-palay-800">
        <x-booking.ui.icon-solid name="wand-magic-sparkles" class="mt-px text-[13px]" />
        <span>We filled these in from your account. Change anything that's out of date.</span>
    </p>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label for="guest-first-name" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">First Name</label>
        <input type="text" name="first_name" id="guest-first-name" value="{{ old('first_name', $prefill['first_name'] ?? '') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold" required>
    </div>
    <div>
        {{-- Server-side this has always been required; the input was not, so a
             guest who left it blank got a red banner after a full page round
             trip instead of a hint while they were still standing in the
             field. Only the "(Optional)" fields are optional now. --}}
        <label for="guest-middle-name" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Middle Name</label>
        <input type="text" name="middle_name" id="guest-middle-name" value="{{ old('middle_name', $prefill['middle_name'] ?? '') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold" required>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label for="guest-last-name" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Last Name</label>
        <input type="text" name="last_name" id="guest-last-name" value="{{ old('last_name', $prefill['last_name'] ?? '') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold" required>
    </div>
    <div>
        <label for="guest-suffix" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Suffix <span class="text-stone-500 font-medium normal-case">(Optional)</span></label>
        <input type="text" name="suffix" id="guest-suffix" value="{{ old('suffix', $prefill['suffix'] ?? '') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold">
    </div>
</div>

{{-- Two numbers, not one. A single contact is a single point of failure on
     the day it matters — the phone is off, or it is in the room the desk is
     ringing about. The second is optional: a guest who genuinely has only one
     number should not be blocked from booking over it. --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label for="guest_phone" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Contact Number</label>
        <div class="relative flex items-center">
            <x-booking.ui.icon-solid name="phone" class="text-stone-500 absolute left-3.5 text-[18px]" />
            <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone', $prefill['guest_phone'] ?? '') }}" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"
                   inputmode="numeric" pattern="^(09|\+639)\d{9}$" placeholder="09xxxxxxxxx" maxlength="13" required>
        </div>
    </div>
    <div>
        <label for="guest_phone_alt" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">
            Second Contact Number <span class="text-stone-500 font-medium normal-case">(Optional)</span>
        </label>
        <div class="relative flex items-center">
            <x-booking.ui.icon-solid name="phone-volume" class="text-stone-500 absolute left-3.5 text-[18px]" />
            <input type="tel" name="guest_phone_alt" id="guest_phone_alt" value="{{ old('guest_phone_alt', $prefill['guest_phone_alt'] ?? '') }}" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"
                   inputmode="numeric" pattern="^(09|\+639)\d{9}$" placeholder="09xxxxxxxxx" maxlength="13">
        </div>
        <p class="text-[11px] font-medium text-stone-500 mt-1.5">Someone else we can reach if we can't get you.</p>
    </div>

    {{-- Reference person.
         A lot of stays here are arranged on somebody's word — a department
         booking a visiting lecturer, an office putting up a contractor — and
         the desk needs to know whose. A name alone turned out to be half an
         answer, though: at 9pm, faced with a guest describing an arrangement
         nobody at the counter has heard of, what the desk actually needs is a
         number to ring and what the stay was endorsed for. So it is three
         fields, not one.

         All three are required, with no self-booking escape hatch. The obvious
         alternative — let the number and purpose go blank when the name says
         "Booking for myself" — needs the server to decide what counts as
         saying so, and that is string matching on free text: "booking for my
         self" would be blocked and "Booking for myself" would pass, for no
         reason the guest could see. A guest nobody sent still has a number and
         still has a reason for coming, so asking for them costs a guest with
         nothing to hide nothing, and the desk gets a field it can rely on. --}}
    <div class="sm:col-span-2 pt-5 mt-1 border-t border-emerald-deep/10">
        <div class="flex items-center gap-2.5 mb-4">
            <span class="w-8 h-8 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0"><x-booking.ui.icon-solid name="user-tie" class="text-[17px]" /></span>
            <div>
                <h4 class="text-sm font-bold text-ink tracking-tight">Reference Person</h4>
                <p class="text-[11px] font-medium text-stone-500">Who is endorsing this stay, and what for.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="referred_by" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">
                    Name
                </label>
                <div class="relative flex items-center">
                    <x-booking.ui.icon-solid name="user" class="text-stone-500 absolute left-3.5 text-[18px]" />
                    <input type="text" name="referred_by" id="referred_by" value="{{ old('referred_by') }}" maxlength="255" required
                           list="referredBySuggestions"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"
                           placeholder="Office or person endorsing you — or “Booking for myself”">
                    <datalist id="referredBySuggestions">
                        <option value="Booking for myself"></option>
                    </datalist>
                </div>
                <p class="text-[11px] font-medium text-stone-500 mt-1.5">The CLSU office or staff member endorsing this stay. Nobody sent you? Say “Booking for myself” and use your own details below.</p>
            </div>
            <div>
                <label for="referred_by_phone" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">
                    Contact Number
                </label>
                <div class="relative flex items-center">
                    <x-booking.ui.icon-solid name="phone" class="text-stone-500 absolute left-3.5 text-[18px]" />
                    <input type="tel" name="referred_by_phone" id="referred_by_phone" value="{{ old('referred_by_phone') }}" maxlength="30" required
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"
                           placeholder="09xxxxxxxxx or office landline">
                </div>
                <p class="text-[11px] font-medium text-stone-500 mt-1.5">A number the front desk can ring to confirm the endorsement. An office landline is fine.</p>
            </div>
            <div class="sm:col-span-2">
                <label for="referred_by_purpose" class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">
                    Purpose
                </label>
                <div class="relative flex items-center">
                    <x-booking.ui.icon-solid name="clipboard-list" class="text-stone-500 absolute left-3.5 text-[18px]" />
                    <input type="text" name="referred_by_purpose" id="referred_by_purpose" value="{{ old('referred_by_purpose') }}" maxlength="255" required
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"
                           placeholder="Seminar resource speaker, OJT deployment, official travel…">
                </div>
                <p class="text-[11px] font-medium text-stone-500 mt-1.5">What the stay is for. This is what the desk reads when your arrangement needs checking.</p>
            </div>
        </div>
    </div>
</div>

<!-- Address Info using Livewire Component in Tailwind Theme -->
<div class="pt-5 mt-5 border-t border-emerald-deep/10">
    <div class="flex items-center gap-2.5 mb-5">
        <span class="w-8 h-8 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0"><x-booking.ui.icon-solid name="house" class="text-[18px]" /></span>
        <h4 class="text-sm font-bold text-ink tracking-tight">Home Address</h4>
    </div>
    <div class="night-fields">
        <x-address-selector theme="tailwind" :saved="$prefill['address'] ?? []" />
    </div>
</div>

{{-- The party size used to sit here, two cards above the rooms it fills.
     That split was the whole problem: step 3 had no way to talk about the
     number except by telling the guest to scroll back up and edit it — and
     the two counts then had to be reconciled by hand, with a meter, a fix
     button and five separate messages saying the same thing. It now lives at
     the top of step 3, directly above the room picker, where the number and
     what it does to the rooms are one glance apart. --}}

{{-- Two things the front desk had no way of knowing until the guest walked in.
     Both optional on purpose: a forced guess about arrival time is worse than
     no answer, because the desk would plan around it. --}}
<div class="pt-5 mt-5 border-t border-emerald-deep/10 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5" for="arrival_time">
            Estimated Arrival <span class="text-stone-500 font-medium normal-case">(Optional)</span>
        </label>
        <div class="relative flex items-center">
            <x-booking.ui.icon-solid name="clock" class="text-stone-500 absolute left-3.5 text-[18px] pointer-events-none" />
            <select name="arrival_time" id="arrival_time" class="w-full appearance-none pl-10 pr-9 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold cursor-pointer">
                <option value="">Not sure yet</option>
                @foreach (\App\Support\StaySchedule::arrivalSlots() as $slot => $slotLabel)
                    {{-- Slots before check-in are labelled as requests, because
                         that is what they are: the room may still have last
                         night's guest in it until {{ $checkoutTime }}. --}}
                    <option value="{{ $slot }}" @selected(old('arrival_time') === $slot)>
                        {{ $slotLabel }}@if(\App\Support\StaySchedule::isEarlyArrival($slot)) — early check-in (on request)@endif
                    </option>
                @endforeach
            </select>
            <x-booking.ui.icon-solid name="chevron-down" class="text-stone-500 absolute right-3.5 text-[13px] pointer-events-none" />
        </div>
        <p class="text-[11px] font-medium text-stone-500 mt-1.5">Check-in opens at {{ $checkinTime }}. The front desk is staffed 24/7, so a late arrival is fine. It just helps to know.</p>
        <p id="earlyCheckinNote" class="hidden text-[11px] font-semibold text-palay-800 mt-1.5 leading-relaxed">
            <x-booking.ui.icon-solid name="circle-info" class="text-[12px]" />
            Early check-in is a request, not a guarantee — the room has to be vacated and cleaned first ({{ $checkoutTime }} check-out). We'll hold your things at the desk if it isn't ready.
        </p>
    </div>
    <div>
        <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5" for="special_requests">
            Special Requests <span class="text-stone-500 font-medium normal-case">(Optional)</span>
        </label>
        <textarea name="special_requests" id="special_requests" rows="3" maxlength="500"
                  class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold resize-y"
                  placeholder="Ground floor, travelling with an elderly parent, allergies…">{{ old('special_requests') }}</textarea>
        <p class="text-[11px] font-medium text-stone-500 mt-1.5">We'll do our best, though requests aren't guaranteed and don't change your rate.</p>
    </div>
</div>

<div class="pt-5 mt-5 border-t border-emerald-deep/10 flex items-start gap-2.5">
    <input type="checkbox" id="request_discount" name="request_discount" class="w-4.5 h-4.5 mt-0.5 rounded-md border-emerald-deep/25 bg-white/60 text-palay-800 focus:ring-gold focus:ring-2 cursor-pointer transition-[color,background-color,border-color,box-shadow]" value="1">
    <label for="request_discount" class="text-xs font-bold text-stone-600 cursor-pointer select-none leading-relaxed">
        I want to request a 20% discount per Senior Citizen / PWD
        <span class="block text-[11px] font-medium text-stone-500 mt-0.5">You'll upload verification documents after booking.</span>
    </label>
</div>
