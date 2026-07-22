import './bootstrap';
import './sortable-tables';
import './animated-content';
import './expandable-bento';
import { createIcons } from 'lucide';
import * as icons from 'lucide';

// Initialize Lucide icons globally
createIcons({ icons });

// ── Unified toast notifications (admin + frontdesk consoles) ──
// window.toast(message, type?, { duration? }) — types: success (default),
// error, warning, info. SweetAlert icon names map 1:1 so legacy per-page
// helpers can delegate. Styles live in resources/css/admin/11-notify-nav.css.
(function () {
    const DURATIONS = { success: 3400, info: 4000, warning: 4600, error: 5200 };
    const ICONS = {
        success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
        error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12.5"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    };
    const CLOSE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

    function stack() {
        let el = document.getElementById('toastStack');
        if (!el) {
            el = document.createElement('div');
            el.id = 'toastStack';
            el.className = 'toast-stack';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        return el;
    }

    window.toast = function (message, type, opts) {
        if (!message) return;
        type = type === 'danger' ? 'error' : (type || 'success');
        if (!ICONS[type]) type = 'info';
        const host = stack();

        // Keep the stack shallow — drop the oldest notice past four
        while (host.children.length >= 4) host.firstElementChild.remove();

        const life = (opts && opts.duration) || DURATIONS[type];
        const slot = document.createElement('div');
        slot.className = 'toast-slot';
        const card = document.createElement('div');
        card.className = 'toast toast-' + type;
        card.innerHTML =
            '<span class="toast-icon">' + ICONS[type] + '</span>' +
            '<p class="toast-msg"></p>' +
            '<button type="button" class="toast-close" aria-label="Dismiss notification">' + CLOSE + '</button>' +
            '<span class="toast-life" style="animation-duration:' + life + 'ms"></span>';
        card.querySelector('.toast-msg').textContent = String(message);
        slot.appendChild(card);
        host.appendChild(slot);

        let closed = false;
        let remaining = life;
        let started = Date.now();
        let timer = setTimeout(dismiss, remaining);

        function dismiss() {
            if (closed) return;
            closed = true;
            clearTimeout(timer);
            slot.setAttribute('data-closing', '');
            setTimeout(function () { slot.remove(); }, 260);
        }

        card.addEventListener('mouseenter', function () {
            if (closed) return;
            clearTimeout(timer);
            remaining -= Date.now() - started;
            card.classList.add('is-paused');
        });
        card.addEventListener('mouseleave', function () {
            if (closed) return;
            started = Date.now();
            timer = setTimeout(dismiss, Math.max(600, remaining));
            card.classList.remove('is-paused');
        });
        card.querySelector('.toast-close').addEventListener('click', dismiss);
        return dismiss;
    };

    // Livewire components toast without a redirect:
    //   $this->dispatch('toast', message: '…', type: 'success');
    document.addEventListener('livewire:init', function () {
        if (!window.Livewire || !window.Livewire.on) return;
        window.Livewire.on('toast', function (params) {
            const p = Array.isArray(params) ? params[0] : params;
            if (typeof p === 'string') return window.toast(p);
            if (p) window.toast(p.message, p.type, { duration: p.duration });
        });
        // First Livewire commit = the user is interacting; retire table entrance
        // choreography for the rest of the page's life (see 05-motion-ux.css).
        if (window.Livewire.hook) {
            window.Livewire.hook('commit', function (ctx) {
                if (ctx && ctx.succeed) ctx.succeed(function () { document.body.classList.add('lw-updated'); });
            });
        }
    });
})();

// ── Scroll-shadow watcher for admin/frontdesk tables ──
// Tables with a pinned Actions column (.scroll-x > .data-table) only get the
// column's separator shadow while the table is actually clipped and not
// scrolled to its end; admin.css gates the shadow on .scroll-x.is-clipped.
(function () {
    let raf = null;

    function update() {
        raf = null;
        document.querySelectorAll('.scroll-x').forEach((el) => {
            const clipped = el.scrollWidth - el.clientWidth > 1;
            const atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;
            el.classList.toggle('is-clipped', clipped && !atEnd);
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
