@php
    use Illuminate\Support\Str;

    $statusColorMap = [
        'paid' => 'clsu', 'active' => 'clsu', 'completed' => 'clsu',
        'pending_payment' => 'palay', 'pending_discount' => 'palay',
        'cancelled' => 'ember', 'expired' => 'ember', 'no_show' => 'ember',
    ];
    $badgeClassMap = [
        'clsu'  => 'bg-clsu-50 text-clsu-700 border-clsu-200',
        'palay' => 'bg-palay-100 text-palay-800 border-palay-200',
        'ember' => 'bg-ember-50 text-ember-700 border-ember-200',
    ];
    $badgeClass = $badgeClassMap[$statusColorMap[$booking->status] ?? ''] ?? 'bg-stone-100 text-stone-600 border-stone-200';
    $statusLabel = ucwords(str_replace('_', ' ', $booking->status));

    $payment = $booking->payments instanceof \Illuminate\Support\Collection ? $booking->payments->first() : $booking->payments;
    $paymentStatusColor = ['success' => 'clsu', 'pending' => 'palay'][$payment->status ?? ''] ?? 'ember';

    // Day maths on plain dates in one timezone. The columns cast to `date` in
    // the app timezone while the rest of the app reckons "today" in
    // Asia/Manila; comparing the two directly leaves an 8-hour skew, and
    // Carbon 3's diffInDays returns a float, so a stay ending today read as
    // "night 4 of 4 · 92%" instead of being due out.
    $checkInDate  = \Carbon\Carbon::parse($booking->check_in->toDateString());
    $checkOutDate = \Carbon\Carbon::parse($booking->check_out->toDateString());
    $todayDate    = \Carbon\Carbon::parse(now(config('hostel.timezone'))->toDateString());

    $nights = max(1, (int) $checkInDate->diffInDays($checkOutDate));
    $roomNumbers = $booking->reservations->pluck('room_number')->filter();
    $payable = $booking->payable_amount > 0 ? $booking->payable_amount : $booking->total_price;

    $initials = Str::of($booking->guest_name)->trim()->explode(' ')
        ->filter()->take(2)->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');

    // Presentation data for the rooms on this booking. Reservations store a
    // room_type snapshot, so the catalog lookup is by that slug — never by the
    // live Room relation, which may have been re-typed since booking time.
    $catalog = \App\Support\RoomCatalog::all();
    $bookedTypes = $booking->reservations
        ->map(fn ($r) => $catalog[$r->room_type] ?? null)
        ->filter()->unique('id')->values();

    $facilityPhotos = $bookedTypes
        ->flatMap(fn ($t) => collect($t['gallery'] ?? [])->take(2)->map(fn ($img) => [
            'img' => $img, 'title' => $t['title'],
        ]))
        ->unique('img')->take(4)->values();

    // Reads as one line of copy on the right of the heading, like a room sheet.
    $amenityList = $bookedTypes
        ->flatMap(fn ($t) => collect($t['amenities'] ?? [])->pluck('label'))
        ->filter()->unique()->implode(', ');

    // How far through the stay we are — only meaningful once a guest is in.
    $showProgress = in_array($booking->status, ['active', 'paid'], true);
    $elapsed = (int) $checkInDate->diffInDays($todayDate, false);
    $progressPct = (int) round(min(1, max(0, $elapsed / $nights)) * 100);
    $currentNight = (int) min($nights, max(1, $elapsed + 1));

    // Queried once here and handed to the history table, which would otherwise
    // run the identical query a second time on the same modal open.
    $stays = \App\Models\Booking::with('reservations')
        ->where(function ($q) use ($booking) {
            $q->where('guest_name', $booking->guest_name);
            if ($booking->user_id) {
                $q->orWhere('user_id', $booking->user_id);
            }
        })
        ->orderByDesc('check_in')->get();

    $completedStays = $stays->where('status', 'completed')->count();
    $lifetimeSpend = $stays->whereIn('status', ['paid', 'active', 'completed'])
        ->sum(fn ($s) => $s->payable_amount > 0 ? $s->payable_amount : $s->total_price);
@endphp

