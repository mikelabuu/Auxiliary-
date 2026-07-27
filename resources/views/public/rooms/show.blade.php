@extends('layouts.public.base')
@section('title', $roomType['title'] . ' · Farmers Hostel')

@section('content')
@php
    use Illuminate\Support\Str;

    $slug = $roomType['id'];

    // The mosaic: hero tile plus up to three companion shots. RoomCatalog
    // already prepends the hero image to `gallery`, so this is just a slice.
    $photos = collect($roomType['gallery'] ?? [])->take(4)->values();
    $hero   = $photos->first();
    $side   = $photos->slice(1)->values();

    // Side-tile spans so the mosaic stays balanced whether a type ships one
    // extra photo or three (a staff-created type may ship none at all).
    $sideSpans = [
        1 => ['lg:col-span-2 lg:row-span-2'],
        2 => ['lg:col-span-2', 'lg:col-span-2'],
        3 => ['lg:col-span-2', 'lg:col-span-1', 'lg:col-span-1'],
    ][$side->count()] ?? [];

    $bedLabel = trim(Str::before($roomType['capacity'] ?? '', '(')) ?: ($roomType['beds'] . ' beds');

    $featureRow = [
        ['icon' => 'users',    'label' => 'Sleeps',    'value' => $roomType['beds'] . ' ' . Str::plural('guest', $roomType['beds'])],
        ['icon' => 'bed',      'label' => 'Beds',      'value' => $bedLabel],
        ['icon' => 'shower',   'label' => 'Bath',      'value' => 'Hot & cold'],
        ['icon' => 'utensils', 'label' => 'Breakfast', 'value' => 'Included'],
        ['icon' => 'map-pin',  'label' => 'Location',  'value' => $roomType['floor'] ?: 'Farmers Hostel'],
    ];

    $tabs = [
        'description' => 'Description',
        'features'    => 'Features',
        'policies'    => 'Policies',
        'location'    => 'Location',
    ];
@endphp

