# 002 — Repair the `.press` / Tailwind transition conflict on primary CTAs

- **Status**: DONE (applied 2026-07-17; also cleaned base.blade.php sign-in/drawer buttons, room-filters pills, discount/create.blade.php — same dead-utility pattern)
- **Commit**: fed2f3d
- **Severity**: HIGH
- **Category**: Easing & duration / Cohesion
- **Estimated scope**: 1 CSS file + 5 blade files, ~15 small edits

## Problem

`.press` is the shared press-feedback utility:

```css
/* resources/css/app.css:712-718 — current */
.press {
    transition: transform 0.15s cubic-bezier(0.22, 1, 0.36, 1);
}

.press:active {
    transform: scale(0.97);
}
```

It is **unlayered** author CSS, while Tailwind v4 utilities live in `@layer utilities` — so `.press`'s `transition` shorthand always beats utility classes like `transition-all duration-500` on the same element, resetting `transition-property` to `transform` only. Every primary CTA that combines both gets **instant, un-animated** hover color/shadow changes — the designed 500ms glow never plays:

- `resources/views/welcome.blade.php:166` — hero "Search rooms" button: `press cta-shine ... transition-all duration-500 ... hover:bg-cream hover:shadow-[...]`
- `resources/views/welcome.blade.php:409` — "Reserve your stay" CTA: `press ... transition-all duration-500`
- `resources/views/welcome.blade.php:600-601` — room-modal footer "Close" (`transition-all`) and "Book this room" (`transition-all duration-500`)
- `resources/views/components/booking/cards/room.blade.php:93` — every room card's Book button: `press ... transition-all duration-500`
- `resources/views/checkout.blade.php:149` — "Confirm Booking" submit: `press ... transition-all duration-500`

Two problems at once: the dead utilities are misleading (`transition: all` + 500ms would also break the sub-300ms UI budget if it ever *did* apply), and the actual rendered behavior is a snapping hover on the site's most important buttons.

## Target

Fix it in one place — teach `.press` to also transition the hover-affected properties at in-budget durations — then delete the dead utilities from the CTAs.

```css
/* target — resources/css/app.css:712 */
.press {
    transition:
        transform 0.15s cubic-bezier(0.22, 1, 0.36, 1),
        background-color 0.2s ease,
        border-color 0.2s ease,
        color 0.2s ease,
        box-shadow 0.3s cubic-bezier(0.22, 1, 0.36, 1);
}
```

In the five blade locations above, remove `transition-all`, `transition-all duration-500`, and any stray `duration-500` from elements that also carry `press`. (Keep `hover:*` classes — they define the end states; `.press` now animates them.)

## Repo conventions to follow

- `cubic-bezier(0.22, 1, 0.36, 1)` is the house curve (`--ease-boutique`, defined at app.css:305) — keep it for transform/box-shadow; plain `ease` for color changes matches the easing decision table (hover/color → `ease`).
- Exemplar of a correct specific-property transition in this repo: `#siteNav` at `resources/views/layouts/public/base.blade.php:63` (`transition-[background-color,border-color,box-shadow,color,backdrop-filter]`).

## Steps

1. Edit `resources/css/app.css:712-714`, replacing the `.press` transition with the target block above.
2. `resources/views/welcome.blade.php:166` — remove `transition-all duration-500` from the Search rooms button class list.
3. `resources/views/welcome.blade.php:409` — remove `transition-all duration-500` from the Reserve CTA.
4. `resources/views/welcome.blade.php:600` — remove `transition-all` from the modal Close button; line 601 — remove `transition-all duration-500` from Book this room.
5. `resources/views/components/booking/cards/room.blade.php:93` — remove `transition-all duration-500` from the Book button.
6. `resources/views/checkout.blade.php:149` — remove `transition-all duration-500` from Confirm Booking (also line 177's mobile Confirm button if it carries `press` + transition utilities).
7. Run `npm run build`.

## Boundaries

- Only touch elements that have BOTH `press` and `transition-*` utilities. Elements using transition utilities without `.press` (inputs, nav links) are out of scope.
- Do NOT change any `hover:*` classes, colors, or shadows — only transition declarations.
- Do NOT touch admin/staff views even if they use `.press`.
- If a listed class string doesn't match the file (drift since fed2f3d), STOP and report.

## Verification

- **Mechanical**: `npm run build` succeeds; grep confirms no element combines `press` and `transition-all` in the touched files.
- **Feel check**:
  - Hover the hero "Search rooms" button: the gold glow ring and background shift now ease in (~300ms), instead of popping on.
  - Press it: the 0.15s scale-down still fires instantly.
  - In DevTools Animations panel at 10% speed, confirm background-color and box-shadow animate together with no property snapping at the end.
- **Done when**: every primary CTA hover eases smoothly and press feedback is unchanged.
