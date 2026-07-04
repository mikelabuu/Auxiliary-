@props([
    'roomTypes' => [],
])

<div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/60">
    <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-2">
        <div class="flex items-center gap-2">
            <span class="w-1 h-4 rounded-full bg-[#0a4f2d]"></span>
            <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider">Room Allocation</h4>
        </div>
        <button type="button" onclick="window.addReservationBlock()" class="text-xs font-bold text-[#0a4f2d] bg-emerald-50 px-3 py-1.5 rounded-lg border border-[#0a4f2d]/20 hover:bg-[#0a4f2d] hover:text-white transition-colors">
            + Add Room Type
        </button>
    </div>
    <p class="text-sm text-slate-500 font-medium mb-4">Please configure the rooms you want to book. You must select specific room numbers for each type.</p>

    <div id="reservationBlocks" class="space-y-4"></div>
</div>