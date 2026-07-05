@props(['quote', 'name', 'role', 'initials', 'rating' => 5])

{{-- Editorial testimonial figure: gold stars, Lora italic quote, avatar sign-off --}}
<figure {{ $attributes->merge(['class' => 'rounded-3xl bg-cream p-10 shadow-boutique-card md:p-14']) }}>
    <div class="flex gap-1 text-gold">
        @for ($i = 0; $i < $rating; $i++)
            <x-booking.ui.icon name="star" class="h-4 w-4 fill-gold text-gold" />
        @endfor
    </div>
    <x-booking.ui.icon name="quote" class="mt-8 h-6 w-6 text-gold" />
    <blockquote class="mt-4 font-display text-2xl italic leading-relaxed text-ink md:text-3xl">&ldquo;{{ $quote }}&rdquo;</blockquote>
    <figcaption class="mt-8 flex items-center gap-4 border-t border-emerald-deep/10 pt-6">
        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-emerald-deep font-display text-sm text-cream">{{ $initials }}</div>
        <div>
            <p class="font-semibold text-ink">{{ $name }}</p>
            <p class="text-xs uppercase tracking-[0.2em] text-ink/50">{{ $role }}</p>
        </div>
    </figcaption>
</figure>
