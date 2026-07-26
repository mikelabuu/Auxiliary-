@extends('layouts.frontdesk')
@section('title', 'Front Desk · Manual Booking')
@section('content')

{{--
    Port of the admin Manual Booking experience (staff/manualbooking/index)
    into the front desk: tap-to-pick room board, guest assignment cards, and
    a sticky live booking summary. Posts to the front desk walk-in store —
    the reservation field contract is identical.
--}}

{{-- Upcoming reservations — light strip --}}
@if($upcomingBookings->isNotEmpty())
    <div class="card animate-in" style="animation-delay:40ms">
        <div class="card-body" style="padding:14px 20px;">
            <div class="flex flex-wrap items-center gap-2">
                <span class="filter-row-label" style="margin-right:4px;">Upcoming this week</span>
                @foreach($upcomingBookings as $booking)
                    @foreach($booking->reservations as $res)
                        <span class="cell-tag" style="gap:6px;">
                            <span style="width:6px;height:6px;border-radius:50%;background:var(--color-g-500);"></span>
                            Room {{ $res->room_number }} · {{ \Carbon\Carbon::parse($booking->check_in)->format('M d') }}-{{ \Carbon\Carbon::parse($booking->check_out)->format('M d') }}
                        </span>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
@endif

<x-frontdesk.flash />

