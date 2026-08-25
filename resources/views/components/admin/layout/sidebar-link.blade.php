@props([
    'href',
    'active' => false,
    'badge' => null,
    /*
     * Which queue this link's chip counts — 'bookings', 'reschedules',
     * 'proofs' or 'discounts', matching a key of StaffAlerts::pendingCounts().
     * Given a key, the chip is rendered even at zero (hidden) so the console's
     * poll has an element to write into; see resources/js/sidebar-counts.js.
     * Links with no queue behind them leave it null and behave as before.
     */
    'badgeKey' => null,
])

@php
    $count = is_numeric($badge) ? (int) $badge : $badge;
    $showChip = $badgeKey !== null ? true : ! is_null($badge);
    $chipEmpty = $badgeKey !== null && (! is_numeric($count) || $count < 1);
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'sidebar-link !no-underline' . ($active ? ' active' : '')]) }}>
    {{ $icon ?? '' }}

    <span class="truncate flex-1">{{ $slot }}</span>

    @if ($showChip)
        {{-- aria-live so a badge that changes under a stationary cursor is
             announced rather than silently updated. 'polite': a queue growing
             by one is not worth interrupting whatever is being read. --}}
        <span class="sidebar-link-chip"
              @if($badgeKey) data-sidebar-count="{{ $badgeKey }}" aria-live="polite" @endif
              @if($chipEmpty) hidden @endif>{{ $chipEmpty ? '' : $count }}</span>
    @endif
</a>
