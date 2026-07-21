# 011 — Fix SweetAlert exit easing; trim entrance for its new frequency

- **Status**: DONE (applied 2026-07-21)
- **Commit**: ac773f6
- **Severity**: LOW
- **Category**: Easing & duration
- **Estimated scope**: 1 CSS file, 2 value edits

## Problem

The SweetAlert overrides carry the only `ease-in` in the codebase — on the popup exit.
`ease-in` delays movement at the exact moment the user is watching; exits should start
fast:

```css
/* resources/css/admin/08-selects-widgets.css:106-119 — current */
.swal2-popup.swal2-show {
    animation: swalEntrance 0.32s var(--ease-spring) !important;
}
.swal2-popup.swal2-hide {
    animation: swalExit 0.2s ease-in forwards !important;
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

Frequency context: staff action buttons (cancel / checkout / check-in / no-show /
suspend) now confirm through SweetAlert with no password step, making this the
highest-frequency dialog in the console — tens of appearances per day. A 320ms spring
entrance is the "occasional modal" tier; this dialog has outgrown it.

## Target

```css
/* target */
.swal2-popup.swal2-show {
    animation: swalEntrance 0.22s var(--ease-spring) !important;
}
.swal2-popup.swal2-hide {
    animation: swalExit 0.16s var(--ease-out) forwards !important;
}
```

Keyframes unchanged. Exit stays faster than entrance (asymmetric timing preserved).

## Repo conventions to follow

- `--ease-out: cubic-bezier(.22, 1, .36, 1)` and `--ease-spring: cubic-bezier(.34, 1.3, .5, 1)`
  live in `resources/css/admin/01-tokens.css:140-141` — use the tokens, not literals.

## Steps

1. **resources/css/admin/08-selects-widgets.css:107** — `0.32s` → `0.22s`.
2. **resources/css/admin/08-selects-widgets.css:110** — `0.2s ease-in` → `0.16s var(--ease-out)`.
3. Rebuild: `npm run build`.

## Boundaries

- Do NOT touch the keyframes, the backdrop blur, or any other swal styling.
- Do NOT remove the `!important`s — they exist to beat SweetAlert's inline animations.

## Verification

- **Mechanical**: `npm run build` succeeds; `rg -n "ease-in[^-]" resources/css` returns zero hits.
- **Feel check**: in the admin console, click Cancel on a pending booking (or any
  confirm-gated action):
  - The dialog arrives with a quick soft spring — noticeably brisker than before.
  - Dismiss it: it recedes immediately on click (no perceptible hesitation at the start
    of the exit — that hesitation was the `ease-in`).
- **Done when**: zero `ease-in` in authored CSS and the confirm dialog feels like a
  routine tool, not an event.
