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
    $indexLabel = str_pad($index, 2, '0', STR_PAD_LEFT);
    // Column-based stagger so each grid row cascades left-to-right on reveal
    $revealDelay = (($index - 1) % 3) * 110;
@endphp

<article data-room-card="{{ $typeId }}" class="group flex h-full flex-col" data-aos="fade-up" data-aos-delay="{{ $revealDelay }}">
    <!-- Portrait image -->
    <div class="hover-lift-premium relative aspect-[3/4] overflow-hidden rounded-2xl bg-canvas-deep sm:aspect-[4/5]">
        <img data-card-image src="{{ asset($image) }}" alt="{{ $title }}" loading="lazy" decoding="async"
             class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-105">
        <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-ink/45 via-transparent to-transparent"></div>

        <!-- Editorial index -->
        <span aria-hidden="true" class="absolute top-4 left-4 font-display text-xs italic tracking-[0.2em] text-cream/95 drop-shadow-[0_1px_2px_rgba(0,0,0,0.4)]">— {{ $indexLabel }}</span>

        <!-- Gold corner hairlines on hover -->
        <span aria-hidden="true" class="pointer-events-none absolute top-3 right-3 h-6 w-6">
            <span class="absolute top-0 right-0 h-px w-0 bg-gold transition-[width] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:w-full"></span>
            <span class="absolute top-0 right-0 h-0 w-px bg-gold transition-[height] duration-500 delay-100 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:h-full"></span>
        </span>

        @if ($badge)
            <span class="absolute top-4 left-16 rounded-full bg-gold px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-ink shadow-sm">{{ $badge }}</span>
        @endif

        <!-- Live availability pill (filled by availability-search.js) -->
        <div data-avail-slot class="absolute top-12 right-3 z-10"></div>

        <!-- Capacity pill -->
        <div class="absolute bottom-4 left-4 inline-flex items-center gap-1.5 rounded-full bg-cream-warm/95 px-3 py-1 text-[11px] font-medium text-ink">
            <x-booking.ui.icon name="users" class="h-3.5 w-3.5 text-emerald" />
            {{ $capacity }}
        </div>
    </div>

    <!-- Body -->
    <div class="mt-6 flex flex-1 flex-col">
        @if ($floor)
            <p class="text-[11px] uppercase tracking-[0.3em] text-emerald/80">{{ $floor }}</p>
        @endif

        <div class="mt-2 grid grid-cols-[minmax(0,1fr)_auto] items-start gap-4">
            <h3 class="text-balance font-display text-2xl leading-tight text-ink">{{ $title }}</h3>
            <div class="shrink-0 text-right">
                <p class="text-[9px] font-bold uppercase tracking-[0.28em] text-emerald/70">From</p>
                <p class="font-display text-xl leading-none text-ink">₱{{ $formattedPrice }}</p>
                <p class="mt-1 text-[10px] uppercase tracking-[0.22em] text-ink/50">per night</p>
            </div>
        </div>

        <span aria-hidden="true" class="mt-4 block h-px w-10 bg-gold"></span>

        @if ($description)
            <p class="text-pretty mt-4 text-sm leading-relaxed text-ink/60">{{ $description }}</p>
        @endif

        <!-- Amenity chips -->
        <div class="mt-4 flex flex-1 flex-wrap content-start gap-2">
            @forelse ($amenities as $amenity)
                <span class="inline-flex h-fit items-center gap-1.5 rounded-full bg-cream-warm px-3 py-1 text-[11px] font-medium text-ink ring-1 ring-emerald-deep/10">
                    <x-booking.ui.icon :name="$amenity['icon'] ?? 'check'" class="h-3.5 w-3.5 text-emerald" />
                    {{ $amenity['label'] ?? $amenity }}
                </span>
            @empty
                <span class="inline-flex h-fit items-center gap-1.5 rounded-full bg-cream-warm px-3 py-1 text-[11px] font-medium text-ink ring-1 ring-emerald-deep/10">
                    <x-booking.ui.icon name="wifi" class="h-3.5 w-3.5 text-emerald" />
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
                class="press focus-ring inline-flex min-h-11 items-center rounded-full bg-emerald-deep px-6 py-2.5 text-[12px] font-semibold uppercase tracking-[0.18em] text-cream transition-all cursor-pointer hover:bg-emerald hover:shadow-[0_0_0_4px_color-mix(in_oklch,var(--color-gold)_22%,transparent)] disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-emerald-deep disabled:hover:shadow-none"
            >
                <span data-book-label>Book · ₱{{ $formattedPrice }}</span>
            </button>
            <button type="button"
                onclick="window.dispatchEvent(new CustomEvent('open-room-detail', { detail: { roomId: '{{ $typeId }}' } }))"
                aria-label="View details for {{ $title }}"
                class="gold-underline focus-ring rounded text-[11px] font-bold uppercase tracking-[0.3em] text-ink cursor-pointer"
            >
                View Details
            </button>
        </div>
    </div>
</article>
