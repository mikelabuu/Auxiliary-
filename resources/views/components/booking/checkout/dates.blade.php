@props([
    'checkIn' => null,
    'checkOut' => null,
])

<div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/60">
    <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
        <span class="w-1 h-4 rounded-full bg-[#0a4f2d]"></span>
        <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Stay Dates</h4>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Check-in</label>
            <input type="text" id="check_in" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none font-semibold cursor-pointer" placeholder="Select Date" value="{{ $checkIn ?? '' }}">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 tracking-wider uppercase mb-1.5">Check-out</label>
            <input type="text" id="check_out" class="flatpickr-date w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-[#0a4f2d] focus:ring-2 focus:ring-[#0a4f2d]/20 outline-none font-semibold cursor-pointer" placeholder="Select Date" value="{{ $checkOut ?? '' }}">
        </div>
    </div>
</div>