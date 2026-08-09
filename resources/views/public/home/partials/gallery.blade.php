{{-- Lightbox2 opens the bento tiles fullscreen. --}}
@push('vendor')
    @include('partials.vendor.lightbox')
@endpush

<section id="gallery" class="mx-auto max-w-7xl scroll-mt-28 px-6 py-24">
    <x-booking.sections.heading
        description="A quiet walk through our rooms, common spaces, dining hall, and campus greenery."
        align="center" class="mb-12" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
        A walk through the <span class="italic text-gold">grounds</span>
    </x-booking.sections.heading>

    {{-- All 12 shots are on the page now. The previous bento grid had room for
         six, and 7-12 were rendered as `hidden` anchors purely to keep the
         lightbox chain complete — so half the gallery was downloaded and
         hidden. Masonry sizes to its content, so nothing has to be hidden.
         Layout mechanics live in public/19-gallery-masonry.css. --}}
    <div class="gallery-masonry" data-aos="fade-up" data-aos-delay="100">
        @php
            // Shot 4 is the only portrait frame; everything else is 4:3.
            $portrait = [4];
        @endphp
        @for ($i = 1; $i <= 12; $i++)
            <a href="{{ asset('image/gallery/' . $i . '.jpg') }}" data-lightbox="visual-tour"
               class="gallery-masonry__item group ring-1 ring-white/10 {{ in_array($i, $portrait, true) ? 'gallery-masonry__item--tall' : '' }}">
                <x-img :src="'image/gallery/' . $i . '.jpg'" alt="Farmers Hostel visual tour" loading="lazy" decoding="async"
                       sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                       class="h-full w-full object-cover brightness-[0.82] saturate-[0.85] transition duration-700 group-hover:scale-105 group-hover:brightness-100 group-hover:saturate-100" />
            </a>
        @endfor
    </div>

    <div class="mt-10 flex justify-center" data-aos="fade-up">
        <button type="button" onclick="document.querySelector('#gallery a[data-lightbox]')?.click()" class="press focus-ring inline-flex items-center gap-3 rounded-full border border-ink/15 bg-ink/5 px-6 py-3 text-[11px] font-bold uppercase tracking-[0.3em] text-ink transition hover:border-clsu-500/60 hover:bg-clsu-50 cursor-pointer">
            <x-booking.ui.icon name="sparkles" class="h-4 w-4 text-gold" />
            Explore the full gallery
        </button>
    </div>
</section>
