<div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
    <span class="w-1 h-4 rounded-full bg-nautical-teal"></span>
    <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Dates and Allocations</h4>
</div>

<p class="text-xs font-semibold text-slate-500 bg-slate-50 border border-slate-100 p-3.5 rounded-xl flex items-center gap-2 mb-4">
    <span class="material-icons text-[18px] text-nautical-teal">schedule</span>
    Check-in time: 2:00 PM &middot; Check-out time: 12:00 NN
</p>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Check-in Date</label>
        <div class="relative flex items-center">
            <span class="material-icons text-slate-400 absolute left-3.5 text-[18px]">calendar_today</span>
            <input type="text" name="check_in" id="check_in" @change="checkIn = $el.value" class="flatpickr-date w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-nautical-teal focus:ring-2 focus:ring-nautical-teal/20 outline-none transition-all font-semibold cursor-pointer" required placeholder="YYYY-MM-DD">
        </div>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Check-out Date</label>
        <div class="relative flex items-center">
            <span class="material-icons text-slate-400 absolute left-3.5 text-[18px]">calendar_today</span>
            <input type="text" name="check_out" id="check_out" @change="checkOut = $el.value" class="flatpickr-date w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-nautical-teal focus:ring-2 focus:ring-nautical-teal/20 outline-none transition-all font-semibold cursor-pointer" required placeholder="YYYY-MM-DD">
        </div>
    </div>
</div>

<!-- Hint text -->
<p class="text-[11px] text-slate-400 font-medium flex items-start gap-1.5 mt-3 mb-2 px-1">
    <span class="material-icons text-[14px] text-nautical-teal flex-shrink-0 mt-0.5">info</span>
    Select your dates first, then choose a room type below. Room availability will be checked automatically.
</p>

<!-- Dynamic reservation blocks container -->
<div id="reservationBlocksContainer" class="pt-2 space-y-4"></div>

<!-- Room tile color legend -->
<div class="bg-slate-50/80 border border-slate-100 p-4 rounded-2xl mt-4">
    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Room Status Legend</span>
    <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs font-semibold text-slate-500">
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-emerald-50 border border-emerald-200 inline-block flex-shrink-0"></span>
            Available
        </span>
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-nautical-teal inline-block border border-nautical-teal/20 flex-shrink-0 shadow-sm"></span>
            Selected
        </span>
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-rose-50 border border-rose-200 inline-block flex-shrink-0"></span>
            Booked
        </span>
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-amber-50 border border-amber-200 inline-block flex-shrink-0"></span>
            Cleaning
        </span>
        <span class="flex items-center gap-2">
            <span class="w-4 h-4 rounded bg-slate-100 border border-slate-200 inline-block flex-shrink-0"></span>
            Maintenance
        </span>
    </div>
</div>

<div class="pt-3">
    <button type="button" id="btnAddRoom" class="inline-flex items-center gap-2 px-5 py-3 rounded-full text-xs font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300 shadow-sm transition-all cursor-pointer">
        <span class="material-icons text-[16px] text-nautical-teal">add</span> Add Another Room
    </button>
</div>
