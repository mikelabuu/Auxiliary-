{{-- Guest details fields — rendered inside the "Step 2 of 3" step-card in
     public/booking/checkout.blade.php (the card supplies the header).

     `$prefill` comes from BookingController::checkoutPrefill(): the phone off
     the account, the name off this guest's last booking (the only place a
     name is ever stored). old() still wins, so a rejected submission shows
     what the guest typed rather than reverting under them. --}}
@php($prefill = $prefill ?? [])

@if (array_filter($prefill ?? []))
    <p class="mb-4 flex items-start gap-2 rounded-2xl border border-gold/30 bg-gold/10 px-3.5 py-2.5 text-[11px] font-semibold text-palay-800">
        <i class="fa-solid fa-wand-magic-sparkles mt-px text-[13px]"></i>
        <span>We filled these in from your last stay. Change anything that's out of date.</span>
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

<div class="mt-4">
    <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Contact Number</label>
    <div class="relative flex items-center">
        <i class="fa-solid fa-phone text-stone-500 absolute left-3.5 text-[18px]"></i>
        <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone', $prefill['guest_phone'] ?? '') }}" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"
               inputmode="numeric" pattern="^(09|\+639)\d{9}$" placeholder="09xxxxxxxxx" maxlength="13" required>
    </div>
</div>

<!-- Address Info using Livewire Component in Tailwind Theme -->
<div class="pt-5 mt-5 border-t border-emerald-deep/10">
    <div class="flex items-center gap-2.5 mb-5">
        <span class="w-8 h-8 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0"><i class="fa-solid fa-house text-[18px]"></i></span>
        <h4 class="text-sm font-bold text-ink tracking-tight">Home Address</h4>
    </div>
    <div class="night-fields">
        <x-address-selector theme="tailwind" />
    </div>
</div>

{{-- The old "Max Seniors / PWD · verification limit" readout lived here. It
     restated the guest count back at the guest and explained nothing, so it
     was removed. Seniors are still capped per room by the room's capacity and
     by the guests assigned to it — enforced in booking.js and again in
     BookingController::store(), which is where a cap belongs. --}}
<div class="pt-5 mt-5 border-t border-emerald-deep/10">
    <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5" for="expected_guests">Total Number of Guests</label>
    <div class="stepper flex items-center gap-2 max-w-xs">
        <button type="button" class="btn-step w-10 h-10 rounded-xl border border-emerald-deep/15 bg-white/60 flex items-center justify-center text-stone-600 hover:bg-white hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="-1" aria-label="Fewer guests">
            <i class="fa-solid fa-minus text-[18px]"></i>
        </button>
        <input type="number" id="expected_guests" name="expected_guests" value="{{ old('expected_guests', 1) }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm text-center focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" max="40" required>
        <button type="button" class="btn-step w-10 h-10 rounded-xl border border-emerald-deep/15 bg-white/60 flex items-center justify-center text-stone-600 hover:bg-white hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="1" aria-label="More guests">
            <i class="fa-solid fa-plus text-[18px]"></i>
        </button>
    </div>
    <p class="text-[11px] font-medium text-stone-500 mt-1.5">Assign every guest to a room below. The totals have to match.</p>
</div>

{{-- Two things the front desk had no way of knowing until the guest walked in.
     Both optional on purpose: a forced guess about arrival time is worse than
     no answer, because the desk would plan around it. --}}
<div class="pt-5 mt-5 border-t border-emerald-deep/10 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5" for="arrival_time">
            Estimated Arrival <span class="text-stone-500 font-medium normal-case">(Optional)</span>
        </label>
        <div class="relative flex items-center">
            <i class="fa-solid fa-clock text-stone-500 absolute left-3.5 text-[18px] pointer-events-none"></i>
            <select name="arrival_time" id="arrival_time" class="w-full appearance-none pl-10 pr-9 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold cursor-pointer">
                <option value="">Not sure yet</option>
                @foreach (\App\Support\StaySchedule::arrivalSlots() as $slot => $slotLabel)
                    <option value="{{ $slot }}" @selected(old('arrival_time') === $slot)>{{ $slotLabel }}</option>
                @endforeach
            </select>
            <i class="fa-solid fa-chevron-down text-stone-500 absolute right-3.5 text-[13px] pointer-events-none"></i>
        </div>
        <p class="text-[11px] font-medium text-stone-500 mt-1.5">Check-in opens at {{ $checkinTime }}. The front desk is staffed 24/7, so a late arrival is fine. It just helps to know.</p>
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
