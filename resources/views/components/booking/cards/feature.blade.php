@props(['icon', 'title', 'description'])

{{-- Landing feature card: icon chip + Lora title + muted body --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl bg-cream-warm p-8 ring-1 ring-emerald-deep/5']) }}>
    <div class="mb-6 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-deep/5 ring-1 ring-emerald-deep/10">
        <x-booking.ui.icon :name="$icon" class="h-5 w-5 text-emerald-deep" />
    </div>
    <h3 class="font-display text-2xl text-ink">{{ $title }}</h3>
    <p class="mt-3 text-sm leading-relaxed text-ink/60">{{ $description }}</p>
</div>
