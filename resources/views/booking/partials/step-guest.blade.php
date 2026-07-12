<div class="flex items-center gap-2.5 mb-5">
    <span class="w-8 h-8 rounded-xl bg-gold/10 text-gold ring-1 ring-gold/25 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">person</span></span>
    <div>
        <span class="block text-[9px] font-black text-ink/45 uppercase tracking-[0.18em] leading-none">Step 2 of 3</span>
        <h4 class="text-sm font-bold text-ink tracking-tight mt-1">Personal Information</h4>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-ink/60 tracking-wider uppercase mb-1.5">First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 text-ink text-sm placeholder:text-ink/35 focus:bg-white/10 focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-all font-semibold" required>
    </div>
    <div>
        <label class="block text-xs font-bold text-ink/60 tracking-wider uppercase mb-1.5">Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 text-ink text-sm placeholder:text-ink/35 focus:bg-white/10 focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-all font-semibold">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-xs font-bold text-ink/60 tracking-wider uppercase mb-1.5">Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 text-ink text-sm placeholder:text-ink/35 focus:bg-white/10 focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-all font-semibold" required>
    </div>
    <div>
        <label class="block text-xs font-bold text-ink/60 tracking-wider uppercase mb-1.5">Suffix <span class="text-ink/40 font-medium normal-case">(Optional)</span></label>
        <input type="text" name="suffix" value="{{ old('suffix') }}" class="w-full px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 text-ink text-sm placeholder:text-ink/35 focus:bg-white/10 focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-all font-semibold">
    </div>
</div>

<div class="mt-4">
    <label class="block text-xs font-bold text-ink/60 tracking-wider uppercase mb-1.5">Contact Number</label>
    <div class="relative flex items-center">
        <span class="material-icons text-ink/40 absolute left-3.5 text-[18px]">phone</span>
        <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-white/10 bg-white/5 text-ink text-sm placeholder:text-ink/35 focus:bg-white/10 focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-all font-semibold"
               inputmode="numeric" pattern="^(09|\+639)\d{9}$" placeholder="09xxxxxxxxx" maxlength="13" required>
    </div>
</div>

<!-- Address Info using Livewire Component in Tailwind Theme -->
<div class="pt-5 mt-5 border-t border-white/10">
    <div class="flex items-center gap-2.5 mb-5">
        <span class="w-8 h-8 rounded-xl bg-gold/10 text-gold ring-1 ring-gold/25 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">home</span></span>
        <h4 class="text-sm font-bold text-ink tracking-tight">Home Address</h4>
    </div>
    <div class="night-fields">
        <livewire:address-selector theme="tailwind" />
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-5 mt-5 border-t border-white/10 items-center">
    <div>
        <label class="block text-xs font-bold text-ink/60 tracking-wider uppercase mb-1.5" for="expected_guests">Total Number of Guests</label>
        <div class="stepper flex items-center gap-2">
            <button type="button" class="btn-step w-10 h-10 rounded-xl border border-white/12 bg-white/5 flex items-center justify-center text-ink/70 hover:bg-white/10 hover:border-gold/50 hover:text-ink active:scale-95 transition-all cursor-pointer shrink-0" data-step="-1" aria-label="Fewer guests">
                <span class="material-icons text-[18px]">remove</span>
            </button>
            <input type="number" id="expected_guests" name="expected_guests" value="{{ old('expected_guests', 1) }}" class="w-full px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 text-ink text-sm text-center focus:bg-white/10 focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-all font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" max="40" required>
            <button type="button" class="btn-step w-10 h-10 rounded-xl border border-white/12 bg-white/5 flex items-center justify-center text-ink/70 hover:bg-white/10 hover:border-gold/50 hover:text-ink active:scale-95 transition-all cursor-pointer shrink-0" data-step="1" aria-label="More guests">
                <span class="material-icons text-[18px]">add</span>
            </button>
        </div>
    </div>
    <div class="bg-gold/10 border border-gold/30 p-3 rounded-xl flex items-center justify-between sm:mt-5">
        <div>
            <span class="block text-[10px] font-bold text-ink/60 uppercase tracking-wider leading-none" id="maxSeniorsLabelDisplay">Max Seniors / PWD</span>
            <span class="text-xs text-ink/45 font-medium mt-1 block">Verification limit</span>
        </div>
        <span class="text-sm font-bold text-gold bg-white/5 px-3 py-1 rounded-lg border border-gold/40" id="maxSeniorsLabel">1</span>
    </div>
</div>

<div class="pt-5 mt-5 border-t border-white/10 flex items-start gap-2.5">
    <input type="checkbox" id="request_discount" name="request_discount" class="w-4.5 h-4.5 mt-0.5 rounded-md border-white/25 bg-white/5 text-gold focus:ring-gold focus:ring-2 cursor-pointer transition-all" value="1">
    <label for="request_discount" class="text-xs font-bold text-ink/75 cursor-pointer select-none leading-relaxed">
        I want to request a 20% discount per Senior Citizen / PWD
        <span class="block text-[11px] font-medium text-ink/45 mt-0.5">You'll upload verification documents after booking.</span>
    </label>
</div>
