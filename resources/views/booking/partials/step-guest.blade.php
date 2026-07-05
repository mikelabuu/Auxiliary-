<div class="flex items-center gap-2.5 mb-5">
    <span class="w-8 h-8 rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">person</span></span>
    <div>
        <span class="block text-[9px] font-black text-stone-400 uppercase tracking-[0.18em] leading-none">Step 2 of 3</span>
        <h4 class="text-sm font-bold text-ink tracking-tight mt-1">Personal Information</h4>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all font-semibold" required>
    </div>
    <div>
        <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all font-semibold">
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
    <div>
        <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name') }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all font-semibold" required>
    </div>
    <div>
        <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Suffix <span class="text-stone-400 font-medium normal-case">(Optional)</span></label>
        <input type="text" name="suffix" value="{{ old('suffix') }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all font-semibold">
    </div>
</div>

<div class="mt-4">
    <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5">Contact Number</label>
    <div class="relative flex items-center">
        <span class="material-icons text-stone-400 absolute left-3.5 text-[18px]">phone</span>
        <input type="tel" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all font-semibold"
               inputmode="numeric" pattern="^(09|\+639)\d{9}$" placeholder="09xxxxxxxxx" maxlength="13" required>
    </div>
</div>

<!-- Address Info using Livewire Component in Tailwind Theme -->
<div class="pt-5 mt-5 border-t border-stone-100">
    <div class="flex items-center gap-2.5 mb-5">
        <span class="w-8 h-8 rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10 flex items-center justify-center shrink-0"><span class="material-icons text-[18px]">home</span></span>
        <h4 class="text-sm font-bold text-ink tracking-tight">Home Address</h4>
    </div>
    <livewire:address-selector theme="tailwind" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-5 mt-5 border-t border-stone-100 items-center">
    <div>
        <label class="block text-xs font-bold text-stone-600 tracking-wider uppercase mb-1.5" for="expected_guests">Total Number of Guests</label>
        <div class="stepper flex items-center gap-2">
            <button type="button" class="btn-step w-10 h-10 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-stone-500 hover:bg-clsu-50 hover:border-clsu-300 hover:text-clsu-700 active:scale-95 transition-all cursor-pointer shrink-0" data-step="-1" aria-label="Fewer guests">
                <span class="material-icons text-[18px]">remove</span>
            </button>
            <input type="number" id="expected_guests" name="expected_guests" value="{{ old('expected_guests', 1) }}" class="w-full px-4 py-2.5 rounded-xl border border-stone-200 bg-stone-50/60 text-stone-800 text-sm text-center focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-all font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" max="40" required>
            <button type="button" class="btn-step w-10 h-10 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-stone-500 hover:bg-clsu-50 hover:border-clsu-300 hover:text-clsu-700 active:scale-95 transition-all cursor-pointer shrink-0" data-step="1" aria-label="More guests">
                <span class="material-icons text-[18px]">add</span>
            </button>
        </div>
    </div>
    <div class="bg-gold-soft/25 border border-gold/30 p-3 rounded-xl flex items-center justify-between sm:mt-5">
        <div>
            <span class="block text-[10px] font-bold text-stone-500 uppercase tracking-wider leading-none" id="maxSeniorsLabelDisplay">Max Seniors / PWD</span>
            <span class="text-xs text-stone-400 font-medium mt-1 block">Verification limit</span>
        </div>
        <span class="text-sm font-bold text-emerald-deep bg-cream-warm px-3 py-1 rounded-lg border border-gold/40 shadow-sm" id="maxSeniorsLabel">1</span>
    </div>
</div>

<div class="pt-5 mt-5 border-t border-stone-100 flex items-start gap-2.5">
    <input type="checkbox" id="request_discount" name="request_discount" class="w-4.5 h-4.5 mt-0.5 rounded-md border-stone-300 text-emerald-deep focus:ring-emerald focus:ring-2 cursor-pointer transition-all" value="1">
    <label for="request_discount" class="text-xs font-bold text-stone-600 cursor-pointer select-none leading-relaxed">
        I want to request a 20% discount per Senior Citizen / PWD
        <span class="block text-[11px] font-medium text-stone-400 mt-0.5">You'll upload verification documents after booking.</span>
    </label>
</div>
