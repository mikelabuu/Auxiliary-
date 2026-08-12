@props([
    'title',
    'beds',
    'price',
    'typeId',
    'image',
    'capacity' => '',
    'badge' => null,
    'amenities' => [],
    'tags' => [],
    'includes' => [],
    'floor' => '',
    'description' => '',
    'index' => 1,
])

@php
    $formattedPrice = number_format($price);
    // Column-based stagger so each grid row cascades left-to-right on reveal
    $revealDelay = (($index - 1) % 3) * 110;

    // Tags carry what makes this room different ("Best Value", "Queen Bed",
    // "Largest Room"). Several of them repeat the floor label verbatim, so a
    // tag already stated in the eyebrow above is dropped rather than printed
    // twice on the same card.
    $displayTags = collect($tags)
        ->reject(fn ($t) => $floor && str_contains($floor, $t))
        ->take(3)
        ->values();

    // Six covers every room's list in full except the Deluxe, which carries
    // seven. The remainder is counted rather than listed, and the modal behind
    // "View details" holds the complete set.
    $shownIncludes = collect($includes)->take(6)->values();
    $extraIncludes = max(0, count($includes) - $shownIncludes->count());
@endphp

{{-- ═══════════════════════════════════════════════════════════════
     ROOM CARD

     A contained surface rather than a photo with copy floating loose
     beneath it. Image bleeds to the card's top corners, body is padded
     inside the same border.

     Radius system for this section: surfaces are rounded-2xl, anything
     interactive is a full pill. No third radius.

     NOTE: no overflow-hidden on the article. .room-card paints its hover
     shadow on an ::after, and clipping would erase it. The image link does
     its own rounding and clipping instead.

     JS contract (availability-search.js) is attribute-based and unchanged:
     data-room-card / data-card-image / data-avail-slot / data-book-btn /
     data-book-label. setCardFull() greys the image and disables the button,
     so both must stay exactly where they are.
     ═══════════════════════════════════════════════════════════════ --}}
{{-- Two elements, because two different systems want to write `transform` on
     this card and the reveal was winning.

     The scroll reveal targets [data-aos] and .js-reveal [data-aos] in
     08-utilities.css sets transition: opacity, transform 0.95s. That selector
     scores higher than .room-card and is imported later, so it took the hover
     lift with it: the card rose over 0.95s instead of 0.5s, and border-color
     never transitioned at all because it was not in the reveal's list.

     Splitting them gives each system its own element. The article keeps the
     reveal, the inner surface owns the hover, and neither can silently
     redefine the other's timing. --}}
<article data-room-card="{{ $typeId }}" class="h-full"
         data-aos="fade-up" data-aos-delay="{{ $revealDelay }}">
<div class="room-card group flex h-full flex-col rounded-2xl border border-ink/10 bg-white/55 hover:border-ink/15">

    {{-- Portrait image, also the crawlable route into the full room page.
         Exactly one overlay lives on the photograph: the live availability
         state, the only thing here a guest reads at a glance.

         4/3 rather than the old 4/5. The taller crop left the body cramped
         under a very large picture, which is what made the card read as thin
         on detail; the same card height now carries the specifications
         underneath instead of more wall and floor. --}}
    <a href="{{ route('rooms.show', $typeId) }}" aria-label="View the {{ $title }} page"
       class="!no-underline relative block aspect-[4/3] overflow-hidden rounded-t-2xl bg-canvas-deep">
        {{-- Only `transform` is animated here now.

             The hover used to ease `filter` as well, lifting brightness 0.94
             to 1 and saturation 0.92 to 1 over 500ms. A filter cannot be
             composited: every frame of that half second re-rastered the whole
             400px photograph, while the scale transform ran on the GPU beside
             it. The two ran at different rates on different threads, which is
             the roughness you were feeling on the picture specifically rather
             than on the card.

             The same brightening is now done by the tint layer below, whose
             opacity is composited. The filter stays as a static value so the
             resting image keeps its slightly muted grade; it just never
             animates. --}}
        {{-- The last hop tracks the rooms grid, which widens at xl/2xl (see
             public/home/partials/rooms.blade.php): cards run ~384px up to
             1279px, ~421px to 1535px, ~458px above. A flat 400px hint was
             already under-declaring the top end, and the card scales its photo
             1.05x on hover, so the served tier has to cover the hover state
             too — hence 480px rather than the resting width. --}}
        <x-img data-card-image :src="$image" :alt="$title" loading="lazy" decoding="async"
               sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, (max-width: 1279px) 400px, 480px"
               class="absolute inset-0 h-full w-full object-cover brightness-[0.94] saturate-[0.92] [transition:transform_800ms_cubic-bezier(0.22,1,0.36,1),opacity_400ms_ease] group-hover:scale-[1.05]" />

        {{-- Tint layer: fades out on hover, which reads as the photograph
             lifting. Opacity only, so it stays on the compositor. --}}
        <div class="pointer-events-none absolute inset-0 bg-night/[0.09] opacity-100 transition-opacity duration-[600ms] ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:opacity-0"></div>

        {{-- Scrim only at the top, where the availability pill sits. --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1/3 bg-linear-to-b from-night/45 to-transparent"></div>

        {{-- Glare sweep on hover (see .card-shine). The single decorative
             flourish; the gold corner hairlines that used to fire on the same
             hover are gone. --}}
        <span class="card-shine" aria-hidden="true"></span>

        @if ($badge)
            <span class="absolute top-4 left-4 rounded-full bg-gold px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-night shadow-sm">{{ $badge }}</span>
        @endif

        {{-- Live availability pill (filled by availability-search.js) --}}
        <div data-avail-slot class="absolute top-4 right-4 z-10"></div>
    </a>

    {{-- Body --}}
    <div class="flex flex-1 flex-col p-5">
        @if ($floor)
            <p class="text-[10.5px] font-semibold uppercase tracking-[0.26em] text-ink/70">{{ $floor }}</p>
        @endif

        <h3 class="text-balance mt-2 font-display text-[26px] leading-[1.15] text-ink">
            <a href="{{ route('rooms.show', $typeId) }}"
               class="focus-ring !no-underline rounded transition-colors duration-300 hover:text-emerald-deep">{{ $title }}</a>
        </h3>

        {{-- Bed configuration, relocated off the photograph --}}
        @if ($capacity)
            <p class="mt-2.5 inline-flex items-center gap-1.5 text-[12px] text-ink/70">
                <x-booking.ui.icon name="users" class="h-3.5 w-3.5 shrink-0 text-gold" />
                {{ $capacity }}
            </p>
        @endif

        {{-- Differentiators.

             These replace the amenity chips that used to sit here. Those
             chips read "Free Wi-Fi / Hot & Cold / Guest Kit" on five of the
             seven rooms, so every card in the grid showed the same three
             pills: real estate spent on information that could not help
             anyone choose between them, which is a large part of why the
             cards felt interchangeable. Every amenity label is also present
             in `includes` below, so nothing is lost by dropping them. --}}
        @if ($displayTags->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach ($displayTags as $tag)
                    <span class="inline-flex items-center rounded-full border border-gold/35 bg-gold/[0.07] px-2.5 py-0.5 text-[10.5px] font-semibold uppercase tracking-[0.12em] text-ink/75">{{ $tag }}</span>
                @endforeach
            </div>
        @endif

        @if ($description)
            {{-- line-clamp, not Str::limit. Character truncation cut mid word
                 and shipped things like "or friend..." to the page; the clamp
                 ends on the rendered line boundary at every width. Three lines
                 shows all seven descriptions in full except the Family Room's,
                 which runs to 226 characters. --}}
            <p class="text-pretty mt-3 line-clamp-3 text-[13.5px] leading-relaxed text-ink/70">{{ $description }}</p>
        @endif

        {{-- What's included.

             The card previously showed nothing from this field at all, which
             is the detail that was missing: rate, beds and a sentence, then
             straight to the button. Two columns keeps four items to two rows. --}}
        @if ($shownIncludes->isNotEmpty())
            <div class="mt-4 border-t border-ink/10 pt-4">
                <p class="text-[9.5px] font-bold uppercase tracking-[0.22em] text-ink/55">Included</p>
                {{-- One column, at every width. Two columns fit at 1280 but the
                     card is only ~276px wide in the sm/md two-up grid, and
                     "Complimentary Guest Kit" wrapped to two lines there, so
                     half the rows were ragged. A single column costs about
                     36px of height and never wraps at any breakpoint. --}}
                <ul class="mt-2 grid grid-cols-1 gap-y-1.5">
                    @foreach ($shownIncludes as $item)
                        <li class="flex items-start gap-1.5 text-[11.5px] leading-snug text-ink/75">
                            <x-booking.ui.icon name="check" class="mt-px h-3 w-3 shrink-0 text-emerald" />
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($extraIncludes > 0)
                    <p class="mt-1.5 text-[11px] text-ink/55">and {{ $extraIncludes }} more</p>
                @endif
            </div>
        @endif

        {{-- Decision row.

             The price used to sit beside the room name as a three-line
             right-aligned stack (FROM / figure / PER NIGHT), which put three
             type sizes in a column next to the title and left every card with
             a ragged right edge. It belongs next to the control it justifies,
             so it anchors the bottom of the card opposite the button. mt-auto
             pins this row to the card's floor no matter how the copy above
             reflows, which is what makes a row of cards line up. --}}
        <div class="mt-auto border-t border-ink/10 pt-5">
            <div class="flex items-baseline gap-2">
                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-ink/65">From</p>
                <p class="font-display text-[26px] leading-none text-ink">
                    &#8369;{{ $formattedPrice }}<span class="ml-1.5 font-sans text-[11px] font-medium tracking-wide text-ink/60">/ night</span>
                </p>
            </div>

            {{-- Two real controls, side by side and equal width.

                 "View details" was a 10px text link tucked under the price. It
                 sat at the very bottom edge of the card, below the fold on a
                 laptop, and read as a footnote rather than a control: easy to
                 miss entirely. It is now a ghost button matched to Book, so
                 the pair reads as one decision point. The labels stay distinct
                 because the destinations are: details opens the quick modal,
                 Book goes to checkout, and the room name above still links to
                 the full crawlable room page. --}}
            {{-- flex-wrap plus a floor width, so the pair drops to stacked
                 rather than squeezing.

                 The label on the primary button is not always "Book":
                 availability-search.js swaps it for "Fully Booked" when a room
                 is sold out, and that string measures 102px. Side by side on a
                 375px phone it had 3px of clearance, and in the two-up grid
                 around 640-900px the card is only ~276px wide, where it would
                 have wrapped to two lines. 9rem is wider than the longest
                 label plus its padding, so below roughly 300px of row the two
                 buttons stack full width instead. --}}
            <div class="mt-4 flex flex-wrap items-stretch gap-2.5">
                <button type="button"
                    onclick="window.dispatchEvent(new CustomEvent('open-room-detail', { detail: { roomId: '{{ $typeId }}' } }))"
                    aria-label="View details for {{ $title }}"
                    class="press focus-ring inline-flex min-h-11 min-w-[9rem] flex-1 items-center justify-center rounded-full border border-emerald-deep/45 bg-transparent px-4 py-2.5 text-[11.5px] font-semibold uppercase tracking-[0.14em] text-emerald-deep cursor-pointer hover:border-emerald-deep hover:bg-emerald-deep/[0.07]"
                >
                    Details
                </button>

            {{-- Primary action.

                 This was bg-bone on a bg-canvas page: 1.1:1 between the button
                 and the surface behind it, so the label floated in space and
                 the site's single conversion control was invisible. It now
                 uses emerald-deep, which is already this page's active/primary
                 signal on the capacity filter pills, so the accent stays
                 locked to one colour across the section. Measures ~11:1.

                 No transition utilities here on purpose: .press is unlayered
                 CSS and beats Tailwind's layered utilities, so a
                 transition-[...] duration-300 alongside it would be dead code
                 that reads as though it were doing something. .press already
                 eases background-color, box-shadow and the :active press. --}}
            <button type="button"
                data-book-btn
                onclick="bookRoomDirect('{{ $typeId }}')"
                aria-label="Book {{ $title }} for &#8369;{{ $formattedPrice }} per night"
                class="press focus-ring inline-flex min-h-11 min-w-[9rem] flex-1 items-center justify-center rounded-full bg-emerald-deep px-4 py-2.5 text-[11.5px] font-semibold uppercase tracking-[0.16em] text-cream cursor-pointer hover:bg-emerald hover:shadow-[0_14px_30px_-14px_rgba(8,52,34,0.7)] disabled:cursor-not-allowed disabled:bg-ink/25 disabled:text-cream/80 disabled:shadow-none"
            >
                <span data-book-label>Book</span>
            </button>
            </div>
        </div>
    </div>
</div>
</article>
