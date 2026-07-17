# 005 — Room-card gold corner hairlines: animate transforms, not width/height

- **Status**: DONE (applied 2026-07-17)
- **Commit**: fed2f3d
- **Severity**: MEDIUM
- **Category**: Performance
- **Estimated scope**: 1 file (room card blade), 2 lines

## Problem

The gold corner hairlines that draw in on room-card hover animate `width` and `height` — layout properties that force layout + paint + composite every frame, on a hover that fires constantly while browsing the grid:

```html
<!-- resources/views/components/booking/cards/room.blade.php:32-35 — current -->
<span aria-hidden="true" class="pointer-events-none absolute top-3 right-3 h-6 w-6">
    <span class="absolute top-0 right-0 h-px w-0 bg-gold transition-[width] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:w-full"></span>
    <span class="absolute top-0 right-0 h-0 w-px bg-gold transition-[height] duration-500 delay-100 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:h-full"></span>
</span>
```

## Target

Same visual (horizontal line grows leftward from the corner, vertical line grows downward, 100ms apart) using GPU-only `scaleX`/`scaleY` at full size with the right `transform-origin`:

```html
<!-- target -->
<span aria-hidden="true" class="pointer-events-none absolute top-3 right-3 h-6 w-6">
    <span class="absolute top-0 right-0 h-px w-full origin-right scale-x-0 bg-gold transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-x-100"></span>
    <span class="absolute top-0 right-0 h-full w-px origin-top scale-y-0 bg-gold transition-transform duration-500 delay-100 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-y-100"></span>
</span>
```

(`origin-right` makes the horizontal hairline grow from the right edge leftward, matching the current `w-0 → w-full` anchored at `right-0`; `origin-top` matches the downward growth. Tailwind v4's `group-hover:` is already gated for touch devices.)

## Repo conventions to follow

- The scaleX-draw pattern already exists in this repo: `.gold-underline::after` (resources/css/app.css:673-690) and `.hairline-gold` (app.css:743-749) both draw 1px gold lines with `transform: scaleX()` + `transform-origin`. This plan brings the room card in line with them.
- Keep the arbitrary-value easing class `ease-[cubic-bezier(0.22,1,0.36,1)]` exactly as written — it's the house curve.

## Steps

1. Edit `resources/views/components/booking/cards/room.blade.php:33` — replace the horizontal hairline's class list with the target version (line 1 of the target block's inner spans).
2. Edit line 34 — replace the vertical hairline's class list with the target version.
3. Run `npm run build` (new utilities: `origin-right`, `scale-x-0`, `scale-y-0`, `transition-transform`, `group-hover:scale-x-100`, `group-hover:scale-y-100`).

## Boundaries

- Do NOT touch anything else in the room card — image zoom, shine, badges, CTAs are out of scope.
- Do NOT change durations, delay, or the easing curve.
- If the two lines don't match the excerpt (drift since fed2f3d), STOP and report.

## Verification

- **Mechanical**: `npm run build` succeeds.
- **Feel check**: hover a room card — the two gold hairlines draw in exactly as before (horizontal first, vertical 100ms later, drawing away from the corner). In DevTools Performance panel, hovering cards shows no purple Layout blocks attributable to the hairlines.
- **Done when**: visual is indistinguishable from before and the animation runs on transforms only.
