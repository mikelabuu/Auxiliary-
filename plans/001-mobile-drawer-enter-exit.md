# 001 — Animate the mobile drawer's enter and exit

- **Status**: DONE (applied 2026-07-17)
- **Commit**: fed2f3d
- **Severity**: HIGH
- **Category**: Purpose & frequency / Missed motion (jarring state change)
- **Estimated scope**: 2 files (1 blade, 1 CSS), ~30 lines

## Problem

The mobile navigation drawer teleports in and out. The container carries `transition-opacity duration-300`, but the open/close JS toggles the `hidden` class (`display: none`), so a `display` swap short-circuits the transition — no animation ever plays. On phones (the primary device for a booking site) the full-screen overlay and side panel appear and vanish in one frame.

```html
<!-- resources/views/layouts/public/base.blade.php:137 — current -->
<div id="mobileDrawer" class="fixed inset-0 z-[60] flex justify-end bg-ink/50 backdrop-blur-sm transition-opacity duration-300 hidden">
    <div class="bg-canvas w-80 h-full shadow-2xl p-7 flex flex-col justify-between border-l border-ink/10">
```

```js
// resources/views/layouts/public/base.blade.php:313-315 — current
function toggleDrawer(open) {
    drawer && drawer.classList.toggle('hidden', !open);
}
```

## Target

Backdrop fades; the panel slides in from the right on the iOS-like drawer curve. Enter 350ms, exit faster (250ms). CSS transitions (not keyframes) so rapid open/close retargets smoothly. Reduced motion keeps the opacity fade but drops the slide.

```html
<!-- target markup (base.blade.php:137-138) -->
<div id="mobileDrawer" class="fixed inset-0 z-[60] flex justify-end bg-ink/50 backdrop-blur-sm" aria-hidden="true">
    <div class="drawer-panel bg-canvas w-80 h-full shadow-2xl p-7 flex flex-col justify-between border-l border-ink/10">
```

```css
/* target CSS (add to resources/css/app.css, near the #navWrap rules ~line 423) */
/* Mobile drawer — backdrop fades, panel slides on the drawer curve.
   Closed state uses opacity+pointer-events (not display) so the
   transition can actually play and retarget mid-flight. */
#mobileDrawer {
    opacity: 0;
    pointer-events: none;
    visibility: hidden;
    transition: opacity 0.25s ease, visibility 0s linear 0.25s;
}

#mobileDrawer .drawer-panel {
    transform: translateX(100%);
    transition: transform 0.25s cubic-bezier(0.32, 0.72, 0, 1);
}

#mobileDrawer.drawer-open {
    opacity: 1;
    pointer-events: auto;
    visibility: visible;
    transition: opacity 0.3s ease, visibility 0s;
}

#mobileDrawer.drawer-open .drawer-panel {
    transform: translateX(0);
    transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1);
}

@media (prefers-reduced-motion: reduce) {
    #mobileDrawer .drawer-panel,
    #mobileDrawer.drawer-open .drawer-panel {
        transition: none;
        transform: translateX(0);
    }
}
```

```js
// target JS (base.blade.php toggleDrawer)
function toggleDrawer(open) {
    if (!drawer) return;
    drawer.classList.toggle('drawer-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
}
```

## Repo conventions to follow

- Motion CSS for public pages lives in `resources/css/app.css` (see `#navWrap` at app.css:423-436 for the exact comment style and a reduced-motion override done the same way).
- `cubic-bezier(0.32, 0.72, 0, 1)` is the standard iOS drawer curve; this repo hand-types curves inline (no `--ease-*` token usage yet — match that for now).
- Asymmetric timing exemplar: enter slower than exit (`0.35s` in / `0.25s` out), like the nav retreat which returns faster than it hides.

## Steps

1. In `resources/views/layouts/public/base.blade.php:137`, remove `transition-opacity duration-300 hidden` from the `#mobileDrawer` class list and add `aria-hidden="true"`.
2. On the child panel div (line 138), add the class `drawer-panel` (keep every other class).
3. Replace the `toggleDrawer` function body (lines 313-315) with the target JS above.
4. Add the target CSS block to `resources/css/app.css` directly after the `#navWrap` reduced-motion rule (~line 436).
5. Run `npm run build` so the Vite CSS bundle picks up the new rules.

## Boundaries

- Do NOT touch the desktop nav, the account dropdown, or the room modal.
- Do NOT add Alpine to the drawer — keep the plain-JS toggle contract (menuBtn/closeBtn/backdrop-click/link-click listeners all call `toggleDrawer`).
- Do NOT add new dependencies.
- If the drawer markup no longer matches the excerpt (drift since fed2f3d), STOP and report.

## Verification

- **Mechanical**: `npm run build` completes; no Blade syntax errors when loading `/`.
- **Feel check** (mobile viewport in DevTools, ≤767px):
  - Tapping the hamburger: backdrop fades in while the panel slides in from the right; nothing pops.
  - Tapping close / the backdrop / a nav link: panel slides out slightly faster than it entered.
  - Spam the hamburger and close button rapidly — the panel reverses mid-flight from its current position, never restarts from fully off-screen.
  - DevTools → Rendering → emulate `prefers-reduced-motion: reduce`: drawer appears/disappears with the fade only, no slide.
- **Done when**: the drawer visibly animates open and closed on a phone-width viewport and the interaction is interruptible.
