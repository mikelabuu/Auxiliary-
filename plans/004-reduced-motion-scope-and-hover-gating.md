# 004 — Scope the reduced-motion nuke; gate custom hover motion for touch

- **Status**: DONE (applied 2026-07-17)
- **Commit**: fed2f3d
- **Severity**: MEDIUM
- **Category**: Accessibility
- **Estimated scope**: 1 file (resources/css/app.css), ~40 lines

## Problem

1. **Universal transition nuke.** One rule flattens every transition on the site to 0.01ms under reduced motion:

```css
/* resources/css/app.css:832-842 — current */
@media (prefers-reduced-motion: reduce) {

    .animate-in,
    .animate-pop {
        animation: none;
    }

    * {
        transition-duration: 0.01ms !important;
    }
}
```

Reduced motion means *gentler, not zero*: opacity/color transitions that aid comprehension should survive; only movement should go. This `*` rule kills the Alpine dropdown/modal fades, focus-state color eases, the nav skin crossfade — everything — and it contradicts the file's own carefully scoped reduced-motion rules elsewhere (e.g. app.css:1052-1058 keeps `[data-aos]` content visible rather than nuking it).

2. **Ungated custom `:hover` motion.** Tailwind v4 gates its `hover:` variant behind `(hover: hover)`, but the hand-written hover rules in app.css are ungated, so taps on touch devices trigger sticky lift/shine states:

```css
/* resources/css/app.css:703-710 — current */
.hover-lift-premium { transition: transform 0.42s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.42s cubic-bezier(0.22, 1, 0.36, 1); }
.hover-lift-premium:hover { transform: translateY(-4px); box-shadow: ...; }

/* resources/css/app.css:1861-1863 — current */
.group:hover .card-shine::before { transform: translateX(320%) skewX(-16deg); }

/* also: .gold-underline:hover::after (688), .room-tile:hover (1105),
   body.theme-night .hover-lift-premium:hover (337),
   body.theme-boutique/.theme-night .room-tile*:hover (1116, 1233, 585, 596) */
```

## Target

1. Replace the `*` nuke with movement-only suppression — keep opacity/color eases, drop transform-driven ones:

```css
/* target — replaces app.css:832-842 */
@media (prefers-reduced-motion: reduce) {

    .animate-in,
    .animate-pop,
    .room-tile,
    .capsule-nights {
        animation: none;
    }

    /* Drop movement; opacity/color transitions elsewhere stay intact */
    .press,
    .gold-underline::after,
    .type-card,
    .room-tile,
    .hover-lift-premium {
        transition: none;
    }

    .press:active,
    .type-card.selected,
    body.theme-night .type-card.selected,
    .room-tile.selected,
    .gold-underline:hover::after {
        transform: none;
    }
}
```

2. Wrap the pure-decoration hover-motion rules in the hover gate (state changes like `.active`, `.selected`, and focus rules stay ungated):

```css
/* target — pattern for each listed hover rule */
@media (hover: hover) and (pointer: fine) {
    .hover-lift-premium:hover {
        transform: translateY(-4px);
        box-shadow: 0 30px 60px -30px oklch(15% 0.05 160 / 0.45), 0 12px 28px -14px oklch(15% 0.05 160 / 0.25);
    }
}
```

Apply the same wrapper to: `.gold-underline:hover::after` + `.gold-underline.active::after` (keep `.active` OUTSIDE the media query — split the selector), `.group:hover .card-shine::before`, `body.theme-night .hover-lift-premium:hover`. Leave `.room-tile:hover` background/border color changes ungated (color-only feedback is fine on tap) — only gate rules that move or transform.

## Repo conventions to follow

- Scoped reduced-motion blocks already exist throughout app.css (exemplars: lines 459-464 `.reveal-line`, 783-798 hairline/scroll-cue/ken-burns, 1404-1420 pills/success). Match their placement style: the override sits directly after the rules it modifies where practical, or in the consolidated block at 832.
- Keep the existing `@media (prefers-reduced-motion: reduce)` blocks untouched — this plan only replaces the `*` rule's job with explicit coverage.

## Steps

1. In `resources/css/app.css:832-842`, replace the block with the target reduced-motion block above.
2. Wrap `.hover-lift-premium:hover` (app.css:707-710) in `@media (hover: hover) and (pointer: fine)`.
3. Wrap `body.theme-night .hover-lift-premium:hover` (app.css:337-339) the same way.
4. Split `.gold-underline:hover::after, .gold-underline.active::after` (app.css:686-690): the `:hover` selector goes inside the hover gate; the `.active` selector stays as-is outside it.
5. Wrap `.group:hover .card-shine::before` (app.css:1861-1863) in the hover gate.
6. Run `npm run build`.
7. Sanity-grep app.css for remaining ungated `:hover` rules that set `transform` — there should be none outside `(hover: hover)` gates.

## Boundaries

- Do NOT gate color/background-only hover rules (`.room-tile:hover`, nav link colors, flatpickr day hovers) — feedback color on tap is desirable.
- Do NOT touch the per-component reduced-motion blocks that already exist.
- Do NOT change any animation/transition values — only media-query scoping and the block replacement in step 1.
- If line numbers have drifted from fed2f3d, locate the rules by selector and proceed; if a selector is missing entirely, STOP and report.

## Verification

- **Mechanical**: `npm run build` succeeds; `Grep 'transition-duration: 0.01ms' resources/css/app.css` returns nothing.
- **Feel check**:
  - DevTools → Rendering → `prefers-reduced-motion: reduce`: the account dropdown still *fades* in/out (opacity survives); room cards no longer lift on hover; pressing buttons gives no scale but color feedback still eases.
  - DevTools device emulation (touch): tapping a room card image does not leave it stuck lifted with a half-swept shine.
  - Normal desktop: hover lift, gold underline, and card shine all behave exactly as before.
- **Done when**: reduced-motion keeps comprehension fades, drops movement; no transform-based hover fires on touch.
