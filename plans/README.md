# Animation improvement plans

Each plan is self-contained: exact file:line, current code, target code, steps, boundaries,
and a feel-check. Execute with `improve-animations execute <plan>` or hand any plan to an
agent as-is.

## Batch 3 — post-loader audit (commit `770cf6a`, 2026-08-09)

Scope: the whole motion surface again, with emphasis on what landed between
`ac773f6` and `770cf6a` — the SpinKit loaders, the page-load curtain, the
Livewire progress bar, the compositor shadow-lift layers and the animated link
underlines. Headline: the older system held up (no `ease-in`, no
`transition: all`, no `scale(0)` entrances, toasts and admin modals still
exemplary); three of the five findings are in motion added on 2026-08-09.

| # | Plan | Severity | Status |
| --- | --- | --- | --- |
| 014 | [Progress bar interruptibility](014-progress-bar-interruptibility.md) | HIGH | TODO |
| 015 | [Live-dot off the paint path](015-live-dot-off-the-paint-path.md) | MEDIUM | TODO |
| 016 | [Easing token consolidation](016-easing-token-consolidation.md) | MEDIUM | TODO |
| 017 | [SweetAlert reduced motion](017-sweetalert-reduced-motion.md) | MEDIUM | TODO |
| 018 | [Loader easing literals](018-loader-easing-literals.md) | LOW | TODO |

### Recommended execution order

**014** (a real defect in the interaction the bar exists for — smallest fix,
highest leverage) → **015** (stops an infinite repaint on an all-day page) →
**017** (self-contained, no interactions) → **018** (5 lines; do it *before* 016
so the consolidation sweep runs over settled files) → **016** (largest, purely
mechanical, best done last and alone).

### Dependencies

- **018 before 016.** 016 explicitly excludes the two inline loader partials
  because `var()` cannot resolve there; 018 settles their literals first so the
  exclusion is verifiable rather than a moving target.
- 014 and 018 touch the *same two files* (`partials/page-progress.blade.php`,
  `partials/page-loader.blade.php`) but different concerns — 014 is JavaScript
  only, 018 is CSS timing functions only. Either order; no conflict.
- 015 and 017 are independent of everything.
- CSS-touching plans (015, 016, 017) finish with `npm run build:only`; one build
  at the end covers several in a session. Blade-touching plans (014, 016, 018)
  need `php artisan view:clear && php artisan view:cache`.

### Raised but deliberately NOT planned

- **`MIN_VISIBLE = 900ms` on the page-load curtain**
  (`partials/page-loader.blade.php`). The audit's frequency rule puts navigation
  at *tens of times/day → remove or drastically reduce*, and this enforces a
  0.9s floor on every one. It is left unplanned because the curtain and its
  readable verse were requested deliberately and the tradeoff was stated when it
  was built — this is a feel decision, not a defect. If it starts to drag, the
  change is one number: drop it to ~450ms.

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

## Vetted and NOT planned (batch 3)

- Every `scale(0)` hit in the repo is exempt: `chartRise` (data draw-on, already
  exempted in batch 2), the alert lifeline bar `scaleX(0)`
  (15-alert-popup.css:233), the `.link-underline` wipes (22-link-underline.css:41,64)
  and the SpinKit dot pulses (23-spinkit.css:148,172). None is an element entrance.
- `public/10-view-transitions.css` has keyframes and no reduced-motion block —
  correct, they animate `opacity` only, which reduced motion explicitly permits.
- `public/18-shadow-lift.css` uses `240ms ease` on hover — `ease` is the
  sanctioned curve for hover, not a finding.
- `.fh-badge` 500ms hover (06-hero.css:431) — pre-existing marketing hero.
- Livewire table rows do not animate on re-render — that is plan 007's
  deliberate outcome (stagger on first paint only). Not re-litigated.

## Vetted and NOT planned (batch 2)

- Toast system (11-notify-nav.css + app.js) — Sonner-grade already: grid-collapse
  transitions, pause-on-hover bookkeeping, asymmetric exits. Leave alone.
- Admin modal enter/exit (04-components.css:498-521) — `@starting-style` + spring +
  asymmetric exit; the exemplar other plans copy.
- `chartRise scaleY(0)` — data draw-on, not an element entrance; exempt from the scale(0) rule.
- Modal `transform-origin: center` — modals are exempt by design.
