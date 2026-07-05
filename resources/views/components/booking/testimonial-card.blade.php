@props([
    'quote',
    'name',
    'role',
    'initials' => null,
    'rating' => 5, // 0-5, supports .5 increments
])

@php
    $initials = $initials ?? collect(explode(' ', $name))->map(fn ($w) => strtoupper($w[0] ?? ''))->take(2)->implode('');
    $fullStars = (int) floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = max(0, 5 - $fullStars - ($hasHalfStar ? 1 : 0));
@endphp

<div {{ $attributes->merge(['class' => 'bg-white p-8 rounded-3xl border border-stone-200/70 shadow-[0_10px_36px_-18px_rgba(17,78,40,0.18)] h-full flex flex-col']) }}>
    <div class="flex gap-0.5 text-palay-400 mb-6">
        @for ($i = 0; $i < $fullStars; $i++)
            <span class="material-icons text-[19px]">star</span>
        @endfor
        @if ($hasHalfStar)
            <span class="material-icons text-[19px]">star_half</span>
        @endif
        @for ($i = 0; $i < $emptyStars; $i++)
            <span class="material-icons text-[19px] text-stone-200">star_outline</span>
        @endfor
    </div>

    <span class="material-icons text-clsu-100 text-[40px] leading-none mb-2 select-none">format_quote</span>
    <p class="text-stone-700 font-medium leading-relaxed mb-8 flex-1 text-[15px]">{{ $quote }}</p>

    <div class="flex items-center gap-4 pt-5 border-t border-stone-100">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-clsu-600 to-clsu-800 flex items-center justify-center font-bold text-lg text-white shadow-inner">{{ $initials }}</div>
        <div>
            <h4 class="font-bold text-ink">{{ $name }}</h4>
            <p class="text-xs font-semibold text-stone-400">{{ $role }}</p>
        </div>
    </div>
</div>
