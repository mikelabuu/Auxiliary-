# 008 — Replace `transition-all` with explicit-property transitions (public blades)

- **Status**: DONE (applied 2026-07-21)
- **Commit**: ac773f6
- **Severity**: MEDIUM
- **Category**: Performance
- **Estimated scope**: 23 blade files, 81 class occurrences (mechanical, per-element judgment)

## Problem

Tailwind's `transition-all` compiles to `transition-property: all`, which animates every
animatable property — including layout properties (padding, width, font-size) — off-GPU
whenever anything changes. The audit catalog treats `transition: all` as an unconditional
finding. The admin CSS never uses it (every rule names its properties); the public blades
use it 81 times across 23 files:

```
resources/views/public/auth/login.blade.php                     ×15
resources/views/public/booking/partials/reservation-block.blade.php ×10
resources/views/public/booking/partials/step-guest.blade.php     ×9
resources/views/layouts/public/base.blade.php                    ×7
resources/views/public/account/bookings.blade.php                ×7
resources/views/sandbox/gateway.blade.php                        ×5
resources/views/public/account/transactions.blade.php            ×4
resources/views/public/auth/reset-password.blade.php             ×3
resources/views/public/booking/show.blade.php                    ×3
resources/views/public/booking/checkout.blade.php                ×3
resources/views/vendor/pagination/simple-tailwind.blade.php      ×2
resources/views/public/auth/forgot-password.blade.php            ×2
(11 more files ×1 each — find with the grep in Step 1)
```

Typical current usage (checkout date input):

```html
<!-- resources/views/public/booking/checkout.blade.php:66 — current -->
class="flatpickr-date w-full … focus:bg-white/10 focus:border-gold/60 focus:ring-2
       focus:ring-gold/20 outline-none font-semibold cursor-pointer transition-all"
```

Nothing here changes on focus except colors and shadow — `all` buys risk, not motion.

## Target

Each `transition-all` becomes the narrowest utility covering the properties that element
actually changes on hover/focus/active:

| The element changes… | Replace `transition-all` with |
| --- | --- |
| only colors (text/bg/border/ring) | `transition-colors` |
| colors + box-shadow (`ring-*` is box-shadow) | `transition-[color,background-color,border-color,box-shadow]` |
| transform (hover lift/scale) + colors | `transition-[transform,color,background-color,border-color,box-shadow]` |
| opacity only | `transition-opacity` |

Keep any existing `duration-*` utilities as they are.

```html
<!-- target for the input above -->
class="flatpickr-date w-full … cursor-pointer
       transition-[color,background-color,border-color,box-shadow]"
```

**Special case — elements that also carry `.press`:** the unlayered `.press` rule in
`resources/css/app.css:801-812` already defines the full transition (transform, colors,
shadow) and beats layered Tailwind utilities; on those elements transition utilities are
dead code. There, just **delete** `transition-all` and add nothing.

## Repo conventions to follow

- `.press` is the sanctioned pattern for pressable things — see app.css:798-812 and its
  comment ("utilities on .press elements are dead").
- The admin bundle is the exemplar for explicit-property transitions, e.g.
  `resources/css/admin/05-motion-ux.css:99` (`transition: background var(--transition),
  color var(--transition), transform 140ms var(--ease-out);`).

## Steps

1. Enumerate every instance: `rg -n "transition-all" resources/views` — expect ~81 hits in
   23 files (all public-side; none in admin views).
2. For each hit, read the element's class list and classify it against the table above
   (what actually changes on hover/focus/active?). Apply the mapped replacement.
3. For hits on elements that also have `press` in their class list: remove
   `transition-all`, add nothing.
4. Do the two big files first and carefully (login ×15, reservation-block ×10) — they are
   form-heavy, so almost every hit is the colors+shadow case.
5. Rebuild: `npm run build`, then `php artisan view:clear && php artisan view:cache`.

## Boundaries

- Do NOT touch admin views or admin CSS (no occurrences expected; skip any found in
  `resources/views/staff/**` — out of scope for this plan).
- Do NOT change durations, easings, hover styles, or any non-transition classes.
- Do NOT edit `resources/views/vendor/pagination/tailwind.blade.php` beyond the
  `transition-all` token itself (vendor-published file; minimal diff).
- If an element's changing properties are ambiguous from the markup, prefer
  `transition-[color,background-color,border-color,box-shadow]` (the superset used by
  form fields) over guessing narrow.

## Verification

- **Mechanical**: `rg -n "transition-all" resources/views` returns **zero** hits;
  `npm run build` succeeds; `php artisan view:cache` succeeds.
- **Feel check**: on the public site —
  - Login page: tab through the form; focus rings/borders still ease in (not snap).
  - Checkout: hover the room-type cards and date inputs; hover/focus feel unchanged.
  - Anything with `.press` still scales on press (that never came from the utility).
- **Done when**: zero `transition-all` in `resources/views`, with hover/focus feel
  visually identical to before.
