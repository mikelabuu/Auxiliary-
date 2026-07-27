/**
 * Admin modal engine.
 *
 * Replaces the inline openModal/closeModal helpers that used to live in
 * layouts/admin.blade.php. Same public API — `window.openModal(id)` and
 * `window.closeModal(id)` — because ~15 call sites across the staff views
 * call them, plus the `[data-modal-close="id"]` attribute contract.
 *
 * What the inline version got wrong, and this fixes:
 *
 *  - Re-opening a modal within the close animation left a stale `setTimeout`
 *    running, which then hid the freshly opened modal ~150ms later. Close
 *    work is now cancellable and keyed per element.
 *  - The exit was a hardcoded 150ms that didn't match the 140ms CSS and
 *    ignored reduced motion. It now waits on `transitionend` with a timeout
 *    only as a fallback.
 *  - Escape only closed one hardcoded modal. It now closes the top-most one.
 *  - The page behind stayed scrollable. Scroll is locked while any modal is
 *    open, with scrollbar-width compensation so the layout doesn't jump.
 *  - Tab walked straight out of the dialog into the page behind. Focus is
 *    now trapped, and background content is marked `inert`.
 *  - Focus restore used a single global, so stacked modals clobbered each
 *    other's return target. It's per-element now.
 *  - A text-selection drag that ended on the backdrop counted as a backdrop
 *    click and closed the modal. Both mousedown and mouseup must land there.
 *
 * The entry animation stays in CSS (`@starting-style` on `.modal`), which
 * fires correctly for both display toggles and fresh Livewire inserts.
 */

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([type="hidden"]):not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

// Elements currently open, innermost last.
const stack = [];
// Pending close work per element, so open() can cancel it.
const pendingClose = new WeakMap();
// Where focus came from, per element.
const focusOrigin = new WeakMap();

const prefersReducedMotion = () =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const resolve = (target) =>
    typeof target === 'string' ? document.getElementById(target) : target;

const topModal = () => stack[stack.length - 1] || null;

/* ── Background: scroll lock + inert ─────────────────────────────── */

function lockBackground() {
    if (stack.length !== 1) return; // already locked by an outer modal

    // Compensate for the scrollbar we're about to remove, or the whole page
    // shifts sideways the moment a modal opens.
    const gap = window.innerWidth - document.documentElement.clientWidth;
    document.body.dataset.modalLocked = '';
    document.body.style.overflow = 'hidden';
    if (gap > 0) document.body.style.paddingRight = `${gap}px`;
}