<div class="px-6 py-5 max-h-[76vh] overflow-y-auto custom-scrollbar">

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,282px)_minmax(0,1fr)] gap-5">

        {{-- ─────────── Left rail: who the guest is ─────────── --}}
        <aside class="min-w-0">
            <div class="rounded-2xl border border-stone-200 overflow-hidden">
                <div class="px-5 pt-6 pb-5 text-center bg-linear-to-b from-clsu-50/70 to-transparent">
                    <span class="inline-flex w-[72px] h-[72px] rounded-2xl items-center justify-center text-xl font-extrabold bg-linear-to-br from-clsu-100 to-clsu-200 text-clsu-800 ring-1 ring-clsu-500/25">{{ $initials ?: '—' }}</span>
                    <p class="mt-3.5 text-lg font-bold text-stone-900 leading-tight break-words">{{ $booking->guest_name }}</p>
                    <p class="mt-1.5 text-xs font-bold text-clsu-700 font-data tracking-wide">
                        {{ $booking->user_id ? 'GS-' . str_pad($booking->user_id, 4, '0', STR_PAD_LEFT) : 'WALK-IN' }}
                    </p>
                    <span class="inline-flex items-center gap-1.5 mt-3 px-2.5 py-1 rounded-full text-2xs font-bold border {{ $booking->user_id ? 'bg-white text-clsu-700 border-clsu-200' : 'bg-white text-stone-600 border-stone-200' }}">
                        <x-admin.ui.icon :name="$booking->user_id ? 'check-circle' : 'user'" class="w-3 h-3" />
                        {{ $booking->user_id ? 'Registered account' : 'No account' }}
                    </span>
                </div>

                <div class="border-t border-stone-100 px-4 py-4 space-y-3.5">
                    @php
                        $contactRows = array_values(array_filter([
                            ['icon' => 'phone', 'label' => 'Phone', 'value' => $booking->guest_phone ?: '—', 'mono' => true],
                            // Only when there is one — an empty "Second phone —"
                            // row is a dead line in a panel staff scan in a hurry.
                            $booking->guest_phone_alt ? ['icon' => 'phone', 'label' => 'Second phone', 'value' => $booking->guest_phone_alt, 'mono' => true] : null,
                            // Who vouched for this guest. Blank on anything booked
                            // before the field existed, and on walk-ins.
                            $booking->referred_by ? ['icon' => 'user', 'label' => 'Endorsed by', 'value' => $booking->referred_by] : null,
                            $booking->user?->email ? ['icon' => 'mail', 'label' => 'Email', 'value' => $booking->user->email, 'mono' => false] : null,
                            ['icon' => 'map-pin', 'label' => 'Address', 'value' => $booking->guest_address ?: '—', 'mono' => false],
                        ]));
                    @endphp
                    @foreach($contactRows as $row)
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 shrink-0 rounded-full bg-clsu-50 text-clsu-700 ring-1 ring-clsu-100 flex items-center justify-center">
                                <x-admin.ui.icon :name="$row['icon']" class="w-4 h-4" />
                            </span>
                            <div class="min-w-0 pt-0.5">
                                <p class="text-2xs font-bold text-faint uppercase tracking-widest">{{ $row['label'] }}</p>
                                <p class="text-sm text-stone-800 font-medium mt-0.5 leading-snug break-words {{ $row['mono'] ? 'font-data' : '' }}">{{ $row['value'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-stone-100 grid grid-cols-3 divide-x divide-stone-100 text-center">
                    <div class="px-2 py-3.5">
                        <p class="text-base font-bold text-stone-900 font-data tabnum leading-none">{{ $stays->count() }}</p>
                        <p class="text-2xs font-bold text-faint uppercase tracking-widest mt-1.5">Stays</p>
                    </div>
                    <div class="px-2 py-3.5">
                        <p class="text-base font-bold text-stone-900 font-data tabnum leading-none">{{ $completedStays }}</p>
                        <p class="text-2xs font-bold text-faint uppercase tracking-widest mt-1.5">Done</p>
                    </div>
                    <div class="px-2 py-3.5">
                        <p class="text-base font-bold text-stone-900 font-data tabnum leading-none">{{ $lifetimeSpend >= 1000 ? '₱' . number_format($lifetimeSpend / 1000, 1) . 'k' : '₱' . number_format($lifetimeSpend) }}</p>
                        <p class="text-2xs font-bold text-faint uppercase tracking-widest mt-1.5">Spend</p>
                    </div>
                </div>
            </div>
        </aside>

        {{-- ─────────── Right column: the stay itself ─────────── --}}
        <div class="space-y-5 min-w-0">

            {{-- Current booking --}}
            <div class="rounded-2xl border border-stone-200 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <span class="w-12 h-12 shrink-0 rounded-2xl bg-linear-to-br from-clsu-500 to-clsu-700 text-white flex items-center justify-center shadow-sm">
                            <x-admin.ui.icon name="bed" class="w-5 h-5" stroke-width="2" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-clsu-700">Booking ID #BK-{{ str_pad($booking->id, 4, '0', STR_PAD_LEFT) }}</p>
                            <p class="text-base font-bold text-stone-900 leading-tight mt-0.5 truncate">
                                {{ $roomNumbers->isNotEmpty() ? 'Room ' . $roomNumbers->implode(', ') : 'No rooms assigned' }}
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-2xs font-bold border shrink-0 {{ $badgeClass }}">{{ $statusLabel }}</span>
                </div>

                @php
                    $facts = [
                        ['icon' => 'users',    'label' => 'Room Capacity', 'value' => ($booking->reservations->sum('capacity') ?: $booking->expected_guests) . ' Person'],
                        ['icon' => 'bed',      'label' => 'Room Type',     'value' => $bookedTypes->pluck('title')->implode(', ') ?: '—'],
                        ['icon' => 'calendar', 'label' => 'Booking Date',  'value' => $booking->check_in->format('M d') . ' – ' . $booking->check_out->format('M d, Y')],
                        ['icon' => 'clock',    'label' => 'Duration',      'value' => $nights . ' ' . Str::plural('night', $nights) . ' · ' . $booking->expected_guests . ' pax'],
                    ];

                    // Only when the guest actually told us. A blank slot is
                    // more honest than "—", which reads as data we lost.
                    if ($booking->arrival_time) {
                        // An arrival before check-in is a request the desk has
                        // to decide on — the room may not be turned over yet —
                        // so it is labelled as one rather than sitting in the
                        // row as though it were already agreed.
                        $early = \App\Support\StaySchedule::isEarlyArrival(
                            \Carbon\Carbon::parse($booking->arrival_time)->format('H:i')
                        );

                        $facts[] = [
                            'icon'  => 'clock',
                            'label' => $early ? 'Early check-in asked' : 'Est. Arrival',
                            'value' => \Carbon\Carbon::parse($booking->arrival_time)->format('g:i A')
                                . ($early ? ' · before ' . \App\Support\StaySchedule::checkinLabel() : ''),
                        ];
                    }
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-3.5 mt-5 pt-4 border-t border-stone-100">
                    @foreach($facts as $fact)
                        <div class="min-w-0">
                            <p class="flex items-center gap-1.5 text-2xs font-bold text-faint uppercase tracking-widest">
                                <x-admin.ui.icon :name="$fact['icon']" class="w-3 h-3 shrink-0" />
                                {{ $fact['label'] }}
                            </p>
                            <p class="text-sm text-stone-800 font-semibold mt-1 leading-snug break-words">{{ $fact['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                {{-- Guest requests are the one field on this card that asks the
                     desk to *do* something, so it gets its own band rather than
                     a slot in the fact grid where it would be truncated. --}}
                @if($booking->special_requests)
                    <div class="mt-4 pt-4 border-t border-stone-100">
                        <p class="flex items-center gap-1.5 text-2xs font-bold text-amber-700 uppercase tracking-widest">
                            <x-admin.ui.icon name="note" class="w-3 h-3 shrink-0" />
                            Guest Request
                        </p>
                        <p class="mt-1.5 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm font-medium text-stone-700 leading-relaxed whitespace-pre-line break-words">{{ $booking->special_requests }}</p>
                    </div>
                @endif

                @if($showProgress)
                    <div class="mt-4 pt-4 border-t border-stone-100">
                        <div class="flex items-center justify-between text-2xs font-semibold mb-2">
                            <span class="text-muted">
                                @if($elapsed < 0)
                                    Arrives in {{ abs($elapsed) }} {{ Str::plural('day', abs($elapsed)) }}
                                @elseif($progressPct >= 100)
                                    Stay complete — due to check out
                                @else
                                    Night {{ $currentNight }} of {{ $nights }}
                                @endif
                            </span>
                            <span class="text-faint font-data tabnum">{{ $progressPct }}%</span>
                        </div>
                        <div class="stay-progress-track">
                            <div class="stay-progress-fill" style="width: {{ $progressPct }}%"></div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Room facilities --}}
            @if($facilityPhotos->isNotEmpty())
                <div>
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 mb-2.5">
                        <p class="flex items-center gap-2 text-xs font-bold text-muted uppercase tracking-widest">
                            <x-admin.ui.icon name="grid" class="w-3.5 h-3.5" />
                            Room Facilities
                        </p>
                        @if($amenityList)
                            <p class="text-xs text-muted min-w-0 truncate" title="{{ $amenityList }}">{{ $amenityList }}</p>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        @foreach($facilityPhotos as $photo)
                            <button type="button"
                                    class="facility-tile block aspect-[4/3] rounded-xl border border-stone-200 cursor-pointer"
                                    data-facility-photo="{{ asset($photo['img']) }}"
                                    data-facility-title="{{ $photo['title'] }}"
                                    aria-label="View {{ $photo['title'] }} photo fullscreen">
                                <x-img :src="$photo['img']" :alt="$photo['title']" loading="lazy" decoding="async"
                                       sizes="(max-width: 768px) 50vw, 240px" class="w-full h-full object-cover" />
                                <span class="facility-tile-scrim">
                                    <x-admin.ui.icon name="maximize" class="w-4 h-4" />
                                    <span>View fullscreen</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Billing: the payable figure leads, the arithmetic supports it --}}
            <div>
                <p class="flex items-center gap-2 text-xs font-bold text-muted uppercase tracking-widest mb-2.5">
                    <x-admin.ui.icon name="receipt" class="w-3.5 h-3.5" />
                    Billing
                </p>
                <div class="rounded-2xl border border-stone-200 overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                        <div class="p-5 bg-clsu-50/50 border-b sm:border-b-0 sm:border-r border-stone-200">
                            <p class="text-2xs font-bold text-clsu-700 uppercase tracking-widest">Amount Payable</p>
                            <p class="text-2xl font-extrabold text-clsu-800 font-data tabnum mt-1 leading-none">₱{{ number_format($payable, 2) }}</p>
                            @if($payment)
                                <span class="inline-flex items-center gap-1.5 mt-3 px-2.5 py-1 rounded-full text-2xs font-bold border {{ $badgeClassMap[$paymentStatusColor] ?? 'bg-stone-100 text-stone-600 border-stone-200' }}">
                                    <x-admin.ui.icon name="credit-card" class="w-3 h-3" />
                                    Payment {{ ucfirst($payment->status) }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 mt-3 px-2.5 py-1 rounded-full text-2xs font-bold border bg-stone-100 text-stone-600 border-stone-200">
                                    No payment recorded
                                </span>
                            @endif
                        </div>
                        <div class="p-5 space-y-2.5 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-muted">Subtotal</span>
                                <span class="text-stone-800 font-semibold font-data tabnum">₱{{ number_format($booking->total_price, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-muted">Discount</span>
                                <span class="font-semibold font-data tabnum {{ $booking->discount > 0 ? 'text-clsu-700' : 'text-stone-800' }}">
                                    {{ $booking->discount > 0 ? '−' : '' }}₱{{ number_format($booking->discount, 2) }}
                                </span>
                            </div>
                            @if($payment)
                                <div class="flex items-center justify-between gap-4 pt-2.5 border-t border-stone-100">
                                    <span class="text-muted">Paid</span>
                                    <span class="text-stone-800 font-semibold font-data tabnum">₱{{ number_format($payment->amount, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-4 min-w-0">
                                    <span class="text-muted shrink-0">Reference</span>
                                    <span class="text-stone-800 font-semibold font-data text-xs truncate">{{ $payment->reference_no ?? 'N/A' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Per-room breakdown --}}
            <div>
                <p class="flex items-center gap-2 text-xs font-bold text-muted uppercase tracking-widest mb-2.5">
                    <x-admin.ui.icon name="bed" class="w-3.5 h-3.5" />
                    Room Details
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @foreach ($booking->reservations as $res)
                        @php $meals = collect($res->meal)->filter(fn ($qty) => (int) $qty > 0); @endphp
                        <div class="rounded-xl border border-stone-200 p-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-stone-800 min-w-0">
                                    Room {{ $res->room_number }}
                                    <span class="block text-xs text-faint font-normal mt-0.5 truncate">{{ $catalog[$res->room_type]['title'] ?? ($res->room->room_type ?? '—') }}</span>
                                </p>
                                <span class="text-sm font-bold text-stone-800 font-data tabnum shrink-0">₱{{ number_format($res->price, 2) }}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-2.5 text-xs text-muted">
                                <p>Guests <span class="block font-semibold text-stone-700 font-data tabnum">{{ $res->num_guests }}</span></p>
                                <p>Seniors / PWD <span class="block font-semibold text-stone-700 font-data tabnum">{{ $res->num_seniors }}</span></p>
                            </div>
                            <div class="mt-2.5 pt-2.5 border-t border-stone-100">
                                <p class="text-2xs font-bold text-faint uppercase tracking-widest mb-1.5">Breakfast</p>
                                @if($meals->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($meals as $mealName => $qty)
                                            <span class="cell-tag">{{ $mealName }} × {{ $qty }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-faint italic">None selected</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <x-admin.bookings.timeline :booking="$booking" />
        </div>
    </div>

    {{-- Stay history spans the full width — it is a table, not a sidebar item --}}
    <div class="mt-6 pt-5 border-t border-stone-200">
        <x-admin.bookings.guest-history :booking="$booking" :stays="$stays" />
    </div>
</div>
