@extends('layouts.public.base')
@section('title', 'Move your stay | Farmers Hostel')

@section('content')
@php
    $nights = max(1, $booking->check_in->diffInDays($booking->check_out));
    $rooms = $booking->reservations->pluck('room_number')->filter()->implode(', ');
@endphp

<div class="min-h-screen bg-canvas pt-28 pb-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <span class="inline-flex items-center gap-3 text-[11px] font-bold uppercase tracking-[0.4em] text-emerald mb-3">
                <span class="h-px w-8 bg-emerald/50"></span> Booking #{{ $booking->id }}
            </span>
            <h1 class="text-balance font-display text-4xl sm:text-5xl leading-[1.08] text-ink tracking-tight">Move your <span class="italic text-gold">stay</span></h1>
            <p class="text-sm font-medium text-ink/55 mt-3 max-w-xl">
                This booking is paid, so it cannot be cancelled — but we can move it. Tell us the dates you would rather come, and our front desk will check whether your rooms are free and email you the answer.
            </p>
        </div>

        <!-- The stay as it stands -->
        <div class="mb-8 grid grid-cols-2 sm:grid-cols-4 rounded-3xl bg-cream-warm ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] divide-x divide-emerald-deep/5 text-center overflow-hidden">
            <div class="px-3 py-4">
                <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">Check-in</span>
                <span class="block text-sm font-extrabold text-ink mt-1 tabnum">{{ $booking->check_in->format('M d, Y') }}</span>
            </div>
            <div class="px-3 py-4">
                <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">Check-out</span>
                <span class="block text-sm font-extrabold text-ink mt-1 tabnum">{{ $booking->check_out->format('M d, Y') }}</span>
            </div>
            <div class="px-3 py-4">
                <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">Nights</span>
                <span class="block text-sm font-extrabold text-ink mt-1 tabnum">{{ $nights }}</span>
            </div>
            <div class="px-3 py-4">
                <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-emerald/70">{{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }}</span>
                <span class="block text-sm font-extrabold text-emerald-deep mt-1 tabnum">{{ $rooms ?: '—' }}</span>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6">
                <x-booking.ui.alert type="danger">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-booking.ui.alert>
            </div>
        @endif

        @if ($existing)
            {{-- A request is already in the queue. Showing the form as well
                 would invite a second one the controller refuses, so the page
                 becomes the status of the request they already made. --}}
            <div class="bg-cream-warm rounded-3xl ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] p-6 sm:p-8">
                <div class="flex items-start gap-3 mb-6 pb-5 border-b border-emerald-deep/10">
                    <span class="w-11 h-11 rounded-2xl bg-gold/12 text-palay-800 ring-1 ring-gold/30 flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-hourglass text-[20px]"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-semibold text-ink tracking-tight font-display">With our front desk</h2>
                        <p class="text-xs font-semibold text-stone-500 mt-1">Sent {{ $existing->submitted_at?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-6 text-sm">
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">New check-in</span>
                        <span class="text-ink font-bold">{{ $existing->requested_check_in->format('F d, Y') }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">New check-out</span>
                        <span class="text-ink font-bold">{{ $existing->requested_check_out->format('F d, Y') }}</span>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="block text-[10px] text-stone-500 uppercase tracking-widest mb-0.5">What you told us</span>
                        <p class="text-stone-700 font-medium leading-relaxed whitespace-pre-line">{{ $existing->reason }}</p>
                    </div>
                </div>

                <div class="mt-6 pt-5 border-t border-emerald-deep/10 flex flex-wrap gap-2.5 justify-end">
                    <form action="{{ route('booking.reschedule.withdraw', $booking->id) }}" method="POST" data-busy-form
                          data-confirm-title="Withdraw this request?"
                          data-confirm="Your original dates will stand. You can send a new request afterwards, as long as there are still 24 hours to go before your check-in."
                          data-confirm-action="Withdraw">
                        @csrf
                        <button type="submit" data-busy-btn class="press px-5 py-2.5 rounded-full text-xs font-bold bg-ember-600/10 hover:bg-ember-600/20 text-ember-700 ring-1 ring-ember-600/35 transition-colors cursor-pointer">
                            Withdraw request
                        </button>
                    </form>
                    <x-booking.ui.button variant="outline" href="{{ route('booking.show', $booking->id) }}" class="py-2.5 px-5 text-xs">
                        Back to booking
                    </x-booking.ui.button>
                </div>
            </div>
        @else
            <form action="{{ route('booking.reschedule.store', $booking->id) }}" method="POST"
                  class="bg-cream-warm rounded-3xl ring-1 ring-emerald-deep/5 shadow-[0_14px_34px_-26px_rgba(6,40,30,0.3)] p-6 sm:p-8 space-y-6"
                  data-busy-form>
                @csrf

                {{-- The deadline is the whole policy, so it leads rather than
                     sitting in small print under the button. It is enforced by
                     RescheduleController and by bookings:mark-no-show, which is
                     what makes it worth saying this loudly. --}}
                <div class="flex items-start gap-3 rounded-2xl bg-gold/12 ring-1 ring-gold/30 px-4 py-3.5">
                    <i class="fa-solid fa-triangle-exclamation text-[18px] text-palay-800 shrink-0 mt-0.5"></i>
                    <p class="text-xs font-bold text-palay-800 leading-relaxed">
                        Ask by {{ $deadline->format('g:i A') }} on {{ $deadline->format('F d') }} — a full 24 hours before your {{ $booking->check_in->format('F d') }} check-in.
                        <span class="block font-semibold text-stone-600 mt-1">After that we cannot move the stay, and a booking nobody checks in to is forfeited with no refund.</span>
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="requested_check_in" class="block text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1.5">New check-in</label>
                        <input type="date" id="requested_check_in" name="requested_check_in" required
                               value="{{ old('requested_check_in') }}"
                               min="{{ \Carbon\Carbon::today()->toDateString() }}"
                               max="{{ $horizon->toDateString() }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/15 bg-white/70 text-stone-800 text-sm font-semibold focus:border-gold focus:ring-2 focus:ring-gold/30 outline-none transition-[color,background-color,border-color,box-shadow]">
                    </div>
                    <div>
                        <label for="requested_check_out" class="block text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1.5">New check-out</label>
                        <input type="date" id="requested_check_out" name="requested_check_out" required
                               value="{{ old('requested_check_out') }}"
                               min="{{ \Carbon\Carbon::tomorrow()->toDateString() }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-emerald-deep/15 bg-white/70 text-stone-800 text-sm font-semibold focus:border-gold focus:ring-2 focus:ring-gold/30 outline-none transition-[color,background-color,border-color,box-shadow]">
                    </div>
                </div>

                <div>
                    <label for="reason" class="block text-[10px] font-bold text-stone-500 uppercase tracking-widest mb-1.5">Why do you need to move it?</label>
                    <textarea id="reason" name="reason" rows="4" required maxlength="1000"
                              placeholder="A short note is enough — it goes straight to the person deciding."
                              class="w-full px-4 py-3 rounded-xl border border-emerald-deep/15 bg-white/70 text-stone-800 text-sm font-medium leading-relaxed focus:border-gold focus:ring-2 focus:ring-gold/30 outline-none transition-[color,background-color,border-color,box-shadow] resize-none">{{ old('reason') }}</textarea>
                </div>

                <div class="rounded-2xl border border-emerald-deep/10 bg-white/50 p-4 space-y-2">
                    <p class="text-[11px] font-semibold text-stone-600 leading-relaxed flex items-start gap-2">
                        <i class="fa-solid fa-door-open mt-px text-[13px] text-palay-800"></i>
                        <span>You keep the same {{ \Illuminate\Support\Str::plural('room', $booking->reservations->count()) }} ({{ $rooms ?: '—' }}). If any of them is already taken on your new dates, we will have to say no — so a second choice of dates in your note helps.</span>
                    </p>
                    <p class="text-[11px] font-semibold text-stone-600 leading-relaxed flex items-start gap-2">
                        <i class="fa-solid fa-receipt mt-px text-[13px] text-palay-800"></i>
                        <span>A longer stay costs more, and the difference is settled at our front desk when you arrive. A shorter one is not cheaper — we don't issue refunds, so what you have already paid stands.</span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-2.5 justify-end pt-2 border-t border-emerald-deep/10">
                    <x-booking.ui.button variant="outline" href="{{ route('booking.show', $booking->id) }}" class="py-2.5 px-5 text-xs">
                        Cancel
                    </x-booking.ui.button>
                    <button type="submit" data-busy-btn class="press focus-ring min-h-11 py-2.5 px-6 rounded-full flex items-center justify-center gap-2 text-[12px] font-semibold uppercase tracking-[0.16em] bg-emerald-deep text-cream cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent)]">
                        <i class="fa-solid fa-paper-plane text-[15px]"></i>
                        Send request
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // check-out must follow check-in. Enforced server-side too (`after:
    // requested_check_in`) — this only stops the guest filling in a whole form
    // to be told something the date picker could have prevented.
    document.addEventListener('DOMContentLoaded', function () {
        const from = document.getElementById('requested_check_in');
        const to = document.getElementById('requested_check_out');
        if (!from || !to) return;

        function syncOut() {
            if (!from.value) return;
            const min = new Date(from.value + 'T00:00:00');
            min.setDate(min.getDate() + 1);
            to.min = min.toISOString().slice(0, 10);
            if (to.value && to.value <= from.value) to.value = to.min;
        }

        from.addEventListener('change', syncOut);
        syncOut();
    });
</script>
@endpush
@endsection
