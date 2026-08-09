# 018 — Put the loader partials on the repo's ease-out curve

- **Status**: TODO
- **Commit**: `770cf6a`
- **Severity**: LOW
- **Category**: Easing & duration
- **Estimated scope**: 2 files, 5 lines

## Problem

The two inline loading partials use the browser's built-in `ease` on exits and
two novel hand-typed curves that appear nowhere else in the codebase. Built-in
`ease` is a weak curve, and on an **exit** the correct choice is a strong
ease-out so the element leaves promptly instead of loitering.

```css
/* resources/views/partials/page-loader.blade.php:72 — current */
        transition: opacity 320ms ease, visibility 0s linear 320ms;
```

```css
/* resources/views/partials/page-loader.blade.php:137 — current */
        animation: plVerseIn 620ms cubic-bezier(.2,.7,.2,1) 180ms both;
```

```css
/* resources/views/partials/page-loader.blade.php:147 — current */
        animation: plVerseIn 620ms cubic-bezier(.2,.7,.2,1) 300ms both;
```

```css
/* resources/views/partials/page-progress.blade.php:35 — current */
        transition: opacity 200ms ease;
```

```css
/* resources/views/partials/page-progress.blade.php:55 — current */
    #pageProgress.is-on > span { animation: pageProgressCreep 8s cubic-bezier(.15,.85,.3,1) forwards; }
```

**Why these files cannot use `var(--ease-out)`** — and why plan 016 explicitly
excludes them: both partials render their own inline `<style>` in the document
body specifically so they paint *before* `admin.css` downloads. That is their
whole purpose. A `var(--ease-out)` reference there resolves to nothing and the
property silently falls back to its initial value. The fix is to inline the
token's literal value, not to reference the token.

`cubic-bezier(.15,.85,.3,1)` on `pageProgressCreep` is deliberate and stays — it
is a bespoke decelerating creep for an indeterminate bar, not a UI transition,
and no shared token expresses it. It is documented here so a future reader does
not "consolidate" it by mistake.

## Target

Use the repo's established ease-out literal, `cubic-bezier(0.22, 1, 0.36, 1)` —
the same curve as `--ease-out` (`resources/css/admin/01-tokens.css:272`) and
`--ease-boutique` (`resources/css/public/03-theme-boutique.css:20`).

```css
/* target — resources/views/partials/page-loader.blade.php:72 */
        transition: opacity 320ms cubic-bezier(0.22, 1, 0.36, 1), visibility 0s linear 320ms;
```

```css
/* target — resources/views/partials/page-loader.blade.php:137 */
        animation: plVerseIn 620ms cubic-bezier(0.22, 1, 0.36, 1) 180ms both;
```

```css
/* target — resources/views/partials/page-loader.blade.php:147 */
        animation: plVerseIn 620ms cubic-bezier(0.22, 1, 0.36, 1) 300ms both;
```

```css
/* target — resources/views/partials/page-progress.blade.php:35 */
        transition: opacity 200ms cubic-bezier(0.22, 1, 0.36, 1);
```

`page-progress.blade.php:55` is **unchanged**.

## Repo conventions to follow

- The canonical ease-out curve in this codebase is `cubic-bezier(0.22, 1, 0.36, 1)`.
  Do not substitute a different strong ease-out from elsewhere — 57 other call
  sites use this exact curve and these loaders should match them.
- **Exemplar** — `resources/css/admin/22-link-underline.css:43` shows the same
  intent expressed the normal way (`transition: transform 280ms var(--ease-out);`).
  These partials express it as a literal only because they cannot see the token.
- Each partial's inline comments explain *why* a constraint exists. When you
  inline the literal, add a brief note that it is the literal form of
  `--ease-out` and why the token cannot be referenced here.

## Steps

1. In `resources/views/partials/page-loader.blade.php`, replace the timing
   function on line 72, line 137 and line 147 as shown in the Target section.
   Durations and delays are unchanged.
2. Add a one-line comment above the `#pageLoader.is-done` rule noting that the
   literal is `--ease-out` inlined, because the token is not available before
   `admin.css` loads.
3. In `resources/views/partials/page-progress.blade.php`, replace the timing
   function on line 35 as shown. Duration unchanged.
4. Add the same one-line explanatory comment near it.
5. Leave `pageProgressCreep` on line 55 exactly as it is.

## Boundaries

- Do NOT change any duration, delay, or `visibility` timing.
- Do NOT change `pageProgressCreep`'s curve or its 8s duration.
- Do NOT change `MIN_VISIBLE` in either file, the interaction gate, the backstop
  timers, or any JavaScript. This plan is timing-function-only.
- Do NOT replace the literals with `var(--ease-out)` — it will not resolve and
  the transition will silently degrade to the initial value.
- Do NOT touch the `sk-chase` keyframes in `page-loader.blade.php`; their
  `linear` and `ease-in-out` timings are SpinKit's own and are correct for
  continuous rotation.
- Do NOT add dependencies.
- If any cited line does not match the excerpt above, STOP and report.

## Verification

- **Mechanical**: `php artisan view:clear && php artisan view:cache` — expect
  `Blade templates cached successfully.` No asset rebuild is needed; neither
  partial is part of the Vite bundle.
- **Feel check**:
  - Navigate between admin pages (Dashboard → Rooms → Booking Operations). The
    full-screen curtain must still appear and then **leave briskly** rather than
    lingering — the exit is the specific thing this plan changes.
  - Watch the verse under the spinner: it should still fade up, slightly after
    the spinner appears, with the two lines staggered.
  - Click a table page number and watch the top bar fade in and out.
  - In DevTools → Elements, select `#pageLoader` mid-load and confirm the
    computed `transition-timing-function` reads `cubic-bezier(0.22, 1, 0.36, 1)`
    and **not** `ease`. A computed value of `ease` means the literal was replaced
    with an unresolvable `var()`.
  - Toggle **Emulate CSS prefers-reduced-motion: reduce** and reload: the
    curtain's reduced-motion block already sets `transition: none` on
    `#pageLoader.is-done`, so it should disappear instantly with no fade. That
    behaviour must not change.
- **Done when**: the four cited timing functions read
  `cubic-bezier(0.22, 1, 0.36, 1)` in computed styles, `pageProgressCreep` is
  untouched, and both loaders behave as before apart from a crisper exit.
