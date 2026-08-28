@extends('layouts.public.base')
@section('title', 'Checkout | Farmers Hostel')

{{-- No @section('livewire') here on purpose. The address-selector inside
     step-guest was the only Livewire component on any public page, and it was
     pulling the whole 339 KB Livewire bundle onto checkout so that three
     dropdowns could round-trip to the server. It is a Blade + Alpine component
     now, so checkout takes the same ~45 KB standalone Alpine as every other
     public page. Re-adding a Livewire component here means restoring the flag,
     or Alpine will be doubled — see the vendor loading contract. --}}

{{-- Date fields are flatpickr instances built by public/js/booking.js. --}}
@push('vendor')
    @include('partials.vendor.flatpickr')
@endpush

{{-- ONE STEP AT A TIME.

     Checkout used to be three cards stacked down a single scroll — stay,
     details, rooms — with the Confirm button pinned in the sidebar beside all
     of them. Everything the guest had not done yet was on screen at once, and
     the only thing telling them where they were was a rail eight hundred
     pixels up.

     It is a wizard now: stay, then rooms, then details, with only the live
     step rendered and a sticky bar at the foot carrying Back, the one thing
     still standing in the way, and the button that moves. Every field stays in
     the DOM the whole time — the panels hide, they do not unmount — so
     booking.js's blocks, the reseating pass and the submit contract are
     untouched by the change.

     Rooms moved ahead of details on purpose. Rates, availability and the total
     are what a guest is deciding on; a name and a barangay are what they type
     once they have decided. --}}
