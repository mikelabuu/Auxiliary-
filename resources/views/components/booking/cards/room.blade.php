@props([
    'title',
    'beds',
    'price',
    'typeId',
    'image',
    'capacity' => '',
    'badge' => null,
    'amenities' => [],
    'floor' => '',
    'description' => '',
    'index' => 1,
])

@php
    $formattedPrice = number_format($price);
    // Column-based stagger so each grid row cascades left-to-right on reveal
    $revealDelay = (($index - 1) % 3) * 110;
@endphp

<article data-room-card="{{ $typeId }}" class="group flex h-full flex-col" data-aos="fade-up" data-aos-delay="{{ $revealDelay }}">
    <!-- Portrait image — also the crawlable route into the full room page.
         Every JS hook below is attribute-based (data-card-image, data-avail-slot),
         so the anchor swap is invisible to availability-search.js. -->
    <a href="{{ route('rooms.show', $typeId) }}" aria-label="View the {{ $title }} page"
       class="hover-lift-premium !no-underline relative block aspect-[3/4] overflow-hidden rounded-2xl bg-canvas-deep ring-1 ring-white/10 sm:aspect-[4/5]">
        {{-- Zoom rides the slow editorial curve; filter/opacity (the fully-booked
             dim toggled by availability-search.js) read as one 400ms state change --}}
        <x-img data-card-image :src="$image" :alt="$title" loading="lazy" decoding="async"
               sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 400px"
               class="absolute inset-0 h-full w-full object-cover brightness-[0.92] saturate-[0.9] [transition:transform_1200ms_cubic-bezier(0.22,1,0.36,1),filter_400ms_ease,opacity_400ms_ease] group-hover:scale-105 group-hover:brightness-100 group-hover:saturate-100" />
        <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-night/60 via-transparent to-night/20"></div>

        <!-- Glare sweep on hover (see .card-shine in app.css) -->
        <span class="card-shine" aria-hidden="true"></span>

        <!-- Gold corner hairlines on hover -->
        <span aria-hidden="true" class="pointer-events-none absolute top-3 right-3 h-6 w-6">
            <span class="absolute top-0 right-0 h-px w-full origin-right scale-x-0 bg-gold transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-x-100"></span>
            <span class="absolute top-0 right-0 h-full w-px origin-top scale-y-0 bg-gold transition-transform duration-500 delay-100 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-y-100"></span>
        </span>

        @if ($badge)
            <span class="absolute top-4 left-4 rounded-full bg-gold px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-night shadow-sm">{{ $badge }}</span>
        @endif

        <!-- Live availability pill (filled by availability-search.js) -->
        <div data-avail-slot class="absolute top-12 right-3 z-10"></div>

        <!-- Capacity pill -->
        <div class="absolute bottom-4 left-4 inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-night/60 px-3 py-1 text-[11px] font-medium text-bone backdrop-blur-md">
            <x-booking.ui.icon name="users" class="h-3.5 w-3.5 text-gold" />
            {{ $capacity }}
        </div>
    </a>

    <!-- Body -->
    <div class="mt-6 flex flex-1 flex-col">
        @if ($floor)
            <p class="text-[11px] uppercase tracking-[0.3em] text-ink/55">{{ $floor }}</p>
        @endif

        <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto] items-start gap-4">
            <h3 class="text-balance font-display text-2xl leading-tight text-ink">
                <a href="{{ route('rooms.show', $typeId) }}" class="focus-ring !no-underline rounded transition-colors hover:text-clsu-700">{{ $title }}</a>
            </h3>
            <div class="shrink-0 text-right">
                <p class="text-[9px] font-bold uppercase tracking-[0.28em] text-ink/50">From</p>
                <p class="font-display text-xl leading-none text-ink">₱{{ $formattedPrice }}</p>
                <p class="mt-1 text-[10px] uppercase tracking-[0.22em] text-ink/55">per night</p>
            </div>
        </div>

        <span aria-hidden="true" class="mt-4 block h-px w-10 bg-gold/80"></span>

        @if ($description)
            <p class="text-pretty mt-4 text-sm leading-relaxed text-ink/55">{{ $description }}</p>
        @endif

        <!-- Amenity chips -->
        <div class="mt-4 flex flex-1 flex-wrap content-start gap-2">
            @forelse ($amenities as $amenity)
                <span class="inline-flex h-fit items-center gap-1.5 rounded-full bg-ink/5 px-3 py-1 text-[11px] font-medium text-ink/80 ring-1 ring-ink/10">
                    <x-booking.ui.icon :name="$amenity['icon'] ?? 'check'" class="h-3.5 w-3.5 text-gold/80" />
                    {{ $amenity['label'] ?? $amenity }}
                </span>
            @empty
                <span class="inline-flex h-fit items-center gap-1.5 rounded-full bg-ink/5 px-3 py-1 text-[11px] font-medium text-ink/80 ring-1 ring-ink/10">
                    <x-booking.ui.icon name="wifi" class="h-3.5 w-3.5 text-gold/80" />
                    Free Wi-Fi
                </span>
            @endforelse
        </div>

        <!-- CTAs -->
        <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3">
            <button type="button"
                data-book-btn
                onclick="bookRoomDirect('{{ $typeId }}')"
                aria-label="Book {{ $title }} for ₱{{ $formattedPrice }} per night"
                class="press focus-ring inline-flex min-h-11 items-center rounded-full bg-bone px-6 py-2.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-night cursor-pointer hover:bg-cream hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_30%,transparent),0_16px_40px_-18px_rgba(0,0,0,0.8)] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-bone disabled:hover:shadow-none"
            >
                <span data-book-label>Book · ₱{{ $formattedPrice }}</span>
            </button>
            <button type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-room-detail', { detail: { roomId: '{{ $typeId }}' } }))"
                aria-label="View details for {{ $title }}"
                class="gold-underline focus-ring rounded text-[11px] font-bold uppercase tracking-[0.3em] text-ink/70 transition-colors hover:text-ink cursor-pointer"
            >
                View Details
            </button>
        </div>
    </div>
</article>
