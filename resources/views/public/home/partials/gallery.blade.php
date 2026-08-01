<section id="gallery" class="mx-auto max-w-7xl scroll-mt-28 px-6 py-24">
    <x-booking.sections.heading
        description="A quiet walk through our rooms, common spaces, dining hall, and campus greenery."
        align="center" class="mb-12" data-aos="fade-up" data-prlx-y="0.06" data-prlx-opacity>
        A walk through the <span class="italic text-gold">grounds</span>
    </x-booking.sections.heading>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:h-[560px] lg:grid-cols-4 lg:grid-rows-6" data-aos="fade-up" data-aos-delay="100">
        @php
            $bento = [
                ['img' => 'image/gallery/1.jpg', 'span' => 'lg:col-span-2 lg:row-span-4 aspect-[4/3] sm:aspect-square lg:aspect-auto', 'prlx' => '0.06'],
                ['img' => 'image/gallery/2.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.1'],
                ['img' => 'image/gallery/3.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.14'],
                ['img' => 'image/gallery/4.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.08'],
                ['img' => 'image/gallery/5.jpg', 'span' => 'lg:col-span-1 lg:row-span-3 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.12'],
                ['img' => 'image/gallery/6.jpg', 'span' => 'sm:col-span-2 lg:col-span-2 lg:row-span-2 aspect-[4/3] lg:aspect-auto', 'prlx' => '0.05'],
            ];
        @endphp
        @foreach ($bento as $tile)
            <a href="{{ asset($tile['img']) }}" data-lightbox="visual-tour" class="group relative block overflow-hidden rounded-2xl ring-1 ring-white/10 {{ $tile['span'] }}" data-prlx-y="{{ $tile['prlx'] }}" data-prlx-scale="0.03">
                <x-img :src="$tile['img']" alt="Farmers Hostel visual tour" loading="lazy" decoding="async"
                       sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                       class="h-full w-full object-cover brightness-[0.82] saturate-[0.85] transition duration-700 group-hover:scale-105 group-hover:brightness-100 group-hover:saturate-100" />
            </a>
        @endforeach
        {{-- Remaining shots stay in the same lightbox chain --}}
        @for ($i = 7; $i <= 12; $i++)
            <a href="{{ asset('image/gallery/' . $i . '.jpg') }}" data-lightbox="visual-tour" class="hidden" aria-hidden="true" tabindex="-1"></a>
        @endfor
    </div>

    <div class="mt-10 flex justify-center" data-aos="fade-up">
        <button type="button" onclick="document.querySelector('#gallery a[data-lightbox]')?.click()" class="press focus-ring inline-flex items-center gap-3 rounded-full border border-ink/15 bg-ink/5 px-6 py-3 text-[11px] font-bold uppercase tracking-[0.3em] text-ink transition hover:border-clsu-500/60 hover:bg-clsu-50 cursor-pointer">
            <x-booking.ui.icon name="sparkles" class="h-4 w-4 text-gold" />
            Explore the full gallery
        </button>
    </div>
</section>
