@props([
    'number',
    'title',
    'description',
    'bg',
    'borderColor',
    'titleColor',
    'showRightBorder' => false,
])

<div class="workflow-step" style="background:{{ $bg }};border-right:{{ $showRightBorder ? '1px solid ' . $borderColor : 'none' }};border-top-color:{{ $borderColor }};">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <span class="workflow-num" style="background:{{ $titleColor }};">{{ $number }}</span>
        <h3 style="font-size:14px;font-weight:800;color:{{ $titleColor }};">{{ $title }}</h3>
    </div>
    <p style="font-size:12px;color:var(--color-muted);line-height:1.7;">{{ $description }}</p>
</div>
