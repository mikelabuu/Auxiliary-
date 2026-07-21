# 013 — Polish: mask the density-cycle reflow; soft empty-state entrance

- **Status**: DONE (applied 2026-07-21)
- **Commit**: ac773f6
- **Severity**: LOW
- **Category**: Missed opportunities / Cohesion
- **Estimated scope**: 3 files (~20 lines): 05-motion-ux.css, layouts/admin.blade.php, one keyframe

## Problem

Two additive polish items from the audit:

1. **Density cycle jolt.** The topbar density button cycles three table sizes
   (compact → comfortable → large) by swapping a body class; every `.data-table` reflows
   in one frame — a visible jolt. Animating padding/font would violate the
   transform/opacity-only performance rule, so the correct bridge is a brief opacity dip
   that masks the reflow. Current cycle handler:

```js
// resources/views/layouts/admin.blade.php:29-32 — current (Alpine, on <body>)
cycleDensity() {
  const order = ['compact', 'normal', 'large'];
  this.density = order[(order.indexOf(this.density) + 1) % order.length];
  localStorage.setItem('adminDensity', this.density);
}
```

2. **Static empty states.** Rare-view empty states render with no entrance while every
   neighboring surface animates in — a small cohesion gap at a frequency tier that
   permits motion. Current styling (icon chip only, no motion):

```css
/* resources/css/admin/05-motion-ux.css:137-144 — current */
.empty-state svg { width: 74px; height: 74px; padding: 19px; … }
```

## Target

1. Density switch: table dips to 35% opacity and back over ~150ms while the reflow happens
   under it:

```css
/* target — 05-motion-ux.css, next to the density rules (~line 106) */
/* Density switches reflow the table in one frame; a brief opacity dip masks the jolt.
   (Padding/font-size must NOT be transitioned — layout properties stay snap.) */
.data-table { transition: opacity 150ms var(--ease-out); }
body.density-switching .data-table { opacity: .35; }
```

```js
// target — cycleDensity() gains the masking class
cycleDensity() {
  const order = ['compact', 'normal', 'large'];
  this.density = order[(order.indexOf(this.density) + 1) % order.length];
  localStorage.setItem('adminDensity', this.density);
  document.body.classList.add('density-switching');
  setTimeout(() => document.body.classList.remove('density-switching'), 160);
}
```

2. Empty states rise in once:

```css
/* target — 05-motion-ux.css, in the empty-state section */
@media (prefers-reduced-motion: no-preference) {
    .empty-state { animation: fadeInUp 250ms var(--ease-out) backwards; }
}
```

(`fadeInUp` already exists at resources/css/admin/04-components.css:696-699 —
`from { opacity: 0; transform: translateY(12px); }`. Note: `backwards`, NOT `both`/
`forwards` — a filled entrance creates a permanent stacking context; see the warning
comment at resources/views/layouts/admin.blade.php:101-105.)

## Repo conventions to follow

- `--ease-out` from `01-tokens.css`; `fadeInUp` from `04-components.css` — reuse, don't redefine.
- The `animationend` cleanup in layouts/admin.blade.php:106-112 clears `fadeInUp` when it
  finishes — the `.empty-state` entrance composes with it automatically.

## Steps

1. **resources/css/admin/05-motion-ux.css** — add the two CSS rules from Target 1 directly
   under the density block (after line ~115), and the Target 2 rule inside the
   "Richer empty states" section (~line 137).
2. **resources/views/layouts/admin.blade.php:29-32** — extend `cycleDensity()` per Target 1.
3. Rebuild: `npm run build`, then `php artisan view:clear && php artisan view:cache`.

## Boundaries

- Do NOT transition padding, font-size, or any layout property on `.data-table`.
- Do NOT stagger empty states or animate their inner elements separately — one soft rise.
- Do NOT persist `density-switching` anywhere; it's a 160ms transient.

## Verification

- **Mechanical**: `npm run build` + `php artisan view:cache` succeed.
- **Feel check**: admin console —
  - Click the density button through all three levels: the table softens for a beat and
    settles at the new size; no hard jolt. Spam the button: never gets stuck dim
    (the transition retargets; the timeout always lifts the class).
  - Visit a page with an empty table/list: the empty state rises in gently, once.
  - `prefers-reduced-motion: reduce`: empty state appears instantly; density dip still
    occurs (opacity-only feedback is permitted) — acceptable as-is.
- **Done when**: density changes read as a soft settle, and empty states enter like their
  neighboring cards.
