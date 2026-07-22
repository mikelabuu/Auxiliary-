/**
 * Bento detail dialog — a grid of cards; clicking one opens its detail in a
 * centered dialog that fades + scales in over a dimmed backdrop, then dismisses
 * on Esc / outside-click / close button.
 *
 * Deliberately simple: the clicked card NEVER moves, reparents, or leaves the
 * grid. The dialog is a separate body-level overlay whose content is copied (or
 * fetched) from the card. No shared-element morph / FLIP / placeholder, so the
 * grid can't reflow and there is nothing to glitch. Animation is a pure CSS
 * transition (see .bento-backdrop / .bento-dialog in 12-bento-blur.css), gated
 * to no-preference so reduced motion just appears instantly. No GSAP.
 *
 * Markup contract (unchanged from before):
 *   [data-bento-item]              a cell; the whole cell is the click target
 *     [data-bento-summary]         compact content (shown in the grid; also the
 *                                  dialog header unless data-bento-flush)
 *     [data-bento-detail]          detail content shown in the dialog
 *   data-bento-flush               drop the summary header in the dialog
 *   data-bento-narrow              narrower dialog
 *   data-bento-detail-src="<url>"  fetch detail on open (always fresh)
 *   data-bento-render="<name>"     window.BentoDetail.renderers[name](data, item) -> html
 *   [data-bento-close]             closes the dialog
 *   [data-bento-dismiss]           closes the dialog but lets the click through
 *                                  (e.g. a link that opens another modal)
 */

// Registry so feature code (e.g. the room map) can turn a fetched JSON payload
// into detail HTML without the engine knowing anything domain-specific.
window.BentoDetail = window.BentoDetail || { renderers: {} };

const state = { open: false, item: null, lastFocus: null };

function ensureBackdrop() {
    let b = document.querySelector('.bento-backdrop');
    if (b) return b;
    b = document.createElement('div');
    b.className = 'bento-backdrop';
    b.style.display = 'none';
    b.innerHTML =
        '<div class="bento-dialog" role="dialog" aria-modal="true" tabindex="-1">' +
            '<button type="button" class="bento-close" data-bento-close aria-label="Close">' +
                '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button>' +
            '<div class="bento-dialog-body"></div>' +
        '</div>';
    document.body.appendChild(b);
    // A click on the dim area (not the dialog) closes.
    b.addEventListener('click', (e) => { if (e.target === b) close(); });
    return b;
}

