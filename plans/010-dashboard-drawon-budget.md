# 010 — Bring dashboard draw-on animations inside the duration budget

- **Status**: DONE (applied 2026-07-21)
- **Commit**: ac773f6
- **Severity**: LOW
- **Category**: Easing & duration
- **Estimated scope**: 2 CSS files, 2 value edits

## Problem

Two data draw-on animations replay on every dashboard visit — a page staff open many
times a day — and the longer one runs 3× the 300ms UI budget:

```css
/* resources/css/admin/05-motion-ux.css:63-67 — current */
/* ── Progress / breakdown bars grow from zero on load ── */
@media (prefers-reduced-motion: no-preference) {
    .bar-fill, .dashboard-breakdown-fill { animation: growBar 900ms var(--ease-out); }
}
@keyframes growBar { from { width: 0; } }
```

```css
/* resources/css/admin/09-craft.css:39 — current (bars inside insight modals) */
    animation: chartRise 520ms var(--ease-out) both;
```

Draw-ons are semi-explanatory, so they may exceed strict UI budgets — but 900ms on an
everyday page reads as slow, not elegant. (Also note `growBar` animates `width`, which is
a layout property; at once-per-load it's tolerable — do not convert it to a transform in
this plan, scaleX would distort any text/rounded caps inside the fill.)

## Target

```css
/* target — 05-motion-ux.css */
    .bar-fill, .dashboard-breakdown-fill { animation: growBar 500ms var(--ease-out); }
```

```css
/* target — 09-craft.css */
    animation: chartRise 420ms var(--ease-out) both;
```

## Repo conventions to follow

- Tokens in `resources/css/admin/01-tokens.css` (`--ease-out`); keep using them.
- The admin personality is a crisp dashboard — motion should be felt, not waited for.

## Steps

1. **resources/css/admin/05-motion-ux.css:65** — change `900ms` → `500ms`.
2. **resources/css/admin/09-craft.css:39** — change `520ms` → `420ms`.
3. Rebuild: `npm run build`.

## Boundaries

- Do NOT change the keyframes, easings, or reduced-motion gates.
- Do NOT convert `growBar` to a transform (see Problem note).
- Do NOT add per-session gating in this plan (deliberate scope cut — durations first;
  promote a session gate later only if 500ms still feels chatty in daily use).

## Verification

- **Mechanical**: `npm run build` succeeds.
- **Feel check**: open the admin dashboard —
  - Breakdown/progress bars settle in about half a second, arriving together with the
    row cascade rather than after it.
  - Open an insights modal: chart bars rise briskly; nothing feels like it's loading.
  - Emulate `prefers-reduced-motion: reduce`: bars render at full width instantly.
- **Done when**: both durations are updated and the dashboard's draw-ons no longer outlast
  the rest of the page's entrance.
