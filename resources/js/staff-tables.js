/**
 * Staff-console table + photo chrome — admin and front desk only.
 *
 * Both behaviours below used to live in app.js, which the public site also
 * loads. Neither has a single consumer on a guest page: `.scroll-x` appears
 * only in the staff consoles, and `[data-facility-photo]` only in the booking
 * dossier. They were costing every visitor a MutationObserver over <body> and
 * two delegated document listeners for markup that never renders. Moved here so
 * admin.js owns them and the landing page stops paying for them.
 */

// ── Scroll-shadow watcher for admin/frontdesk tables ──
// Tables with a pinned Actions column (.scroll-x > .data-table) only get the
// column's separator shadow while the table is actually clipped and not
// scrolled to its end; admin.css gates the shadow on .scroll-x.is-clipped.
(function () {
    let raf = null;

    // A clipped region holding nothing focusable is mouse-only: there is no
    // tab stop inside it, so the columns past the right edge cannot be reached
    // by keyboard at all (axe `scrollable-region-focusable`). Give those, and
    // only those, a tab stop — tables whose rows carry View/Checkout buttons
    // already scroll on tab and would just collect a redundant one. The name
    // comes off the panel heading so it announces as "Active Stays" rather
    // than an anonymous region.
    function keyboardAccess(el, clipped) {
        const needed = clipped && !el.querySelector('a, button, input, select, textarea, [tabindex]');

        if (!needed) {
            if (el.dataset.scrollFocusable) {
                el.removeAttribute('tabindex');
                el.removeAttribute('role');
                el.removeAttribute('aria-label');
                delete el.dataset.scrollFocusable;
            }
            return;
        }
        if (el.dataset.scrollFocusable) return;

        const title = el.closest('.card')?.querySelector('.card-title')?.textContent.replace(/\s+/g, ' ').trim();
        el.setAttribute('tabindex', '0');
        el.setAttribute('role', 'region');
        el.setAttribute('aria-label', title ? `${title}, scrollable` : 'Scrollable table');
        el.dataset.scrollFocusable = '1';
    }

    function update() {
        raf = null;
        document.querySelectorAll('.scroll-x').forEach((el) => {
            const clipped = el.scrollWidth - el.clientWidth > 1;
            const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;
            el.classList.toggle('is-clipped', clipped && !atEnd);
            keyboardAccess(el, clipped);
        });
    }

    function schedule() {
        if (!raf) raf = requestAnimationFrame(update);
    }

    // scroll doesn't bubble — catch it in the capture phase
    document.addEventListener('scroll', (e) => {
        if (e.target instanceof Element && e.target.classList.contains('scroll-x')) schedule();
    }, true);
    window.addEventListener('resize', schedule, { passive: true });

    document.addEventListener('DOMContentLoaded', () => {
        update();
        // Livewire morphs and JS-built tables appear after load
        new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
    });
})();

// ── Fullscreen viewer for the booking dossier's facility photos ──
// Lives in the bundle rather than beside the markup because the dossier
// reaches the page three ways: a Livewire DOM patch (which does not execute
// plain <script> tags), an AJAX HTML injection, and a normal render. Delegated
// from `document` so all three are covered by one listener.
//
// The overlay is appended to <body> on purpose: .modal carries a transform,
// which makes it the containing block for position:fixed descendants, so a
// viewer nested inside the modal would be clipped to the panel.
(function () {
    let overlay = null;
    let img = null;
    let caption = null;
    let lastFocus = null;

    function build() {
        overlay = document.createElement('div');
        overlay.className = 'fh-lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.innerHTML =
            '<button type="button" class="fh-lightbox-close" aria-label="Close photo">&times;</button>' +
            '<figure><img alt=""><figcaption></figcaption></figure>';
        document.body.appendChild(overlay);
        img = overlay.querySelector('img');
        caption = overlay.querySelector('figcaption');
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.closest('.fh-lightbox-close')) close();
        });
    }

    function open(src, title) {
        if (!overlay) build();
        lastFocus = document.activeElement;
        img.src = src;
        img.alt = title;
        caption.textContent = title;
        overlay.classList.add('is-open');
        overlay.querySelector('.fh-lightbox-close').focus();
        document.addEventListener('keydown', onKey, true);
    }

    function close() {
        if (!overlay) return;
        overlay.classList.remove('is-open');
        document.removeEventListener('keydown', onKey, true);
        if (lastFocus && lastFocus.focus) lastFocus.focus();
    }

    function onKey(e) {
        if (e.key !== 'Escape') return;
        // Capture phase + stop: Escape closes the photo, not the booking modal
        // underneath it.
        e.stopPropagation();
        close();
    }

    document.addEventListener('click', (e) => {
        const tile = e.target.closest('[data-facility-photo]');
        if (!tile) return;
        e.preventDefault();
        open(tile.getAttribute('data-facility-photo'), tile.getAttribute('data-facility-title') || '');
    });
})();
