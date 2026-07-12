@props([
    'text',
    'time',
    'status',
    'statusLabel' => null,
    'last' => false,
])

<div style="display:flex;align-items:flex-start;gap:12px;padding:12px 0;{{ !$last ? 'border-bottom:1px solid var(--color-border);' : '' }}">
    <x-aais.ui.status-badge :status="$status" :label="$statusLabel" size="sm" style="flex-shrink:0;margin-top:2px;" />

    <div style="flex:1;min-width:0;">
        <p style="font-size:13px;font-weight:600;color:var(--color-ink);line-height:1.4;">{{ $text }}</p>
        <p style="font-size:11px;color:var(--color-faint);margin-top:2px;">{{ $time }}</p>
    </div>
</div>