// Fill the dialog's detail area: clone the card's inline detail, or fetch it.
function renderDetailInto(item, container) {
    const src = item.getAttribute('data-bento-detail-src');
    if (!src) {
        const detail = item.querySelector('[data-bento-detail]');
        container.innerHTML = detail ? detail.innerHTML : '';
        return;
    }
    container.innerHTML = '<div class="bento-detail-state">Loading…</div>';
    fetch(src, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((r) => r.text())
        .then((txt) => {
            if (state.item !== item) return; // user moved on
            let data = null;
            try { data = JSON.parse(txt); } catch (_) { /* raw HTML */ }
            const rname = item.getAttribute('data-bento-render');
            let html;
            if (rname && typeof window.BentoDetail.renderers[rname] === 'function') {
                html = window.BentoDetail.renderers[rname](data != null ? data : txt, item);
            } else if (data && data.html) {
                html = data.html;
            } else {
                html = txt;
            }
            container.innerHTML = html;
        })
        .catch(() => { if (state.item === item) container.innerHTML = '<div class="bento-detail-state">Could not load details.</div>'; });
}

function open(item) {
    if (state.open) return;
    state.open = true;
    state.item = item;
    state.lastFocus = document.activeElement;

    const backdrop = ensureBackdrop();
    const dialog = backdrop.querySelector('.bento-dialog');
    const body = backdrop.querySelector('.bento-dialog-body');
    dialog.classList.toggle('bento-dialog--narrow', item.hasAttribute('data-bento-narrow'));

    // Header = the card's summary (unless flush); detail below.
    let header = '';
    if (!item.hasAttribute('data-bento-flush')) {
        const summary = item.querySelector('[data-bento-summary]');
        if (summary && summary.innerHTML.trim()) header = '<div class="bento-dialog-head">' + summary.innerHTML + '</div>';
    }
    body.innerHTML = header + '<div class="bento-dialog-detail"></div>';
    renderDetailInto(item, body.querySelector('.bento-dialog-detail'));

    item.setAttribute('aria-expanded', 'true');
    document.documentElement.classList.add('bento-lock');

    // Show, force a reflow, then activate — so the CSS transition runs from the
    // hidden (opacity 0 / scaled) state instead of snapping straight to shown.
    backdrop.style.display = 'flex';
    void backdrop.offsetWidth;
    backdrop.classList.add('is-active');

    const cb = backdrop.querySelector('.bento-close');
    if (cb) { try { cb.focus({ preventScroll: true }); } catch (e) {} }
}

function finishClose(keepDisplay) {
    const backdrop = document.querySelector('.bento-backdrop');
    const item = state.item;
    if (item) item.setAttribute('aria-expanded', 'false');
    document.documentElement.classList.remove('bento-lock');
    const lastFocus = state.lastFocus;
    state.open = false;
    state.item = null;
    state.lastFocus = null;
    if (backdrop && !keepDisplay) backdrop.style.display = 'none';
    if (lastFocus && !keepDisplay) { try { lastFocus.focus({ preventScroll: true }); } catch (e) {} }
}

function close() {
    if (!state.open) return;
    const backdrop = document.querySelector('.bento-backdrop');
    const item = state.item;
    const lastFocus = state.lastFocus;
    if (item) item.setAttribute('aria-expanded', 'false');
    document.documentElement.classList.remove('bento-lock');
    state.open = false;
    state.item = null;
    state.lastFocus = null;

    if (!backdrop) return;
    backdrop.classList.remove('is-active'); // fade out via CSS
    let done = false;
    const hide = () => {
        if (done) return;
        done = true;
        backdrop.style.display = 'none';
        backdrop.removeEventListener('transitionend', hide);
    };
    backdrop.addEventListener('transitionend', hide);
    setTimeout(hide, 320); // fallback if no transition fires
    if (lastFocus) { try { lastFocus.focus({ preventScroll: true }); } catch (e) {} }
}

// Snap shut with no fade — used when a click inside the dialog opens another
// layer (e.g. a guest-history modal) that must not sit under the backdrop.
function closeInstant() {
    if (!state.open) return;
    const backdrop = document.querySelector('.bento-backdrop');
    if (backdrop) { backdrop.classList.remove('is-active'); backdrop.style.display = 'none'; }
    finishClose(true);
}

// One delegated listener covers every grid on the page (and future ones).
document.addEventListener('click', (e) => {
    if (e.target.closest('[data-bento-close]')) { e.preventDefault(); close(); return; }
    // A control inside the open dialog that opens another layer: snap shut but
    // let the click through to its own handler.
    if (state.open && e.target.closest('[data-bento-dismiss]')) { closeInstant(); return; }
    if (state.open) return; // ignore grid clicks while a dialog is open
    const item = e.target.closest('[data-bento-item]');
    if (!item) return;
    // Let genuine controls inside a collapsed cell act normally.
    if (e.target.closest('a[href], button:not([data-bento-item]), input, select, textarea')) return;
    e.preventDefault();
    open(item);
});

// Keyboard: Enter/Space opens a focused cell; Esc closes the dialog.
document.addEventListener('keydown', (e) => {
    if ((e.key === 'Enter' || e.key === ' ') && !state.open) {
        const item = e.target.closest('[data-bento-item]');
        if (item && item === e.target) { e.preventDefault(); open(item); }
        return;
    }
    if (e.key === 'Escape' && state.open) { e.preventDefault(); e.stopImmediatePropagation(); close(); }
}, true);

// Close before a Livewire SPA swap tears the DOM out from under the dialog.
document.addEventListener('livewire:navigating', () => { if (state.open) closeInstant(); });

export { open as openBento, close as closeBento };
