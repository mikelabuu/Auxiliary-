{{-- Cloned by booking.js for each reservation block. __INDEX__ is replaced at runtime. --}}
<div class="reservation-block ring-1 ring-white/10 bg-white/[0.03] p-6 rounded-3xl relative transition-all duration-300 hover:ring-white/15" data-index="__INDEX__">

    <!-- Block header -->
    <div class="flex items-center justify-between mb-5 pb-3 border-b border-white/10">
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full bg-gold text-night font-display italic text-[11px] flex items-center justify-center block-number shadow-sm">1</span>
            <span class="text-xs font-bold text-ink/70 uppercase tracking-[0.18em]">Room Allocation</span>
        </div>
        <button type="button" class="btn-remove-block inline-flex items-center gap-1 py-1.5 px-3 rounded-xl text-[11px] font-bold bg-ember-600/15 text-ember-200 border border-ember-600/40 hover:bg-ember-600/25 transition-all cursor-pointer" style="display:none;">
            <span class="material-icons text-[14px]">delete_outline</span>
            Remove
        </button>
    </div>

    <!-- Room Type — visual picker cards. The hidden select keeps the
         form-name contract with booking.js and the backend. -->
    <div>
        <label class="block text-[10px] font-bold text-ink/45 uppercase tracking-widest mb-2">Choose a Room Style</label>
        <select name="reservations[__INDEX__][room_type]" class="room-type-select hidden" tabindex="-1" aria-hidden="true">
            <option value="">Select room type...</option>
            @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                <option value="{{ $type['id'] }}" data-beds="{{ $type['beds'] }}" data-price="{{ $type['price'] }}">{{ $type['title'] }} ({{ $type['beds'] }} pax)</option>
            @endforeach
        </select>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                <button type="button" data-type-value="{{ $type['id'] }}" data-type-title="{{ $type['title'] }}"
                        class="type-card group relative cursor-pointer overflow-hidden rounded-2xl border border-white/10 bg-white/[0.05] text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-gold hover:shadow-[0_16px_36px_-20px_rgba(0,0,0,0.8)]">
                    <span class="block h-20 w-full overflow-hidden sm:h-24">
                        <img src="{{ asset($type['image']) }}" alt="{{ $type['title'] }}" loading="lazy"
                             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </span>
                    {{-- Per-type availability badge for the chosen dates; filled by booking.js --}}
                    <span data-type-avail class="type-card-avail hidden"></span>
                    <span class="type-card-check absolute right-2 top-2 hidden h-6 w-6 place-items-center rounded-full bg-gold text-night shadow-md ring-2 ring-night">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span class="block px-3 py-2.5">
                        <span class="block truncate text-xs font-bold text-ink">{{ $type['title'] }}</span>
                        <span class="mt-0.5 block text-[11px] font-bold text-gold tabnum">₱{{ number_format($type['price']) }}<span class="font-medium text-ink/45"> / night · sleeps {{ $type['beds'] }}</span></span>
                    </span>
                </button>
            @endforeach
        </div>

        <!-- Selected-type recap consumed and filled by booking.js -->
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <input type="hidden" name="reservations[__INDEX__][beds]" class="res-beds">
            <input type="hidden" name="reservations[__INDEX__][price_per_night]" class="res-price-hidden">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-gold/40 bg-gold/10 px-3.5 py-1.5 text-[11px] font-bold text-gold">
                <span class="material-icons text-[14px]">payments</span>
                <input type="text" class="res-price w-20 border-0 bg-transparent p-0 text-[11px] font-bold text-gold outline-none pointer-events-none select-none" readonly placeholder="₱--" tabindex="-1" aria-label="Nightly rate">
            </span>
            <span class="capacity-hint text-[11px] font-semibold text-ink/45"></span>
        </div>
    </div>

    <!-- Numbers allocation details -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-white/10">
        <div>
            <label class="block text-[10px] font-bold text-ink/55 uppercase tracking-widest mb-1.5">No. of Guests</label>
            <div class="stepper flex items-center gap-1.5">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-white/12 bg-white/5 flex items-center justify-center text-ink/70 hover:bg-white/10 hover:border-gold/50 hover:text-ink active:scale-95 transition-all cursor-pointer shrink-0" data-step="-1" aria-label="Fewer guests in room">
                    <span class="material-icons text-[16px]">remove</span>
                </button>
                <input type="number" name="reservations[__INDEX__][num_guests]" class="res-num-guests w-full px-2 py-2 bg-white/5 border border-white/10 rounded-xl text-sm font-bold text-ink text-center outline-none focus:border-gold/60 focus:ring-2 focus:ring-gold/20 transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" placeholder="e.g. 2" required>
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-white/12 bg-white/5 flex items-center justify-center text-ink/70 hover:bg-white/10 hover:border-gold/50 hover:text-ink active:scale-95 transition-all cursor-pointer shrink-0" data-step="1" aria-label="More guests in room">
                    <span class="material-icons text-[16px]">add</span>
                </button>
            </div>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-ink/55 uppercase tracking-widest mb-1.5">Seniors / PWD in Room</label>
            <div class="stepper flex items-center gap-1.5">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-white/12 bg-white/5 flex items-center justify-center text-ink/70 hover:bg-white/10 hover:border-gold/50 hover:text-ink active:scale-95 transition-all cursor-pointer shrink-0" data-step="-1" aria-label="Fewer seniors">
                    <span class="material-icons text-[16px]">remove</span>
                </button>
                <input type="number" name="reservations[__INDEX__][num_seniors]" class="res-num-seniors w-full px-2 py-2 bg-white/5 border border-white/10 rounded-xl text-sm font-bold text-ink text-center outline-none focus:border-gold/60 focus:ring-2 focus:ring-gold/20 transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="0" value="0">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-white/12 bg-white/5 flex items-center justify-center text-ink/70 hover:bg-white/10 hover:border-gold/50 hover:text-ink active:scale-95 transition-all cursor-pointer shrink-0" data-step="1" aria-label="More seniors">
                    <span class="material-icons text-[16px]">add</span>
                </button>
            </div>
            <small class="text-[10px] text-ink/45 mt-1.5 block font-medium">For 20% Senior/PWD discount verification</small>
        </div>
    </div>

    <!-- Meal Selection (Alpine-powered collapsible panel) -->
    <div class="mt-4" x-data="{ mealsOpen: false }">
        <button type="button" @click="mealsOpen = !mealsOpen" class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-bold bg-white/5 hover:bg-white/10 text-ink/80 transition-all cursor-pointer border border-white/12">
            <span class="material-icons text-[15px] text-emerald">restaurant_menu</span>
            Breakfast Meal Selections
            <span class="text-ink/45 font-medium">(optional)</span>
            <span class="material-icons text-[14px] ml-1 transition-transform duration-200 text-ink/45" :class="mealsOpen ? 'rotate-180' : ''">expand_more</span>
        </button>
        <div class="mt-2.5"
             x-show="mealsOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-out duration-120"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             style="display: none;"
         >
            <div class="border border-white/10 rounded-2xl p-5 bg-white/[0.03] space-y-3.5">
                <p class="text-[10px] font-bold text-ink/50 uppercase tracking-wider flex items-center gap-1.5">
                    <span class="material-icons text-[14px] text-emerald">info</span>
                    Free Breakfast (silog meal count must equal the room's guests)
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach (['bangsilog' => 'Bangsilog', 'tocilog' => 'Tocilog', 'hotsilog' => 'Hotsilog', 'spamsilog' => 'Spamsilog', 'tapsilog' => 'Tapsilog'] as $mealKey => $mealLabel)
                        <div>
                            <label class="block text-[10px] font-bold text-ink/55 uppercase tracking-widest mb-1 text-center">{{ $mealLabel }}</label>
                            <div class="flex items-center rounded-lg border border-white/10 bg-white/5 overflow-hidden">
                                <button type="button" class="btn-minus-meal w-7 h-8 flex items-center justify-center text-ink/60 hover:bg-white/10 hover:text-gold transition-colors cursor-pointer shrink-0">
                                    <span class="material-icons text-[15px]">remove</span>
                                </button>
                                <input type="number" name="reservations[__INDEX__][meal][{{ $mealKey }}]" class="meal-qty w-full min-w-0 px-1 py-1.5 border-0 bg-transparent text-xs text-center font-bold text-ink outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="0" value="0">
                                <button type="button" class="btn-plus-meal w-7 h-8 flex items-center justify-center text-ink/60 hover:bg-white/10 hover:text-gold transition-colors cursor-pointer shrink-0">
                                    <span class="material-icons text-[15px]">add</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Availability section — loads automatically once dates + type are set -->
    <div class="mt-5 pt-4 border-t border-white/10">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <p class="text-[11px] text-ink/50 font-medium flex items-center gap-1.5">
                <span class="material-icons text-[14px] text-emerald">touch_app</span>
                Pick a room style above and open rooms for your dates appear here. Tap a room number to reserve it.
            </p>
            <button type="button" class="btn-check-availability press inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/5 px-3.5 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ink transition-colors hover:bg-bone hover:text-night cursor-pointer">
                <span class="material-icons text-[13px]">refresh</span>
                Refresh
            </button>
        </div>

        <!-- Tile status legend -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mb-2">
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-ink/45 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-emerald/20 border border-emerald/50"></span> Available</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-ink/45 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-emerald"></span> Selected</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-ink/45 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-ember-600/25 border border-ember-600/50"></span> Booked</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-ink/45 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-gold/20 border border-gold/50"></span> Cleaning</span>
            <span class="flex items-center gap-1.5 text-[10px] font-bold text-ink/45 uppercase tracking-wider"><span class="w-2.5 h-2.5 rounded-[4px] bg-white/10 border border-white/25"></span> Maintenance</span>
        </div>

        <!-- Room Tiles wrapper populated via AJAX -->
        <div class="room-tiles-wrapper mb-1"></div>
        <input type="hidden" name="reservations[__INDEX__][room_number]" class="res-room-number-hidden">
    </div>
</div>
