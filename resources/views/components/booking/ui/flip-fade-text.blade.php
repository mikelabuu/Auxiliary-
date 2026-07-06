{{-- Per-character 3D flip-fade text (ported from vengence-ui flip-fade-text):
     each letter flips in from rotateX(90°) with blur + fade, holds, then flips
     out upward — staggered so the word washes in and out as a wave. --}}
@props([
    'text' => '',
    'duration' => 4.5,  // seconds per full in–hold–out cycle
    'stagger' => 0.07,  // seconds between characters
    'delay' => 0,       // initial delay in seconds
    'loop' => true,
])
<span {{ $attributes->merge(['class' => 'flip-fade-wrapper inline-block whitespace-nowrap']) }}><span class="sr-only">{{ $text }}</span>@foreach (mb_str_split($text) as $i => $char)<span aria-hidden="true" class="flip-fade-char" style="--ff-duration: {{ $duration }}s; --ff-delay: {{ round($delay + $i * $stagger, 3) }}s; --ff-iteration: {{ $loop ? 'infinite' : '1' }}">{{ $char === ' ' ? "\u{00A0}" : $char }}</span>@endforeach</span>
