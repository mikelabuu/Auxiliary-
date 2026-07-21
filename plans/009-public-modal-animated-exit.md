# 009 — Give the public shared modal an animated exit

- **Status**: DONE (applied 2026-07-21)
- **Commit**: ac773f6
- **Severity**: MEDIUM
- **Category**: Interruptibility / Physicality
- **Estimated scope**: 2-3 files (~30 lines): component blade, app.css, plus call sites found by grep

## Problem

The public shared modal (`x-booking.ui.modal`) enters with a 150ms pop but exits by
teleport — the close button adds `.hidden` synchronously, so the dialog vanishes in one
frame. The backdrop's `transition-opacity duration-300` is dead code (a `display:none`
toggle never transitions):

```html
<!-- resources/views/components/booking/ui/modal.blade.php:18-24 — current -->
<div id="{{ $id }}"
     class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-clsu-950/50 backdrop-blur-sm transition-opacity duration-300 hidden"
     aria-modal="true" role="dialog">
    <div class="bg-white rounded-3xl shadow-2xl w-full {{ $sizeClass }} overflow-hidden flex flex-col border border-stone-100 max-h-[90vh] animate-pop">

<!-- line 29-31 — the teleport close -->
<button type="button" … onclick="document.getElementById('{{ $id }}').classList.add('hidden')" …>
```

Dismissable surfaces should exit the way they entered, faster (asymmetric timing).
The admin console already has this exact contract: `data-closing` + a ~150ms CSS exit +
delayed `display:none` (resources/views/layouts/admin.blade.php:86-99 and
resources/css/admin/04-components.css:515-521).

## Target

A `window.pubModalClose(id)` helper on the public layout and a `[data-closing]` CSS exit —
exit runs at 140ms `ease-out` (faster than the 150ms entrance, and from the settled state):

```css
/* target — resources/css/app.css, next to the .animate-pop rules (~line 922) */
/* Public modal animated exit — pubModalClose() sets [data-closing], waits 140ms,
   then hides (mirrors the admin data-closing contract in 04-components.css). */
[data-closing].pub-modal { transition: opacity 140ms cubic-bezier(0.22, 1, 0.36, 1); opacity: 0; }
[data-closing].pub-modal > div {
    transition: opacity 140ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 140ms cubic-bezier(0.22, 1, 0.36, 1);
    opacity: 0; transform: translateY(-6px) scale(.98);
}
@media (prefers-reduced-motion: reduce) {
    [data-closing].pub-modal, [data-closing].pub-modal > div { transition: none; }
}
```

```js
// target — resources/views/layouts/public/base.blade.php, in an existing inline script block
window.pubModalClose = function (id) {
    const el = document.getElementById(id);
    if (!el || el.classList.contains('hidden') || el.hasAttribute('data-closing')) return;
    el.setAttribute('data-closing', '');
    setTimeout(function () {
        el.classList.add('hidden');
        el.removeAttribute('data-closing');
    }, 140);
};
```

```html
<!-- target — component wrapper gains .pub-modal; close button calls the helper -->
class="pub-modal fixed inset-0 z-[70] … hidden"
…
onclick="window.pubModalClose('{{ $id }}')"
```

## Repo conventions to follow

- The exit values (140ms, exit-faster-than-entry, `translateY` + slight scale, never
  `scale(0)`) mirror the admin modal: resources/css/admin/04-components.css:517-521.
- The curve is the repo's boutique ease `cubic-bezier(0.22, 1, 0.36, 1)` — the same one
  `.press` uses (app.css:803).
- Reduced-motion on public follows the scoped pattern (app.css:929-954): drop movement,
  never a universal nuke.

## Steps

1. **resources/views/components/booking/ui/modal.blade.php**:
   - Add `pub-modal` as the first class on the wrapper div (line 19). Remove the dead
     `transition-opacity duration-300` from that class list while there.
   - Change the close button's `onclick` (line 31) to `window.pubModalClose('{{ $id }}')`.
2. Find other places that close these modals the teleport way:
   `rg -n "classList.add\('hidden'\)" resources/views/public resources/views/components/booking resources/views/partials`
   — for each hit that targets a modal wrapper rendered by this component, switch it to
   `window.pubModalClose('<id>')`. (Backdrop-click or Escape handlers included.)
3. **resources/views/layouts/public/base.blade.php** — add the `pubModalClose` helper above
   (put it in the existing early inline `<script>` block so it exists before any page
   markup references it).
4. **resources/css/app.css** — add the CSS block above next to `.animate-pop` (~line 922).
5. Rebuild: `npm run build`, then `php artisan view:clear && php artisan view:cache`.

## Boundaries

- Do NOT touch the entrance (`animate-pop` stays as is).
- Do NOT convert the component to Alpine — keep the vanilla contract.
- Do NOT touch admin modals or `window.closeModal` (admin layout) — they already work.
- If a call site closes a modal that is NOT this component (different markup), leave it
  and note it in the report rather than improvising.

## Verification

- **Mechanical**: `npm run build` + `php artisan view:cache` succeed; grep from Step 2
  returns no remaining modal-teleport closes for this component.
- **Feel check**: open a page using the component (e.g. the room details modal on the
  landing page, or any `<x-booking.ui.modal>` page):
  - Close via ✕: the card slides up ~6px and fades over ~140ms; the backdrop fades with it.
  - Spam open/close rapidly: no stuck `data-closing` state, reopening always works
    (the guard returns early while closing).
  - DevTools → Rendering → `prefers-reduced-motion: reduce`: close is instant, no movement.
- **Done when**: exit visibly animates, is faster than the entrance, and rapid toggling
  never wedges the modal.
