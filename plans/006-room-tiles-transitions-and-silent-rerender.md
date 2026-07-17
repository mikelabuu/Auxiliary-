# 006 — Room-number tiles: specific transitions, ease-out, and calm live re-renders

- **Status**: DONE (applied 2026-07-17)
- **Commit**: fed2f3d
- **Severity**: MEDIUM
- **Category**: Interruptibility / Easing / Purpose & frequency
- **Estimated scope**: 2 files (app.css, booking.js), ~15 lines

## Problem

1. **`transition: all` + `ease-in-out` on rapidly-clicked tiles.** The checkout room-number tiles are clicked, deselected, and re-clicked while a guest decides; their transition animates every property with a symmetric curve:

```css
/* resources/css/app.css:1087-1103 — current (excerpt) */
.room-tile {
    ...
    transition: all 0.18s ease-in-out;
    user-select: none;
    /* staggered entrance — booking.js sets --i per tile */
    animation: popIn .28s cubic-bezier(.16, 1, .3, 1) both;
    animation-delay: calc(var(--i, 0) * 24ms);
}
```

2. **Background re-renders replay the entrance stagger.** `renderRoomTilesForBlock` rebuilds the tile grid from scratch, and it is also called by the silent Reverb availability re-check (`recheckBlockAvailability`, booking.js:436-451) and the auto re-check on date changes. Every push from the front desk makes the whole tile grid blink and re-cascade its popIn stagger while the guest may be mid-selection — motion with no purpose on a background update.

```js
// public/js/booking.js:347-351 — current
rooms.forEach((r, i) => {
  const tile = document.createElement('div');
  tile.classList.add('room-tile');
  tile.style.setProperty('--i', i); // staggered entrance (see app.css)
```

## Target

1. Specific, GPU-friendly transition with ease-out for the moving parts:

```css
/* target — app.css .room-tile transition line */
transition:
    background-color 0.18s ease,
    border-color 0.18s ease,
    color 0.18s ease,
    box-shadow 0.18s ease,
    transform 0.18s cubic-bezier(0.22, 1, 0.36, 1),
    opacity 0.18s ease;
```

2. Entrance stagger plays only on user-visible renders; silent re-renders update in place with no animation:

```css
/* target — add after the .room-tile block in app.css */
/* Silent (Reverb/live) re-renders skip the entrance cascade */
.room-tiles.no-anim .room-tile {
    animation: none;
}
```

```js
// target — booking.js
// renderRoomTilesForBlock gains an options param:
function renderRoomTilesForBlock(index, rooms, blockEl, opts = {}) {
  ...
  const container = document.createElement('div');
  container.className = 'room-tiles' + (opts.silent ? ' no-anim' : '');

// and the silent re-check passes it:
const result = renderRoomTilesForBlock(block.dataset.index, data.rooms || [], block, { silent: true });
```

## Repo conventions to follow

- `cubic-bezier(0.22, 1, 0.36, 1)` is the house curve (see `.press`, `.hover-lift-premium` in app.css); color changes use plain `ease` per the easing decision table.
- The `silent` option pattern already exists in this codebase: `availability-search.js` `runSearch({ silent: true })` suppresses skeletons/spinners for background refreshes — mirror that naming.

## Steps

1. `resources/css/app.css:1098` — replace `transition: all 0.18s ease-in-out;` with the target multi-property transition.
2. After the `.room-tile` rule block (following line 1103), add the `.room-tiles.no-anim .room-tile { animation: none; }` rule with its comment.
3. `public/js/booking.js:325` — change the signature to `function renderRoomTilesForBlock(index, rooms, blockEl, opts = {})`.
4. `public/js/booking.js:342-343` — where `container.className = 'room-tiles'` is set, append `' no-anim'` when `opts.silent` is truthy.
5. `public/js/booking.js:448` — in `recheckBlockAvailability`, pass `{ silent: true }` as the fourth argument.
6. Leave the two interactive call sites (the `btnCheck` click handler at booking.js:257 and anything else) without the option so first paints keep the cascade.
7. Run `npm run build` (app.css) — booking.js is served from `public/js` with an mtime cache-buster, no build step.

## Boundaries

- Do NOT change tile colors, selection logic, or the `selectedRoomNumbersSet` bookkeeping.
- Do NOT alter the popIn keyframes or the 24ms stagger for interactive renders.
- Do NOT debounce or throttle anything — the 400ms debounce already exists.
- If the code has drifted from fed2f3d, STOP and report.

## Verification

- **Mechanical**: `npm run build` succeeds; page loads `/checkout?room_type=…` without console errors.
- **Feel check**:
  - Pick dates + a room style: tiles cascade in with the 24ms stagger as before.
  - Click "Refresh": cascade replays (interactive → animated) — acceptable and unchanged.
  - Simulate a live push (from an admin session change a room status, or temporarily call `recheckAllBlocksAvailability()` in the console): tiles update **without** blinking through the popIn cascade; a selected tile stays visually selected.
  - Click a tile, then another, rapidly: selection styling retargets smoothly (transitions, not restarts).
- **Done when**: interactive renders animate, background renders don't, and no `transition: all` remains on `.room-tile`.
