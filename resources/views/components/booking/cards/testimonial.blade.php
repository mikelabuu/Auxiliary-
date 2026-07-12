@props(['quote', 'name', 'role', 'initials', 'rating' => 5])

{{-- Editorial testimonial: gold stars, large Lora italic quote, sign-off.
     No card box; the quote breathes on the open canvas. --}}
<figure {{ $attributes->merge(['class' => 'px-1 py-8 md:px-4 md:py-10']) }}>
    <div class="flex gap-1 text-gold">
        @for ($i = 0; $i < $rating; $i++)
            <x-booking.ui.icon name="star" class="h-4 w-4 fill-gold text-gold" />
        @endfor
    </div>
    <blockquote class="text-pretty mt-8 pb-1 font-display text-2xl italic leading-[1.45] text-ink md:text-3xl">&ldquo;{{ $quote }}&rdquo;</blockquote>
    <figcaption class="mt-9 flex items-center gap-4 border-t border-ink/10 pt-7">
        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gold/15 font-display text-sm italic text-gold ring-1 ring-gold/40">{{ $initials }}</div>
        <div>
            <p class="font-semibold text-ink">{{ $name }}</p>
            <p class="mt-0.5 text-xs uppercase tracking-[0.2em] text-ink/50">{{ $role }}</p>
        </div>
    </figcaption>
</figure>