@section('content')

    @php
        // A rejected submission comes back with the whole form repopulated, and
        // the field that was refused is almost always in Details. Opening on
        // step 1 would hide the error behind two Continue presses.
        $openStep = $errors->any() ? 'details' : 'dates';
    @endphp

    <div class="min-h-screen bg-canvas pt-28 pb-24 relative isolate overflow-x-clip">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="co-enter mb-9 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4" style="--co:0">
                <div>
                    <span class="font-label inline-flex items-center gap-3 text-[11px] font-normal uppercase tracking-[0.4em] text-palay-800 mb-3">
                        <span class="h-px w-8 bg-gold/50"></span> New reservation
                    </span>
                    <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">Complete your {{-- brass-ink, not gold. --color-gold is a 75%-lightness accent and lands
                         at 2.02:1 on cream — DESIGN.md is explicit that gold never carries
                         text on this ground, and at 48px display the floor is still 3:1.
                         brass-ink is the same brass darkened until it reads (~4.9:1), so the
                         accent survives and the word does too. --}}
                        <span class="italic text-brass-ink">booking</span></h1>
                    <p class="text-sm font-medium text-ink-soft mt-3">Dates, then rooms, then your details. No payment needed yet.</p>
                </div>
                <a href="{{ route('home') }}#rooms" class="font-label gold-underline self-start sm:self-end text-[11px] font-normal uppercase tracking-[0.3em] text-ink-soft hover:text-emerald-deep transition-colors">
                    &larr; Back to Rooms
                </a>
            </div>

            {{-- The rail is the wizard's map and its steering. Each step shows a
                 dot (its number, or a tick once satisfied), its name, and a bar
                 underneath that fills for the live step. booking.js sets
                 .is-current and .done; pressing one goes there, subject to the
                 same gate as Continue, so a guest cannot jump to Details before
                 a room exists. --}}
            <ol id="checkoutProgress" class="co-rail co-enter" style="--co:1">
                @foreach (['dates' => 'Your stay', 'rooms' => 'Your rooms', 'details' => 'Your details'] as $step => $label)
                    {{-- The <li> used to carry role="button" itself, which strips
                         its listitem semantics and left the <ol> announcing as a
                         list with no items (axe `list`). The interactive part is
                         a real <button> inside instead: the list stays a list,
                         and Enter/Space/focus come from the element natively
                         rather than from tabindex + a keydown handler.
                         booking.js delegates from #checkoutProgress via
                         closest('[data-progress-step]'), so clicks still resolve
                         to this <li> exactly as before. --}}
                    <li data-progress-step="{{ $step }}" class="checkout-step @if ($step === $openStep) is-current @endif">
                        <button type="button" class="focus-ring co-rail-btn" aria-label="Go to {{ strtolower($label) }}">
                            <span class="co-rail-head">
                                <span class="step-dot">
                                    <span class="step-num">{{ $loop->iteration }}</span>
                                    <svg class="step-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                </span>
                                <span class="co-rail-label">{{ $label }}</span>
                            </span>
                            <span class="step-bar"></span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <x-booking.ui.error-list class="mb-6" />
            {{-- animate-pop replays each time booking.js un-hides this (display swap restarts the keyframes) --}}
            <div id="bookingFormAlert" role="alert" class="animate-pop mb-6 p-4 bg-ember-600/15 text-ember-700 border border-ember-600/40 rounded-2xl text-sm font-semibold d-none"></div>

            <form id="bookingForm" method="POST" action="{{ route('booking.store') }}" class="co-layout" data-open-step="{{ $openStep }}">
                @csrf

                <!-- Hidden aggregate values needed for backend forms -->
                {{-- `room_numbers` went with the picker. The guest chooses a
                     room style; BookingController::store assigns the actual
                     rooms from what is free at the moment it commits. --}}
                <input type="hidden" name="num_seniors" id="num_seniors" value="0">
                <input type="hidden" name="check_in" id="check_in_hidden">
                <input type="hidden" name="check_out" id="check_out_hidden">

                <!-- Left column: the live step, then the action bar -->
                <div class="co-main">

                    {{-- ─────────────── STEP 1 · YOUR STAY ─────────────── --}}
                    <section class="co-panel @if ($openStep === 'dates') is-active @endif" data-step-panel="dates" id="stepCardDates" aria-label="Your stay">
                        <x-booking.checkout.step-card title="Your Stay" lead="How many nights, and how many people are coming. Everything after this depends on it.">
                            <x-slot:aside>
                                <span id="nights_duration_badge" class="font-label hidden px-3.5 py-1.5 rounded-full bg-gold/15 border border-gold/40 text-ink-soft text-[11px] font-normal uppercase tracking-[0.2em] animate-pop whitespace-nowrap"></span>
                            </x-slot:aside>
                            {{-- Most stays here are short and near. Typing two dates
                                 into two pickers to say "tonight" is three taps too
                                 many, and on a phone it is the first thing a guest
                                 has to do — booking.js fills both fields and lets
                                 the existing change handlers do the rest. --}}
                            <div class="date-presets" id="datePresets">
                                <button type="button" class="date-preset" data-preset="tonight">
                                    Tonight <span class="date-preset-nights">1 night</span>
                                </button>
                                <button type="button" class="date-preset" data-preset="tomorrow">
                                    Tomorrow <span class="date-preset-nights">1 night</span>
                                </button>
                                <button type="button" class="date-preset" data-preset="weekend">
                                    This weekend <span class="date-preset-nights">2 nights</span>
                                </button>
                                <button type="button" class="date-preset" data-preset="week">
                                    Next week <span class="date-preset-nights">7 nights</span>
                                </button>
                            </div>
                            {{-- One stay bar: check-in, check-out, guests.

                                 These were a two-column date grid, then a rule,
                                 then a guest stepper on its own row underneath —
                                 which read as two separate questions and made the
                                 card look like it was asking for something extra.
                                 Every booking site a guest here has already used
                                 (Agoda, Booking, Hostelworld) puts the three on one
                                 line as equal segments of a single control, with
                                 the guest count collapsed to a summary and its
                                 stepper behind a popover. Same shape here, in this
                                 site's own materials. --}}
                            <div class="stay-bar">
                                <div class="stay-field">
                                    <label class="stay-field-label" for="check_in_display">Check-in</label>
                                    <span class="stay-field-control">
                                        <x-booking.ui.icon-solid name="calendar-days" class="stay-field-icon" />
                                        <input type="text" id="check_in" class="flatpickr-date stay-field-input" placeholder="Select date" value="{{ $checkIn ?? '' }}">
                                    </span>
                                </div>

                                <div class="stay-field">
                                    <label class="stay-field-label" for="check_out_display">Check-out</label>
                                    <span class="stay-field-control">
                                        <x-booking.ui.icon-solid name="calendar-days" class="stay-field-icon" />
                                        <input type="text" id="check_out" class="flatpickr-date stay-field-input" placeholder="Select date" value="{{ $checkOut ?? '' }}">
                                    </span>
                                </div>

                                {{-- The stepper is one tap away rather than always on
                                     screen. It is the least-changed of the three —
                                     most parties are one or two people and never
                                     touch it — and left inline it was the loudest
                                     thing in the card. --}}
                                <div class="stay-field stay-field--guests" x-data="{ open: false }" @keydown.escape.window="open = false">
                                    <span class="stay-field-label" id="guestsFieldLabel">Guests</span>
                                    <button type="button" class="stay-field-control stay-field-trigger"
                                            {{-- Stacked on a phone this row is the last in the bar, so
                                                 the panel it opens starts near the fold. Bring it up
                                                 rather than leaving the guest to discover it. --}}
                                            @click="open = !open; open && $nextTick(() => $refs.pop.scrollIntoView({ block: 'center', behavior: 'smooth' }))"
                                            :aria-expanded="open ? 'true' : 'false'"
                                            aria-labelledby="guestsFieldLabel guestSummary">
                                        <x-booking.ui.icon-solid name="users" class="stay-field-icon" />
                                        <span id="guestSummary" class="stay-field-value">1 guest</span>
                                        <x-booking.ui.icon-solid name="chevron-down" class="stay-field-caret" ::class="open ? 'is-open' : ''" />
                                    </button>

                                    <div class="guest-pop"
                                         x-ref="pop"
                                         x-show="open"
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                         x-transition:enter-end="opacity-100 translate-y-0"
                                         x-transition:leave="transition ease-out duration-100"
                                         x-transition:leave-start="opacity-100 translate-y-0"
                                         x-transition:leave-end="opacity-0 -translate-y-1"
                                         @click.outside="open = false"
                                         style="display:none;">
                                        <div class="guest-pop-head">
                                            <label class="guest-pop-label" for="expected_guests">How many guests?</label>
                                            <span id="totalGuestsReadout" class="count-readout tabnum" aria-hidden="true">1</span>
                                        </div>
                                        <div class="stepper flex items-center gap-2">
                                            <button type="button" class="btn-step w-10 h-10 rounded-xl border border-emerald-deep/15 bg-cream-warm/60 flex items-center justify-center text-ink-soft hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="-1" aria-label="Fewer guests">
                                                <x-booking.ui.icon-solid name="minus" class="text-[18px]" />
                                            </button>
                                            <input type="number" id="expected_guests" name="expected_guests" value="{{ old('expected_guests', 1) }}" aria-describedby="totalGuestsNote" class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/10 bg-cream-warm/60 text-ink text-sm text-center focus:bg-cream-warm focus:border-gold/60 focus:ring-2 focus:ring-gold/20 outline-none transition-[color,background-color,border-color,box-shadow] font-bold [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" min="1" max="{{ $maxGuestsPerBooking }}" required>
                                            <button type="button" class="btn-step w-10 h-10 rounded-xl border border-emerald-deep/15 bg-cream-warm/60 flex items-center justify-center text-ink-soft hover:bg-cream-warm hover:border-gold/50 hover:text-emerald-deep active:scale-95 transition-[transform,color,background-color,border-color,box-shadow] cursor-pointer shrink-0" data-step="1" aria-label="More guests">
                                                <x-booking.ui.icon-solid name="plus" class="text-[18px]" />
                                            </button>
                                        </div>
                                        <p id="totalGuestsNote" class="count-note">Everyone staying, including children. We fit them into rooms in the next step.</p>
                                        <button type="button" class="guest-pop-done" @click="open = false">Done</button>
                                    </div>
                                </div>
                            </div>

                            {{-- What the dates just bought, said as soon as there are
                                 two of them. Filled by booking.js from the same
                                 availability pass the room grid reads. --}}
                            <div id="stayAvailability" class="co-note" hidden>
                                <p class="co-note-line" id="stayAvailabilityLine"></p>
                                <p class="co-note-sub" id="stayAvailabilitySub"></p>
                            </div>
                        </x-booking.checkout.step-card>
                    </section>

                    {{-- ─────────────── STEP 2 · YOUR ROOMS ─────────────── --}}
                    <section class="co-panel @if ($openStep === 'rooms') is-active @endif" data-step-panel="rooms" id="stepCardRooms" aria-label="Your rooms">
                        <x-booking.checkout.step-card title="Your Rooms" lead="Pick the styles you want. We seat your party as you add them, and you can move people between rooms below.">
                            {{-- No preamble beyond the card's own lead. The status
                                 line below says the same thing about the guest's
                                 actual numbers rather than in the abstract.

                                 The single status line replaces a meter, a separate
                                 note two cards up, a "Set total to N guests" button
                                 that offered to shrink the party to whatever the
                                 rooms happened to hold, and a hint that told the
                                 guest to go and edit a field in another step. --}}
                            <div id="allocationMeter" class="alloc-meter mb-4" data-state="empty" role="status" aria-live="polite">
                                {{-- Pips or figures, never both. They said the same
                                     thing twice, side by side. The dots are the
                                     faster read, so they carry it whenever the party
                                     is small enough to count at a glance, and the
                                     figures step in only when it is not. --}}
                                <div class="alloc-meter-head">
                                    <span id="allocPips" class="alloc-pips" aria-hidden="true"></span>
                                    <span id="allocCount" class="alloc-meter-count tabnum" hidden><span id="allocAssigned">0</span> of <span id="allocExpected">1</span> seated</span>
                                </div>
                                <div class="alloc-meter-track">
                                    <span id="allocMeterFill" class="alloc-meter-fill"></span>
                                </div>
                                <p id="allocMeterHint" class="alloc-meter-hint">Pick a room style below and we’ll seat your guests in it.</p>
                            </div>

                            {{-- A ready-made answer, offered before the guest is
                                 asked to work one out.

                                 Seating a party across room styles is a packing
                                 problem, and the form knows the rates, the bed
                                 counts and what is free tonight — everything
                                 needed to solve it. Making the guest solve it
                                 anyway, by adding rooms one at a time and watching
                                 a counter, was the hardest part of this page. This
                                 states an allocation that seats everyone and what
                                 it costs, and one press takes it. Hidden whenever
                                 it has nothing to offer: no dates yet, one guest,
                                 or a party already seated. --}}
                            <div id="roomSuggestion" class="room-suggestion mb-4" hidden>
                                <div class="room-suggestion-body">
                                    <p class="room-suggestion-label">Suggested</p>
                                    <p id="roomSuggestionText" class="room-suggestion-text"></p>
                                </div>
                                <button type="button" id="roomSuggestionApply" class="press room-suggestion-btn">Use this</button>
                            </div>

                            <p class="co-eyebrow" id="roomPickerLabel">Available <span id="roomPickerDates"></span></p>

                            {{-- A grid of cards, not a list of rows.

                                 Seven styles stacked one per line ran to ~660px —
                                 most of a screen of scrolling to compare four
                                 numbers that would fit side by side. Four to a row
                                 puts the whole property in two rows, which is what
                                 a guest is actually doing here: looking at all of
                                 them at once and picking.

                                 One button per card, not a stepper. A stepper asks
                                 "how many of these", which is a question about a
                                 quantity whose consequences the guest cannot see;
                                 Add says what it does, and the room it produces
                                 appears in the list underneath with its own guest
                                 count and its own way out. Removing happens there,
                                 next to the thing being removed. --}}
                            {{-- data-max-rooms is store()'s MAX_ROOMS_PER_BOOKING, so the picker
                                 stops where the server does rather than letting the guest build
                                 a room it will reject. --}}
                            <ul id="roomPicker" class="room-picker-list hide-sold-out" data-max-rooms="{{ $maxRoomsPerBooking }}" aria-labelledby="roomPickerLabel">
                                @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                                    <li class="room-card" data-room-type="{{ $type['id'] }}" data-beds="{{ $type['beds'] }}" data-price="{{ $type['price'] }}">
                                        <span class="room-card-media">
                                            <x-img :src="$type['image']" :alt="$type['title']" loading="lazy" sizes="(max-width: 640px) 45vw, 200px" class="h-full w-full object-cover" />
                                            {{-- What is left of this style for these dates. Filled by
                                                 booking.js once the availability pass has run. --}}
                                            <span class="room-card-avail" data-room-avail hidden></span>
                                            <output class="room-card-qty tabnum" data-room-qty aria-label="{{ $type['title'] }} in your booking">0</output>
                                        </span>
                                        <span class="room-card-body">
                                            <span class="room-card-title">{{ $type['title'] }}</span>
                                            <span class="room-card-line">
                                                <span class="room-cap-pill">Sleeps {{ $type['beds'] }}</span>
                                                <span class="room-card-price"><span class="tabnum">₱{{ number_format($type['price']) }}</span><span class="room-card-per">/night</span></span>
                                            </span>
                                            {{-- Filled by booking.js against the party size: fits
                                                 everyone, takes some of them, or too small. --}}
                                            <span class="room-card-fit" data-room-fit></span>
                                            <span class="room-card-note" data-room-note></span>
                                            <button type="button" class="room-add press" data-room-step="1" data-room-add>
                                                <span data-room-add-label>Add</span>
                                            </button>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            {{-- Sold-out styles are not removed from the grid — the
                                 availability pass, the fit pass and the suggestion
                                 all read every card — but a guest scanning for a
                                 room should not have to read past four of them.
                                 They collapse behind one press that says how many
                                 there are. --}}
                            <button type="button" id="roomSoldOutToggle" class="co-disclosure" hidden></button>

                            {{-- The way out of the strict view.

                                 Three Doubles for six people is a real booking,
                                 and often the cheaper one, so the styles that
                                 cannot hold the party alone are not removed —
                                 they are held back behind one press, and the
                                 press explains what it does. If nothing at all
                                 fits the party on its own, booking.js turns this
                                 on by itself rather than presenting a list where
                                 everything is disabled. --}}
                            <div class="room-split" id="roomSplit" hidden>
                                <p class="room-split-text" id="roomSplitText"></p>
                                <button type="button" id="roomSplitToggle" class="room-split-btn"></button>
                            </div>

                            {{-- The Senior/PWD request, asked where it has an effect.

                                 It used to sit at the very bottom of the personal-
                                 details form, a step away from the rooms whose
                                 counters it governs — so a guest ticked a box and
                                 nothing visible happened. Here it opens the
                                 per-room senior counter in each room below it, and
                                 the sentence says so. --}}
                            <div class="co-check-card mt-6">
                                <input type="checkbox" id="request_discount" name="request_discount" value="1" class="co-check" @checked(old('request_discount'))>
                                <div class="co-check-body">
                                    <label for="request_discount" class="co-check-title">I want to request a 20% discount per Senior Citizen / PWD</label>
                                    <span class="co-check-sub">You'll upload verification documents after booking. Tick this and each room below gets a counter for how many of them sleep there.</span>
                                </div>
                            </div>

                            <p class="co-eyebrow co-eyebrow--spaced">Rooms in your booking</p>
                            <div id="reservationBlocks" class="co-picks">
                                <!-- JS will inject blocks here -->
                            </div>
                            <p id="reservationEmpty" class="co-picks-empty">Nothing added yet. Pick a room above and we’ll seat your guests in it.</p>

                            {{-- Said once for the booking, not once per room.

                                 Guests choose a style, not a door. The room is
                                 assigned by BookingController::store from what is
                                 actually free at the moment the booking commits,
                                 which is the only moment the answer is true — a
                                 tile picked five minutes earlier was a promise the
                                 server then had to break about a third of the time
                                 on a busy weekend. Front desk and admin still pick
                                 numbers, because they are standing in the building. --}}
                            <p id="reservationKeyNote" class="co-keynote" hidden>
                                <x-booking.ui.icon-solid name="key" />
                                <span>We’ll assign your room numbers when the booking is confirmed, and they’re on your confirmation. Travelling together or need a particular floor? Say so in Special requests on the next step.</span>
                            </p>
                        </x-booking.checkout.step-card>
                    </section>

                    {{-- ─────────────── STEP 3 · YOUR DETAILS ─────────────── --}}
                    <section class="co-panel @if ($openStep === 'details') is-active @endif" data-step-panel="details" id="stepCardDetails" aria-label="Your details">
                        <x-booking.checkout.step-card title="Your Details" lead="Your rooms are picked. This is the last thing before we hold them.">
                            @include('public.booking.partials.step-guest')

                            {{-- A booking used to be agreed to in silence, and then in
                                 the sidebar beside three cards at once. It belongs at
                                 the end of the last step, immediately above the button
                                 that acts on it.

                                 The terms are stated inline rather than behind a link
                                 because every one of them is enforced in code: the
                                 hold in the summary, cancelBooking()'s unpaid-only
                                 rule, the reschedule deadline in
                                 RescheduleRequestController, and the configured
                                 check-in time the confirmation page states. Nothing
                                 is promised here that the system does not do. --}}
                            <div class="co-check-card co-check-card--terms mt-6" id="termsCard">
                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required class="co-check" @checked(old('accept_terms'))>
                                <div class="co-check-body">
                                    <label for="accept_terms" class="co-check-title">I agree to the booking terms</label>
                                    <span class="co-check-sub">Free to cancel while unpaid · {{ $holdLabel }} hold · a paid booking can’t be cancelled</span>
                                    <div x-data="{ open: false }">
                                        <button type="button" class="co-check-more" @click="open = !open" :aria-expanded="open ? 'true' : 'false'"
                                                x-text="open ? 'Hide the full terms' : 'Read the full terms'">Read the full terms</button>
                                        <p class="co-terms-full" x-show="open" x-transition.opacity style="display:none;">
                                            Check-in from {{ $checkinTime }} with a valid ID for every guest. An unpaid booking can be cancelled free of charge, and is released automatically once the {{ $holdLabel }} hold runs out. <strong>A paid booking cannot be cancelled</strong> — if your plans change, request a reschedule before {{ $checkinTime }} on your check-in day. Miss that and the booking is forfeited with no refund.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </x-booking.checkout.step-card>
                    </section>

                    {{-- ─────────────── ACTION BAR ───────────────
                         Back, what is still missing, and the one button that
                         moves. Sticky to the foot of the column so it is under
                         the guest's thumb on a phone and in the corner of the
                         eye on a desktop, rather than pinned in a sidebar that
                         is off-screen below lg. --}}
                    <div class="co-actions" id="checkoutActions">
                        <button type="button" id="coBack" class="co-btn co-btn--ghost" aria-label="Back a step" hidden><span aria-hidden="true">&larr;</span><span class="co-btn-word">Back</span></button>

                        {{-- The total, for the widths where the summary column is
                             stacked out of sight underneath. --}}
                        <div class="co-actions-total lg:hidden">
                            <span class="co-actions-total-label">Total due</span>
                            {{-- "—" until a real total exists; ₱0 due would be a false statement --}}
                            <span id="mobileTotalAmount" class="co-actions-total-value tabnum">—</span>
                            <span id="mobileMetaLine" class="co-actions-total-meta"></span>
                        </div>

                        {{-- What is still missing, stated where the guest is about to
                             press the button rather than after they have. The button
                             stays enabled on purpose: a dead control with no
                             explanation is worse than a live one that tells you where
                             to go (and booking.js scrolls to and focuses the offending
                             field either way). --}}
                        <p id="bookingBlocker" class="blocker-line">
                            <x-booking.ui.icon-solid name="circle-info" />
                            <span id="bookingBlockerText">Start by choosing your stay dates.</span>
                        </p>

                        <button type="submit" id="btnSubmitBooking" class="co-btn co-btn--primary press focus-ring">
                            <span id="coPrimaryLabel">Continue</span>
                        </button>
                    </div>
                </div>

                <!-- Right column: sticky summary -->
                <aside class="co-side" aria-label="Booking summary">
                    {{-- Sticky only where there is a sidebar to be sticky in, and never taller
                         than the space it is pinned into.

                         This card runs to ~774px once rooms are picked. Pinned at
                         top-28 (112px) that puts its bottom at 886px, so it needed a
                         886px-tall viewport to be seen whole — more than a 1366x768
                         laptop has at 100%, and far more than the 614px it has at
                         125% display scaling, which is what a lot of people run.
                         Everything past the fold simply could not be scrolled to: a
                         stuck element does not move, and the page scroll moves the
                         form beside it.

                         Capping to the viewport and letting the card scroll itself
                         fixes it at every height. Below lg it is not sticky at all —
                         the column is stacked there and the action bar's own total
                         keeps the figure in view. --}}
                    <div class="co-card co-summary co-enter" style="--co:3">
                        <h3 class="co-summary-title">Booking <span class="italic text-brass-ink">Summary</span></h3>

                        {{-- Rendered by booking.js. The initial markup mirrors its
                             empty state exactly, so the takeover on load is
                             invisible. --}}
                        <div id="summaryInvoice" class="co-summary-body">
                            <div class="co-summary-empty">
                                <x-booking.ui.icon-solid name="calendar-days" class="text-4xl mb-3 block text-emerald-deep/20" />
                                <p>Please select your stay dates.</p>
                            </div>
                        </div>

                        {{-- The badges below have always promised an "Instant hold"
                             without ever saying how long it lasts. It is
                             App\Support\PaymentWindow and ExpireBookingsCommand
                             really does drop the booking, so the number belongs on
                             the page the guest is agreeing on — not only in the
                             confirmation email. --}}
                        <p class="co-summary-hold">
                            Your rooms are held for <strong>{{ $holdLabel }}</strong> after you confirm — or until check-in on your arrival day, if that comes first. Pay within that window or they’re released back to other guests.
                        </p>

                        {{-- Ticking the Senior/PWD box changes how this booking is
                             paid, and the guest ticked it a step ago on a screen they
                             can no longer see. Said here rather than discovered on the
                             payment page. --}}
                        <p id="deskPaymentNotice" class="co-summary-desk hidden">
                            You asked for the Senior&nbsp;/&nbsp;PWD discount, so this booking is <strong>settled at our front desk</strong> — not online. Bring the original ID for every discounted guest.
                        </p>

                        <div class="co-summary-assure">
                            <span><x-booking.ui.icon-solid name="lock" /> Secure</span>
                            <span><x-booking.ui.icon-solid name="ban" /> No prepayment</span>
                            <span><x-booking.ui.icon-solid name="circle-check" /> Instant hold</span>
                        </div>
                    </div>
                </aside>
            </form>
        </div>
    </div>

    <!-- Template for Room Blocks -->
    <template id="reservationBlockTemplate">
        @include('public.booking.partials.reservation-block')
    </template>

    {{-- Glyphs for the fragments booking.js builds as HTML strings: the
         summary's two empty states and the Senior/PWD note. Those used to be
         `<i class="fa-solid …">` written into the JS, which drew nothing once
         the public layout dropped the Font Awesome webfont. Rendered here
         instead of hardcoding the path data in the script, so
         App\Support\AdminIcons stays the one place a glyph is defined. --}}
    <template id="bookingIcons">
        <span data-icon="calendar-days"><x-booking.ui.icon-solid name="calendar-days" class="text-4xl mb-3 block text-emerald-deep/20" /></span>
        <span data-icon="bed"><x-booking.ui.icon-solid name="bed" class="text-4xl mb-3 block text-emerald-deep/20" /></span>
        <span data-icon="circle-info"><x-booking.ui.icon-solid name="circle-info" class="text-[15px] text-palay-800" /></span>
    </template>

    {{-- Without JS there is no wizard to step through, so every panel shows and
         the bar stops floating. A booking still cannot be completed — the room
         blocks are built by booking.js — but the form is at least legible
         rather than one card and a dead button. --}}
    <noscript>
        <style>
            .co-panel { display: block !important; }
            .co-actions { position: static !important; }
        </style>
    </noscript>

@endsection

@push('scripts')
{{-- booking.js is vanilla JS — no jQuery dependency --}}
<script>
    // Make PHP variables available to JS
    window.INITIAL_ROOM_TYPE = "{{ $selectedRoomType ? $selectedRoomType['id'] : '' }}";
    window.INITIAL_GUESTS = "{{ $guests ?? 1 }}";
    window.ROOM_TYPES_CONFIG = @json($roomTypes);
</script>
<script src="{{ \App\Support\PublicScript::url('js/booking.js') }}"></script>

<script>
    // Senior/PWD bookings are settled in person (PaymentController refuses the
    // online route for them), so the notice in the summary follows the checkbox
    // rather than waiting to surprise the guest on a payment page that turns
    // them away. The same tick opens the per-room senior counters, which is why
    // the class lands on the picks list too.
    document.addEventListener('DOMContentLoaded', function () {
        const box = document.getElementById('request_discount');
        const notice = document.getElementById('deskPaymentNotice');
        const picks = document.getElementById('reservationBlocks');
        if (!box) return;

        const sync = () => {
            notice?.classList.toggle('hidden', !box.checked);
            picks?.classList.toggle('wants-seniors', box.checked);
        };
        box.addEventListener('change', sync);
        sync();
    });

    // Early check-in is a request the desk grants, not a slot the form sells.
    // The caveat appears only when the guest picks a time before check-in, so
    // it reads as an answer to what they just did rather than standing small
    // print nobody attributes to anything.
    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('arrival_time');
        const note = document.getElementById('earlyCheckinNote');
        if (!select || !note) return;

        const checkinTime = @json(config('hostel.checkin_time', '14:00'));
        const sync = () => {
            const v = select.value;
            // '00:00' is the "after midnight" catch-all at the end of the list,
            // not an early arrival — same carve-out as StaySchedule.
            const early = v !== '' && v !== '00:00' && v < checkinTime;
            note.classList.toggle('hidden', !early);
        };
        select.addEventListener('change', sync);
        sync();
    });
</script>

@if ($errors->any() || session('error'))
<script>
    // Server-side rejections pop up too, not just the client-side checks.
    // "The following rooms are already booked: 102" comes back from the
    // double-booking guard in BookingController::store() after a redirect, and
    // used to land in a banner above a very long form that the guest was
    // already scrolled past. DOMContentLoaded because window.toast is defined
    // by app.js, which is a deferred Vite module.
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.toast !== 'function') return;
        @foreach ($errors->all() as $error)
            window.toast(@json($error), 'error');
        @endforeach
        @if (session('error'))
            window.toast(@json(session('error')), 'error');
        @endif
    });
</script>
@endif
@endpush
