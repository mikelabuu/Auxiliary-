{{-- Cloned by booking.js for each reservation block. __INDEX__ is replaced at runtime. --}}
<div class="reservation-block ring-1 ring-emerald-deep/5 bg-cream-warm/60 p-4 sm:p-6 rounded-3xl relative transition-[color,background-color,border-color,box-shadow] duration-300 hover:ring-emerald-deep/10" data-index="__INDEX__">

    <!-- Block header -->
    <div class="flex items-center justify-between mb-5 pb-3 border-b border-emerald-deep/10">
        <div class="flex items-center gap-2.5">
            <span class="w-6 h-6 rounded-full bg-gold text-night font-display italic text-[11px] flex items-center justify-center block-number shadow-sm">1</span>
            {{-- The room this block IS, not the name of the step it sits in.
                 Every block used to be headed "Room Allocation", so three
                 rooms carried the same title three times and none of them said
                 which room it was. --}}
            <span class="font-label block-room-name text-xs font-normal text-ink-soft uppercase tracking-[0.2em]">Room</span>
            <span class="block-room-rate text-[11px] font-bold text-palay-800 tabnum"></span>
        </div>
        <button type="button" class="btn-remove-block inline-flex items-center gap-1 py-1.5 px-3 rounded-xl text-[11px] font-bold bg-ember-600/15 text-ember-700 border border-ember-600/40 hover:bg-ember-600/25 transition-[color,background-color,border-color,box-shadow] cursor-pointer" style="display:none;">
            <x-booking.ui.icon-solid name="trash-can" class="text-[14px]" />
            Remove
        </button>
    </div>

    {{-- Room type.

         The select is the form contract and stays. The card grid beside it
         does not: it used to be repeated inside every block, so booking three
         rooms meant scrolling past the same seven styles three times — 21
         cards, 5.2 screens — and doing the packing by hand in between. Styles
         are chosen once now, from the single picker above this list, which
         sets quantities. The grid stays in the DOM because the availability
         pass, the fit pass and the selection sync all still read it; it is
         simply not something the guest is asked to work through per room. --}}
    <div class="room-style-picker" hidden aria-hidden="true">
        <label class="font-label block text-[10px] font-normal text-ink-faint uppercase tracking-[0.2em] mb-2">Choose a Room Style</label>
        <select name="reservations[__INDEX__][room_type]" class="room-type-select hidden" tabindex="-1" aria-hidden="true">
            <option value="">Select room type...</option>
            @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                <option value="{{ $type['id'] }}" data-beds="{{ $type['beds'] }}" data-price="{{ $type['price'] }}">{{ $type['title'] }} ({{ $type['beds'] }} pax)</option>
            @endforeach
        </select>

        {{-- Off by default: three Doubles for six people is a real choice, and
             often the cheaper one, so the styles that cannot hold the party on
             their own are still worth seeing. booking.js hides this whole row
             while every style fits anyway, which is most bookings. --}}
        <label class="fit-filter hidden" data-fit-filter>
            <input type="checkbox" class="fit-filter-box" data-fit-filter-box>
            <span data-fit-filter-label>Only show rooms that fit everyone</span>
        </label>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                <button type="button" data-type-value="{{ $type['id'] }}" data-type-title="{{ $type['title'] }}" data-beds="{{ $type['beds'] }}"
                        class="type-card group relative cursor-pointer overflow-hidden rounded-2xl border border-emerald-deep/10 bg-cream-warm/60 text-left transition-[transform,color,background-color,border-color,box-shadow] duration-200 hover:-translate-y-0.5 hover:border-gold hover:shadow-[0_16px_36px_-22px_rgba(6,40,30,0.35)]">
                    <span class="block h-20 w-full overflow-hidden sm:h-24">
                        <x-img :src="$type['image']" :alt="$type['title']" loading="lazy" sizes="200px"
                               class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    </span>
                    {{-- Per-type availability badge for the chosen dates; filled by booking.js --}}
                    <span data-type-avail class="type-card-avail hidden"></span>
                    <span class="type-card-check absolute right-2 top-2 hidden h-6 w-6 place-items-center rounded-full bg-gold text-night shadow-md ring-2 ring-cream">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span class="block px-3 py-2.5">
                        <span class="block truncate text-xs font-bold text-ink">{{ $type['title'] }}</span>
                        {{-- The rate and what it buys break as one unit. Two
                             cards to a row on a phone leaves this line about
                             110px to work in and it needs ~121px, so it wrapped
                             — but wrapped wherever the text happened to run
                             out, splitting "/ night · sleeps 2" across lines.
                             block below sm puts the break after the peso figure
                             instead, which is where a reader would put it. --}}
                        <span class="mt-0.5 block text-[11px] font-bold text-palay-800 tabnum">₱{{ number_format($type['price']) }}<span class="block font-medium text-ink-faint sm:inline"> / night · sleeps {{ $type['beds'] }}</span></span>
                        {{-- "sleeps 3" is a fact about the room; this is the
                             answer to the question actually being asked —
                             does it hold the people I still have to seat.
                             Filled by booking.js against the running count. --}}
                        <span data-type-fit class="type-card-fit"></span>
                    </span>
                </button>
            @endforeach
        </div>

        <!-- Selected-type recap consumed and filled by booking.js -->
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <input type="hidden" name="reservations[__INDEX__][beds]" class="res-beds">
            <input type="hidden" name="reservations[__INDEX__][price_per_night]" class="res-price-hidden">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-gold/40 bg-gold/10 px-3.5 py-1.5 text-[11px] font-bold text-palay-800">
                <x-booking.ui.icon-solid name="money-bill-wave" class="text-[14px]" />
                <input type="text" class="res-price w-20 border-0 bg-transparent p-0 text-[11px] font-bold text-palay-800 outline-none pointer-events-none select-none" readonly placeholder="₱--" tabindex="-1" aria-label="Nightly rate">
            </span>
            <span class="capacity-hint text-[11px] font-semibold text-ink-faint"></span>
        </div>
    </div>

    <!-- Numbers allocation details.

         Both counts are capped by the room style: guests by its beds, seniors
         by the guests actually in the room. The caps used to exist only in the
         input's `max`, so a guest pressing + on a full room got nothing and no
         reason. Each count now states its own ceiling (the "2 / 3" readout and
         the bed pips beside it), the ± buttons disable when they are spent,
         and the line underneath says what to do next instead of leaving the
         guest to guess. -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-emerald-deep/10">
        {{-- Derived, not asked.

             The party size in step 1 is the number the guest owns; this one is
             the form's answer to "so where does everyone sleep", filled in as
             soon as a room style is picked. It wore exactly the same label,
             stepper, readout and pips as the party field, so a guest who had
             just said "five" met another guest counter and could not tell
             whether it was a second question or the same one asked twice. It is
             still editable — moving people between rooms is a real thing to
             want — but it now says up front that it has already been filled,
             and stops saying it the moment the guest sets it by hand. --}}
        <div class="count-field count-field--derived">
            {{-- booking.js does `replace(/__INDEX__/g, index)` over this whole
                 template, so the placeholder carries into the id and each
                 cloned room block still gets unique label bindings. --}}
            <div class="count-field-head">
                <span class="count-field-labelwrap">
                    <label for="res-__INDEX__-num-guests" class="count-field-label">Guests in this room</label>
                    <span class="count-auto-tag" data-auto-tag hidden>Filled for you</span>
                </span>
                {{-- Visual only: the same numbers are spelled out in the note
                     below, which is what a screen reader is pointed at. --}}
                <span class="count-readout guests-readout tabnum" aria-hidden="true">&mdash;</span>
            </div>
            <div class="stepper flex items-center gap-1.5">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-emerald-deep/15 bg-cream-warm/60 flex items-center justify-center text-ink-soft hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="-1" aria-label="Fewer guests in room">
                    <x-booking.ui.icon-solid name="minus" class="text-[16px]" />
                </button>
                <input type="number" name="reservations[__INDEX__][num_guests]" id="res-__INDEX__-num-guests" aria-describedby="res-__INDEX__-guests-note" class="res-num-guests w-full px-2 py-2 bg-cream-warm/60 border border-emerald-deep/10 rounded-xl text-sm font-bold text-ink text-center outline-none focus:border-gold/60 focus:ring-2 focus:ring-gold/20 transition-[color,background-color,border-color,box-shadow] [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" placeholder="e.g. 2" required>
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-emerald-deep/15 bg-cream-warm/60 flex items-center justify-center text-ink-soft hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="1" aria-label="More guests in room">
                    <x-booking.ui.icon-solid name="plus" class="text-[16px]" />
                </button>
            </div>
            {{-- One pip per bed, filled as the room fills. Drawn by booking.js
                 once a style is chosen; a count you can see at a glance beats
                 one you have to read. --}}
            <span class="count-pips guests-pips" aria-hidden="true"></span>
            <small id="res-__INDEX__-guests-note" class="count-note guests-note">Pick a room style above and we’ll fill this in for you.</small>
        </div>
        <div class="count-field">
            <div class="count-field-head">
                <label for="res-__INDEX__-num-seniors" class="count-field-label">Seniors / PWD in this room</label>
                <span class="count-readout seniors-readout tabnum" aria-hidden="true">&mdash;</span>
            </div>
            <div class="stepper flex items-center gap-1.5">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-emerald-deep/15 bg-cream-warm/60 flex items-center justify-center text-ink-soft hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="-1" aria-label="Fewer seniors">
                    <x-booking.ui.icon-solid name="minus" class="text-[16px]" />
                </button>
                <input type="number" name="reservations[__INDEX__][num_seniors]" id="res-__INDEX__-num-seniors" aria-describedby="res-__INDEX__-seniors-note" class="res-num-seniors w-full px-2 py-2 bg-cream-warm/60 border border-emerald-deep/10 rounded-xl text-sm font-bold text-ink text-center outline-none focus:border-gold/60 focus:ring-2 focus:ring-gold/20 transition-[color,background-color,border-color,box-shadow] [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="0" value="0">
                <button type="button" class="btn-step w-9 h-9 rounded-xl border border-emerald-deep/15 bg-cream-warm/60 flex items-center justify-center text-ink-soft hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="1" aria-label="More seniors">
                    <x-booking.ui.icon-solid name="plus" class="text-[16px]" />
                </button>
            </div>
            <small id="res-__INDEX__-seniors-note" class="count-note seniors-note">For the 20% Senior / PWD discount. Leave at 0 if none.</small>
        </div>
    </div>

    <!-- Meal Selection (Alpine-powered collapsible panel) -->
    <div class="mt-4" x-data="{ mealsOpen: false }">
        <button type="button" @click="mealsOpen = !mealsOpen" class="inline-flex items-center gap-1.5 py-2 px-3.5 rounded-xl text-xs font-bold bg-cream-warm/60 hover:bg-cream-warm text-ink-soft transition-[color,background-color,border-color,box-shadow] cursor-pointer border border-emerald-deep/15">
            <x-booking.ui.icon-solid name="utensils" class="text-[15px] text-emerald" />
            Breakfast Meal Selections
            <span class="text-ink-faint font-medium">(optional)</span>
            {{-- ::class, not :class — on a Blade component a single colon is a
                 PHP binding, so Alpine's expression has to be escaped through
                 to the rendered SVG. Same as the account menu chevron in
                 layouts/public/base. --}}
            <x-booking.ui.icon-solid name="chevron-down" class="text-[14px] ml-1 transition-transform duration-200 text-ink-faint" ::class="mealsOpen ? 'rotate-180' : ''" />
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
            <div class="border border-emerald-deep/10 rounded-2xl p-5 bg-cream-warm/60 space-y-3.5">
                <p class="font-label text-[10px] font-normal text-ink-faint uppercase tracking-[0.2em] flex items-center gap-1.5">
                    <x-booking.ui.icon-solid name="circle-info" class="text-[14px] text-emerald" />
                    Free breakfast, optional, up to one silog per guest
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    @foreach (['bangsilog' => 'Bangsilog', 'tocilog' => 'Tocilog', 'hotsilog' => 'Hotsilog', 'spamsilog' => 'Spamsilog', 'tapsilog' => 'Tapsilog'] as $mealKey => $mealLabel)
                        <div>
                            <label class="font-label block text-[10px] font-normal text-ink-faint uppercase tracking-[0.2em] mb-1 text-center">{{ $mealLabel }}</label>
                            <div class="flex items-center rounded-lg border border-emerald-deep/10 bg-cream-warm/60 overflow-hidden">
                                <button type="button" class="btn-minus-meal w-7 h-8 flex items-center justify-center text-ink-faint hover:bg-cream-warm hover:text-palay-800 transition-colors cursor-pointer shrink-0">
                                    <x-booking.ui.icon-solid name="minus" class="text-[15px]" />
                                </button>
                                <input type="number" name="reservations[__INDEX__][meal][{{ $mealKey }}]" class="meal-qty w-full min-w-0 px-1 py-1.5 border-0 bg-transparent text-xs text-center font-bold text-ink outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="0" value="0">
                                <button type="button" class="btn-plus-meal w-7 h-8 flex items-center justify-center text-ink-faint hover:bg-cream-warm hover:text-palay-800 transition-colors cursor-pointer shrink-0">
                                    <x-booking.ui.icon-solid name="plus" class="text-[15px]" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Where the room-number tile grid used to be.
         Guests choose a style, not a door. The room is assigned by
         BookingController::store from what is actually free at the moment the
         booking commits, which is the only moment the answer is true — a tile
         picked five minutes earlier was a promise the server then had to break
         about a third of the time on a busy weekend. Front desk and admin still
         pick numbers, because they are standing in the building. --}}
    <div class="mt-5 pt-4 border-t border-emerald-deep/10">
        <p class="flex items-start gap-2 text-[11px] font-semibold text-ink-soft leading-relaxed">
            <x-booking.ui.icon-solid name="key" class="mt-px text-[14px] text-emerald" />
            <span>
                We'll assign your room number when the booking is confirmed, and it's on your confirmation.
                <span class="block font-medium text-ink-faint mt-0.5">Travelling together or need a particular floor? Add it to the requests box below and our front desk will do what it can.</span>
            </span>
        </p>
    </div>
</div>
