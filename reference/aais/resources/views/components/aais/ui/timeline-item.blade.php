@props([
    'title',
    'meta' => null,
    'done' => false,
    'active' => false,
    'last' => false,
    'status' => null,
    'statusLabel' => null,
])

<div class="timeline-item">
    @if (!$last)
        <div class="timeline-line {{ $done ? 'done' : '' }}"></div>
    @endif

    <div class="timeline-dot {{ $done ? 'done' : ($active ? 'active' : '') }}">
        @if ($done)
            <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        @elseif ($active)
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        @else
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
            </svg>
        @endif
    </div>

    <div class="timeline-content">
        <p class="timeline-title" style="{{ $active ? 'color:var(--color-g-900);font-weight:800;' : '' }}{{ !$done && !$active ? 'color:var(--color-faint);' : '' }}">
            {{ $title }}

            @if ($status)
                <x-aais.ui.status-badge
                    :status="$status"
                    :label="$statusLabel"
                    size="xs"
                    style="margin-left:6px;"
                />
            @endif
        </p>

        @if ($meta)
            <p class="timeline-meta">{{ $meta }}</p>
        @endif
    </div>
</div>
