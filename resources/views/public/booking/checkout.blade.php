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
{{-- Cream Boutique, not Night Estate: checkout now matches the discount and
     payment pages either side of it, so the booking journey reads as one
     place instead of dipping into a dark room in the middle. The night aura
     and film grain that dressed the dark canvas came out with it. --}}
@section('content')

    <div class="min-h-screen bg-canvas pt-28 pb-24 relative isolate overflow-x-clip">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="co-enter mb-10 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4" style="--co:0">
                <div>
                    <span class="font-label inline-flex items-center gap-3 text-[11px] font-normal uppercase tracking-[0.4em] text-palay-800 mb-3">
                        <span class="h-px w-8 bg-gold/50"></span> Almost there
                    </span>
                    <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">Complete your {{-- brass-ink, not gold. --color-gold is a 75%-lightness accent and lands
                         at 2.02:1 on cream — DESIGN.md is explicit that gold never carries
                         text on this ground, and at 48px display the floor is still 3:1.
                         brass-ink is the same brass darkened until it reads (~4.9:1), so the
                         accent survives and the word does too. --}}
                        <span class="italic text-brass-ink">booking</span></h1>
                    <p class="text-sm font-medium text-ink-soft mt-3">Fill in your details to secure your reservation. No payment needed yet.</p>
                </div>
                <a href="{{ route('home') }}#rooms" class="font-label gold-underline self-start sm:self-end text-[11px] font-normal uppercase tracking-[0.3em] text-ink-soft hover:text-emerald-deep transition-colors">
                    &larr; Back to Rooms
                </a>
            </div>

            <!-- Live progress rail — booking.js toggles .done per step; each
                 step doubles as a jump-link to its card (same deep-link
                 contract as the summary rows) -->
            <ol id="checkoutProgress" class="co-enter mb-8 grid grid-cols-3 gap-3" style="--co:1">
                @foreach (['dates' => 'Your stay', 'details' => 'Your details', 'rooms' => 'Rooms'] as $step => $label)
                    {{-- The <li> used to carry role="button" itself, which strips
                         its listitem semantics and left the <ol> announcing as a
                         list with no items (axe `list`). The interactive part is
                         a real <button> inside instead: the list stays a list,
                         and Enter/Space/focus come from the element natively
                         rather than from tabindex + a keydown handler.
                         booking.js delegates from #checkoutProgress via
                         closest('[data-progress-step]'), so clicks still resolve
                         to this <li> exactly as before. --}}
                    <li data-progress-step="{{ $step }}" class="checkout-step">
                        <button type="button" class="focus-ring w-full cursor-pointer rounded-lg text-left"
                                aria-label="Jump to {{ strtolower($label) }}">
                        <div class="flex items-center gap-2.5">
                            <span class="step-dot grid h-7 w-7 shrink-0 place-items-center rounded-full border border-emerald-deep/20 bg-cream-warm/60 text-[11px] font-bold text-ink-faint transition-[color,background-color,border-color,box-shadow] duration-200">
                                <span class="step-num">{{ $loop->iteration }}</span>
                                <svg class="step-check hidden h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <span class="font-label hidden text-[11px] font-normal uppercase tracking-[0.2em] text-ink-soft sm:block">{{ $label }}</span>
                        </div>
                        {{-- The un-done track needs to be DARKER than the page,
                             not lighter — white-on-cream is invisible. --}}
                        <span class="step-bar mt-2.5 block h-1 rounded-full bg-emerald-deep/25 transition-colors duration-300"></span>
                        </button>
                    </li>
                @endforeach
            </ol>

            <x-booking.ui.error-list class="mb-6" />
            {{-- animate-pop replays each time booking.js un-hides this (display swap restarts the keyframes) --}}
            <div id="bookingFormAlert" role="alert" class="animate-pop mb-6 p-4 bg-ember-600/15 text-ember-700 border border-ember-600/40 rounded-2xl text-sm font-semibold d-none"></div>

            <form id="bookingForm" method="POST" action="{{ route('booking.store') }}" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                @csrf

                <!-- Hidden aggregate values needed for backend forms -->
                {{-- `room_numbers` went with the picker. The guest chooses a
                     room style; BookingController::store assigns the actual
                     rooms from what is free at the moment it commits. --}}
                <input type="hidden" name="num_seniors" id="num_seniors" value="0">
                <input type="hidden" name="check_in" id="check_in_hidden">
                <input type="hidden" name="check_out" id="check_out_hidden">

                <!-- Left Column: Guest Details & Config -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- DATES -->
                    <x-booking.checkout.step-card icon="calendar-days" step="Step 1 of 3" title="Your Stay" id="stepCardDates" class="co-enter scroll-mt-28" style="--co:2">
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
                                    <p id="totalGuestsNote" class="count-note">Everyone staying, including children. We fit them into rooms in step 3.</p>
                                    <button type="button" class="guest-pop-done" @click="open = false">Done</button>
                                </div>
                            </div>
                        </div>
                    </x-booking.checkout.step-card>

                    <!-- GUEST INFO -->
                    <x-booking.checkout.step-card icon="user" step="Step 2 of 3" title="Personal Information" id="stepCardDetails" class="co-enter scroll-mt-28" style="--co:3">
                        @include('public.booking.partials.step-guest')
                    </x-booking.checkout.step-card>

                    <!-- ROOM SELECTION -->
                    <x-booking.checkout.step-card icon="door-open" step="Step 3 of 3" title="Your Rooms" id="stepCardRooms" class="co-enter scroll-mt-28" style="--co:4">
                        {{-- No preamble. A form that needs one to be understood
                             has not been made clear, it has been annotated —
                             and the status line below says the same thing about
                             the guest's actual numbers rather than in the
                             abstract.

                             The party size itself is asked in step 1 now, with
                             the dates. What is left in this card is the one
                             question it exists to ask: which rooms. --}}
                        {{-- The single status line. It replaces a meter, a
                             separate note two cards up, a "Set total to N
                             guests" button that offered to shrink the party to
                             whatever the rooms happened to hold, and a hint
                             that told the guest to go and edit a field in step
                             2. One sentence, one place, and one action beneath
                             it — the Add-room button in the card header. --}}
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

                        {{-- One picker for the whole booking.

                             Quantities, not a grid per room: "two Doubles and a
                             Triple" is how a guest describes what they want, and
                             it is now how they say it. Each row still writes the
                             same reservations[] the server has always read — the
                             blocks below are generated from these numbers. --}}
                        {{-- One picker for the whole booking.

                             Quantities, not a grid per room: "two Doubles and a
                             Triple" is how a guest describes what they want, and
                             it is now how they say it. Each row still writes the
                             same reservations[] the server has always read — the
                             blocks below are generated from these numbers.

                             Every row answers the question actually being asked.
                             It used to say "₱1,800 / night · sleeps 2", which is
                             a fact about the room; against a party of five what
                             the guest needs to know is whether it holds them,
                             and it did not say. Now each row states its capacity
                             against the party — and a style that cannot hold
                             everyone on its own is not selectable until the
                             guest says they are willing to split up. --}}
                        <div class="room-picker mb-5">
                            <p class="room-picker-label">Choose your rooms</p>
                            {{-- A grid of cards, not a list of rows.

                                 Seven styles stacked one per line ran to ~660px
                                 — most of a screen of scrolling to compare four
                                 numbers that would fit side by side. Four to a
                                 row puts the whole property in two rows, which
                                 is what a guest is actually doing here: looking
                                 at all of them at once and picking. --}}
                            {{-- data-max-rooms is store()'s MAX_ROOMS_PER_BOOKING, so the steppers
                                     stop where the server does rather than letting the
                                     guest build a room it will reject. --}}
                            <ul id="roomPicker" class="room-picker-list" data-max-rooms="{{ $maxRoomsPerBooking }}">
                                @foreach (($roomTypes ?? \App\Support\RoomCatalog::all()) as $type)
                                    <li class="room-card" data-room-type="{{ $type['id'] }}" data-beds="{{ $type['beds'] }}" data-price="{{ $type['price'] }}">
                                        <span class="room-card-media">
                                            <x-img :src="$type['image']" :alt="$type['title']" loading="lazy" sizes="(max-width: 640px) 45vw, 200px" class="h-full w-full object-cover" />
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
                                        </span>
                                        <span class="room-card-qty stepper">
                                            <button type="button" class="btn-step room-qty-btn" data-room-step="-1" aria-label="One fewer {{ $type['title'] }}">
                                                <x-booking.ui.icon-solid name="minus" class="text-[14px]" />
                                            </button>
                                            <output class="room-qty tabnum" data-room-qty aria-live="polite" aria-label="{{ $type['title'] }} booked">0</output>
                                            <button type="button" class="btn-step room-qty-btn" data-room-step="1" aria-label="One more {{ $type['title'] }}">
                                                <x-booking.ui.icon-solid name="plus" class="text-[14px]" />
                                            </button>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

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
                        </div>

                        <div id="reservationBlocks" class="space-y-4">
                            <!-- JS will inject blocks here -->
                        </div>

                        {{-- The "Add another room" button is gone: rooms are
                             added by raising a quantity in the picker above,
                             which is the same gesture as changing one’s mind
                             about how many. --}}
                    </x-booking.checkout.step-card>

                </div>

                <!-- Right Column: Sticky Summary -->
                <div class="lg:col-span-4">
                    {{-- Sticky only where there is a sidebar to be sticky in, and never taller
                         than the space it is pinned into.

                         This card runs to ~774px once rooms are picked. Pinned at
                         top-28 (112px) that puts its bottom at 886px, so it needed a
                         886px-tall viewport to be seen whole — more than a 1366x768
                         laptop has at 100%, and far more than the 614px it has at
                         125% display scaling, which is what a lot of people run.
                         Everything past the fold, the total and the confirm button
                         included, simply could not be scrolled to: a stuck element
                         does not move, and the page scroll moves the form beside it.

                         Capping to the viewport and letting the card scroll itself
                         fixes it at every height. Below lg it is not sticky at all —
                         the column is stacked there and the mobile total bar further
                         down already keeps the figure in view. --}}
                    <div class="co-enter bg-cream-warm rounded-3xl p-6 ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] lg:sticky lg:top-28 lg:max-h-[calc(100dvh-8rem)] lg:overflow-y-auto" style="--co:3">
                        <h3 class="text-xl text-ink border-b border-emerald-deep/10 pb-4 mb-4 font-display flex items-center gap-2.5">
                            <x-booking.ui.icon-solid name="receipt" class="text-palay-800 text-[20px]" />
                            Booking <span class="italic text-palay-800 -ml-1">Summary</span>
                        </h3>

                        <!-- Summary Invoice will be rendered here by JS -->
                        {{-- Initial markup mirrors booking.js's empty state exactly,
                             so the JS takeover on load is invisible --}}
                        <div id="summaryInvoice" class="space-y-4 mb-6 text-sm font-medium text-ink-soft">
                            <div class="text-center py-10 text-ink-faint">
                                <x-booking.ui.icon-solid name="calendar-days" class="text-5xl mb-3 block text-emerald-deep/25" />
                                <p class="font-semibold">Please select your stay dates.</p>
                            </div>
                        </div>

                        {{-- What is still missing, stated where the guest is about
                             to press the button rather than after they have.
                             The button stays enabled on purpose: a dead control
                             with no explanation is worse than a live one that
                             tells you where to go (and booking.js now scrolls to
                             and focuses the offending field either way). --}}
                        <p id="bookingBlocker" class="blocker-line">
                            <x-booking.ui.icon-solid name="circle-info" />
                            <span id="bookingBlockerText">Start by choosing your stay dates.</span>
                        </p>

                        {{-- The badges below have always promised an "Instant hold"
                             without ever saying how long it lasts. It is
                             App\Support\PaymentWindow and ExpireBookingsCommand
                             really does drop the booking, so the number belongs on
                             the page the guest is agreeing on — not only in the
                             confirmation email. --}}
                        <div class="mb-4 rounded-2xl border border-emerald-deep/10 bg-cream-warm/50 p-3.5">
                            <p class="flex items-start gap-2 text-[11px] font-semibold text-ink-soft leading-relaxed">
                                <x-booking.ui.icon-solid name="hourglass-half" class="mt-px text-[13px] text-palay-800" />
                                <span>Your rooms are held for <strong class="text-ink">{{ $holdLabel }}</strong> after you confirm — or until check-in on your arrival day, if that comes first. Pay within that window or they're released back to other guests.</span>
                            </p>
                        </div>

                        {{-- Ticking the Senior/PWD box changes how this booking is
                             paid, and the guest ticked it two steps ago on a
                             screen they can no longer see. Said here, next to the
                             button, rather than discovered on the payment page. --}}
                        <div id="deskPaymentNotice" class="mb-4 hidden rounded-2xl border border-gold/40 bg-gold/10 p-3.5">
                            <p class="flex items-start gap-2 text-[11px] font-semibold text-palay-800 leading-relaxed">
                                <x-booking.ui.icon-solid name="building-columns" class="mt-px text-[13px]" />
                                <span>You asked for the Senior&nbsp;/&nbsp;PWD discount, so this booking is <strong>settled at our front desk</strong> — not online. Bring the original ID for every discounted guest.</span>
                            </p>
                        </div>

                        {{-- A booking used to be agreed to in silence. The terms are
                             stated inline rather than behind a link because every one
                             of them is enforced in code: the hold above,
                             cancelBooking()'s unpaid-only rule, the reschedule
                             deadline in RescheduleRequestController, and the
                             configured check-in time the confirmation page states.
                             Nothing is promised here that the system does not do. --}}
                        <div class="mb-4">
                            <label for="accept_terms" class="flex items-start gap-2.5 cursor-pointer select-none">
                                <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required
                                       class="w-4.5 h-4.5 mt-0.5 shrink-0 rounded-md border-emerald-deep/25 bg-cream-warm/60 text-palay-800 focus:ring-gold focus:ring-2 cursor-pointer transition-[color,background-color,border-color,box-shadow]"
                                       @checked(old('accept_terms'))>
                                <span class="text-[11px] font-semibold text-ink-soft leading-relaxed">
                                    I agree to the booking terms
                                    <span class="block font-medium text-ink-faint mt-1">
                                        Check-in from {{ $checkinTime }} with a valid ID for every guest. An unpaid booking can be cancelled free of charge, and is released automatically once the {{ $holdLabel }} hold runs out. <strong class="text-ink-soft">A paid booking cannot be cancelled</strong> — if your plans change, request a reschedule before {{ $checkinTime }} on your check-in day. Miss that and the booking is forfeited with no refund.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <button type="submit" id="btnSubmitBooking" class="font-label press focus-ring w-full min-h-12 py-4 rounded-full text-[12px] font-normal uppercase tracking-[0.2em] bg-emerald-deep text-cream cursor-pointer flex items-center justify-center gap-2 hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)] disabled:opacity-70 disabled:pointer-events-none">
                            <x-booking.ui.icon-solid name="circle-check" class="text-[18px]" />
                            Confirm Booking
                        </button>
                        <div class="mt-4 grid grid-cols-3 gap-1 text-center">
                            <div class="font-label text-[10px] font-normal text-ink-faint uppercase tracking-[0.2em] flex flex-col items-center gap-1">
                                <x-booking.ui.icon-solid name="lock" class="text-[16px] text-palay-800" /> Secure
                            </div>
                            <div class="font-label text-[10px] font-normal text-ink-faint uppercase tracking-[0.2em] flex flex-col items-center gap-1">
                                <x-booking.ui.icon-solid name="ban" class="text-[16px] text-palay-800" /> No prepayment
                            </div>
                            <div class="font-label text-[10px] font-normal text-ink-faint uppercase tracking-[0.2em] flex flex-col items-center gap-1">
                                <x-booking.ui.icon-solid name="circle-check" class="text-[16px] text-palay-800" /> Instant hold
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Mobile sticky total bar (summary column is off-screen on phones) -->
    <div class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-cream-warm/95 backdrop-blur-xl border-t border-emerald-deep/15 px-4 py-3 shadow-[0_-16px_40px_-20px_rgba(6,40,30,0.35)] flex items-center justify-between gap-3">
        <div>
            <p class="font-label text-[10px] font-normal text-ink-faint uppercase tracking-[0.28em] leading-none">Total due</p>
            {{-- "—" until a real total exists; ₱0 due would be a false statement --}}
            <p id="mobileTotalAmount" class="font-display text-xl text-ink tabnum mt-1">-</p>
            <p id="mobileMetaLine" class="text-[10px] font-semibold text-ink-faint mt-0.5"></p>
        </div>
        <button type="submit" form="bookingForm" id="btnSubmitBookingMobile" class="font-label press min-h-11 px-6 py-2.5 rounded-full text-cream text-[12px] font-normal uppercase tracking-[0.2em] cursor-pointer bg-emerald-deep hover:bg-emerald flex items-center gap-1.5 disabled:opacity-70 disabled:pointer-events-none">
            <x-booking.ui.icon-solid name="circle-check" class="text-[16px]" />
            Confirm
        </button>
    </div>

    <!-- Template for Room Blocks -->
    <template id="reservationBlockTemplate">
        @include('public.booking.partials.reservation-block')
    </template>

    {{-- Glyphs for the three fragments booking.js builds as HTML strings: the
         summary's two empty states and the Senior/PWD note. Those used to be
         `<i class="fa-solid …">` written into the JS, which drew nothing once
         the public layout dropped the Font Awesome webfont. Rendered here
         instead of hardcoding the path data in the script, so
         App\Support\AdminIcons stays the one place a glyph is defined. --}}
    <template id="bookingIcons">
        <span data-icon="calendar-days"><x-booking.ui.icon-solid name="calendar-days" class="text-5xl mb-3 block text-emerald-deep/10" /></span>
        <span data-icon="bed"><x-booking.ui.icon-solid name="bed" class="text-5xl mb-3 block text-emerald-deep/10" /></span>
        <span data-icon="circle-info"><x-booking.ui.icon-solid name="circle-info" class="text-[16px] text-palay-800" /></span>
    </template>

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
    // online route for them), so the notice beside the confirm button follows
    // the checkbox two steps up the form rather than waiting to surprise the
    // guest on a payment page that turns them away.
    document.addEventListener('DOMContentLoaded', function () {
        const box = document.getElementById('request_discount');
        const notice = document.getElementById('deskPaymentNotice');
        if (!box || !notice) return;

        const sync = () => notice.classList.toggle('hidden', !box.checked);
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
