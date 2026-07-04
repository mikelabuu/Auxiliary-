{{-- Cloned by booking.js for each reservation block. __INDEX__ is replaced at runtime. --}}
<div class="reservation-block border border-slate-200/70 bg-white p-6 rounded-3xl mb-5 relative shadow-[0_8px_30px_rgba(0,0,0,0.02)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] transition-all duration-300" data-index="__INDEX__">

    <!-- Block header -->
    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-lg bg-nautical-teal text-white text-[11px] font-black flex items-center justify-center block-number shadow-xs">1</span>
            <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Room Allocation</span>
        </div>
        <button type="button" class="btn-remove-block inline-flex items-center gap-1 py-1.5 px-3 rounded-xl text-[11px] font-bold bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100 hover:text-rose-700 transition-all cursor-pointer" style="display:none;">
            <span class="material-icons text-[14px]">delete_outline</span>
            Remove
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <!-- Room Type -->
        <div class="md:col-span-5">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Room Type</label>
            <select name="reservations[__INDEX__][room_type]" class="room-type-select w-full px-3 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 text-sm focus:border-nautical-teal focus:ring-2 focus:ring-nautical-teal/20 outline-none transition-all cursor-pointer font-bold shadow-xs" required>
                <option value="">Select room type...</option>
                @foreach (config('room_types', []) as $type)
                    <option value="{{ $type['id'] }}" data-beds="{{ $type['beds'] }}" data-price="{{ $type['price'] }}">{{ $type['title'] }} ({{ $type['beds'] }} pax)</option>
                @endforeach
            </select>
        </div>

        <!-- Beds capacity -->
        <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Max Capacity</label>
            <div class="relative flex items-center">
                <span class="material-icons text-slate-400 absolute left-3.5 text-[16px] pointer-events-none">bed</span>
                <input type="number" name="reservations[__INDEX__][beds]" class="res-beds w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-200/60 bg-slate-50/60 text-slate-600 text-sm outline-none font-extrabold text-center select-none cursor-default pointer-events-none shadow-xs" readonly placeholder="--">
            </div>
        </div>

        <!-- Price label -->
        <div class="md:col-span-4">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Rate / Night</label>
            <div class="relative flex items-center">
                <span class="material-icons text-nautical-teal/70 absolute left-3.5 text-[16px] pointer-events-none">payments</span>
                <input type="hidden" name="reservations[__INDEX__][price_per_night]" class="res-price-hidden">
                <input type="text" class="res-price w-full pl-10 pr-3 py-2.5 rounded-xl border border-slate-200/60 bg-sky-wash/30 text-nautical-teal text-sm outline-none font-black text-center select-none cursor-default pointer-events-none shadow-xs" readonly placeholder="P--">
            </div>
        </div>
    </div>

    <!-- Numbers allocation details -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-slate-100">
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">No. of Guests</label>
            <input type="number" name="reservations[__INDEX__][num_guests]" class="res-num-guests w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-nautical-teal focus:ring-2 focus:ring-nautical-teal/20 transition-all shadow-xs" min="1" placeholder="e.g. 2" required>
            <small class="capacity-hint text-[10px] text-slate-400 mt-1.5 block font-medium"></small>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1.5">Seniors / PWD in Room</label>
            <input type="number" name="reservations[__INDEX__][num_seniors]" class="res-num-seniors w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 outline-none focus:border-nautical-teal focus:ring-2 focus:ring-nautical-teal/20 transition-all shadow-xs" min="0" value="0">
            <small class="text-[10px] text-slate-400 mt-1.5 block font-medium">For 20% Senior/PWD discount verification</small>
        </div>
    </div>

    <!-- Meal Selection (Alpine-powered collapsible panel) -->
    <div class="mt-4" x-data="{ mealsOpen: false }">
        <button type="button" @click="mealsOpen = !mealsOpen" class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-bold bg-white hover:bg-slate-50 text-slate-700 transition-all cursor-pointer border border-slate-200 shadow-xs">
            <span class="material-icons text-[15px] text-nautical-teal">restaurant_menu</span>
            Breakfast Meal Selections
            <span class="text-slate-400 font-medium">(optional)</span>
            <span class="material-icons text-[14px] ml-1 transition-transform duration-200 text-slate-400" :class="mealsOpen ? 'rotate-180' : ''">expand_more</span>
        </button>
        <div class="mt-2.5"
             x-show="mealsOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="display: none;"
         >
            <div class="border border-slate-200/60 rounded-2xl p-5 bg-slate-50/50 space-y-3.5 shadow-inner">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-icons text-[14px] text-nautical-teal">info</span>
                    Free Breakfast (Select silog meals)
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Bangsilog</label>
                        <input type="number" name="reservations[__INDEX__][meal][bangsilog]" class="meal-qty w-full px-2 py-1.5 border border-slate-200 bg-white rounded-lg text-xs text-center font-bold text-slate-800" min="0" value="0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Tocilog</label>
                        <input type="number" name="reservations[__INDEX__][meal][tocilog]" class="meal-qty w-full px-2 py-1.5 border border-slate-200 bg-white rounded-lg text-xs text-center font-bold text-slate-800" min="0" value="0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Hotsilog</label>
                        <input type="number" name="reservations[__INDEX__][meal][hotsilog]" class="meal-qty w-full px-2 py-1.5 border border-slate-200 bg-white rounded-lg text-xs text-center font-bold text-slate-800" min="0" value="0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Spamsilog</label>
                        <input type="number" name="reservations[__INDEX__][meal][spamsilog]" class="meal-qty w-full px-2 py-1.5 border border-slate-200 bg-white rounded-lg text-xs text-center font-bold text-slate-800" min="0" value="0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-1">Tapsilog</label>
                        <input type="number" name="reservations[__INDEX__][meal][tapsilog]" class="meal-qty w-full px-2 py-1.5 border border-slate-200 bg-white rounded-lg text-xs text-center font-bold text-slate-800" min="0" value="0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Availability Check section -->
    <div class="mt-5 pt-4 border-t border-slate-100">
        <!-- Hint -->
        <p class="text-[11px] text-slate-400 font-medium mb-3 flex items-center gap-1.5">
            <span class="material-icons text-[14px] text-nautical-teal">touch_app</span>
            Select a room type above, then tap <strong class="text-slate-600 font-bold mx-0.5">Check Availability</strong> to see open rooms.
        </p>

        <!-- Room Tiles wrapper populated via AJAX -->
        <div class="room-tiles-wrapper mb-3"></div>
        <input type="hidden" name="reservations[__INDEX__][room_number]" class="res-room-number-hidden">

        <button type="button" class="btn-check-availability inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold bg-nautical-teal text-white hover:bg-nautical-teal/90 shadow-[0_4px_12px_rgba(8,78,114,0.15)] hover:shadow-[0_6px_16px_rgba(8,78,114,0.25)] transition-all cursor-pointer">
            <span class="material-icons text-[16px]">search</span>
            Check Availability
        </button>
    </div>
</div>
