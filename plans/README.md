# Animation improvement plans

Each plan is self-contained: exact file:line, current code, target code, steps, boundaries,
and a feel-check. Execute with `improve-animations execute <plan>` or hand any plan to an
agent as-is.

## Batch 2 — whole-system audit (commit `ac773f6`, 2026-07-21)

Scope: admin console, frontdesk, and remaining public surfaces (the booking flow was
covered by batch 1). Headline: the motion system is mature — toasts, admin modals, dropdown
origins, and reduced-motion scoping all passed clean. These plans cover what survived vetting.

| # | Plan | Severity | Status |
| --- | --- | --- | --- |
| 007 | [Table row stagger → first paint only](007-table-row-stagger-first-paint-only.md) | HIGH | DONE |
| 008 | [Replace `transition-all` on public blades](008-replace-transition-all-public.md) | MEDIUM | DONE |
| 009 | [Public modal animated exit](009-public-modal-animated-exit.md) | MEDIUM | DONE |
| 010 | [Dashboard draw-on budget](010-dashboard-drawon-budget.md) | LOW | DONE |
| 011 | [SweetAlert easing + tempo](011-sweetalert-easing-and-tempo.md) | LOW | DONE |
| 012 | [Admin hover gating for touch](012-admin-hover-gating-touch.md) | LOW | DONE |
| 013 | [Polish: density crossfade + empty states](013-density-crossfade-and-empty-states.md) | LOW (optional) | DONE |

### Recommended execution order

**007** (highest daily leverage) → **011** (2-line quick win) → **009** → **008** (large but
mechanical) → **010** → **012** → **013** (optional polish, last).

### Dependencies

- All plans are independent of each other; none block.
- **013** touches `cycleDensity()` in `layouts/admin.blade.php` — if other work edits that
  function, re-read it before applying (plans stamp `ac773f6`).
- **008** and **009** both edit public blades/app.css but different lines; either order.
- Admin-CSS plans (007, 010, 011, 012, 013) all finish with `npm run build` — when executing
  several in one session, a single build at the end suffices. Blade-touching plans
  (008, 009, 013) also need `php artisan view:clear && php artisan view:cache`.

## Batch 1 — public booking flow (commit `fed2f3d`, 2026-07-17) — all DONE

| # | Plan | Severity | Status |
| --- | --- | --- | --- |
| 001 | [Mobile drawer enter/exit](001-mobile-drawer-enter-exit.md) | HIGH | DONE |
| 002 | [`.press` / CTA hover transition repair](002-press-cta-hover-transition-repair.md) | HIGH | DONE |
| 003 | [Exit easing + dropdown origin](003-exit-easing-and-dropdown-origin.md) | MEDIUM | DONE |
| 004 | [Reduced-motion scoping + hover gating](004-reduced-motion-scope-and-hover-gating.md) | MEDIUM | DONE |
| 005 | [Room-card hairlines → GPU transforms](005-room-card-hairlines-gpu.md) | MEDIUM | DONE |
| 006 | [Room tiles: transitions + calm live re-renders](006-room-tiles-transitions-and-silent-rerender.md) | MEDIUM | DONE |

All six applied 2026-07-17, plus from the not-planned list: checkout error-alert pop-in,
reservation-block enter/exit, progress-rail check pop, and the 400ms fully-booked dim.

### Still open from batch 1 (unpromoted)

- Easing-token consolidation: `--ease-boutique` (app.css:305) defined but barely referenced;
  ~7 distinct cubic-beziers hand-typed ~25×. A `@theme` token would unlock a Tailwind utility.
- Hero `flip-fade-text` infinite loop (judgment call; consider `:loop="false"`).

## Vetted and NOT planned (batch 2)

- Toast system (11-notify-nav.css + app.js) — Sonner-grade already: grid-collapse
  transitions, pause-on-hover bookkeeping, asymmetric exits. Leave alone.
- Admin modal enter/exit (04-components.css:498-521) — `@starting-style` + spring +
  asymmetric exit; the exemplar other plans copy.
- `chartRise scaleY(0)` — data draw-on, not an element entrance; exempt from the scale(0) rule.
- Modal `transform-origin: center` — modals are exempt by design.
