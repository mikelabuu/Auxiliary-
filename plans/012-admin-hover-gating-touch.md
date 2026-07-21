# 012 — Gate admin hover transforms for touch devices

- **Status**: DONE (applied 2026-07-21)
- **Commit**: ac773f6
- **Severity**: LOW
- **Category**: Accessibility
- **Estimated scope**: 2-3 admin CSS files, ~10 lines (wrap-in-media-query edits)

## Problem

The admin bundle has **zero** `(hover: hover)` media queries, so every hand-written
`:hover` rule fires on touch taps — a tap "sticks" the hover state until the next tap.
For color-only hovers that's harmless; for **transform-bearing** hovers it reads as a
glitch. The front desk plausibly runs on tablets. Confirmed transform hovers:

```css
/* resources/css/admin/05-motion-ux.css:129-133 — current */
.back-to-top:hover {
    color: var(--color-g-700); border-color: var(--color-g-300);
    background: var(--color-g-50); transform: translateY(-2px);
}
.back-to-top:active { transform: translateY(0) scale(.94); }
```

```css
/* resources/css/admin/10-frontdesk.css:182-183 — current */
.fd-nav-link svg { width: 15px; height: 15px; flex-shrink: 0; transition: transform 340ms var(--ease-spring); }
.fd-nav-link:hover svg { transform: translateY(-1px) scale(1.16); }
```

(10-frontdesk.css:201 already zeroes the nav-icon hover under reduced motion — that gate
stays; this plan adds the pointer gate.)

## Target

Transform-bearing hover rules wrapped in the standard gate; `:active` press feedback and
color-only hovers stay ungated (press feedback must work on touch):

```css
/* target — 05-motion-ux.css */
.back-to-top:hover {
    color: var(--color-g-700); border-color: var(--color-g-300);
    background: var(--color-g-50);
}
@media (hover: hover) and (pointer: fine) {
    .back-to-top:hover { transform: translateY(-2px); }
}
```

```css
/* target — 10-frontdesk.css */
@media (hover: hover) and (pointer: fine) {
    .fd-nav-link:hover svg { transform: translateY(-1px) scale(1.16); }
}
```

## Repo conventions to follow

- The public bundle already does this exactly: `resources/css/app.css:791-796`
  (`.hover-lift-premium:hover` inside `@media (hover: hover) and (pointer: fine)`).
  Imitate that structure and comment style.
- Keep the color/background parts of a hover rule ungated — only the `transform`
  declaration moves inside the gate.

## Steps

1. **resources/css/admin/05-motion-ux.css:129-132** — remove `transform: translateY(-2px);`
   from the existing `.back-to-top:hover` rule; add the gated block directly below it
   (see Target).
2. **resources/css/admin/10-frontdesk.css:183** — wrap the `.fd-nav-link:hover svg` rule
   in the gate (see Target). Leave line 182 (the base `svg` rule) and the reduced-motion
   override at line 201 untouched.
3. Sweep for any transform hovers this audit missed:
   `rg -n -A2 ":hover" resources/css/admin | rg "transform"` — gate any additional
   transform-bearing `:hover` matches the same way (color-only hovers: leave alone).
4. Rebuild: `npm run build`.

## Boundaries

- Do NOT gate `:active` rules — press feedback is wanted on touch.
- Do NOT gate color/background/border hover changes.
- Do NOT touch public CSS (app.css) — its gating shipped in plan 004.

## Verification

- **Mechanical**: `npm run build` succeeds.
- **Feel check**: DevTools device emulation (touch, e.g. iPad) on the admin console:
  - Tap the back-to-top button: it presses (`scale(.94)`) but does not lift or stick lifted.
  - Frontdesk: tap a nav link — the icon must not stay springed at 1.16×.
  - With a mouse (no emulation): both hovers behave exactly as before.
- **Done when**: `rg -n -A2 ":hover" resources/css/admin | rg "transform"` only matches
  lines inside `(hover: hover)` blocks.
