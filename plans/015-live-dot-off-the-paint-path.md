# 015 — Move the live-dot pulse off the paint path and gate it

- **Status**: TODO
- **Commit**: `770cf6a`
- **Severity**: MEDIUM
- **Category**: Performance + Accessibility
- **Estimated scope**: 1 file, ~20 lines

## Problem

The "Auto-refresh · 15s" indicator on Booking Operations runs an **infinite**
animation on `box-shadow`:

```css
/* resources/css/admin/07-ops-log-table.css:90-98 — current */
.ops-aside-foot .live-dot {
    width: 6px; height: 6px; border-radius: 50%; background: var(--color-g-300);
    box-shadow: 0 0 0 0 rgba(115,226,163,.6); animation: livePulse 2s ease-out infinite;
}
@keyframes livePulse {
    0% { box-shadow: 0 0 0 0 rgba(115,226,163,.55); }
    70% { box-shadow: 0 0 0 7px rgba(115,226,163,0); }
    100% { box-shadow: 0 0 0 0 rgba(115,226,163,0); }
}
```

Two problems, both real:

1. **`box-shadow` is a paint property.** Animating it cannot be promoted to the
   compositor, so this repaints a region of the page every frame, forever. It is
   rendered on `resources/views/staff/bookings/index.blade.php:28` — Booking
   Operations, a page front-desk staff leave open all day beside a 15s poll.
   Only `transform` and `opacity` may be animated.
2. **No `prefers-reduced-motion` gate.** `07-ops-log-table.css` contains no
   `prefers-reduced-motion` block at all. An indefinite pulsing element is
   precisely what that preference exists to suppress.

## Target

Draw the ring as a pseudo-element and animate `transform` + `opacity`, which the
compositor can own. The dot itself stays exactly as it looks now.

```css
/* target — replaces resources/css/admin/07-ops-log-table.css:90-98 */
.ops-aside-foot .live-dot {
    position: relative;
    width: 6px; height: 6px; border-radius: 50%; background: var(--color-g-300);
}

/* The expanding ring. A pseudo-element scaling from the dot's own size means
   the growth is a transform, not a repainted shadow spread. */
.ops-aside-foot .live-dot::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: rgba(115, 226, 163, .55);
    animation: livePulse 2s var(--ease-out) infinite;
    pointer-events: none;
}

@keyframes livePulse {
    0%   { transform: scale(1);   opacity: .55; }
    70%  { transform: scale(3.3); opacity: 0; }
    100% { transform: scale(3.3); opacity: 0; }
}

/* An indefinite pulse is the clearest case for this preference. The dot itself
   still shows, so the "live" state is never lost — only the motion is. */
@media (prefers-reduced-motion: reduce) {
    .ops-aside-foot .live-dot::after { animation: none; opacity: 0; }
}
```

`scale(3.3)` reproduces the current visual: the old ring reached a 7px spread on
a 6px dot, i.e. 6 + 7 + 7 = 20px across, and 20 / 6 ≈ 3.3.

## Repo conventions to follow

- Easing tokens: use `var(--ease-out)` — defined at
  `resources/css/admin/01-tokens.css:272` as `cubic-bezier(.22, 1, .36, 1)`.
  Do not hand-type a curve.
- **Exemplar to imitate** — `resources/css/admin/23-spinkit.css` gates every
  infinite animation behind `@media (prefers-reduced-motion: reduce)` and keeps
  a static visual so the element still reads as a loader. Follow that shape:
  suppress the motion, keep the meaning.
- Pulsing/blinking status indicators elsewhere in this bundle keep the element
  visible and animate only the decoration around it.

## Steps

1. Open `resources/css/admin/07-ops-log-table.css`.
2. Replace the `.ops-aside-foot .live-dot` rule (line 90-93) with the two rules
   in the Target section: the dot (now `position: relative`, no `box-shadow`,
   no `animation`) and the new `::after` ring.
3. Replace the `@keyframes livePulse` block (lines 94-98) with the
   transform/opacity version in the Target section.
4. Append the `@media (prefers-reduced-motion: reduce)` block from the Target
   section directly after the keyframes.

## Boundaries

- Do NOT change the dot's size, colour, or its position in
  `resources/views/staff/bookings/index.blade.php` — this is a CSS-only change.
- Do NOT touch any other rule in `07-ops-log-table.css`; the `.ref-cell`,
  `.cell-name`, `.data-table` and sticky-actions rules in this file are
  unrelated and load-bearing.
- Do NOT add a global `prefers-reduced-motion` block covering other selectors.
- Do NOT add dependencies.
- If the code at lines 90-98 does not match the excerpt above, STOP and report.

## Verification

- **Mechanical**: `npm run build:only` — expect a clean build and a new
  `public/build/assets/admin-<hash>.css`. Then `php artisan view:clear`.
- **Feel check**: open `/bookings` (Booking Operations) in the admin console and
  look at the "Auto-refresh · 15s" line in the header aside.
  - The green dot must still emit a ring that expands and fades every 2s, and it
    must look the same as before this change.
  - Open DevTools → **Rendering** → enable **Paint flashing**. The pulsing dot
    must no longer flash a repaint rectangle every frame. Before this change it
    repaints continuously.
  - DevTools → **Rendering** → **Emulate CSS prefers-reduced-motion: reduce**,
    then reload. The ring must be gone entirely while the green dot itself stays
    visible — the "live" meaning survives, the motion does not.
  - In the Animations panel at 10% speed, the ring should grow smoothly outward
    and fade, with no size jump at the 70% mark.
- **Done when**: paint flashing shows no recurring repaint at the dot, and
  reduced-motion removes the ring but not the dot.
