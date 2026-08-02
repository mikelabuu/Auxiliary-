@props(['quote', 'name', 'role', 'initials', 'rating' => 5])

{{-- Editorial testimonial: gold stars, display-face italic quote, sign-off. Sized
     for the glass card deck (see testimonials partial) — quote scaled to
     hold four lines without the deck's cards diverging in height. --}}
<figure {{ $attributes->merge(['class' => 'flex h-full flex-col px-1 py-7 md:px-2 md:py-8']) }}>
    <div class="flex gap-1 text-gold">
        @for ($i = 0; $i < $rating; $i++)
            <x-booking.ui.icon name="star" class="h-4 w-4 fill-gold text-gold" />
        @endfor
    </div>
    <blockquote class="text-pretty mt-6 mb-6 pb-1 font-display text-xl italic leading-normal text-ink md:text-2xl">&ldquo;{{ $quote }}&rdquo;</blockquote>
    <figcaption class="mt-auto flex items-center gap-4 border-t border-ink/10 pt-6">
        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gold/15 font-display text-sm italic text-gold ring-1 ring-gold/40">{{ $initials }}</div>
        <div>
            <p class="font-semibold text-ink">{{ $name }}</p>
            <p class="mt-0.5 text-xs uppercase tracking-[0.2em] text-ink/50">{{ $role }}</p>
        </div>
    </figcaption>
</figure>
