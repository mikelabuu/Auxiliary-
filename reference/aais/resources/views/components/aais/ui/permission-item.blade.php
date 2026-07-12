@props([
    'icon',
    'text',
    'ok' => null,
])

@php
    $iconClass = $ok === true ? 'perm-ok' : ($ok === false ? 'perm-no' : '');
    $iconStyle = $ok === null ? 'color:var(--color-au-700);' : null;
@endphp

<div class="perm-item">
    <span class="perm-icon {{ $iconClass }}" @if($iconStyle) style="{{ $iconStyle }}" @endif>{{ $icon }}</span>
    <span class="perm-text">{{ $text }}</span>
</div>
