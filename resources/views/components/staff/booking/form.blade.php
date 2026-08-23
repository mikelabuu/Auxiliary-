@props([
    'action',
    'availableUrl',
    'emptyBoardMessage' => 'No rooms found.',
    // Passed straight to the summary panel; see its props for why it is a
    // length rather than a utility class.
    'stickyOffset' => '6rem',
])

{{--
    The staff booking form, shared by the admin Manual Booking screen and the
    front desk Walk-In screen. Those two pages were byte-identical here apart
    from the post target and the sticky offset, so they now differ only in
    those two props.

    The behaviour lives in resources/js/pages/staff-booking-form.js, which
    binds to the ids and data-slots below — renaming any of them means
    updating that module too.
--}}
<form method="POST" action="{{ $action }}" id="walkin-form"
      data-staff-booking-form
      data-available-url="{{ $availableUrl }}"
      data-empty-board-message="{{ $emptyBoardMessage }}"
      class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
    @csrf

    {{-- Picks from a submission that failed validation, for the script to restore. --}}
    <script type="application/json" id="staff-booking-old-reservations">@json(old('reservations', []))</script>

    {{-- ══════════ Left column — the three steps ══════════ --}}
    <div class="space-y-6 lg:col-span-8">

        <x-staff.booking.step-card icon="calendar" step="1" title="Stay Dates" :delay="80">
            <x-slot:aside>
                <span id="nights-badge" class="hidden animate-pop whitespace-nowrap rounded-full border border-clsu-200 bg-clsu-50 px-3.5 py-1.5 text-2xs font-bold uppercase tracking-[0.14em] text-ink/80"></span>
            </x-slot:aside>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach([
                    ['name' => 'check_in',  'label' => 'Check-In',  'default' => date('Y-m-d')],
                    ['name' => 'check_out', 'label' => 'Check-Out', 'default' => date('Y-m-d', strtotime('+1 day'))],
                ] as $date)
                    <div>
                        <x-staff.booking.label :for="$date['name']">{{ $date['label'] }}</x-staff.booking.label>
                        <div class="relative">
                            <x-admin.ui.icon name="calendar" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-brand-ink" />
                            <x-staff.booking.input
                                type="text"
                                :name="$date['name']"
                                :id="$date['name']"
                                :value="old($date['name'], $date['default'])"
                                placeholder="Select date"
                                readonly
                                required
                                class="flatpickr-date cursor-pointer !pl-10 !pr-4 font-semibold" />
                        </div>
                    </div>
                @endforeach
            </div>

            <x-staff.booking.stay-presets />

            <div id="availability-status" class="mt-4 flex min-h-[1.25rem] items-center gap-2 text-xs font-medium text-muted"></div>
        </x-staff.booking.step-card>

        <x-staff.booking.step-card icon="user" step="2" title="Guest Details" :delay="120">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-staff.booking.label for="guest_name">Guest Name</x-staff.booking.label>
                    <x-staff.booking.input type="text" name="guest_name" id="guest_name" :value="old('guest_name')" placeholder="Full name" required />
                </div>
                <div>
                    <x-staff.booking.label for="guest_phone">Guest Phone</x-staff.booking.label>
                    <x-staff.booking.input type="text" name="guest_phone" id="guest_phone" :value="old('guest_phone')" placeholder="09xx xxx xxxx" required />
                </div>
                {{-- Second number, same as the guest-facing form. Optional: the
                     desk cannot invent one the walk-in does not have. --}}
                <div>
                    <x-staff.booking.label for="guest_phone_alt">Second Phone <span class="font-medium normal-case text-faint">(optional)</span></x-staff.booking.label>
                    <x-staff.booking.input type="text" name="guest_phone_alt" id="guest_phone_alt" :value="old('guest_phone_alt')" placeholder="Another number we can reach" />
                </div>
                <div>
                    {{-- Optional at the desk, required on the public form: a
                         walk-in is standing here, an online booking is not. --}}
                    <x-staff.booking.label for="referred_by">Reference Person <span class="font-medium normal-case text-faint">(optional)</span></x-staff.booking.label>
                    <x-staff.booking.input type="text" name="referred_by" id="referred_by" :value="old('referred_by')" placeholder="Office or person endorsing this guest" />
                </div>
                {{-- The other two thirds of the reference person. Same three
                     answers the public form now insists on, so a walk-in typed
                     at the counter and a booking made online produce the same
                     record — the desk should not have to remember which surface
                     a booking came through to know what it can rely on. --}}
                <div>
                    <x-staff.booking.label for="referred_by_phone">Reference Contact <span class="font-medium normal-case text-faint">(optional)</span></x-staff.booking.label>
                    <x-staff.booking.input type="text" name="referred_by_phone" id="referred_by_phone" :value="old('referred_by_phone')" placeholder="Number to ring to confirm" />
                </div>
                <div>
                    <x-staff.booking.label for="referred_by_purpose">Purpose <span class="font-medium normal-case text-faint">(optional)</span></x-staff.booking.label>
                    <x-staff.booking.input type="text" name="referred_by_purpose" id="referred_by_purpose" :value="old('referred_by_purpose')" placeholder="What the stay is for" />
                </div>
                <div>
                    <x-staff.booking.label>Guest Address</x-staff.booking.label>
                    <div class="mb-selects">
                        <x-address-selector />
                    </div>
                </div>
                <div>
                    <x-staff.booking.label for="expected_guests">Expected Guests</x-staff.booking.label>
                    <x-staff.booking.stepper label="guests">
                        <x-staff.booking.input
                            type="number"
                            name="expected_guests"
                            id="expected_guests"
                            :value="old('expected_guests', 1)"
                            min="1"
                            max="60"
                            align="center"
                            class="!px-4 !py-2.5"
                            required />
                    </x-staff.booking.stepper>
                </div>
            </div>
        </x-staff.booking.step-card>

        <x-staff.booking.step-card icon="bed" step="3" title="Pick Rooms" :delay="160">
            <x-slot:aside>
                <div class="min-w-[190px]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-2xs font-bold uppercase tracking-[0.18em] text-faint">Guests assigned</span>
                        <span id="assign-progress-text" class="whitespace-nowrap text-2xs font-bold text-ink/80 font-data tabnum">0 / 1</span>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-emerald-deep/10">
                        <div id="assign-progress-bar" class="h-full w-0 rounded-full bg-palay-400 transition-[width] duration-300"></div>
                    </div>
                </div>
            </x-slot:aside>

            <p class="mb-3 text-xs font-medium text-muted">Tap an available room to add it to the booking, then assign guests to each room below.</p>

            {{-- Type filter pills + board are both rendered by the page script --}}
            <div id="type-filter-pills" class="mb-4 flex flex-wrap gap-2"></div>

            <x-staff.booking.room-legend />

            <div id="room-board" class="space-y-5"></div>

            {{-- ── Guest assignment for the picked rooms ── --}}
            <div class="mt-6 border-t border-emerald-deep/10 pt-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h5 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-brand-ink-deep">
                        <x-admin.ui.icon name="users" class="w-4 h-4" />
                        Guest Assignment
                    </h5>
                    <button type="button" id="auto-assign" class="press inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-emerald-deep/20 bg-white px-4 py-2 text-2xs font-bold uppercase tracking-[0.14em] text-brand-ink-deep transition-colors hover:bg-emerald-deep hover:text-cream">
                        <x-admin.ui.icon name="zap" class="w-3.5 h-3.5" />
                        Auto-distribute guests
                    </button>
                </div>

                <div id="assignment-list" class="space-y-3"></div>

                <p id="assignment-empty" class="rounded-2xl border border-dashed border-emerald-deep/20 bg-white/50 px-5 py-6 text-center text-sm font-medium text-faint">
                    No rooms picked yet. Tap available rooms on the board above.
                </p>
            </div>
        </x-staff.booking.step-card>
    </div>

    {{-- ══════════ Right column — live booking summary ══════════ --}}
    <x-staff.booking.summary-panel :sticky-offset="$stickyOffset" />
</form>

<x-staff.booking.assignment-template />
