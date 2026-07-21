# 007 — Gate the admin table row stagger to first paint only

- **Status**: DONE (applied 2026-07-21)
- **Commit**: ac773f6
- **Severity**: HIGH
- **Category**: Purpose & frequency
- **Estimated scope**: 2 files (1 CSS, 1 JS), ~12 lines

## Problem

Every admin data table plays a staggered row entrance on every render, not just page load. Staff search (400ms debounce), sort, filter, and paginate these Livewire tables dozens-to-hundreds of times a day, and each interaction that replaces rows replays a cascade of up to ~540ms (300ms animation + 240ms max delay):

```css
/* resources/css/admin/05-motion-ux.css:50-61 — current */
@media (prefers-reduced-motion: no-preference) {
    .data-table tbody tr { animation: rowIn 300ms var(--ease-out) both; }
    .data-table tbody tr:nth-child(1) { animation-delay: 30ms; }
    .data-table tbody tr:nth-child(2) { animation-delay: 60ms; }
    .data-table tbody tr:nth-child(3) { animation-delay: 90ms; }
    .data-table tbody tr:nth-child(4) { animation-delay: 120ms; }
    .data-table tbody tr:nth-child(5) { animation-delay: 150ms; }
    .data-table tbody tr:nth-child(6) { animation-delay: 180ms; }
    .data-table tbody tr:nth-child(7) { animation-delay: 210ms; }
    .data-table tbody tr:nth-child(n+8) { animation-delay: 240ms; }
}
@keyframes rowIn { from { opacity: 0; transform: translateY(4px); } }
```

An existing mitigation in `resources/views/layouts/admin.blade.php:106-112` clears the
animation inline after it finishes (`animationend` → `style.animation = 'none'`), which
protects rows that Livewire *morphs in place* — but rows **replaced** by a search/sort/page
change are new DOM nodes and re-animate. The frequency rule: tens-of-times-a-day actions get
no entrance animation. The stagger should welcome the page once, then get out of the way.

## Target

The stagger runs exactly once — on the initial page load. After the first Livewire update
of the session (search, sort, pagination, poll), rows appear instantly:

```css
/* target — same block, with one extra guard selector at the end */
@media (prefers-reduced-motion: no-preference) {
    .data-table tbody tr { animation: rowIn 300ms var(--ease-out) both; }
    /* …nth-child delays unchanged… */
}
@keyframes rowIn { from { opacity: 0; transform: translateY(4px); } }

/* Any Livewire-driven re-render after first paint renders rows instantly —
   entrance choreography is a page-load event, not a search-keystroke event. */
body.lw-updated .data-table tbody tr { animation: none; }
```

```js
// target — resources/js/app.js, inside the existing livewire:init listener
window.Livewire.hook('commit', function ({ succeed }) {
    succeed(function () { document.body.classList.add('lw-updated'); });
});
```

## Repo conventions to follow

- Admin motion rules live in `resources/css/admin/05-motion-ux.css`; tokens (`--ease-out`)
  in `01-tokens.css`. Do not hand-type curves.
- The repo already has this exact "suppress entrance on silent re-render" idea for the
  public room tiles: `.room-tiles.no-anim .room-tile { animation: none; }`
  (resources/css/app.css:1225). Imitate that pattern.
- `resources/js/app.js` already has a `document.addEventListener('livewire:init', …)` block
  (~line 90, the toast bridge). Add the hook inside that same listener.

## Steps

1. **resources/css/admin/05-motion-ux.css** — after the `@keyframes rowIn` line (61), add:

   ```css
   /* Livewire re-renders after first paint skip the entrance — the stagger
      is a page-load welcome, not a per-search event (class set in app.js). */
   body.lw-updated .data-table tbody tr { animation: none; }
   ```

2. **resources/js/app.js** — inside the existing `livewire:init` listener (the block that
   registers the `toast` handler, ~line 90-96), add after the toast registration:

   ```js
   // First Livewire commit = the user is interacting; retire table entrance
   // choreography for the rest of the page's life (see 05-motion-ux.css).
   window.Livewire.hook('commit', function ({ succeed }) {
       succeed(function () { document.body.classList.add('lw-updated'); });
   });
   ```

3. Rebuild: `npm run build`.

## Boundaries

- Do NOT remove the `rowIn` animation or its delays — first-paint behavior is intentional.
- Do NOT touch the `animationend` handler in layouts/admin.blade.php — it still serves
  fadeInUp/popIn stacking-context cleanup.
- Do NOT touch public CSS (resources/css/app.css).
- If `Livewire.hook('commit', …)` does not exist in the installed Livewire version
  (check `composer.json` — expect v3), STOP and report instead of improvising.

## Verification

- **Mechanical**: `node --check resources/js/app.js` passes; `npm run build` succeeds.
- **Feel check**: log into `/staff` (admin console), open Bookings:
  - On first load, rows cascade in (unchanged).
  - Type in the table search box: result rows must appear **instantly** — no fade, no stagger.
  - Sort a column and change pages: same, instant.
  - Reload the page: cascade plays again (per page load, that's correct).
  - DevTools → Rendering → emulate `prefers-reduced-motion: reduce`: no cascade even on load.
- **Done when**: searching/sorting/paginating any admin table never replays the row entrance,
  while a fresh page load still does.
