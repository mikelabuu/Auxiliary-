{{-- Width: 7xl (1280px) up to large laptops, then widened in two steps. The
     three room cards are the densest thing on the page — at 1280px they land
     at 384px each, which crops the photo hard and squeezes the amenity list
     into three lines. Letting the grid breathe on bigger screens takes them to
     ~421px and ~458px without touching the laptop layout, so the shared
     max-w-7xl edge that story/gallery/cta align to still reads as the page's
     spine at the sizes most visitors are on.

     If you widen this further, raise the `sizes` hint on the card image to
     match (components/booking/cards/room.blade.php) or the photos go soft. --}}
<section id="rooms" class="mx-auto max-w-7xl xl:max-w-[86rem] 2xl:max-w-[94rem] scroll-mt-28 px-6 pt-24 pb-28 md:pt-32">
    <x-booking.sections.heading
        eyebrow="Accommodations"
        description="{{ count($roomTypes) }} fully-serviced room types for short stays, transient guests, and university researchers. Filter by capacity or open a room for the full picture. Booking takes one click."
        class="mb-10" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
        Reserve a <span class="italic text-gold">room</span>
    </x-booking.sections.heading>

    <!-- Capacity filter pills -->
    <x-booking.sections.room-filters class="mb-12" data-aos="fade-up" data-aos-delay="100" />

    <x-booking.ui.error-list class="mx-auto mb-8 max-w-3xl" />

    <!-- Live Availability Results Banner (filled by availability-search.js) -->
    <div id="availabilityBanner" class="hidden mb-10">
        <div class="animate-pop glass-light mx-auto flex max-w-3xl flex-col items-center justify-between gap-3 rounded-full px-6 py-4 sm:flex-row">
            <div class="flex items-center gap-3">
                <span class="relative flex h-2.5 w-2.5 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-clsu-500 opacity-60"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-clsu-500"></span>
                </span>
                <p class="text-sm font-semibold text-ink" id="availabilityBannerText">Live availability</p>
            </div>
            <button type="button" id="btnClearAvailability" class="gold-underline inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-[0.2em] text-ink/60 hover:text-ink transition-colors cursor-pointer">
                Clear dates
            </button>
        </div>
    </div>

    <!-- Room Grid (cards reveal individually with a stagger — see x-booking.cards.room) -->
    <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3" data-prlx-y="0.06" data-prlx-scale="0.02">
        @foreach ($roomTypes as $type)
            <div data-room-item
                 data-beds="{{ $type['beds'] }}"
                 data-premium="{{ ($type['badge'] ?? '') === 'Premium' ? 1 : 0 }}"
                 class="transition-[transform,opacity] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]">
                <x-booking.cards.room
                    :title="$type['title']"
                    :beds="$type['beds']"
                    :price="$type['price']"
                    :typeId="$type['id']"
                    :image="$type['image']"
                    :capacity="$type['capacity']"
                    :badge="$type['badge'] ?? null"
                    :amenities="$type['amenities'] ?? []"
                    {{-- tags differentiate the room, includes is the spec list.
                         Both already existed in config/room_types.php and
                         neither had ever reached the card. --}}
                    :tags="$type['tags'] ?? []"
                    :includes="$type['includes'] ?? []"
                    :floor="$type['floor'] ?? ''"
                    {{-- Full text: the card clamps it to two rendered lines in
                         CSS. Str::limit(90) cut at a character count and shipped
                         broken words to the page ("or friend..."). --}}
                    :description="$type['description'] ?? ''"
                    :index="$loop->iteration"
                />
            </div>
        @endforeach
    </div>

    {{-- Filter empty state. rounded-2xl, not 3xl: this section runs one
         radius for surfaces and a full pill for anything interactive. --}}
    <div id="roomFilterEmpty" class="hidden mt-4 rounded-2xl border border-dashed border-ink/15 bg-ink/[0.02] px-8 py-16 text-center">
        <x-booking.ui.icon name="bed" class="mx-auto h-8 w-8 text-ink/25" />
        <p class="mt-4 font-display text-2xl text-ink">No rooms in this range</p>
        <p class="mt-2 text-sm text-ink/55">Try a different capacity, or the dormitories for larger groups.</p>
    </div>
</section>
