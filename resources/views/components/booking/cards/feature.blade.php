@props(['icon' => null, 'title', 'description'])

{{-- Typographic feature column: hairline top, display-face title, muted body.
     No card box; the hairline and the whitespace do the grouping.
     ($icon is accepted for backwards compatibility but not rendered.) --}}
<div {{ $attributes->merge(['class' => 'border-t border-ink/15 pt-7']) }}>
    <h3 class="font-display text-2xl leading-snug text-ink">{{ $title }}</h3>
    <p class="text-pretty mt-3 text-sm leading-relaxed text-ink/60">{{ $description }}</p>
</div>
