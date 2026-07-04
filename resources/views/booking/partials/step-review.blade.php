<div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
    <span class="w-1 h-4 rounded-full bg-nautical-teal"></span>
    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Review Your Reservation</h4>
</div>

{{-- Stay summary banner --}}
<div class="bg-slate-50 border border-slate-200/80 rounded-2xl px-5 py-4 flex flex-wrap gap-5 sm:gap-10 mb-5 shadow-xs">
    <div class="flex items-center gap-3 min-w-0">
        <div class="w-9 h-9 rounded-xl bg-sky-wash text-nautical-teal flex items-center justify-center shadow-inner">
            <span class="material-icons text-[18px]">login</span>
        </div>
        <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Check-in</p>
            <p class="text-[14px] font-extrabold text-slate-700 mt-1" x-text="checkIn || 'Not set'">--</p>
        </div>
    </div>
    <div class="flex items-center gap-3 min-w-0">
        <div class="w-9 h-9 rounded-xl bg-sky-wash text-nautical-teal flex items-center justify-center shadow-inner">
            <span class="material-icons text-[18px]">logout</span>
        </div>
        <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Check-out</p>
            <p class="text-[14px] font-extrabold text-slate-700 mt-1" x-text="checkOut || 'Not set'">--</p>
        </div>
    </div>
    <div class="flex items-center gap-3 min-w-0">
        <div class="w-9 h-9 rounded-xl bg-sky-wash text-nautical-teal flex items-center justify-center shadow-inner">
            <span class="material-icons text-[18px]">people</span>
        </div>
        <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none">Total Guests</p>
            <p class="text-[14px] font-extrabold text-slate-700 mt-1"><span x-text="expectedGuests">0</span> Pax</p>
        </div>
    </div>
</div>

{{-- Dynamic booking summary -- populated by JS generateBookingSummary() --}}
<div id="bookingSummaryContainer" class="mb-4">
    {{-- JS will inject room-by-room rows + grand total here --}}
    <div class="text-center py-8 text-slate-400">
        <span class="material-icons text-[36px] opacity-40 block mb-2">receipt_long</span>
        <p class="text-xs font-semibold">Loading booking summary...</p>
    </div>
</div>

{{-- Policies reminder --}}
<div class="bg-amber-50/80 border border-amber-100 rounded-2xl px-5 py-4 shadow-xs">
    <div class="flex items-start gap-3 mb-4">
        <span class="material-icons text-amber-500 text-[20px] mt-0.5">policy</span>
        <div>
            <h5 class="text-sm font-extrabold text-slate-800">Before you confirm</h5>
            <p class="text-xs font-semibold text-slate-500 mt-1 leading-relaxed">Please verify room types, guest counts, meal allocations, and dates. Reservations cannot be changed after submission.</p>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-[11px] font-bold text-amber-800">
        <span class="flex items-center gap-2 bg-amber-100/50 rounded-xl px-3 py-2 border border-amber-200/40">
            <span class="material-icons text-[14px] text-amber-500">schedule</span> Check-in from 2:00 PM
        </span>
        <span class="flex items-center gap-2 bg-amber-100/50 rounded-xl px-3 py-2 border border-amber-200/40">
            <span class="material-icons text-[14px] text-amber-550">timelapse</span> Check-out by 12:00 NN
        </span>
        <span class="flex items-center gap-2 bg-amber-100/50 rounded-xl px-3 py-2 border border-amber-200/40">
            <span class="material-icons text-[14px] text-amber-550">no_food</span> No outside food
        </span>
    </div>
</div>
