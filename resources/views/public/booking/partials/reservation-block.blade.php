{{-- One room in the booking. Cloned by booking.js for each reservation block;
     __INDEX__ is replaced at runtime.

     This is a row now, not a card. It used to be a 3xl-radius panel with its
     own header rule, a numbered gold bead, a two-column count grid and a
     paragraph about room numbers — about 320px of chrome per room, repeated
     for every room in the booking, inside a card that already said what it
     was. What a guest does here is read the room back, move a person in or
     out of it, and drop it: a thumbnail, a name, a rate, a stepper and a way
     out, on one line. Everything else either moved up to the card (the room
     -number note) or folded behind a press (breakfast).

     The Senior/PWD counter is not shown until the guest asks for the
     discount, one card up. Its input stays in the DOM and stays at 0, so a
     booking that never asks still posts the field the server expects. --}}
<div class="reservation-block" data-index="__INDEX__">

    <div class="pick-row">
        {{-- Filled by booking.js from the picker card's own <img>, so the
             thumbnail is the same asset the grid above showed and there is no
             second place a room photo is named. --}}
        <span class="pick-thumb" data-pick-thumb aria-hidden="true"></span>

        <span class="pick-id">
            {{-- The room this block IS, not the name of the step it sits in.
                 Every block used to be headed "Room Allocation", so three
                 rooms carried the same title three times and none of them said
                 which room it was. --}}
            <span class="pick-name block-room-name">Room</span>
            <span class="pick-meta">
                <span class="block-room-rate tabnum"></span>
                <span class="capacity-hint"></span>
            </span>
        </span>

        <span class="pick-controls">
            {{-- One pip per bed, filled as the room fills. Drawn by booking.js
                 once a style is chosen; a count you can see at a glance beats
                 one you have to read. --}}
            <span class="count-pips guests-pips" aria-hidden="true"></span>

            {{-- Derived, not asked.

                 The party size in step 1 is the number the guest owns; this one
                 is the form's answer to "so where does everyone sleep", filled
                 in as soon as a room style is picked. It is still editable —
                 moving people between rooms is a real thing to want — but it
                 says up front that it has already been filled, and stops saying
                 it the moment the guest sets it by hand. --}}
            <span class="pick-count">
                <span class="pick-stepper stepper">
                    <button type="button" class="btn-step pick-step" data-step="-1" aria-label="Fewer guests in room">
                        <x-booking.ui.icon-solid name="minus" class="text-[15px]" />
                    </button>
                    {{-- booking.js does `replace(/__INDEX__/g, index)` over this whole
                         template, so the placeholder carries into the id and each
                         cloned room block still gets unique label bindings. --}}
                    <input type="number" name="reservations[__INDEX__][num_guests]" id="res-__INDEX__-num-guests"
                           aria-label="Guests in this room" aria-describedby="res-__INDEX__-guests-note"
                           class="res-num-guests pick-input tabnum" min="1" required>
                    <button type="button" class="btn-step pick-step" data-step="1" aria-label="More guests in room">
                        <x-booking.ui.icon-solid name="plus" class="text-[15px]" />
                    </button>
                </span>
                {{-- Visual only: the same numbers are spelled out in the note
                     below, which is what a screen reader is pointed at. --}}
                <span class="count-readout guests-readout tabnum" aria-hidden="true">&mdash;</span>
                <span class="count-auto-tag" data-auto-tag hidden>Filled for you</span>
            </span>

            <button type="button" class="btn-remove-block pick-remove" style="display:none;">Remove</button>
        </span>
    </div>

    <small id="res-__INDEX__-guests-note" class="count-note guests-note pick-note">Pick a room style above and we’ll fill this in for you.</small>

    {{-- Opened by the Senior/PWD request one card up — see .co-picks.wants-seniors. --}}
    <div class="pick-seniors">
        <label for="res-__INDEX__-num-seniors" class="pick-seniors-label">Seniors / PWD sleeping in this room</label>
        <span class="pick-count">
            <span class="pick-stepper stepper">
                <button type="button" class="btn-step pick-step" data-step="-1" aria-label="Fewer seniors">
                    <x-booking.ui.icon-solid name="minus" class="text-[15px]" />
                </button>
                <input type="number" name="reservations[__INDEX__][num_seniors]" id="res-__INDEX__-num-seniors"
                       aria-describedby="res-__INDEX__-seniors-note"
                       class="res-num-seniors pick-input tabnum" min="0" value="0">
                <button type="button" class="btn-step pick-step" data-step="1" aria-label="More seniors">
                    <x-booking.ui.icon-solid name="plus" class="text-[15px]" />
                </button>
            </span>
            <span class="count-readout seniors-readout tabnum" aria-hidden="true">&mdash;</span>
        </span>
        <small id="res-__INDEX__-seniors-note" class="count-note seniors-note pick-note">Leave at 0 if none sleep in this room.</small>
    </div>

    <!-- Breakfast, behind a press. Optional, free, and not something a guest
         picking rooms is deciding on — so it does not get a fifth of the row. -->
    <div class="pick-meals" x-data="{ mealsOpen: false }">
        <button type="button" @click="mealsOpen = !mealsOpen" class="pick-meals-toggle" :aria-expanded="mealsOpen ? 'true' : 'false'">
            <x-booking.ui.icon-solid name="utensils" class="text-[14px]" />
            Breakfast selections <span class="pick-meals-opt">(optional)</span>
            {{-- ::class, not :class — on a Blade component a single colon is a
                 PHP binding, so Alpine's expression has to be escaped through
                 to the rendered SVG. Same as the account menu chevron in
                 layouts/public/base. --}}
            <x-booking.ui.icon-solid name="chevron-down" class="text-[13px] transition-transform duration-200" ::class="mealsOpen ? 'rotate-180' : ''" />
        </button>
        <div class="pick-meals-panel"
             x-show="mealsOpen"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-out duration-120"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             style="display: none;">
            <p class="pick-meals-note">Free breakfast, optional, up to one silog per guest.</p>
            <div class="pick-meals-grid">
                @foreach (['bangsilog' => 'Bangsilog', 'tocilog' => 'Tocilog', 'hotsilog' => 'Hotsilog', 'spamsilog' => 'Spamsilog', 'tapsilog' => 'Tapsilog'] as $mealKey => $mealLabel)
                    <div class="pick-meal">
                        <label class="pick-meal-label" for="res-__INDEX__-meal-{{ $mealKey }}">{{ $mealLabel }}</label>
                        <div class="pick-meal-control">
                            <button type="button" class="btn-minus-meal" aria-label="Fewer {{ $mealLabel }}">
                                <x-booking.ui.icon-solid name="minus" class="text-[14px]" />
                            </button>
                            <input type="number" id="res-__INDEX__-meal-{{ $mealKey }}" name="reservations[__INDEX__][meal][{{ $mealKey }}]" class="meal-qty tabnum" min="0" value="0">
                            <button type="button" class="btn-plus-meal" aria-label="More {{ $mealLabel }}">
                                <x-booking.ui.icon-solid name="plus" class="text-[14px]" />
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- The form contract, and the machinery the availability / fit / selection
         passes still read.

         The select is what the server is posted. The card grid beside it is not
         something the guest works through any more: it used to be repeated
         inside every block, so booking three rooms meant scrolling past the
         same seven styles three times — 21 cards, 5.2 screens — and doing the
         packing by hand in between. Styles are chosen once now, from the single
         grid above this list, which sets quantities. The grid stays in the DOM
         because syncTypeCards(), updateTypeFit() and the availability pass all
         still address it; it is simply never shown. --}}
    <div class="room-style-picker" hidden aria-hidden="true">
        <label class="font-label block text-[10px] font-normal text-ink-faint uppercase tracking-[0.2em] mb-2">Choose a Room Style</label>
        <select name="reservations[__INDEX__][room_type]" class="room-type-select hidden" tabindex="-1" aria-hidden="true">
            <option value="">Select room type...</option>
            @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                <option value="{{ $type['id'] }}" data-beds="{{ $type['beds'] }}" data-price="{{ $type['price'] }}">{{ $type['title'] }} ({{ $type['beds'] }} pax)</option>
            @endforeach
        </select>

        <label class="fit-filter hidden" data-fit-filter>
            <input type="checkbox" class="fit-filter-box" data-fit-filter-box>
            <span data-fit-filter-label>Only show rooms that fit everyone</span>
        </label>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                <button type="button" data-type-value="{{ $type['id'] }}" data-type-title="{{ $type['title'] }}" data-beds="{{ $type['beds'] }}"
                        class="type-card group relative cursor-pointer overflow-hidden rounded-2xl border border-emerald-deep/10 bg-cream-warm/60 text-left">
                    <span class="block h-20 w-full overflow-hidden sm:h-24">
                        <x-img :src="$type['image']" :alt="$type['title']" loading="lazy" sizes="200px" class="h-full w-full object-cover" />
                    </span>
                    {{-- Per-type availability badge for the chosen dates; filled by booking.js --}}
                    <span data-type-avail class="type-card-avail hidden"></span>
                    <span class="type-card-check absolute right-2 top-2 hidden h-6 w-6 place-items-center rounded-full bg-gold text-night shadow-md ring-2 ring-cream">
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span class="block px-3 py-2.5">
                        <span class="block truncate text-xs font-bold text-ink">{{ $type['title'] }}</span>
                        <span class="mt-0.5 block text-[11px] font-bold text-palay-800 tabnum">₱{{ number_format($type['price']) }}<span class="block font-medium text-ink-faint sm:inline"> / night · sleeps {{ $type['beds'] }}</span></span>
                        <span data-type-fit class="type-card-fit"></span>
                    </span>
                </button>
            @endforeach
        </div>

        <!-- Selected-type recap consumed and filled by booking.js -->
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <input type="hidden" name="reservations[__INDEX__][beds]" class="res-beds">
            <input type="hidden" name="reservations[__INDEX__][price_per_night]" class="res-price-hidden">
            <input type="text" class="res-price" readonly placeholder="₱--" tabindex="-1" aria-label="Nightly rate">
        </div>
    </div>
</div>
