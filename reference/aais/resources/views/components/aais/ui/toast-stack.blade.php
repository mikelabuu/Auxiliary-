@props([
    'items' => 'notifications',
])

<div style="position:fixed;bottom:24px;right:24px;z-index:999;display:flex;flex-direction:column;gap:10px;">
    <template x-for="n in {{ $items }}" :key="n.id">
        <div x-show="true" x-transition.duration.300ms style="background:var(--color-card);border:1px solid var(--color-border);padding:14px 20px;border-radius:var(--radius-sm);box-shadow:var(--shadow-lg);display:flex;align-items:center;gap:12px;min-width:260px;">
            <div :class="{'text-g-500':n.type==='success','text-red-500':n.type==='error'}" style="flex-shrink:0;">
                <svg x-show="n.type==='success'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:20px;height:20px;"><path d="M5 13l4 4L19 7"/></svg>
                <svg x-show="n.type==='error'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:20px;height:20px;color:#dc2626;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <p style="font-size:13px;font-weight:700;color:var(--color-ink);" x-text="n.title"></p>
                <p style="font-size:11px;color:var(--color-muted);margin-top:2px;" x-text="n.message"></p>
            </div>
        </div>
    </template>
</div>