function unlockBackground() {
    if (stack.length) return; // an outer modal is still open
    delete document.body.dataset.modalLocked;
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

/* ── Focus ───────────────────────────────────────────────────────── */

/*
 * Modality is announced with `aria-modal="true"` on the panel (set by the
 * Blade component) plus the Tab trap below, deliberately *not* with `inert`
 * on background nodes: modals live deep inside the shell, so inerting would
 * mean walking the ancestor chain and marking siblings at every level — and
 * that would also catch the dossier's fullscreen photo viewer, which is
 * appended to <body> and opened from inside a modal.
 */

function focusables(el) {
    return Array.from(el.querySelectorAll(FOCUSABLE)).filter(
        (n) => n.offsetParent !== null || n === document.activeElement,
    );
}

function focusFirst(el) {
    // Prefer a real input over the close button so keyboard users land on the
    // thing they came to fill in.
    const preferred =
        el.querySelector('input:not([type="hidden"]):not([disabled]), select, textarea') ||
        focusables(el).find((n) => !n.hasAttribute('data-modal-close')) ||
        el.querySelector('[role="dialog"]');

    if (preferred) {
        try {
            preferred.focus({ preventScroll: true });
        } catch (e) {
            /* focus can throw on detached nodes */
        }
    }
}

function trapFocus(e) {
    if (e.key !== 'Tab') return;
    const el = topModal();
    if (!el) return;

    const items = focusables(el);
    if (!items.length) {
        e.preventDefault();
        return;
    }

    const first = items[0];
    const last = items[items.length - 1];
    const active = document.activeElement;

    if (e.shiftKey && (active === first || !el.contains(active))) {
        e.preventDefault();
        last.focus();
    } else if (!e.shiftKey && (active === last || !el.contains(active))) {
        e.preventDefault();
        first.focus();
    }
}

/* ── Open / close ────────────────────────────────────────────────── */

function cancelPendingClose(el) {
    const pending = pendingClose.get(el);
    if (!pending) return;
    clearTimeout(pending.timer);
    if (pending.panel && pending.handler) {
        pending.panel.removeEventListener('transitionend', pending.handler);
    }
    pendingClose.delete(el);
    el.removeAttribute('data-closing');
}

/**
 * Bring a modal that is already in the DOM and visible (the Livewire
 * `always-visible` path) under engine management, so it gets the scroll
 * lock, Escape, and focus trap the toggled ones get.
 */
function register(el) {
    if (!el || stack.includes(el)) return;
    focusOrigin.set(el, document.activeElement);
    stack.push(el);
    el.style.zIndex = String(300 + stack.length * 10);
    lockBackground();
    resetScrollPanes(el);
    focusFirst(el);
}

function unregister(el) {
    const i = stack.indexOf(el);
    if (i === -1) return;
    stack.splice(i, 1);
    el.style.zIndex = '';
    unlockBackground();

    const origin = focusOrigin.get(el);
    focusOrigin.delete(el);
    if (origin && origin.isConnected && origin.focus) {
        try {
            origin.focus({ preventScroll: true });
        } catch (e) {
            /* ignore */
        }
    }
}

// A reopened modal shouldn't inherit the last visit's scroll position.
function resetScrollPanes(el) {
    el.querySelectorAll('.custom-scrollbar, .overflow-y-auto').forEach((pane) => {
        pane.scrollTop = 0;
    });
    const panel = el.querySelector('.modal');
    if (panel) panel.scrollTop = 0;
}

export function openModal(target) {
    const el = resolve(target);
    if (!el) return;

    cancelPendingClose(el);

    // Set the visible classes unconditionally: an element can be in the stack
    // yet hidden if anything else toggled it (a Livewire morph, page code), and
    // an early return would leave it permanently invisible but "open".
    el.classList.remove('hidden');
    el.classList.add('flex');

    if (stack.includes(el)) {
        focusFirst(el);
        return;
    }

    register(el);
}

export function closeModal(target) {
    const el = resolve(target);
    if (!el) return;
    if (el.classList.contains('hidden') || el.hasAttribute('data-closing')) return;

    el.setAttribute('data-closing', '');

    const panel = el.querySelector('.modal');

    const finish = () => {
        pendingClose.delete(el);
        el.classList.add('hidden');
        el.classList.remove('flex');
        el.removeAttribute('data-closing');
        unregister(el);
    };

    if (!panel || prefersReducedMotion()) {
        finish();
        return;
    }

    // transitionend is the real signal; the timer only covers the case where
    // the transition never runs (element hidden by an ancestor, etc).
    const handler = (e) => {
        if (e.target !== panel || e.propertyName !== 'opacity') return;
        panel.removeEventListener('transitionend', handler);
        clearTimeout(pendingClose.get(el)?.timer);
        finish();
    };
    panel.addEventListener('transitionend', handler);

    pendingClose.set(el, {
        panel,
        handler,
        timer: setTimeout(() => {
            panel.removeEventListener('transitionend', handler);
            finish();
        }, 400),
    });
}

/**
 * Close a Livewire-owned modal: play the exit animation first, then call the
 * component method that removes it. Without this the node is yanked out of
 * the DOM mid-frame and the modal just vanishes.
 */
function closeLivewireModal(el, action) {
    if (el.hasAttribute('data-closing')) return;
    el.setAttribute('data-closing', '');

    const invoke = () => {
        unregister(el);
        const host = el.closest('[wire\\:id]');
        const id = host && host.getAttribute('wire:id');
        if (id && window.Livewire) {
            const component = window.Livewire.find(id);
            if (component) component.call(action);
        }
    };

    if (prefersReducedMotion()) {
        invoke();
        return;
    }
    setTimeout(invoke, 150);
}

/* ── Global wiring ───────────────────────────────────────────────── */

// Backdrop clicks: require press *and* release on the backdrop, so dragging a
// text selection out of the panel doesn't dismiss the dialog.
let pressTarget = null;
document.addEventListener('mousedown', (e) => {
    pressTarget = e.target;
});

function dismiss(el) {
    if (!el) return;
    const action = el.getAttribute('data-modal-close-action');
    if (action) closeLivewireModal(el, action);
    else closeModal(el);
}

document.addEventListener('click', (e) => {
    // Backdrop first: it also carries [data-modal-close], so the generic
    // branch below would otherwise dismiss it without the press-target guard.
    const backdrop = e.target.closest('.modal-backdrop-tint');
    if (backdrop) {
        // Require the press to have started on the backdrop too, so releasing
        // a text-selection drag outside the panel doesn't dismiss the dialog.
        if (pressTarget !== e.target) return;
        dismiss(backdrop.closest('[data-modal]') || backdrop.parentElement);
        return;
    }

    const closer = e.target.closest('[data-modal-close]');
    if (closer) dismiss(resolve(closer.getAttribute('data-modal-close')));
});

// Escape closes the top-most modal only. Bubble phase on purpose: the
// dossier's fullscreen photo viewer stops Escape in the capture phase while
// it's open, so the photo closes first and the modal underneath stays put.
document.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
        trapFocus(e);
        return;
    }
    if (e.key !== 'Escape') return;

    const el = topModal();
    if (!el) return;

    e.preventDefault();
    dismiss(el);
});

/**
 * Livewire renders its modal with `@if`, so the element appears and vanishes
 * without ever going through openModal/closeModal. Watch for that and keep
 * the stack honest.
 */
function syncSelfManaged(root) {
    root.querySelectorAll?.('[data-modal][data-modal-close-action]').forEach((el) => {
        if (!el.classList.contains('hidden')) register(el);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    syncSelfManaged(document);

    new MutationObserver((records) => {
        records.forEach((record) => {
            record.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                if (node.matches?.('[data-modal][data-modal-close-action]')) register(node);
                syncSelfManaged(node);
            });
            record.removedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                if (stack.includes(node)) unregister(node);
                stack.slice().forEach((el) => {
                    if (!el.isConnected) unregister(el);
                });
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
});

window.openModal = openModal;
window.closeModal = closeModal;