<div class="min-h-screen bg-canvas pt-28 pb-24">
    <div class="mx-auto max-w-7xl px-6">

        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="mb-6 flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.24em] text-ink/45">
            <a href="{{ route('home') }}" class="gold-underline focus-ring rounded hover:text-ink">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('home') }}#rooms" class="gold-underline focus-ring rounded hover:text-ink">Rooms</a>
            <span aria-hidden="true">/</span>
            <span class="text-ink/75">{{ $roomType['title'] }}</span>
        </nav>

        {{-- Photo mosaic. Every tile is a lightbox link in one chain, so the
             "View all photos" button just clicks the first tile. --}}
        <div class="relative grid grid-cols-2 gap-3 lg:h-[520px] lg:grid-cols-4 lg:grid-rows-2" data-aos="fade-up">
            <a id="roomGalleryFirst" href="{{ asset($hero) }}" data-lightbox="room-{{ $slug }}" data-title="{{ $roomType['title'] }}"
               class="group relative col-span-2 block aspect-[4/3] overflow-hidden rounded-3xl ring-1 ring-ink/10 lg:col-span-2 lg:row-span-2 lg:aspect-auto">
                <img src="{{ asset($hero) }}" alt="{{ $roomType['title'] }}" decoding="async"
                     class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                <span class="card-shine" aria-hidden="true"></span>
                @if (!empty($roomType['badge']))
                    <span class="absolute top-5 left-5 rounded-full bg-gold px-4 py-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-night shadow-sm">{{ $roomType['badge'] }}</span>
                @endif
            </a>

            @foreach ($side as $photo)
                <a href="{{ asset($photo) }}" data-lightbox="room-{{ $slug }}" data-title="{{ $roomType['title'] }}"
                   class="group relative block aspect-square overflow-hidden rounded-3xl ring-1 ring-ink/10 lg:aspect-auto {{ $sideSpans[$loop->index] ?? '' }}">
                    <img src="{{ asset($photo) }}" alt="{{ $roomType['title'] }} — view {{ $loop->iteration + 1 }}" loading="lazy" decoding="async"
                         class="h-full w-full object-cover brightness-[0.96] transition duration-700 group-hover:scale-105 group-hover:brightness-100">
                    <span class="card-shine" aria-hidden="true"></span>
                </a>
            @endforeach

            @if ($photos->count() > 1)
                <button type="button"
                        onclick="document.getElementById('roomGalleryFirst')?.click()"
                        class="press focus-ring absolute bottom-5 right-5 hidden items-center gap-2 rounded-full border border-ink/10 bg-canvas/90 px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.2em] text-ink shadow-sm backdrop-blur-md hover:bg-canvas lg:inline-flex cursor-pointer">
                    <x-booking.ui.icon name="images" class="h-4 w-4 text-gold" />
                    View all photos
                </button>
            @endif
        </div>

        {{-- Body: editorial column + sticky booking rail --}}
        <div class="mt-12 grid grid-cols-1 gap-12 lg:grid-cols-3 lg:items-start">

            <div class="lg:col-span-2">
                <h1 class="text-balance font-display text-4xl leading-[1.12] text-ink sm:text-5xl" data-aos="fade-up">{{ $roomType['title'] }}</h1>

                <p class="mt-4 flex items-center gap-2 text-sm font-medium text-ink/55" data-aos="fade-up" data-aos-delay="60">
                    <x-booking.ui.icon name="map-pin" class="h-4 w-4 shrink-0 text-gold" />
                    {{ $roomType['floor'] ?: 'Farmers Hostel' }} · CLSU, Science City of Muñoz
                </p>

                @if (!empty($roomType['tags']))
                    <div class="mt-6 flex flex-wrap gap-2" data-aos="fade-up" data-aos-delay="90">
                        @foreach ($roomType['tags'] as $tag)
                            <span class="inline-flex items-center rounded-full bg-ink/5 px-4 py-1.5 text-[11px] font-semibold text-ink/70 ring-1 ring-ink/10">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                {{-- Feature strip --}}
                <div class="mt-10" data-aos="fade-up" data-aos-delay="120">
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.32em] text-ink/45">Room features</h2>
                    <div class="mt-5 grid grid-cols-2 gap-x-6 gap-y-6 sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($featureRow as $feature)
                            <div class="min-w-0">
                                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gold/12 text-gold ring-1 ring-gold/20">
                                    <x-booking.ui.icon :name="$feature['icon']" class="h-[18px] w-[18px]" />
                                </span>
                                <p class="mt-3 text-[10px] font-bold uppercase tracking-[0.22em] text-ink/45">{{ $feature['label'] }}</p>
                                <p class="text-pretty mt-1 text-sm font-semibold leading-snug text-ink">{{ $feature['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tabbed detail --}}
                <div class="mt-12" x-data="{ tab: 'description' }" data-aos="fade-up" data-aos-delay="150">
                    <div role="tablist" aria-label="Room information" class="flex flex-wrap gap-x-8 gap-y-2 border-b border-ink/10">
                        @foreach ($tabs as $key => $label)
                            <button type="button" role="tab"
                                    :aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                                    :tabindex="tab === '{{ $key }}' ? 0 : -1"
                                    @click="tab = '{{ $key }}'"
                                    :class="tab === '{{ $key }}' ? 'text-ink border-gold' : 'text-ink/45 border-transparent hover:text-ink/75'"
                                    class="focus-ring -mb-px rounded-t border-b-2 pb-3 text-[12px] font-bold uppercase tracking-[0.2em] transition-colors cursor-pointer">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Description --}}
                    <div x-show="tab === 'description'" x-cloak role="tabpanel" class="pt-7">
                        <p class="text-pretty max-w-2xl text-base leading-relaxed text-ink/65">{{ $roomType['description'] }}</p>
                        <p class="text-pretty mt-4 max-w-2xl text-base leading-relaxed text-ink/65">
                            Rooms are serviced daily and kept quiet — the hostel sits inside the CLSU campus, a short walk from the
                            colleges, research stations, and the dining hall where breakfast is served each morning.
                        </p>
                    </div>

                    {{-- Features --}}
                    <div x-show="tab === 'features'" x-cloak role="tabpanel" class="pt-7">
                        @if (!empty($roomType['amenities']))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($roomType['amenities'] as $amenity)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-ink/5 px-4 py-2 text-[12px] font-medium text-ink/80 ring-1 ring-ink/10">
                                        <x-booking.ui.icon :name="$amenity['icon'] ?? 'check'" class="h-4 w-4 text-gold" />
                                        {{ $amenity['label'] ?? $amenity }}
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        @if (!empty($roomType['includes']))
                            <p class="mt-8 text-[11px] font-bold uppercase tracking-[0.32em] text-ink/45">What's included</p>
                            <ul class="mt-4 grid grid-cols-1 gap-x-8 gap-y-3 sm:grid-cols-2">
                                @foreach ($roomType['includes'] as $item)
                                    <li class="flex items-center gap-3 text-sm font-medium text-ink/75">
                                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-gold/15 text-gold">
                                            <x-booking.ui.icon name="check" class="h-3 w-3" />
                                        </span>
                                        {{ $item }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Policies --}}
                    <div x-show="tab === 'policies'" x-cloak role="tabpanel" class="pt-7">
                        @php
                            $policies = [
                                ['icon' => 'clock',       'title' => 'Check-in from 2:00 PM',   'body' => 'Front desk is staffed 24/7. Present a valid ID for every guest on the booking.'],
                                ['icon' => 'clock',       'title' => 'Check-out by 12:00 NN',   'body' => 'Late check-out is subject to availability — ask the front desk on the morning of departure.'],
                                ['icon' => 'utensils',    'title' => 'Outside food',            'body' => 'Outside food is not allowed inside the rooms. The dining hall is open to all guests.'],
                                ['icon' => 'badge-check', 'title' => 'Senior / PWD discount',   'body' => 'A 20% discount is available on request — upload a valid ID after booking for staff review.'],
                            ];
                        @endphp
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            @foreach ($policies as $policy)
                                <div class="rounded-2xl border border-ink/10 bg-ink/[0.02] p-5">
                                    <p class="flex items-center gap-2 text-sm font-bold text-ink">
                                        <x-booking.ui.icon :name="$policy['icon']" class="h-4 w-4 shrink-0 text-gold" />
                                        {{ $policy['title'] }}
                                    </p>
                                    <p class="text-pretty mt-2 text-sm leading-relaxed text-ink/60">{{ $policy['body'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Location --}}
                    <div x-show="tab === 'location'" x-cloak role="tabpanel" class="pt-7">
                        <div class="rounded-3xl border border-ink/10 bg-ink/[0.02] p-6 sm:p-8">
                            <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-gold">Finding us</p>
                            <p class="mt-3 font-display text-2xl leading-tight text-ink">Farmers Hostel, CLSU Campus</p>
                            <p class="mt-2 text-sm leading-relaxed text-ink/60">Central Luzon State University, Science City of Muñoz, Nueva Ecija</p>

                            <div class="mt-6 grid grid-cols-1 gap-x-8 gap-y-3 text-sm text-ink/65 sm:grid-cols-2">
                                <p class="flex items-center gap-2"><x-booking.ui.icon name="map-pin" class="h-4 w-4 shrink-0 text-gold" /> {{ $roomType['floor'] ?: 'Farmers Hostel' }}</p>
                                <p class="flex items-center gap-2"><x-booking.ui.icon name="clock" class="h-4 w-4 shrink-0 text-gold" /> Front desk open 24 / 7</p>
                                <p class="flex items-center gap-2"><x-booking.ui.icon name="leaf" class="h-4 w-4 shrink-0 text-gold" /> Two-minute walk to the research stations</p>
                                <p class="flex items-center gap-2"><x-booking.ui.icon name="utensils" class="h-4 w-4 shrink-0 text-gold" /> Dining hall on the ground floor</p>
                            </div>

                            <a href="https://www.google.com/maps/search/?api=1&query=Central+Luzon+State+University+Science+City+of+Mu%C3%B1oz"
                               target="_blank" rel="noopener noreferrer"
                               class="press focus-ring mt-7 inline-flex items-center gap-2 rounded-full border border-ink/15 bg-canvas px-5 py-2.5 text-[11px] font-bold uppercase tracking-[0.2em] text-ink hover:border-clsu-500/60 hover:bg-clsu-50">
                                <x-booking.ui.icon name="map-pin" class="h-4 w-4 text-gold" />
                                Open in Google Maps
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Booking rail --}}
            <aside class="lg:sticky lg:top-28" data-aos="fade-up" data-aos-delay="100">
                <div class="rounded-3xl border border-ink/10 bg-canvas p-7 shadow-[0_24px_60px_-32px_rgba(4,14,10,0.35)]">
                    <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-ink/45">Room rate</p>
                    <p class="mt-2 font-display text-4xl leading-none text-ink">₱{{ number_format($roomType['price']) }}</p>
                    <p class="mt-2 text-[10px] uppercase tracking-[0.22em] text-ink/50">per night</p>

                    <span aria-hidden="true" class="mt-6 block h-px w-10 bg-gold"></span>

                    <dl class="mt-6 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink/50">Capacity</dt>
                            <dd class="text-right font-semibold text-ink">{{ $roomType['beds'] }} pax max</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink/50">Bed setup</dt>
                            <dd class="text-right font-semibold text-ink">{{ $bedLabel }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink/50">Check-in</dt>
                            <dd class="text-right font-semibold text-ink">2:00 PM</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-ink/50">Check-out</dt>
                            <dd class="text-right font-semibold text-ink">12:00 NN</dd>
                        </div>
                    </dl>

                    <a href="{{ route('checkout.form', ['room_type' => $slug]) }}"
                       class="press focus-ring !no-underline mt-7 flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-emerald-deep px-6 py-3.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-clsu-500)_28%,transparent)]">
                        <x-booking.ui.icon name="calendar" class="h-4 w-4" />
                        Book this room
                    </a>

                    <a href="{{ route('home') }}#rooms"
                       class="press focus-ring !no-underline mt-3 flex w-full items-center justify-center gap-2 rounded-full border border-ink/15 bg-ink/[0.03] px-6 py-3 text-[11px] font-bold uppercase tracking-[0.18em] text-ink/70 cursor-pointer hover:bg-ink/[0.06] hover:text-ink">
                        Check dates &amp; availability
                    </a>

                    <p class="mt-5 text-center text-[11px] leading-relaxed text-ink/45">Rates are per room, per night. Breakfast is already included.</p>
                </div>
            </aside>
        </div>

        {{-- Other rooms --}}
        @if (count($otherTypes))
            <section class="mt-24">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 class="font-display text-3xl leading-tight text-ink sm:text-4xl" data-aos="fade-up">Other <span class="italic text-gold">rooms</span></h2>
                    <a href="{{ route('home') }}#rooms" class="gold-underline focus-ring rounded text-[11px] font-bold uppercase tracking-[0.3em] text-ink/60 hover:text-ink">View all rooms</a>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (collect($otherTypes)->take(3) as $other)
                        <a href="{{ route('rooms.show', $other['id']) }}"
                           class="group !no-underline block overflow-hidden rounded-3xl border border-ink/10 bg-canvas transition duration-300 hover:border-ink/20 hover:shadow-[0_24px_60px_-32px_rgba(4,14,10,0.35)]"
                           data-aos="fade-up" data-aos-delay="{{ $loop->index * 90 }}">
                            <div class="relative aspect-[4/3] overflow-hidden">
                                <img src="{{ asset($other['image']) }}" alt="{{ $other['title'] }}" loading="lazy" decoding="async"
                                     class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            </div>
                            <div class="p-6">
                                <p class="text-[10px] font-bold uppercase tracking-[0.24em] text-ink/45">{{ $other['floor'] ?? '' }}</p>
                                <h3 class="mt-2 font-display text-2xl leading-tight text-ink">{{ $other['title'] }}</h3>
                                <p class="mt-3 flex items-center justify-between text-sm">
                                    <span class="text-ink/55">{{ $other['beds'] }} pax max</span>
                                    <span class="font-display text-lg text-ink">₱{{ number_format($other['price']) }}</span>
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/parallax.js') }}?v={{ filemtime(public_path('js/parallax.js')) }}" defer></script>
@endpush
@endsection
