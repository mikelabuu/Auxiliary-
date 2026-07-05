{{-- Cloned by booking.js for each reservation block. __INDEX__ is replaced at runtime. --}}
<div class="reservation-block ring-1 ring-emerald-deep/10 bg-cream p-6 rounded-3xl relative shadow-[0_10px_30px_-24px_rgba(6,40,30,0.35)] hover:shadow-[0_16px_36px_-24px_rgba(6,40,30,0.45)] transition-all duration-300" data-index="__INDEX__">

    <!-- Block header -->
    <div class="flex items-center justify-between mb-5 pb-3 border-b border-emerald-deep/10">
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full bg-emerald-deep text-cream font-display italic text-[11px] flex items-center justify-center block-number shadow-sm">1</span>
            <span class="text-xs font-bold text-stone-700 uppercase tracking-[0.18em]">Room Allocation</span>
        </div>
        <button type="button" class="btn-remove-block inline-flex items-center gap-1 py-1.5 px-3 rounded-xl text-[11px] font-bold bg-ember-50 text-ember-600 border border-ember-200 hover:bg-ember-100 hover:text-ember-700 transition-all cursor-pointer" style="display:none;">
            <span class="material-icons text-[14px]">delete_outline</span>
            Remove
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <!-- Room Type -->
        <div class="md:col-span-5">
            <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Room Type</label>
            <select name="reservations[__INDEX__][room_type]" class="room-type-select w-full px-3 py-2.5 rounded-xl border border-stone-200 bg-white text-stone-800 text-sm focus:border-emerald focus:ring-2 focus:ring-emerald/25 outline-none transition-all cursor-pointer font-bold" required>
                <option value="">Select room type...</option>
                @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                    <option value="{{ $type['id'] }}" data-beds="{{ $type['beds'] }}" data-price="{{ $type['price'] }}">{{ $type['title'] }} ({{ $type['beds'] }} pax)</option>
                @endforeach
            </select>
        </div>

        <!-- Beds capacity -->
        <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Max Capacity</label>
            <div class="relative flex items-center">
                <span class="material-icons text-stone-400 absolute left-3.5 text-[16px] pointer-events-none">bed</span>
                <input type="number" name="reservations[__INDEX__][beds]" class="res-beds w-full pl-10 pr-3 py-2.5 rounded-xl border border-stone-200/70 bg-stone-50/70 text-stone-600 text-sm outline-none font-bold text-center select-none cursor-default pointer-events-none" readonly placeholder="--">
            </div>
        </div>

        <!-- Price label -->
        <div class="md:col-span-4">
            <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest mb-1.5">Rate / Night</label>
            <div class="relative flex items-center">
                <span class="material-icons text-emerald absolute left-3.5 text-[16px] pointer-events-none">payments</span>
                <input type="hidden" name="reservations[__INDEX__][price_per_night]" class="res-price-hidden">
                <input type="text" class="res-price w-full pl-10 pr-3 py-2.5 rounded-xl border border-gold/40 bg-gold-soft/20 text-emerald-deep text-sm outline-none font-bold text-center select-none cursor-default pointer-events-none" readonly placeholder="₱--">
            </div>
        </div>
    </div>

    <!-- Numbers allocation details -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-stone-100">
        <div>
            <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1.5">No. of Guests</label>
            <div class="stepper flex items-center gap-1.5">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-stone-500 hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-all cursor-pointer shrink-0" data-step="-1" aria-label="Fewer guests in room">
                    <span class="material-icons text-[16px]">remove</span>
                </button>
                <input type="number" name="reservations[__INDEX__][num_guests]" class="res-num-guests w-full px-2 py-2 bg-white border border-stone-200 rounded-xl text-sm font-bold text-stone-800 text-center outline-none focus:border-emerald focus:ring-2 focus:ring-emerald/25 transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" placeholder="e.g. 2" required>
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-stone-500 hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-all cursor-pointer shrink-0" data-step="1" aria-label="More guests in room">
                    <span class="material-icons text-[16px]">add</span>
                </button>
            </div>
            <small class="capacity-hint text-[10px] text-stone-400 mt-1.5 block font-medium"></small>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1.5">Seniors / PWD in Room</label>
            <div class="stepper flex items-center gap-1.5">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-stone-500 hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-all cursor-pointer shrink-0" data-step="-1" aria-label="Fewer seniors">
                    <span class="material-icons text-[16px]">remove</span>
                </button>
                <input type="number" name="reservations[__INDEX__][num_seniors]" class="res-num-seniors w-full px-2 py-2 bg-white border border-stone-200 rounded-xl text-sm font-bold text-stone-800 text-center outline-none focus:border-emerald focus:ring-2 focus:ring-emerald/25 transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="0" value="0">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-stone-200 bg-white flex items-center justify-center text-stone-500 hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-all cursor-pointer shrink-0" data-step="1" aria-label="More seniors">
                    <span class="material-icons text-[16px]">add</span>
                </button>
            </div>
            <small class="text-[10px] text-stone-400 mt-1.5 block font-medium">For 20% Senior/PWD discount verification</small>
        </div>
    </div>

    <!-- Meal Selection (Alpine-powered collapsible panel) -->
    <div class="mt-4" x-data="{ mealsOpen: false }">
        <button type="button" @click="mealsOpen = !mealsOpen" class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-bold bg-white hover:bg-stone-50 text-stone-700 transition-all cursor-pointer border border-stone-200">
            <span class="material-icons text-[15px] text-emerald">restaurant_menu</span>
            Breakfast Meal Selections
            <span class="text-stone-400 font-medium">(optional)</span>
            <span class="material-icons text-[14px] ml-1 transition-transform duration-200 text-stone-400" :class="mealsOpen ? 'rotate-180' : ''">expand_more</span>
        </button>
        <div class="mt-2.5"
             x-show="mealsOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             style="display: none;"
         >
            <div class="border border-stone-200/70 rounded-2xl p-5 bg-stone-50/50 space-y-3.5">
                <p class="text-[10px] font-bold text-stone-400 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-icons text-[14px] text-emerald">info</span>
                    Free Breakfast (select silog meals — total must equal guests)
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach (['bangsilog' => 'Bangsilog', 'tocilog' => 'Tocilog', 'hotsilog' => 'Hotsilog', 'spamsilog' => 'Spamsilog', 'tapsilog' => 'Tapsilog'] as $mealKey => $mealLabel)
                        <div>
                            <label class="block text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1 text-center">{{ $mealLabel }}</label>
                            <div class="flex items-center rounded-lg border border-stone-200 bg-white overflow-hidden">
                                <button type="button" class="btn-minus-meal w-7 h-8 flex items-center justify-center text-stone-500 hover:bg-cream-warm hover:text-emerald-deep transition-colors cursor-pointer shrink-0">
                                    <span class="material-icons text-[15px]">remove</span>
                                </button>
                                <input type="number" name="reservations[__INDEX__][meal][{{ $mealKey }}]" class="meal-qty w-full min-w-0 px-1 py-1.5 border-0 text-xs text-center font-bold text-stone-800 outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="0" value="0">
                                <button type="button" class="btn-plus-meal w-7 h-8 flex items-center justify-center text-stone-500 hover:bg-cream-warm hover:text-emerald-deep transition-colors cursor-pointer shrink-0">
                                    <span class="material-icons text-[15px]">add</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Availability Check section -->
    <div class="mt-5 pt-4 border-t border-stone-100">
        <!-- Hint -->
        <p class="text-[11px] text-stone-400 font-medium mb-3 flex items-center gap-1.5">
            <span class="material-icons text-[14px] text-emerald">touch_app</span>
            Select a room type above, then tap <strong class="text-stone-600 font-bold mx-0.5">Check Availability</strong> to see open rooms.
        </p>

        <!-- Tile status legend -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mb-2">
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-stone-400 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-emerald/10 border border-emerald/40"></span> Available</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-stone-400 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-emerald-deep"></span> Selected</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-stone-400 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-ember-50 border border-ember-200"></span> Booked</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-stone-400 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-gold-soft/60 border border-gold/50"></span> Cleaning</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-stone-400 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-stone-100 border border-stone-300"></span> Maintenance</span>
        </div>

        <!-- Room Tiles wrapper populated via AJAX -->
        <div class="room-tiles-wrapper mb-3"></div>
        <input type="hidden" name="reservations[__INDEX__][room_number]" class="res-room-number-hidden">

        <button type="button" class="btn-check-availability press focus-ring inline-flex min-h-11 items-center gap-2 px-6 py-2.5 rounded-full text-[12px] font-semibold uppercase tracking-[0.16em] bg-emerald-deep text-cream transition-all cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_22%,transparent)]">
            <span class="material-icons text-[16px]">search</span>
            Check Availability
        </button>
    </div>
</div>
