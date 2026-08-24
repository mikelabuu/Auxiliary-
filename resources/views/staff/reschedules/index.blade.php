@php
    // One page, two consoles — same reasoning as payment verification. The
    // guest rings the front desk about a stay they cannot make, so the desk
    // must be able to answer without leaving its own shell.
    $isFrontDesk = auth('staff')->user()?->role === 'frontdesk';
@endphp
@extends($isFrontDesk ? 'layouts.frontdesk' : 'layouts.admin')
@section('title', 'Reschedule Requests')
@section('page-title', 'Reschedule Requests')

@section('content')
@php
    use App\Models\RescheduleRequest;

    $tabs = [
        RescheduleRequest::STATUS_PENDING   => 'Waiting',
        RescheduleRequest::STATUS_APPROVED  => 'Approved',
        RescheduleRequest::STATUS_DECLINED  => 'Declined',
        RescheduleRequest::STATUS_WITHDRAWN => 'Withdrawn',
    ];
@endphp

<div class="space-y-6 max-w-[1680px] mx-auto">

    @unless($isFrontDesk)
        <x-admin.ui.page-header subtitle="Paid guests who cannot make their dates. A paid booking cannot be cancelled, so moving it is the only thing they can ask for — and they must ask at least 24 hours before their check-in.">
            Reschedule Requests
        </x-admin.ui.page-header>
    @endunless

    @if ($errors->any())
        <div class="rounded-[var(--radius)] border border-ember-200 bg-ember-50 px-4 py-3">
            <ul class="space-y-1 text-sm font-medium text-ember-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-admin.ui.section-card icon="calendar" title="Reschedule Queue"
        :subtitle="$requests->total() . ' request' . ($requests->total() === 1 ? '' : 's')">

        <div class="filter-row mb-5">
            <span class="filter-row-label">Status</span>
            @foreach ($tabs as $key => $label)
                <a href="{{ route('staff.reschedules.index', ['status' => $key]) }}"
                   @class(['filter-tab', '!no-underline', 'selected' => $filter === $key])>
                    {{ $label }}
                    <span class="ft-count">{{ $counts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        @forelse ($requests as $req)
            @php
                $booking = $req->booking;
                $rooms = $booking?->reservations->pluck('room_number')->filter()->implode(', ');
                $deadline = $booking ? RescheduleRequest::deadlineFor($booking) : null;
                // Only meaningful while the request is open. Once decided, the
                // booking's dates may already have moved and this would be the
                // deadline for the *new* stay, which is a different question.
                $isPending = $req->isPending();
                $lapsed = $isPending && $deadline?->isPast();

                // Same nightly rates the stay is already billed at, so the desk
                // sees the consequence of approving before it approves. The
                // controller recomputes this itself — nothing here is trusted.
                $nightly = (float) ($booking?->reservations->sum('price') ?? 0);
                $newTotal = round($nightly * $req->requested_nights, 2);
                $paidBefore = (float) ($booking?->payable_amount ?? $booking?->total_price ?? 0);
                // Never negative: there is no refund, so a shorter stay is not
                // money owed back — it is simply a shorter stay at the price
                // already paid. Mirrors the max() in RescheduleAdminController.
                $difference = round(max(0, ($newTotal - (float) ($booking?->discount ?? 0)) - $paidBefore), 2);
                $shorterStay = $newTotal < $paidBefore;
            @endphp

            <article class="mb-4 rounded-[var(--radius)] border border-stone-200 bg-white overflow-hidden">
                <div class="p-5 sm:p-6">

                    <div class="flex flex-wrap items-start justify-between gap-3 pb-4 border-b border-stone-100">
                        <div class="min-w-0">
                            <p class="text-base font-bold text-stone-800 truncate">
                                {{ $booking->guest_name ?? 'Unknown guest' }}
                            </p>
                            <p class="text-xs font-semibold text-muted mt-0.5 tabnum">
                                Booking #{{ $req->booking_id }}
                                @if ($rooms) · {{ \Illuminate\Support\Str::plural('Room', $booking->reservations->count()) }} {{ $rooms }} @endif
                                @if ($booking?->guest_phone) · {{ $booking->guest_phone }} @endif
                            </p>
                        </div>
                        <p class="text-xs font-semibold text-muted tabnum whitespace-nowrap">
                            Asked {{ $req->submitted_at?->timezone(config('hostel.timezone'))->format('M d, g:i A') ?? '—' }}
                        </p>
                    </div>

                    {{-- The move itself, laid out as one line: what they have,
                         what they want. Reading it any other way makes the desk
                         reconstruct the change from two date pairs. --}}
                    <div class="flex flex-wrap items-center gap-3 py-4">
                        <div class="rounded-[var(--radius)] border border-stone-200 bg-stone-50 px-4 py-2.5">
                            <p class="text-2xs font-bold uppercase tracking-[0.14em] text-faint">Booked for</p>
                            <p class="mt-0.5 text-sm font-bold text-stone-700 tabnum">
                                {{ $req->original_check_in->format('M d') }} → {{ $req->original_check_out->format('M d, Y') }}
                                <span class="text-muted">· {{ $req->original_nights }}n</span>
                            </p>
                        </div>
                        <x-admin.ui.icon name="arrow-right" class="w-4 h-4 shrink-0 text-faint" stroke-width="2.5" />
                        <div class="rounded-[var(--radius)] border border-clsu-200 bg-clsu-50 px-4 py-2.5">
                            <p class="text-2xs font-bold uppercase tracking-[0.14em] text-clsu-700">Wants</p>
                            <p class="mt-0.5 text-sm font-bold text-clsu-800 tabnum">
                                {{ $req->requested_check_in->format('M d') }} → {{ $req->requested_check_out->format('M d, Y') }}
                                <span class="opacity-70">· {{ $req->requested_nights }}n</span>
                            </p>
                        </div>

                        @if ($difference > 0)
                            {{-- Money is the consequence the system will not act
                                 on: it re-prices the booking and stops. Whoever
                                 approves has to collect the difference in
                                 person, so they see it first. --}}
                            <div class="rounded-[var(--radius)] border border-palay-200 bg-palay-50 px-4 py-2.5">
                                <p class="text-2xs font-bold uppercase tracking-[0.14em] text-palay-800">Guest owes</p>
                                <p class="mt-0.5 text-sm font-bold tabnum text-palay-800">
                                    ₱{{ number_format($difference, 2) }} at the desk
                                </p>
                            </div>
                        @elseif ($shorterStay)
                            {{-- Deliberately not a "refund due". There is no
                                 refund policy, so the shorter stay is simply
                                 worth less than what was already paid — saying
                                 anything else would have the desk reaching for
                                 the cash box. --}}
                            <div class="rounded-[var(--radius)] border border-stone-200 bg-stone-50 px-4 py-2.5">
                                <p class="text-2xs font-bold uppercase tracking-[0.14em] text-muted">Shorter stay</p>
                                <p class="mt-0.5 text-sm font-bold text-muted">No refund · amount paid stands</p>
                            </div>
                        @endif
                    </div>

                    <div class="pb-4">
                        <p class="text-2xs font-bold uppercase tracking-[0.14em] text-faint">Their reason</p>
                        <p class="mt-1 text-sm font-medium leading-relaxed text-stone-700 whitespace-pre-line">{{ $req->reason }}</p>
                    </div>

                    @if ($isPending)
                        @if ($lapsed)
                            {{-- The deadline passed while this sat in the queue.
                                 Approving is still allowed — the guest asked in
                                 time and the desk was late, which is not their
                                 fault — but nobody should do it without knowing. --}}
                            <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-ember-200 bg-ember-50 px-4 py-3 text-xs font-semibold leading-relaxed text-ember-700">
                                <x-admin.ui.icon name="clock" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                                Check-in time on {{ $req->original_check_in->format('M d') }} has already passed. This booking is due to be forfeited by the overnight no-show sweep — decide it now, or speak to the guest.
                            </div>
                        @else
                            <div class="flex items-start gap-2.5 rounded-[var(--radius)] border border-palay-200 bg-palay-50 px-4 py-3 text-xs font-semibold leading-relaxed text-palay-800">
                                <x-admin.ui.icon name="shield" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                                Answer before {{ $deadline?->format('g:i A, M d') }}. Approving keeps the same {{ \Illuminate\Support\Str::plural('room', max(1, $booking?->reservations->count() ?? 1)) }} and will be refused if any of them is taken over the new dates.
                            </div>
                        @endif

                        <div class="mt-4 flex flex-wrap items-center gap-2.5">
                            <form method="POST" action="{{ route('staff.reschedules.approve', $req->id) }}"
                                  data-busy-form
                                  data-confirm-title="Move this stay?"
                                  data-confirm="Booking #{{ $req->booking_id }} moves to {{ $req->requested_check_in->format('M d') }} – {{ $req->requested_check_out->format('M d, Y') }} and the guest is emailed.@if($difference > 0) There will be ₱{{ number_format($difference, 2) }} to collect at the desk.@elseif($shorterStay) The new stay is shorter; there is no refund and the amount paid stands.@endif"
                                  data-confirm-action="Yes, move it">
                                @csrf
                                <button type="submit" data-busy-btn class="btn btn-primary btn-sm">
                                    <x-admin.ui.icon name="check-circle" class="w-4 h-4" stroke-width="2" />
                                    Approve &amp; move dates
                                </button>
                            </form>

                            <button type="button" class="btn btn-ghost btn-sm text-ember-700"
                                    onclick="openDeclineModal({{ $req->id }}, {{ $req->booking_id }})">
                                <x-admin.ui.icon name="block" class="w-4 h-4" stroke-width="2" />
                                Decline
                            </button>

                            <a href="{{ route('staff.bookings.index', ['search' => $req->booking_id]) }}" class="btn btn-ghost btn-sm !no-underline">
                                <x-admin.ui.icon name="eye" class="w-4 h-4" stroke-width="2" />
                                Open booking
                            </a>
                        </div>
                    @else
                        @php
                            $approved = $req->status === RescheduleRequest::STATUS_APPROVED;
                            $withdrawn = $req->status === RescheduleRequest::STATUS_WITHDRAWN;
                        @endphp
                        <div @class([
                            'flex items-start gap-2.5 rounded-[var(--radius)] px-4 py-3 text-xs font-semibold leading-relaxed border',
                            'border-clsu-200 bg-clsu-50 text-clsu-800' => $approved,
                            'border-ember-200 bg-ember-50 text-ember-700' => ! $approved && ! $withdrawn,
                            'border-stone-200 bg-stone-50 text-muted' => $withdrawn,
                        ])>
                            <x-admin.ui.icon :name="$approved ? 'check-circle' : 'block'" class="w-4 h-4 mt-0.5 shrink-0" stroke-width="2" />
                            <div>
                                @if ($withdrawn)
                                    Withdrawn by the guest on {{ $req->reviewed_at?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') ?? '—' }}.
                                @else
                                    {{ $approved ? 'Approved' : 'Declined' }}
                                    by {{ $req->reviewer->name ?? 'staff' }}
                                    on {{ $req->reviewed_at?->timezone(config('hostel.timezone'))->format('M d, Y g:i A') ?? '—' }}.
                                @endif
                                @if ($req->decision_note)
                                    <span class="block mt-1 font-bold">Note: {{ $req->decision_note }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <x-admin.ui.empty-state icon="calendar"
                title="Nothing here — a paid guest who cannot make their dates lands in this queue the moment they ask." />
        @endforelse

        @if ($requests->hasPages())
            <div class="mt-5">{{ $requests->links() }}</div>
        @endif
    </x-admin.ui.section-card>
</div>

{{-- Decline: the reason is mandatory. The guest is left holding a paid booking
     for dates they have already said they cannot make, with the no-show sweep
     still coming — "declined" on its own tells them nothing to act on. --}}
<x-admin.ui.modal id="declineRescheduleModal" icon="block" title="Decline reschedule request" max-width="lg">
    <form method="POST" id="declineRescheduleForm" data-busy-form>
        @csrf
        <div class="px-7 pt-5 space-y-4">
            <p class="text-sm text-stone-600">
                Booking <strong id="declineBookingLabel" class="text-stone-800"></strong> keeps its original dates. Your note is emailed to the guest.
            </p>
            <div>
                <label for="decision_note" class="block text-2xs font-bold uppercase tracking-[0.16em] text-muted mb-1.5">
                    Reason shown to the guest <span class="text-ember-600">*</span>
                </label>
                <textarea id="decision_note" name="decision_note" rows="3" required maxlength="500"
                          placeholder="e.g. Your rooms are fully booked that week — we could offer Mar 14–16 instead. Please call us."
                          class="w-full rounded-[var(--radius)] border border-stone-200 bg-stone-50/60 px-4 py-3 text-sm text-stone-800 focus:bg-white focus:border-clsu-400 focus:ring-2 focus:ring-clsu-200 outline-none transition-[color,background-color,border-color,box-shadow]"></textarea>
            </div>
        </div>
        <div class="flex gap-2.5 justify-end px-7 py-7">
            <button type="button" class="btn btn-outline" data-modal-close="declineRescheduleModal">Cancel</button>
            <button type="submit" data-busy-btn class="btn btn-danger">
                <x-admin.ui.icon name="block" class="w-4 h-4" stroke-width="2" />
                Decline request
            </button>
        </div>
    </form>
</x-admin.ui.modal>

@push('scripts')
<script>
    function openDeclineModal(requestId, bookingId) {
        const form = document.getElementById('declineRescheduleForm');
        form.action = '{{ url('staff/reschedules') }}/' + requestId + '/decline';
        document.getElementById('declineBookingLabel').textContent = '#' + bookingId;
        // A note typed for the previous request must not ride along.
        document.getElementById('decision_note').value = '';
        window.openModal('declineRescheduleModal');
    }

    // Real-time: a request arriving, or being decided at another desk.
    // Deferred: liveRefresh comes from admin.js, which Vite emits as a
    // <script type="module"> — deferred, so it runs *after* this block. Calling it
    // directly threw "window.liveRefresh is not a function" and left the page with
    // no live updates at all. Module scripts run before DOMContentLoaded, so by
    // here it exists.
    document.addEventListener('DOMContentLoaded', () => {
        window.liveRefresh([
            { channel: 'bookings', event: 'BookingChanged' },
        ]);
    });
</script>
@endpush
@endsection
