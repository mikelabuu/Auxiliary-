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

<div {{ $attributes->merge(['class' => 'bg-slate-800/50 backdrop-blur-md p-8 rounded-3xl border border-slate-700/50 h-full flex flex-col']) }}>
    <div class="flex gap-1 text-accent mb-6">
        @for ($i = 0; $i < $fullStars; $i++)
            <span class="material-icons text-[18px]">star</span>
        @endfor
        @if ($hasHalfStar)
            <span class="material-icons text-[18px]">star_half</span>
        @endif
        @for ($i = 0; $i < $emptyStars; $i++)
            <span class="material-icons text-[18px]">star_outline</span>
        @endfor
    </div>

    <p class="text-slate-300 font-medium leading-relaxed mb-8 flex-1">&quot;{{ $quote }}&quot;</p>

    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-slate-700 flex items-center justify-center font-bold text-lg text-white">{{ $initials }}</div>
        <div>
            <h4 class="font-bold text-white">{{ $name }}</h4>
            <p class="text-xs text-slate-400">{{ $role }}</p>
        </div>
    </div>
</div>
