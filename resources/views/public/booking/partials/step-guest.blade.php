{{-- Guest details fields — rendered inside the "Step 2 of 3" step-card in
     public/booking/checkout.blade.php (the card supplies the header). --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold" required>
    </div>
    <div>
        <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold" required>
    </div>
    <div>
        <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Suffix <span class="text-stone-500 font-medium normal-case">(Optional)</span></label>
        <input type="text" name="suffix" value="{{ old('suffix') }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold">
    </div>
</div>

<div class="mt-4">
    <label class="block text-xs font-bold text-stone-500 tracking-wider uppercase mb-1.5">Contact Number</label>
    <div class="relative flex items-center">
        <span class="material-icons text-stone-500 absolute left-3.5 text-[18px]">phone</span>
        <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm placeholder:text-stone-400 focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-semibold"
               inputmode="numeric" pattern="^(09|\+639)\d{9}$" placeholder="09xxxxxxxxx" maxlength="13" required>
    </div>
</div>

<!-- Address Info using Livewire Component in Tailwind Theme -->
<div class="pt-5 mt-5 border-t border-emerald-deep/10">
    <div class="flex items-center gap-2.5 mb-5">
        <span class="w-8 h-8 rounded-xl bg-gold/10 text-palay-800 ring-1 ring-gold/25 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">home</span></span>
        <h4 class="text-sm font-bold text-ink tracking-tight">Home Address</h4>
    </div>
    <div class="night-fields">
        <livewire:address-selector theme="tailwind" />
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
            <span class="material-icons text-[18px]">remove</span>
        </button>
        <input type="number" id="expected_guests" name="expected_guests" value="{{ old('expected_guests', 1) }}" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-white/60 text-ink text-sm text-center focus:bg-white focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" max="40" required>
        <button type="button" class="btn-step w-10 h-10 rounded-xl border border-emerald-deep/15 bg-white/60 flex items-center justify-center text-stone-600 hover:bg-white hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="1" aria-label="More guests">
            <span class="material-icons text-[18px]">add</span>
        </button>
    </div>
    <p class="text-[11px] font-medium text-stone-500 mt-1.5">Assign every guest to a room below — the totals have to match.</p>
</div>

<div class="pt-5 mt-5 border-t border-emerald-deep/10 flex items-start gap-2.5">
    <input type="checkbox" id="request_discount" name="request_discount" class="w-4.5 h-4.5 mt-0.5 rounded-md border-emerald-deep/25 bg-white/60 text-palay-800 focus:ring-gold focus:ring-2 cursor-pointer transition-[color,background-color,border-color,box-shadow]" value="1">
    <label for="request_discount" class="text-xs font-bold text-stone-600 cursor-pointer select-none leading-relaxed">
        I want to request a 20% discount per Senior Citizen / PWD
        <span class="block text-[11px] font-medium text-stone-500 mt-0.5">You'll upload verification documents after booking.</span>
    </label>
</div>
