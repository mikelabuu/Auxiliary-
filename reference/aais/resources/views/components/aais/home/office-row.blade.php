@props([
    'name',
    'location',
    'hours',
    'open' => true,
    'last' => false,
])

<div style="display:flex;align-items:flex-start;gap:12px;padding:12px 0;{{ !$last ? 'border-bottom:1px solid var(--color-border);' : '' }}">
    <div style="width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;background:{{ $open ? '#16a34a' : '#ef4444' }};box-shadow:0 0 6px {{ $open ? 'rgba(22,163,74,.3)' : 'rgba(239,68,68,.3)' }};"></div>

    <div style="flex:1;min-width:0;">
        <p style="font-size:13px;font-weight:700;color:var(--color-ink);">{{ $name }}</p>
        <p style="font-size:11px;color:var(--color-faint);margin-top:2px;">{{ $location }} · {{ $hours }}</p>
    </div>

    <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:{{ $open ? '#16a34a' : '#ef4444' }};">{{ $open ? 'Open' : 'Closed' }}</span>
</div>
