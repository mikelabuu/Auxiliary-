@props([
    'position' => 'bottom',   // top | bottom | left | right — the edge that dissolves
    'height' => '6rem',        // fade thickness for top/bottom
    'width' => '6rem',         // fade thickness for left/right
    'strength' => 2,           // blur multiplier
    'divCount' => 6,           // number of ramp layers
    'opacity' => 1,
    'exponential' => false,    // exponential vs linear blur ramp
    'mode' => 'absolute',      // absolute (pin to a relative parent) | fixed (viewport) | sticky (scroll edge)
    'z' => 20,
])

{{--
    GradualBlur — reactbits.dev/animations/gradual-blur, ported to a static Blade
    component. Stacks `divCount` absolutely-positioned layers, each with a
    progressively stronger backdrop-filter blur clipped by a stepped
    linear-gradient mask, so a container's edge dissolves sharp -> blurred. No
    animation (purely visual), so no reduced-motion concern. Decorative -> aria-hidden
    + pointer-events:none so it never intercepts clicks or reaches AT.

    Usage (attributes only — angle brackets kept out of this comment so Blade
    does not compile them and recurse):
      admin.ui.gradual-blur                                 bottom fade on a relative parent
      admin.ui.gradual-blur mode=sticky class=gb-tint-card  scroll-edge fade
      admin.ui.gradual-blur mode=fixed  class=gb-page        viewport-pinned page fade
--}}

@php
    $position = in_array($position, ['top', 'bottom', 'left', 'right'], true) ? $position : 'bottom';
    $divCount = max(1, (int) $divCount);
    $horizontalEdge = in_array($position, ['left', 'right'], true);
    $direction = ['top' => 'to top', 'bottom' => 'to bottom', 'left' => 'to left', 'right' => 'to right'][$position];

    // ReactBits ramp: layer i reveals a [p1..p2..p3..p4] band and carries a blur
    // that grows with i, so the stack blends into a smooth gradient of blur.
    $increment = 100 / $divCount;
    $layers = [];
    for ($i = 1; $i <= $divCount; $i++) {
        $progress = $i / $divCount;
        $blur = $exponential
            ? pow(2, $progress * 4) * 0.0625 * $strength
            : 0.0625 * ($progress * $divCount + 1) * $strength;

        $p1 = $increment * $i - $increment;
        $p2 = $increment * $i;
        $p3 = $increment * $i + $increment;
        $p4 = $increment * $i + $increment * 2;

        $fmt = fn ($v) => number_format($v, 1, '.', '');
        $stops = "rgba(255,255,255,0) {$fmt($p1)}%, rgba(255,255,255,1) {$fmt($p2)}%";
        if ($p3 <= 100) { $stops .= ", rgba(255,255,255,1) {$fmt($p3)}%"; }
        if ($p4 <= 100) { $stops .= ", rgba(255,255,255,0) {$fmt($p4)}%"; }

        $layers[] = [
            'grad' => "linear-gradient({$direction}, {$stops})",
            'blur' => number_format($blur, 3, '.', ''),
        ];
    }

    // Wrapper anchoring. sticky = a flow element pulled up over the content it
    // fades (for scroll containers); fixed = viewport; absolute = relative parent.
    $pos = ['fixed' => 'position:fixed;', 'sticky' => 'position:sticky;'][$mode] ?? 'position:absolute;';
    $edge = match ($position) {
        'top'    => "left:0;right:0;top:0;height:{$height};",
        'left'   => "top:0;bottom:0;left:0;width:{$width};",
        'right'  => "top:0;bottom:0;right:0;width:{$width};",
        default  => "left:0;right:0;bottom:0;height:{$height};",
    };
    $wrapStyle = $pos . $edge . 'z-index:' . (int) $z . ';pointer-events:none;';
    if ($mode === 'sticky') {
        $wrapStyle .= 'width:100%;';
        $wrapStyle .= $position === 'top' ? "margin-bottom:-{$height};" : "margin-top:-{$height};";
    }
@endphp

<div aria-hidden="true" {{ $attributes->merge(['class' => 'gradual-blur gradual-blur--' . $position]) }} style="{{ $wrapStyle }}">
    @foreach ($layers as $l)
        <div class="gradual-blur-layer" style="-webkit-mask-image:{{ $l['grad'] }};mask-image:{{ $l['grad'] }};-webkit-backdrop-filter:blur({{ $l['blur'] }}rem);backdrop-filter:blur({{ $l['blur'] }}rem);opacity:{{ $opacity }};"></div>
    @endforeach
</div>
