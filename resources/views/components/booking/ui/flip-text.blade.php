{{-- Staggered 3D character-flip text (ported from vengence-ui flip-text) --}}
@props([
    'text' => '',
    'duration' => 2.2,   // seconds per flip cycle
    'delay' => 0,        // initial delay in seconds
    'loop' => true,
    'separator' => ' ',
    'together' => false, // true = all characters flip at once (no stagger)
])
@php
    $words = explode($separator, $text);
    $totalChars = max(mb_strlen($text), 1);
    $sepLength = $separator === ' ' ? 1 : mb_strlen($separator);
    $globalIndex = 0;
@endphp
<span {{ $attributes->merge(['class' => 'flip-text-wrapper inline-block']) }}>@foreach ($words as $wordIndex => $word)<span class="inline-block whitespace-nowrap" style="transform-style: preserve-3d">@foreach (mb_str_split($word) as $char)@php
    $charDelay = $together
        ? $delay
        : sin(($globalIndex / $totalChars) * (M_PI / 2)) * ($duration * 0.25) + $delay;
    $globalIndex++;
@endphp<span class="flip-char relative inline-block" style="--flip-duration: {{ $duration }}s; --flip-delay: {{ round($charDelay, 3) }}s; --flip-iteration: {{ $loop ? 'infinite' : '1' }}">{{ $char }}</span>@endforeach @php $globalIndex += $sepLength; @endphp @if ($wordIndex < count($words) - 1)<span class="inline-block">{{ $separator === ' ' ? "\u{00A0}" : $separator }}</span>@endif
</span>@endforeach</span>
