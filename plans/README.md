# Animation improvement plans — public booking flow

Written by the `improve-animations` audit at commit `fed2f3d` (2026-07-17). Each plan is
self-contained: exact file:line, current code, target code, steps, boundaries, and a feel-check.
Execute with `improve-animations execute <plan>` or hand any plan to an agent as-is.

| # | Plan | Severity | Status |
| --- | --- | --- | --- |
| 001 | [Mobile drawer enter/exit](001-mobile-drawer-enter-exit.md) | HIGH | DONE |
| 002 | [`.press` / CTA hover transition repair](002-press-cta-hover-transition-repair.md) | HIGH | DONE |
| 003 | [Exit easing + dropdown origin](003-exit-easing-and-dropdown-origin.md) | MEDIUM | DONE |
| 004 | [Reduced-motion scoping + hover gating](004-reduced-motion-scope-and-hover-gating.md) | MEDIUM | DONE |
| 005 | [Room-card hairlines → GPU transforms](005-room-card-hairlines-gpu.md) | MEDIUM | DONE |
| 006 | [Room tiles: transitions + calm live re-renders](006-room-tiles-transitions-and-silent-rerender.md) | MEDIUM | DONE |

All six plans were applied on 2026-07-17 (`npm run build` + `php artisan view:clear` run).
Also applied from the "not planned" list: checkout error-alert pop-in, reservation-block
enter/exit, progress-rail check pop, and the 400ms fully-booked dim on room-card images.
Still open: easing-token consolidation and the hero flip-fade `:loop="false"` judgment call.

## Recommended execution order

001 → 002 → 003 → 004 → 005 → 006 (leverage order; all are independent).

## Dependencies

- None are blocking. Note only that **002** and **004** both edit `resources/css/app.css` near the
  `.press` rules — apply 002 first so 004's reduced-motion block references the final `.press`.
- All plans finish with `npm run build`; if executing several, one build at the end suffices.

## Audited but not planned (ask to promote any of these)

- Easing-token consolidation: `--ease-boutique` (app.css:305) is defined but never referenced;
  ~7 distinct cubic-beziers are hand-typed ~25×. A `@theme` token would also unlock a Tailwind
  `ease-boutique` utility.
- Hero `flip-fade-text` infinite loop on the brand word (welcome.blade.php:128) — judgment call;
  consider `:loop="false"` so it plays once. Needs a feel-check, not a rule.
- Fully-booked card dim (availability-search.js:147-163) — opacity snaps while grayscale eases
  over the hover-zoom's 1200ms; deserves a purposeful ~400ms state transition.
- Small entrance polish: checkout error alert (`d-none` toggle), reservation-block add/remove,
  progress-rail check icon swap.
