# 017 — Gate the SweetAlert dialog motion under reduced motion

- **Status**: TODO
- **Commit**: `770cf6a`
- **Severity**: MEDIUM
- **Category**: Accessibility
- **Estimated scope**: 1 file, ~10 lines

## Problem

SweetAlert dialogs carry the console's confirm/verify flows — password
verification on booking actions, delete confirmations, discount review. Their
enter and exit animations move the dialog vertically and scale it:

```css
/* resources/css/admin/08-selects-widgets.css:106-119 — current */
.swal2-popup.swal2-show {
    animation: swalEntrance 0.22s var(--ease-spring) !important;
}
.swal2-popup.swal2-hide {
    animation: swalExit 0.16s var(--ease-out) forwards !important;
}
@keyframes swalEntrance {
    0% { transform: scale(0.94) translateY(10px); opacity: 0; }
    100% { transform: scale(1) translateY(0); opacity: 1; }
}
@keyframes swalExit {
    0% { transform: scale(1); opacity: 1; }
    100% { transform: scale(0.96); opacity: 0; }
}
```

`resources/css/admin/08-selects-widgets.css` contains **no**
`prefers-reduced-motion` block anywhere. Plan 011 (DONE) tuned this dialog's
easing and tempo but did not add a reduced-motion path, so users who ask for
reduced motion still get a scaling, rising dialog on every destructive confirm.

The timing and curve are correct and are not in scope here. The physicality is
also correct — `scale(0.94)`, not `scale(0)`.

## Target

Keep the opacity change, drop the movement. Reduced motion means gentler, not
none: the dialog must still fade so its arrival and dismissal remain legible.

```css
/* target — append after resources/css/admin/08-selects-widgets.css:119 */

/* The dialog still needs to announce itself — a confirm that appears with no
   transition at all reads as a rendering glitch. Keep the fade, drop the
   scale and the rise. */
@media (prefers-reduced-motion: reduce) {
    .swal2-popup.swal2-show {
        animation: swalEntranceReduced 0.18s var(--ease-out) !important;
    }
    .swal2-popup.swal2-hide {
        animation: swalExitReduced 0.14s var(--ease-out) forwards !important;
    }
    @keyframes swalEntranceReduced {
        0%   { opacity: 0; }
        100% { opacity: 1; }
    }
    @keyframes swalExitReduced {
        0%   { opacity: 1; }
        100% { opacity: 0; }
    }
}
```

## Repo conventions to follow

- Easing tokens: `var(--ease-out)` is declared at
  `resources/css/admin/01-tokens.css:272`. Use it; do not hand-type a curve.
- The `!important` flags are required here and must be kept — SweetAlert ships
  its own animation rules and this bundle overrides them. Every rule in this
  section of `08-selects-widgets.css` already carries them.
- **Exemplar** — `resources/css/admin/23-spinkit.css` ends with a
  `@media (prefers-reduced-motion: reduce)` block that suppresses motion while
  preserving the element's meaning. Follow that pattern: a reduced-motion block
  appended at the end of the relevant section, not scattered inline.

## Steps

1. Open `resources/css/admin/08-selects-widgets.css`.
2. Immediately after the `@keyframes swalExit` block that ends at line 119,
   append the `@media (prefers-reduced-motion: reduce)` block from the Target
   section verbatim.
3. Leave lines 106-119 untouched — the default-motion path does not change.

## Boundaries

- Do NOT modify the existing `swalEntrance` / `swalExit` keyframes, their
  durations, or their easings.
- Do NOT touch the flatpickr calendar rules or the `.form-select` chevron rules
  elsewhere in this file.
- Do NOT remove any `!important` — SweetAlert's own stylesheet wins without them.
- Do NOT add dependencies.
- If lines 106-119 do not match the excerpt above, STOP and report.

## Verification

- **Mechanical**: `npm run build:only` — expect a clean build and a new
  `public/build/assets/admin-<hash>.css`.
- **Feel check**: log into the admin console and open `/bookings`.
  - Click **View** on any booking row to trigger the password-verify dialog.
    With default settings it must still scale up slightly and rise as it does
    today — no regression.
  - DevTools → **Rendering** → **Emulate CSS prefers-reduced-motion: reduce**.
    Reload, then trigger the same dialog. It must **fade in with no scaling and
    no vertical movement**, and fade out on dismiss. It must not appear
    instantly with no transition at all — that is the failure mode to watch for.
  - In the Animations panel at 10% speed under reduced motion, confirm the only
    property changing is `opacity`.
- **Done when**: reduced motion yields a fade-only dialog in both directions,
  and the default path is visually unchanged.