<form method="POST" action="{{ route('frontdesk.walkin.store') }}" id="walkin-form" class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
    @csrf

    <!-- ══════════ Left column — the three steps ══════════ -->
    <div class="space-y-6 lg:col-span-8">

        <!-- STEP 1 · STAY DATES -->
        <div class="animate-in rounded-xl bg-cream-warm p-6 ring-1 ring-emerald-deep/10 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sm:p-7" style="animation-delay:80ms">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                        <x-admin.ui.icon name="calendar" class="w-4 h-4" />
                    </span>
                    <div>
                        <span class="block text-[9px] font-black uppercase tracking-[0.2em] leading-none text-stone-400">Step 1 of 3</span>
                        <h4 class="mt-1 text-lg font-semibold leading-none text-ink">Stay Dates</h4>
                    </div>
                </div>
                <span id="nights-badge" class="hidden animate-pop whitespace-nowrap rounded-full border border-clsu-200 bg-clsu-50 px-3.5 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-ink/80"></span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Check-In</label>
                    <div class="relative">
                        <x-admin.ui.icon name="calendar" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-emerald" />
                        <input type="text" name="check_in" id="check_in" value="{{ old('check_in', date('Y-m-d')) }}" placeholder="Select date" readonly
                               class="flatpickr-date w-full cursor-pointer rounded-xl border border-emerald-deep/15 bg-white pl-10 pr-4 py-2.5 text-sm font-semibold text-ink transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none" required>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Check-Out</label>
                    <div class="relative">
                        <x-admin.ui.icon name="calendar" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-emerald" />
                        <input type="text" name="check_out" id="check_out" value="{{ old('check_out', date('Y-m-d', strtotime('+1 day'))) }}" placeholder="Select date" readonly
                               class="flatpickr-date w-full cursor-pointer rounded-xl border border-emerald-deep/15 bg-white pl-10 pr-4 py-2.5 text-sm font-semibold text-ink transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none" required>
                    </div>
                </div>
            </div>

            <!-- Quick stay presets -->
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400">Quick pick</span>
                <button type="button" data-preset="tonight" class="stay-preset press cursor-pointer rounded-full border border-emerald-deep/15 bg-white px-3.5 py-1.5 text-[11px] font-bold text-emerald-deep transition-colors hover:border-clsu-400 hover:bg-clsu-50">Tonight</button>
                <button type="button" data-preset="two" class="stay-preset press cursor-pointer rounded-full border border-emerald-deep/15 bg-white px-3.5 py-1.5 text-[11px] font-bold text-emerald-deep transition-colors hover:border-clsu-400 hover:bg-clsu-50">2 nights</button>
                <button type="button" data-preset="three" class="stay-preset press cursor-pointer rounded-full border border-emerald-deep/15 bg-white px-3.5 py-1.5 text-[11px] font-bold text-emerald-deep transition-colors hover:border-clsu-400 hover:bg-clsu-50">3 nights</button>
                <button type="button" data-preset="weekend" class="stay-preset press cursor-pointer rounded-full border border-emerald-deep/15 bg-white px-3.5 py-1.5 text-[11px] font-bold text-emerald-deep transition-colors hover:border-clsu-400 hover:bg-clsu-50">Weekend</button>
            </div>

            <div id="availability-status" class="mt-4 flex min-h-[1.25rem] items-center gap-2 text-xs font-medium text-stone-500"></div>
        </div>

        <!-- STEP 2 · GUEST DETAILS -->
        <div class="animate-in rounded-xl bg-cream-warm p-6 ring-1 ring-emerald-deep/10 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sm:p-7" style="animation-delay:120ms">
            <div class="mb-5 flex items-center gap-3">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                    <x-admin.ui.icon name="user" class="w-4 h-4" />
                </span>
                <div>
                    <span class="block text-[9px] font-black uppercase tracking-[0.2em] leading-none text-stone-400">Step 2 of 3</span>
                    <h4 class="mt-1 text-lg font-semibold leading-none text-ink">Guest Details</h4>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Guest Name</label>
                    <input type="text" name="guest_name" value="{{ old('guest_name') }}" placeholder="Full name"
                           class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-sm text-ink placeholder:text-stone-400 transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Guest Phone</label>
                    <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" placeholder="09xx xxx xxxx"
                           class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-sm text-ink placeholder:text-stone-400 transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none" required>
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald">Guest Address</label>
                    <div class="mb-selects">
                        <livewire:address-selector />
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.24em] text-emerald" for="expected_guests">Expected Guests</label>
                    <div class="mb-stepper flex items-center gap-2">
                        <button type="button" data-step="-1" class="mb-step press grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-stone-500 transition-colors hover:border-clsu-500 hover:text-emerald-deep" aria-label="Fewer guests">
                            <x-admin.ui.icon name="minus" class="w-4 h-4" stroke-width="2" />
                        </button>
                        <input type="number" name="expected_guests" id="expected_guests" value="{{ old('expected_guests', 1) }}" min="1" max="60"
                               class="w-full rounded-xl border border-emerald-deep/15 bg-white px-4 py-2.5 text-center text-sm font-bold text-ink transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" required>
                        <button type="button" data-step="1" class="mb-step press grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-stone-500 transition-colors hover:border-clsu-500 hover:text-emerald-deep" aria-label="More guests">
                            <x-admin.ui.icon name="plus" class="w-4 h-4" stroke-width="2" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3 · ROOM BOARD -->
        <div class="animate-in rounded-xl bg-cream-warm p-6 ring-1 ring-emerald-deep/10 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] sm:p-7" style="animation-delay:160ms">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-deep/5 text-emerald-deep ring-1 ring-emerald-deep/10">
                        <x-admin.ui.icon name="bed" class="w-4 h-4" />
                    </span>
                    <div>
                        <span class="block text-[9px] font-black uppercase tracking-[0.2em] leading-none text-stone-400">Step 3 of 3</span>
                        <h4 class="mt-1 text-lg font-semibold leading-none text-ink">Pick Rooms</h4>
                    </div>
                </div>
                <div class="min-w-[190px]">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[10px] font-bold uppercase tracking-[0.18em] text-stone-400">Guests assigned</span>
                        <span id="assign-progress-text" class="whitespace-nowrap text-[11px] font-bold text-ink/80 font-data tabnum">0 / 1</span>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-emerald-deep/10">
                        <div id="assign-progress-bar" class="h-full w-0 rounded-full bg-palay-400 transition-[width] duration-300"></div>
                    </div>
                </div>
            </div>

            <p class="mb-3 text-xs font-medium text-stone-500">Tap an available room to add it to the booking, then assign guests to each room below.</p>

            <!-- Type filter pills -->
            <div id="type-filter-pills" class="mb-4 flex flex-wrap gap-2"></div>

            <!-- Status legend -->
            <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-stone-400"><span class="h-2.5 w-2.5 rounded-[4px] border border-clsu-300 bg-white"></span> Available</span>
                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-stone-400"><span class="h-2.5 w-2.5 rounded-[4px] bg-emerald-deep"></span> Selected</span>
                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-stone-400"><span class="h-2.5 w-2.5 rounded-[4px] border border-ember-200 bg-ember-50"></span> Booked</span>
                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-stone-400"><span class="h-2.5 w-2.5 rounded-[4px] border border-palay-300 bg-palay-100"></span> Cleaning</span>
                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-stone-400"><span class="h-2.5 w-2.5 rounded-[4px] border border-stone-300 bg-stone-100"></span> Maintenance</span>
            </div>

            <!-- Room board (rendered by JS) -->
            <div id="room-board" class="space-y-5"></div>

            <!-- ── Guest assignment for the picked rooms ── -->
            <div class="mt-6 border-t border-emerald-deep/10 pt-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h5 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-emerald-deep">
                        <x-admin.ui.icon name="users" class="w-4 h-4" />
                        Guest Assignment
                    </h5>
                    <button type="button" id="auto-assign" class="press inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-emerald-deep/20 bg-white px-4 py-2 text-[11px] font-bold uppercase tracking-[0.14em] text-emerald-deep transition-colors hover:bg-emerald-deep hover:text-cream">
                        <x-admin.ui.icon name="zap" class="w-3.5 h-3.5" />
                        Auto-distribute guests
                    </button>
                </div>

                <div id="assignment-list" class="space-y-3"></div>

                <p id="assignment-empty" class="rounded-2xl border border-dashed border-emerald-deep/20 bg-white/50 px-5 py-6 text-center text-sm font-medium text-stone-400">
                    No rooms picked yet. Tap available rooms on the board above.
                </p>
            </div>
        </div>
    </div>

    <!-- ══════════ Right column — live booking summary ══════════ -->
    <div class="animate-in lg:sticky lg:top-6 lg:col-span-4" style="animation-delay:200ms">
        <div class="card card-accent card-overflow-hidden">
            <div class="card-header">
                <h3 class="card-title">
                    <x-admin.ui.icon name="receipt" class="w-[18px] h-[18px]" />
                    Booking Summary
                </h3>
                <span id="summary-nights" class="hidden chip chip-green"></span>
            </div>

            <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
                {{-- Dates + guests --}}
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="kv-label" style="margin:0;">Stay dates</span>
                        <span id="summary-dates" class="text-sm font-semibold text-ink">—</span>
                    </div>
                    <div id="summary-guests" class="mt-1.5 text-xs font-medium text-muted text-right">—</div>
                </div>

                {{-- Room lines --}}
                <div id="summary-rooms" class="space-y-2.5 border-t border-[color:var(--color-border)] pt-4 text-sm">
                    <p class="text-faint">Pick rooms on the board to build the summary.</p>
                </div>

                {{-- Subtotal --}}
                <div class="flex items-center justify-between border-t border-[color:var(--color-border)] pt-4 text-sm">
                    <span class="text-muted">Subtotal</span>
                    <span id="summary-subtotal" class="font-semibold tabnum text-ink">₱0</span>
                </div>

                {{-- Senior / PWD flag --}}
                <label for="has_senior_pwd" class="flex cursor-pointer items-center gap-2.5 text-sm text-ink">
                    <input type="checkbox" name="has_senior_pwd" id="has_senior_pwd" class="row-check">
                    Senior / PWD guest present
                </label>

                {{-- Discount --}}
                <div class="form-group">
                    <label class="form-label" for="discount_amount">Discount (₱)</label>
                    <input type="number" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', 0) }}" min="0" step="1" class="form-input tabnum">
                    <p id="discount-hint" class="hidden text-[11px] font-semibold" style="color:var(--color-au-700);"></p>
                </div>
            </div>

            {{-- Total payable — emerald accent footer --}}
            <div style="padding:22px 26px;background:radial-gradient(120% 120% at 100% 0%, rgba(255,255,255,.16) 0%, transparent 50%), linear-gradient(135deg,#10a45c 0%, var(--color-g-700) 100%);color:#fff;">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.24em]" style="color:rgba(255,255,255,.72);">Total payable</p>
                        <p class="mt-1.5 font-display text-3xl font-extrabold leading-none">
                            <span id="summary-total" class="anim-number tabnum" style="display:inline-block;overflow:hidden;"><span>₱0</span></span>
                        </p>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-[0.16em]" style="color:rgba(255,255,255,.7);">Manual · Paid</span>
                </div>

                <button type="submit" id="submit-booking" class="btn btn-center mt-5" style="width:100%;background:#fff;color:var(--color-g-800);font-weight:700;">
                    <x-admin.ui.icon name="check-circle" class="w-4 h-4" />
                    Create Booking
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Template for a picked-room assignment card -->
<template id="assignment-card-template">
    <div class="assignment-card animate-pop space-y-4 rounded-2xl border border-emerald-deep/10 bg-white/80 p-5">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <span data-slot="number" class="grid h-11 w-14 shrink-0 place-items-center rounded-xl bg-emerald-deep font-data text-sm font-bold text-cream shadow-sm"></span>
                <div class="min-w-0">
                    <p data-slot="type" class="truncate text-sm font-bold text-ink"></p>
                    <p data-slot="meta" class="text-[11px] font-medium text-stone-500"></p>
                </div>
            </div>
            <button type="button" data-slot="remove" class="press inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-full border border-ember-200 bg-ember-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-ember-600 transition-colors hover:bg-ember-100">
                Remove
            </button>
        </div>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald">Guests in room</label>
                <div class="mb-stepper flex items-center gap-1.5">
                    <button type="button" data-step="-1" class="mb-step press grid h-9 w-9 shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-stone-500 transition-colors hover:border-clsu-500 hover:text-emerald-deep" aria-label="Fewer guests in room">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <input type="number" data-slot="guests" min="1" value="1"
                           class="w-full rounded-xl border border-emerald-deep/15 bg-white px-2 py-2 text-center text-sm font-bold text-ink transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none" required>
                    <button type="button" data-step="1" class="mb-step press grid h-9 w-9 shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-stone-500 transition-colors hover:border-clsu-500 hover:text-emerald-deep" aria-label="More guests in room">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
                <p data-slot="capacity-hint" class="mt-1.5 text-[10px] font-medium text-stone-400"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.18em] text-emerald">Seniors / PWD</label>
                <div class="mb-stepper flex items-center gap-1.5">
                    <button type="button" data-step="-1" class="mb-step press grid h-9 w-9 shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-stone-500 transition-colors hover:border-clsu-500 hover:text-emerald-deep" aria-label="Fewer seniors">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                    <input type="number" data-slot="seniors" min="0" value="0"
                           class="w-full rounded-xl border border-emerald-deep/15 bg-white px-2 py-2 text-center text-sm font-bold text-ink transition-colors focus:border-clsu-500 focus:ring-2 focus:ring-clsu-500/25 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                    <button type="button" data-step="1" class="mb-step press grid h-9 w-9 shrink-0 cursor-pointer place-items-center rounded-xl border border-emerald-deep/15 bg-white text-stone-500 transition-colors hover:border-clsu-500 hover:text-emerald-deep" aria-label="More seniors">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
                <p class="mt-1.5 text-[10px] font-medium text-stone-400">Cannot exceed guests in this room</p>
            </div>
        </div>
        <span data-slot="hidden-inputs"></span>
    </div>
