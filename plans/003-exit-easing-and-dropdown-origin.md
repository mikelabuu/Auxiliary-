# 003 — Exits use ease-out; account dropdown scales from its trigger

- **Status**: DONE (applied 2026-07-17)
- **Commit**: fed2f3d
- **Severity**: MEDIUM
- **Category**: Easing & duration / Physicality & origin
- **Estimated scope**: 3 blade files, ~6 line edits

## Problem

1. **`ease-in` on exits.** Alpine leave transitions across the public flow use `ease-in`, which delays movement at the exact moment the user is watching. Exits should start fast (`ease-out`):

```html
<!-- resources/views/layouts/public/base.blade.php:97 — account dropdown leave -->
x-transition:leave="transition ease-in duration-100"

<!-- resources/views/welcome.blade.php:482 — room modal backdrop leave -->
x-transition:leave="ease-in duration-200"

<!-- resources/views/welcome.blade.php:496 — room modal panel leave -->
x-transition:leave="ease-in duration-200"
```

2. **Dropdown scales from center.** The account menu pops out of the avatar button (top-right anchor) but scales from its own center — no `transform-origin` set:

```html
<!-- resources/views/layouts/public/base.blade.php:93-100 — current -->
<div x-show="open"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
     ...
     class="absolute right-0 mt-3 w-60 overflow-hidden rounded-2xl border border-ink/10 bg-canvas py-2 ...">
```

(The room modal panel itself is a centered modal — its origin is correctly center and must NOT be changed.)

3. **Meal panel snaps closed.** The checkout breakfast accordion animates open but has no leave transition, so it vanishes in one frame:

```html
<!-- resources/views/booking/partials/reservation-block-template.blade.php:97-103 — current -->
<div class="mt-2.5"
     x-show="mealsOpen"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 -translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     style="display: none;"
>
```

## Target

```html
<!-- base.blade.php:97 -->
x-transition:leave="transition ease-out duration-100"

<!-- base.blade.php:100 — add origin-top-right to the panel's class list -->
class="absolute right-0 mt-3 w-60 origin-top-right overflow-hidden rounded-2xl border border-ink/10 bg-canvas py-2 ..."

<!-- welcome.blade.php:482 and 496 -->
x-transition:leave="ease-out duration-200"

<!-- reservation-block-template.blade.php — add after the enter lines -->
x-transition:leave="transition ease-out duration-120"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 -translate-y-1"
```

## Repo conventions to follow

- Alpine `x-transition` with Tailwind utility classes is the established pattern (exemplar: the dropdown's own enter at base.blade.php:94-96 — `ease-out duration-150` — is already correct).
- Exit faster than enter (100-200ms leaves vs 150-400ms enters) is already the house rhythm; keep the existing durations, change only the easing keyword.

## Steps

1. `resources/views/layouts/public/base.blade.php:97` — change `ease-in` → `ease-out` in the dropdown's leave transition.
2. `resources/views/layouts/public/base.blade.php:100` — add `origin-top-right` to the dropdown panel's class attribute.
3. `resources/views/welcome.blade.php:482` — change `ease-in` → `ease-out` (modal backdrop leave).
4. `resources/views/welcome.blade.php:496` — change `ease-in` → `ease-out` (modal panel leave).
5. `resources/views/booking/partials/reservation-block-template.blade.php:97-103` — add the three leave lines from the target block to the meals panel.
6. No build needed for blade-only class changes if the classes already exist in the bundle; run `npm run build` anyway to be safe (`origin-top-right` and `duration-120` may be new to the content scan).

## Boundaries

- Do NOT change enter transitions, durations, or any `scale`/`translate` values other than specified.
- Do NOT touch the room modal panel's transform-origin — modals stay centered.
- Do NOT convert these to CSS classes or restructure markup.
- If a line doesn't match (drift since fed2f3d), STOP and report.

## Verification

- **Mechanical**: `npm run build` succeeds.
- **Feel check**:
  - Open the account menu (logged-in, desktop): it grows out of the avatar button's corner, not its own middle; closing starts moving immediately (no sluggish first frames).
  - Open a room's "View Details", press Escape: backdrop and panel begin fading instantly.
  - In checkout, toggle "Breakfast Meal Selections" open and closed rapidly: the panel eases both ways and retargets mid-flight.
  - DevTools Animations panel at 10% speed: leave curves start steep (fast), not shallow.
- **Done when**: no `ease-in` remains in the public booking blades and the dropdown is origin-aware.
