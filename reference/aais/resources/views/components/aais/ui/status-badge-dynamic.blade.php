@props([
    'classExpr',
    'textExpr',
    'style' => null,
])

<span class="status" :class="{{ $classExpr }}" x-text="{{ $textExpr }}" @if($style) style="{{ $style }}" @endif></span>
