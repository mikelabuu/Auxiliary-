<div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/60">
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
        <span class="w-1 h-4 rounded-full bg-[#0a4f2d]"></span>
        <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Personal Information</h4>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">First Name</label>
            <input type="text" name="first_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none transition-all font-semibold" required>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Middle Name</label>
            <input type="text" name="middle_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none transition-all font-semibold">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Last Name</label>
            <input type="text" name="last_name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none transition-all font-semibold" required>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Suffix <span class="text-slate-400 font-medium">(Optional)</span></label>
            <input type="text" name="suffix" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none transition-all font-semibold">
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Contact Number</label>
        <div class="relative flex items-center">
            <span class="material-icons text-slate-400 absolute left-3.5 text-[18px]">phone</span>
            <input type="tel" name="guest_phone" id="guest_phone" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none transition-all font-semibold"
                   inputmode="numeric" pattern="^(09|\+639)\d{9}$" placeholder="09xxxxxxxxx" maxlength="13" required>
        </div>
    </div>

    <div class="pt-3 border-t border-slate-100">
        <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
            <span class="w-1 h-4 rounded-full bg-[#0a4f2d]"></span>
            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Home Address</h4>
        </div>
        <livewire:address-selector theme="tailwind" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100 items-center">
        <div>
            <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5" for="expected_guests">Total Number of Guests</label>
            <div class="relative flex items-center">
                <span class="material-icons text-slate-400 absolute left-3.5 text-[18px]">people</span>
                <input type="number" id="expected_guests" name="expected_guests" @input="expectedGuests = $el.value" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none transition-all font-semibold" min="1" value="1" required>
            </div>
        </div>
        <div class="bg-slate-50 border border-slate-100 p-3 rounded-xl flex items-center justify-between sm:mt-5">
            <div>
                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-wider leading-none" id="maxSeniorsLabelDisplay">Max Seniors / PWD</span>
                <span class="text-xs text-slate-400 font-medium mt-1 block">Verification limit</span>
            </div>
            <span class="text-sm font-black text-[#0a4f2d] bg-emerald-50 px-3 py-1 rounded-lg border border-[#0a4f2d]/15 shadow-sm" id="maxSeniorsLabel">1</span>
        </div>
    </div>

    <div class="pt-4 border-t border-slate-100 flex items-center gap-2.5">
        <input type="checkbox" id="request_discount" name="request_discount" class="w-4.5 h-4.5 rounded-md border-slate-300 text-[#0a4f2d] focus:ring-[#0a4f2d]/30 focus:ring-2 cursor-pointer transition-all" value="1">
        <label for="request_discount" class="text-xs font-bold text-slate-600 cursor-pointer select-none">
            I want to request a 20% discount per Senior citizen / PWD
        </label>
    </div>
</div>