</template>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/flatpickr/flatpickr.min.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('vendor/flatpickr/flatpickr.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ───────────────────────── State ───────────────────────── */
    const state = {
        rooms: [],              // latest availability payload
        selected: new Map(),    // room_number -> room object
        activeType: 'all',
        loaded: false,
    };
    // Reservations posted before a failed server validation — restored after
    // the first availability fetch so staff don't lose their picks.
    const OLD_RESERVATIONS = @json(old('reservations', []));

    const board = document.getElementById('room-board');
    const pills = document.getElementById('type-filter-pills');
    const assignmentList = document.getElementById('assignment-list');
    const assignmentEmpty = document.getElementById('assignment-empty');
    const cardTemplate = document.getElementById('assignment-card-template');
    const availabilityStatusEl = document.getElementById('availability-status');
    const checkInInput = document.getElementById('check_in');
    const checkOutInput = document.getElementById('check_out');
    const discountInput = document.getElementById('discount_amount');
    const expectedGuestsInput = document.getElementById('expected_guests');
    const progressBar = document.getElementById('assign-progress-bar');
    const progressText = document.getElementById('assign-progress-text');
    const submitBtn = document.getElementById('submit-booking');
    let fpIn = null, fpOut = null; // flatpickr instances (assigned in Boot)

    const peso = n => '₱' + Number(n).toLocaleString('en-PH', { maximumFractionDigits: 2 });
    const wingLabel = w => (w || '').toString().replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

    function toast(message, icon = 'success') {
        window.toast(message, icon); // unified console toasts (resources/js/app.js)
    }

    /* ─────────────────── Generic ± steppers ─────────────────── */
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.mb-step');
        if (!btn) return;
        const input = btn.closest('.mb-stepper')?.querySelector('input');
        if (!input) return;
        const step = parseInt(btn.dataset.step, 10) || 0;
        const min = input.min !== '' ? parseInt(input.min, 10) : 0;
        const max = input.max !== '' ? parseInt(input.max, 10) : Infinity;
        input.value = Math.min(max, Math.max(min, (parseInt(input.value, 10) || 0) + step));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    /* ────────────────── Dates & quick presets ────────────────── */
    function fmtDate(d) {
        const y = d.getFullYear(), m = String(d.getMonth() + 1).padStart(2, '0'), dd = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${dd}`;
    }
    function calcNights() {
        if (!checkInInput.value || !checkOutInput.value) return 0;
        const diff = (new Date(checkOutInput.value) - new Date(checkInInput.value)) / 86400000;
        return diff > 0 ? Math.max(1, Math.round(diff)) : 0;
    }
    document.querySelectorAll('.stay-preset').forEach(btn => {
        btn.addEventListener('click', () => {
            const today = new Date();
            let inD = new Date(today), outD = new Date(today);
            switch (btn.dataset.preset) {
                case 'tonight': outD.setDate(outD.getDate() + 1); break;
                case 'two':     outD.setDate(outD.getDate() + 2); break;
                case 'three':   outD.setDate(outD.getDate() + 3); break;
                case 'weekend': {
                    // Upcoming Friday (or today if already Fri/Sat) through Sunday
                    const day = today.getDay(); // 0 Sun … 6 Sat
                    const toFriday = day <= 5 ? (5 - day) : 6;
                    inD.setDate(inD.getDate() + (day === 6 ? 0 : toFriday));
                    outD = new Date(inD);
                    outD.setDate(inD.getDate() + (day === 6 ? 1 : 2));
                    break;
                }
            }
            if (fpIn) fpIn.setDate(inD, false); else checkInInput.value = fmtDate(inD);
            if (fpOut) { fpOut.set('minDate', new Date(inD.getTime() + 86400000)); fpOut.setDate(outD, false); }
            else checkOutInput.value = fmtDate(outD);
            onDatesChanged();
        });
    });

    function onDatesChanged() {
        if (checkInInput.value) {
            const minOut = new Date(checkInInput.value);
            minOut.setDate(minOut.getDate() + 1);
            if (fpOut) {
                fpOut.set('minDate', minOut);
                if (fpOut.selectedDates[0] && fpOut.selectedDates[0] < minOut) fpOut.setDate(minOut, false);
            } else {
                checkOutInput.min = fmtDate(minOut);
                if (checkOutInput.value && checkOutInput.value < checkOutInput.min) checkOutInput.value = checkOutInput.min;
            }
        }
        updateSummary();
        fetchAvailableRooms();
    }
    checkInInput.addEventListener('change', onDatesChanged);
    checkOutInput.addEventListener('change', onDatesChanged);

    /* ───────────────── Availability fetch ───────────────── */
    function setAvailabilityStatus(stateName) {
        if (stateName === 'loading') {
            availabilityStatusEl.innerHTML = `
                <svg class="w-3.5 h-3.5 animate-spin text-stone-400" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
                <span class="text-stone-400">Checking room availability…</span>`;
            return;
        }
        if (stateName === 'error') {
            availabilityStatusEl.innerHTML = `<span class="text-ember-600">Couldn't check room availability. Please try again.</span>`;
            return;
        }
        const availableCount = state.rooms.filter(r => r.status === 'available').length;
        availabilityStatusEl.innerHTML = availableCount > 0
            ? `<span class="relative flex h-2 w-2 shrink-0">
                   <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-clsu-500 opacity-60"></span>
                   <span class="relative inline-flex h-2 w-2 rounded-full bg-clsu-600"></span>
               </span>
               <span class="font-semibold text-clsu-700">${availableCount} room${availableCount === 1 ? '' : 's'} available for these dates.</span>`
            : `<span class="h-2 w-2 shrink-0 rounded-full bg-ember-500"></span>
               <span class="font-semibold text-ember-600">No rooms available for these dates. Try different dates.</span>`;
    }

    function renderBoardSkeleton() {
        board.innerHTML = `
            <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-6">
                ${Array.from({ length: 12 }).map(() => '<div class="h-[74px] animate-pulse rounded-xl bg-emerald-deep/5"></div>').join('')}
            </div>`;
    }

    function fetchAvailableRooms(silent = false) {
        if (!checkInInput.value || !checkOutInput.value) return;
        // A silent refresh (real-time push) keeps the current board on screen
        // instead of flashing the skeleton while staff are mid-booking.
        if (!silent) {
            setAvailabilityStatus('loading');
            renderBoardSkeleton();
        }

        fetch("{{ route('frontdesk.available') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': "{{ csrf_token() }}" },
            body: JSON.stringify({ check_in: checkInInput.value, check_out: checkOutInput.value }),
        })
        .then(r => r.json())
        .then(data => {
            state.rooms = data.rooms || [];
            setAvailabilityStatus('ready');
            pruneUnavailableSelections();
            if (!state.loaded) { state.loaded = true; restoreOldSelections(); }
            renderPills();
            renderBoard();
            updateSummary();
        })
        .catch(err => {
            console.error('Room availability fetch error:', err);
            setAvailabilityStatus('error');
            board.innerHTML = '<p class="rounded-2xl border border-ember-200 bg-ember-50 px-5 py-4 text-sm font-semibold text-ember-600">Could not load the room board. Change the dates to retry.</p>';
        });
    }

    // Dates changed → previously picked rooms may now clash. Drop them loudly.
    function pruneUnavailableSelections() {
        const stillOpen = new Set(state.rooms.filter(r => r.status === 'available').map(r => r.room_number));
        const dropped = [];
        state.selected.forEach((room, number) => {
            if (!stillOpen.has(number)) {
                dropped.push(number);
                state.selected.delete(number);
                assignmentList.querySelector(`.assignment-card[data-room="${CSS.escape(number)}"]`)?.remove();
            }
        });
        if (dropped.length) {
            toast(`Removed room${dropped.length > 1 ? 's' : ''} ${dropped.join(', ')}: no longer available for these dates.`, 'warning');
            syncAssignmentUI();
        }
    }

    // Re-select rooms posted in a submission that failed server validation.
    function restoreOldSelections() {
        if (!Array.isArray(OLD_RESERVATIONS) || !OLD_RESERVATIONS.length) return;
        const byNumber = new Map(state.rooms.map(r => [String(r.room_number), r]));
        const missing = [];
        OLD_RESERVATIONS.forEach(block => {
            const room = byNumber.get(String(block.room_number));
            if (room && room.status === 'available' && !state.selected.has(room.room_number)) {
                selectRoom(room, parseInt(block.num_guests, 10) || 1, parseInt(block.num_seniors, 10) || 0);
            } else if (!room || room.status !== 'available') {
                missing.push(block.room_number);
            }
        });
        if (missing.length) toast(`Room${missing.length > 1 ? 's' : ''} ${missing.join(', ')} could not be restored (no longer available).`, 'warning');
    }

    /* ───────────────────── Room board ───────────────────── */
    const TILE_BASE = 'room-pick-tile relative flex flex-col items-center justify-center gap-0.5 rounded-xl border px-2 py-2.5 text-center transition-[color,background-color,border-color,box-shadow,transform] duration-150 select-none';
    const TILE_STYLES = {
        available:   'border-clsu-200 bg-white text-clsu-800 shadow-subtle hover:-translate-y-0.5 hover:border-clsu-400 hover:bg-clsu-50 cursor-pointer',
        selected:    'border-emerald-deep bg-emerald-deep text-cream shadow-card -translate-y-0.5 cursor-pointer',
        booked:      'border-ember-200 bg-ember-50/70 text-ember-400 cursor-not-allowed opacity-75',
        cleaning:    'border-palay-300 bg-palay-100/70 text-palay-700 cursor-not-allowed opacity-75',
        maintenance: 'border-stone-200 bg-stone-100 text-stone-400 cursor-not-allowed opacity-60',
        occupied:    'border-stone-200 bg-stone-100 text-stone-400 cursor-not-allowed opacity-60',
    };
    const TILE_TAGS = { booked: 'Booked', cleaning: 'Cleaning', maintenance: 'Repair', occupied: 'In use' };

    function typeGroups() {
        const groups = new Map();
        state.rooms.forEach(r => {
            const key = (r.room_type || '').toLowerCase();
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(r);
        });
        return groups;
    }

    function renderPills() {
        const groups = typeGroups();
        let html = pillHtml('all', 'All rooms', state.rooms.filter(r => r.status === 'available').length);
        groups.forEach((rooms, slug) => {
            const open = rooms.filter(r => r.status === 'available').length;
            html += pillHtml(slug, rooms[0].type_name || slug, open);
        });
        pills.innerHTML = html;
        pills.querySelectorAll('[data-type-pill]').forEach(p => p.addEventListener('click', () => {
            state.activeType = p.dataset.typePill;
            renderPills();
            renderBoard();
        }));
    }

    function pillHtml(slug, label, openCount) {
        const active = state.activeType === slug;
        return `<button type="button" data-type-pill="${slug}" class="press inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[11px] font-bold transition-colors ${active ? 'border-emerald-deep bg-emerald-deep text-cream' : 'border-emerald-deep/15 bg-white text-emerald-deep hover:border-clsu-400 hover:bg-clsu-50'}">
            ${label}
            <span class="rounded-full px-1.5 py-0.5 font-data text-[9px] leading-none ${active ? 'bg-cream/15 text-cream' : 'bg-clsu-50 text-clsu-700'}">${openCount}</span>
        </button>`;
    }

    function renderBoard() {
        const groups = typeGroups();
        if (!state.rooms.length) {
            board.innerHTML = '<p class="rounded-2xl border border-dashed border-emerald-deep/20 bg-white/50 px-5 py-6 text-center text-sm font-medium text-stone-400">No rooms found. Ask an admin to add rooms first.</p>';
            return;
        }
        let html = '';
        groups.forEach((rooms, slug) => {
            if (state.activeType !== 'all' && state.activeType !== slug) return;
            const open = rooms.filter(r => r.status === 'available').length;
            const prices = rooms.map(r => parseFloat(r.price) || 0);
            const minPrice = Math.min(...prices), maxPrice = Math.max(...prices);
            const priceLabel = minPrice === maxPrice ? `${peso(minPrice)} / night` : `from ${peso(minPrice)} / night`;
            const cap = rooms[0].capacity || 1;

            html += `
            <section data-board-section="${slug}">
                <header class="mb-2.5 flex flex-wrap items-baseline justify-between gap-2">
                    <div class="flex items-baseline gap-2.5">
                        <h5 class="text-xs font-bold uppercase tracking-[0.18em] text-ink/80">${rooms[0].type_name || slug}</h5>
                        <span class="text-[11px] font-semibold text-stone-400">${priceLabel} · sleeps ${cap}</span>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider ${open ? 'text-clsu-600' : 'text-ember-500'}">${open ? open + ' open' : 'Fully booked'}</span>
                </header>
                <div class="grid grid-cols-3 gap-2.5 sm:grid-cols-4 md:grid-cols-5 xl:grid-cols-6">
                    ${rooms.map((r, i) => tileHtml(r, i)).join('')}
                </div>
            </section>`;
        });
        board.innerHTML = html || '<p class="px-2 py-4 text-sm font-medium text-stone-400">No rooms of this type.</p>';

        board.querySelectorAll('[data-room-tile]').forEach(tile => {
            tile.addEventListener('click', () => {
                const room = state.rooms.find(r => String(r.room_number) === tile.dataset.roomTile);
                if (!room || room.status !== 'available') return;
                state.selected.has(room.room_number) ? deselectRoom(room.room_number) : selectRoom(room);
            });
        });
    }

    function tileHtml(room, i) {
        const isSelected = state.selected.has(room.room_number);
        const styleKey = isSelected ? 'selected' : room.status;
        const cls = TILE_STYLES[styleKey] || TILE_STYLES.maintenance;
        const tag = !isSelected && TILE_TAGS[room.status]
            ? `<span class="mt-0.5 text-[8px] font-black uppercase tracking-[0.14em]">${TILE_TAGS[room.status]}</span>`
            : `<span class="mt-0.5 text-[9px] font-semibold uppercase tracking-wide ${isSelected ? 'text-cream/70' : 'text-stone-400'}">${wingLabel(room.wing) || '&nbsp;'}</span>`;
        const check = isSelected
            ? '<span class="absolute right-1.5 top-1.5 grid h-4 w-4 place-items-center rounded-full bg-white text-emerald-deep"><svg class="h-2.5 w-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>'
            : '';
        const anim = room.status === 'available' || isSelected ? `style="animation:popIn .28s cubic-bezier(.16,1,.3,1) both;animation-delay:${Math.min(i, 20) * 18}ms"` : '';
        return `<button type="button" data-room-tile="${room.room_number}" ${anim} class="${TILE_BASE} ${cls}" ${room.status !== 'available' ? 'disabled' : ''} aria-pressed="${isSelected}">
            ${check}
            <span class="font-data text-sm font-extrabold tabnum">${room.room_number}</span>
            ${tag}
        </button>`;
    }

    /* ─────────────── Selection / assignment cards ─────────────── */
    function selectRoom(room, guests = 1, seniors = 0) {
        state.selected.set(room.room_number, room);

        const card = cardTemplate.content.firstElementChild.cloneNode(true);
        card.dataset.room = room.room_number;
        card.querySelector('[data-slot="number"]').textContent = room.room_number;
        card.querySelector('[data-slot="type"]').textContent = room.type_name || room.room_type;
        card.querySelector('[data-slot="meta"]').textContent = `${peso(room.price)} / night · sleeps ${room.capacity}${room.wing ? ' · ' + wingLabel(room.wing) : ''}`;
        card.querySelector('[data-slot="capacity-hint"]').textContent = `Up to ${room.capacity} guest${room.capacity > 1 ? 's' : ''} in this room`;

        const guestsInput = card.querySelector('[data-slot="guests"]');
        guestsInput.max = room.capacity;
        guestsInput.value = Math.min(Math.max(1, guests), room.capacity);

        const seniorsInput = card.querySelector('[data-slot="seniors"]');
        seniorsInput.max = room.capacity;
        seniorsInput.value = Math.min(Math.max(0, seniors), parseInt(guestsInput.value, 10));

        // Hidden fields the backend contract expects per reservation block
        card.querySelector('[data-slot="hidden-inputs"]').innerHTML = `
            <input type="hidden" data-name="room_type" value="${room.room_type}">
            <input type="hidden" data-name="room_number" value="${room.room_number}">
            <input type="hidden" data-name="price_per_night" value="${room.price}">`;

        guestsInput.addEventListener('input', () => {
            let v = parseInt(guestsInput.value, 10) || 1;
            v = Math.min(Math.max(1, v), room.capacity);
            guestsInput.value = v;
            if (parseInt(seniorsInput.value, 10) > v) seniorsInput.value = v;
            syncAssignmentUI();
        });
        seniorsInput.addEventListener('input', () => {
            let v = parseInt(seniorsInput.value, 10) || 0;
            v = Math.min(Math.max(0, v), parseInt(guestsInput.value, 10) || 1);
            seniorsInput.value = v;
            syncAssignmentUI();
        });
        card.querySelector('[data-slot="remove"]').addEventListener('click', () => deselectRoom(room.room_number));

        assignmentList.appendChild(card);
        renderBoard();
        syncAssignmentUI();
    }

    function deselectRoom(roomNumber) {
        state.selected.delete(roomNumber);
        assignmentList.querySelector(`.assignment-card[data-room="${CSS.escape(String(roomNumber))}"]`)?.remove();
        renderBoard();
        syncAssignmentUI();
    }

    // Reindex the hidden/visible inputs into reservations[i][…] form names.
    function reindexInputs() {
        assignmentList.querySelectorAll('.assignment-card').forEach((card, i) => {
            card.querySelectorAll('[data-name]').forEach(inp => inp.name = `reservations[${i}][${inp.dataset.name}]`);
            card.querySelector('[data-slot="guests"]').name = `reservations[${i}][num_guests]`;
            card.querySelector('[data-slot="seniors"]').name = `reservations[${i}][num_seniors]`;
        });
    }

    function assignedTotals() {
        let guests = 0, seniors = 0;
        assignmentList.querySelectorAll('.assignment-card').forEach(card => {
            guests += parseInt(card.querySelector('[data-slot="guests"]').value, 10) || 0;
            seniors += parseInt(card.querySelector('[data-slot="seniors"]').value, 10) || 0;
        });
        return { guests, seniors };
    }

    function syncAssignmentUI() {
        reindexInputs();
        assignmentEmpty.classList.toggle('hidden', state.selected.size > 0);

        const expected = parseInt(expectedGuestsInput.value, 10) || 0;
        const { guests } = assignedTotals();
        progressText.textContent = `${guests} / ${expected}`;
        const pct = expected > 0 ? Math.min(100, (guests / expected) * 100) : 0;
        progressBar.style.width = pct + '%';
        progressBar.className = 'h-full rounded-full transition-[width] duration-300 ' +
            (guests === expected && expected > 0 ? 'bg-clsu-500' : guests > expected ? 'bg-ember-500' : 'bg-palay-400');

        updateSummary();
    }

    document.getElementById('auto-assign').addEventListener('click', () => {
        const cards = Array.from(assignmentList.querySelectorAll('.assignment-card'));
        if (!cards.length) { toast('Pick rooms on the board first.', 'info'); return; }
        const expected = parseInt(expectedGuestsInput.value, 10) || 0;
        const caps = cards.map(c => parseInt(c.querySelector('[data-slot="guests"]').max, 10) || 1);
        const totalCap = caps.reduce((a, b) => a + b, 0);

        if (expected < cards.length) {
            toast(`You have ${cards.length} rooms but only ${expected} guest${expected === 1 ? '' : 's'}. Remove rooms or raise the guest count.`, 'warning');
            return;
        }
        if (expected > totalCap) {
            toast(`These rooms sleep ${totalCap} max, but ${expected} guests are expected. Pick more rooms.`, 'warning');
        }

        // Everyone gets a bed: 1 guest per room, then fill up in order.
        let remaining = Math.min(expected, totalCap) - cards.length;
        cards.forEach((card, i) => {
            const input = card.querySelector('[data-slot="guests"]');
            const extra = Math.min(remaining, caps[i] - 1);
            input.value = 1 + Math.max(0, extra);
            remaining -= Math.max(0, extra);
            const seniorsInput = card.querySelector('[data-slot="seniors"]');
            if (parseInt(seniorsInput.value, 10) > parseInt(input.value, 10)) seniorsInput.value = input.value;
        });
        syncAssignmentUI();
    });

    expectedGuestsInput.addEventListener('input', syncAssignmentUI);

    /* ───────────────────── Summary card ───────────────────── */
    const summaryDatesEl = document.getElementById('summary-dates');
    const summaryNightsEl = document.getElementById('summary-nights');
    const summaryGuestsEl = document.getElementById('summary-guests');
    const summaryRoomsEl = document.getElementById('summary-rooms');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryTotalEl = document.getElementById('summary-total');
    const nightsBadgeEl = document.getElementById('nights-badge');
    const discountHintEl = document.getElementById('discount-hint');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let lastTotal = 0;

    // Same odometer roll as the admin manual booking summary.
    function rollTotal(total) {
        if (total === lastTotal) return;
        const dir = total > lastTotal ? 1 : -1;
        lastTotal = total;

        const current = summaryTotalEl.querySelector('span:not(.is-leaving)');
        if (reduceMotion || !current || !current.animate) {
            summaryTotalEl.textContent = '';
            const s = document.createElement('span');
            s.textContent = peso(total);
            summaryTotalEl.appendChild(s);
            return;
        }
        const next = document.createElement('span');
        next.textContent = peso(total);
        summaryTotalEl.appendChild(next);

        const easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
        current.classList.add('is-leaving');
        current.animate([
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
            { transform: `translateY(${dir * -100}%)`, opacity: 0, filter: 'blur(2px)' },
        ], { duration: 260, easing, fill: 'forwards' }).onfinish = () => current.remove();
        next.animate([
            { transform: `translateY(${dir * 100}%)`, opacity: 0, filter: 'blur(2px)' },
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
        ], { duration: 260, easing });
    }

    function updateSummary() {
        const nights = calcNights();
        const fmt = { month: 'short', day: 'numeric' };

        if (nights > 0) {
            const ci = new Date(checkInInput.value).toLocaleDateString('en-US', fmt);
            const co = new Date(checkOutInput.value).toLocaleDateString('en-US', fmt);
            const label = `${nights} night${nights === 1 ? '' : 's'}`;
            summaryDatesEl.textContent = `${ci} → ${co}`;
            summaryNightsEl.textContent = label;
            summaryNightsEl.classList.remove('hidden');
            nightsBadgeEl.textContent = label;
            nightsBadgeEl.classList.remove('hidden');
        } else {
            summaryDatesEl.textContent = 'Select valid dates';
            summaryNightsEl.classList.add('hidden');
            nightsBadgeEl.classList.add('hidden');
        }

        const expected = parseInt(expectedGuestsInput.value, 10) || 0;
        const { guests, seniors } = assignedTotals();
        summaryGuestsEl.innerHTML = state.selected.size
            ? `${guests} of ${expected} guest${expected === 1 ? '' : 's'} assigned${seniors ? ` · ${seniors} senior/PWD` : ''}` +
              (guests !== expected ? ' <span class="font-bold text-palay-700">· mismatch</span>' : '')
            : `${expected} guest${expected === 1 ? '' : 's'} expected`;

        let subtotal = 0;
        let lines = '';
        assignmentList.querySelectorAll('.assignment-card').forEach(card => {
            const number = card.dataset.room;
            const room = state.selected.get(number) || state.selected.get(Number(number));
            if (!room) return;
            const price = parseFloat(room.price) || 0;
            const g = parseInt(card.querySelector('[data-slot="guests"]').value, 10) || 1;
            const lineTotal = price * nights;
            subtotal += lineTotal;
            lines += `
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-ink">Room ${room.room_number} <span class="font-normal capitalize text-muted">· ${room.type_name || room.room_type}</span></p>
                        <p class="text-xs text-faint">${peso(price)} × ${nights} night${nights === 1 ? '' : 's'} · ${g} guest${g === 1 ? '' : 's'}</p>
                    </div>
                    <span class="whitespace-nowrap font-semibold tabnum text-ink">${peso(lineTotal)}</span>
                </div>`;
        });

        summaryRoomsEl.innerHTML = lines || '<p class="text-faint">Pick rooms on the board to build the summary.</p>';
        summarySubtotalEl.textContent = peso(subtotal);

        let discount = Math.max(0, parseFloat(discountInput.value) || 0);
        if (discount > subtotal && subtotal > 0) {
            discountHintEl.textContent = `Discount is larger than the ${peso(subtotal)} subtotal.`;
            discountHintEl.classList.remove('hidden');
        } else {
            discountHintEl.classList.add('hidden');
        }
        rollTotal(Math.max(0, subtotal - discount));
    }

    discountInput.addEventListener('input', updateSummary);

    /* ───────────────────── Submit guard ───────────────────── */
    document.getElementById('walkin-form').addEventListener('submit', function (e) {
        const cards = assignmentList.querySelectorAll('.assignment-card');
        const expected = parseInt(expectedGuestsInput.value, 10) || 0;
        const { guests, seniors } = assignedTotals();

        let problem = null;
        if (!cards.length) problem = 'Pick at least one room on the board.';
        else if (guests !== expected) problem = `Guests assigned to rooms (${guests}) must equal the expected guests (${expected}). Use “Auto-distribute guests” to fix this quickly.`;
        else if (seniors > guests) problem = 'Senior/PWD count cannot exceed total guests.';

        if (problem) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Almost there', text: problem, confirmButtonColor: '#14532d' });
            return;
        }

        reindexInputs();
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
            </svg>
            Creating booking…`;
    });

    /* ───────────────────── Real-time availability ─────────────────────
       RoomStatusChanged broadcasts fire whenever any room's status changes
       elsewhere (admin board, a check-in, cleaning). Silently re-fetch so a
       room another staffer just closed drops off this board immediately. */
    if (window.Echo) {
        let realtimeTimer = null;
        window.Echo.channel('rooms').listen('.RoomStatusChanged', () => {
            clearTimeout(realtimeTimer);
            realtimeTimer = setTimeout(() => fetchAvailableRooms(true), 400);
        });
    }

    /* ───────────────────── Boot ───────────────────── */
    // Flatpickr calendars — same picker as the admin manual booking, themed light emerald.
    if (typeof flatpickr !== 'undefined') {
        const initialOutMin = checkInInput.value
            ? new Date(new Date(checkInInput.value).getTime() + 86400000)
            : 'today';
        fpOut = flatpickr(checkOutInput, {
            dateFormat: 'Y-m-d', minDate: initialOutMin, disableMobile: true,
            defaultDate: checkOutInput.value || null,
            onChange: () => checkOutInput.dispatchEvent(new Event('change', { bubbles: true })),
        });
        fpIn = flatpickr(checkInInput, {
            dateFormat: 'Y-m-d', minDate: 'today', disableMobile: true,
            defaultDate: checkInInput.value || 'today',
            onChange: () => checkInInput.dispatchEvent(new Event('change', { bubbles: true })),
        });
    } else {
        // Fallback if the flatpickr library fails to load: native date inputs.
        [checkInInput, checkOutInput].forEach(el => { el.type = 'date'; el.readOnly = false; });
        checkInInput.min = fmtDate(new Date());
    }

    syncAssignmentUI();
    updateSummary();
    fetchAvailableRooms();
});
</script>
@endpush
