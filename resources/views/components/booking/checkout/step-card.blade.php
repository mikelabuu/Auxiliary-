{{-- Checkout step card.

     The header is a gold hairline, an editorial title and one line saying what
     the step is for — the shape the checkout reference uses, and the shape the
     rest of this journey (discount, payment) already reads in.

     What it dropped: the icon chip and the "Step 2 of 3" eyebrow. Both were
     saying what the progress rail above the card says, and the rail says it
     for all three steps at once. Inside a wizard that shows one card at a
     time, a card repeating its own number is the least useful line on screen.

     `aside` is still here for the badge the Stay card puts beside its title
     (the nights count). --}}
@props(['title', 'lead' => null])
<div {{ $attributes->merge(['class' => 'co-card']) }}>
    <div class="co-card-head">
        <span class="co-card-rule" aria-hidden="true"></span>
        <div class="co-card-headline">
            <h2 class="co-card-title">{{ $title }}</h2>
            @isset($aside)<div class="co-card-aside">{{ $aside }}</div>@endisset
        </div>
        @if ($lead)
            <p class="co-card-lead">{{ $lead }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
