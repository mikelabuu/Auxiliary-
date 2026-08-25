/**
 * Live desk alerts — the client half of App\Events\StaffNotification.
 *
 * Subscribes to the PRIVATE `staff.alerts` channel (authorised in
 * routes/channels.php against the staff guard) and does two things with every
 * alert that lands:
 *
 *   1. pops a card, top-right, via window.staffAlert() — styles in
 *      resources/css/admin/15-alert-popup.css
 *   2. re-broadcasts it as a `staff-alert` CustomEvent on window, which the
 *      topbar's Alpine component listens for to prepend the row and bump the
 *      unread badge
 *
 * Why a CustomEvent instead of calling into Alpine: the topbar owns its own
 * read-state and ordering rules, and this file should not have to know them.
 * It also means the bell keeps working if this module is ever loaded on a
 * layout that has no bell, and vice versa.
 *
 * Everything here is inert when it should be:
 *   · no window.Echo (Reverb not running, or a public page) → nothing happens
 *   · no [data-staff-alerts] marker on <body> → we are not in a staff console,
 *     so we never even attempt the private-channel auth handshake (an
 *     unauthenticated /broadcasting/auth call would just 403 in the console)
 *
 * The screens keep every fallback they already had — the Livewire panels still
 * re-query on their own events, live-refresh.js still reloads plain Blade
 * screens. This is additive.
 */

const LIFE = { success: 7000, info: 7000, warning: 9000, error: 10000 };
const MAX_ON_SCREEN = 3;

const ICONS = {
    booking: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1.5a1.5 1.5 0 0 0 0 3V16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2.5a1.5 1.5 0 0 0 0-3V9z"/></svg>',
    payment: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/><path d="m8.5 15 2 2 4-4"/></svg>',
    discount: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 12l9 9 10-10V2z"/><circle cx="7.5" cy="7.5" r="1.4"/></svg>',
    maintenance: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
    // A clock, not the door glyph the bell uses: at this size the door reads as
    // the arrival icon flipped, and arrival/departure confusion is the one
    // mistake this alert exists to prevent.
    checkout_due: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>',
    // A calendar with an arrow through it: the stay is not being cancelled,
    // it is being moved, and those are two very different desk actions.
    reschedule: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/><path d="M9 16h6m-2-2 2 2-2 2"/></svg>',
};

// Which bell tile each alert type wears — same three tiles the dropdown uses.
const TILES = {
    booking: 'notif-icon-gold',
    payment: 'notif-icon-green',
    discount: 'notif-icon-green',
    maintenance: 'notif-icon-rose',
    // Gold, not rose. Rose is what the console uses for something already
    // wrong; a check-out that is merely due is on schedule.
    checkout_due: 'notif-icon-gold',
    // Gold for the same reason: a guest asking to move a stay is a decision
    // waiting, not something that has already gone wrong.
    reschedule: 'notif-icon-gold',
};

const ARROW = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
const CLOSE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

function stack() {
    let el = document.getElementById('alertStack');
    if (!el) {
        el = document.createElement('div');
        el.id = 'alertStack';
        el.className = 'alert-stack';
        // 'polite', not 'assertive': these interrupt nothing. A screen-reader
        // user mid-sentence on a check-in should finish the sentence.
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        document.body.appendChild(el);
    }
    return el;
}

/**
 * Pop one alert card.
 *
 * @param {{type?:string,title?:string,text:string,url?:string,level?:string}} a
 * @returns {function(): void} dismiss
 */
window.staffAlert = function staffAlert(a) {
    if (!a || !a.text) return function () {};

    const type = ICONS[a.type] ? a.type : 'booking';
    const level = ['success', 'info', 'warning', 'error'].includes(a.level) ? a.level : 'info';
    const host = stack();

    // Keep the corner shallow. Three is enough to show a burst is happening
    // without burying the page; the bell holds the rest.
    while (host.children.length >= MAX_ON_SCREEN) host.firstElementChild.remove();

    const life = LIFE[level];
    const slot = document.createElement('div');
    slot.className = 'alert-slot';

    // An <a> when there is somewhere to go, a <div> when there isn't — so the
    // card is never a link that does nothing.
    const card = document.createElement(a.url ? 'a' : 'div');
    card.className = 'alert-card alert-' + level;
    if (a.url) card.href = a.url;

    card.innerHTML =
        '<div class="alert-head">' +
            '<div class="notif-icon ' + TILES[type] + '">' + ICONS[type] + '</div>' +
            '<div class="alert-body">' +
                '<p class="alert-title"><span data-alert-title></span><span class="alert-time">just now</span></p>' +
                '<p class="alert-text" data-alert-text></p>' +
                (a.url ? '<span class="alert-cta">View' + ARROW + '</span>' : '') +
            '</div>' +
        '</div>' +
        '<button type="button" class="alert-close" aria-label="Dismiss alert">' + CLOSE + '</button>' +
        '<span class="alert-life" style="animation-duration:' + life + 'ms"></span>';

    // textContent, never innerHTML — guest names reach this card, and a guest
    // controls their own name. The room-name XSS fixed in ccde1d8 was the same
    // shape of bug.
    card.querySelector('[data-alert-title]').textContent = a.title || 'Update';
    card.querySelector('[data-alert-text]').textContent = String(a.text);

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

    // Hovering is reading. Don't yank it away mid-name.
    card.addEventListener('mouseenter', function () {
        if (closed) return;
        clearTimeout(timer);
        remaining -= Date.now() - started;
        card.classList.add('is-paused');
    });
    card.addEventListener('mouseleave', function () {
        if (closed) return;
        started = Date.now();
        timer = setTimeout(dismiss, Math.max(1200, remaining));
        card.classList.remove('is-paused');
    });
    card.addEventListener('focusin', function () {
        clearTimeout(timer);
        card.classList.add('is-paused');
    });

    card.querySelector('.alert-close').addEventListener('click', function (e) {
        // The card itself is the link; the X must not follow it.
        e.preventDefault();
        e.stopPropagation();
        dismiss();
    });

    return dismiss;
};

// ── Reverb subscription ──────────────────────────────────────────────────────
(function () {
    function connect() {
        // Marker set by layouts/admin + layouts/frontdesk. Its absence means
        // this page has no staff session, so the private-channel handshake
        // would 403 — don't make it.
        if (!document.body || !document.body.hasAttribute('data-staff-alerts')) return;
        if (!window.Echo) return;

        const seen = new Set();

        window.Echo.private('staff.alerts').listen('.StaffNotification', function (payload) {
            if (!payload || !payload.id) return;

            // Reverb can redeliver, and a burst of writes in one request can
            // legitimately produce the same alert twice. The id is stable, so
            // de-dupe on it rather than showing the desk the same thing twice.
            if (seen.has(payload.id)) return;
            seen.add(payload.id);

            window.staffAlert(payload);

            // The bell owns its own list — hand it the payload and let it
            // decide where the row goes and whether it counts as unread.
            window.dispatchEvent(new CustomEvent('staff-alert', { detail: payload }));

            // An alert that arrived this way did not come from the poll, so
            // the sidebar's queue counts are now a push behind. Ask the poller
            // for fresh ones rather than deriving them from the payload: one
            // alert does not map to one badge (verifying a proof moves two of
            // them), and the server is the only thing that knows.
            window.dispatchEvent(new CustomEvent('staff-counts-stale'));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', connect);
    } else {
        connect();
    }
})();

// ── Polling fallback ─────────────────────────────────────────────────────────
// The bell used to update only when someone reloaded the page. Reverb was meant
// to be what kept it live, but it needs a daemon and a WebSocket proxy this
// host cannot currently keep running, so in practice the desk had to refresh to
// find out anything had happened.
//
// This asks the server for the same list the topbar rendered at page load and
// feeds anything new through the exact two side effects the Reverb path uses —
// a toast and a `staff-alert` window event. The topbar de-dupes on the stable
// id, so this and Reverb can both be live without showing anything twice, and
// whichever gets there first wins.
(function () {
    const INTERVAL = 20000;
    const ENDPOINT = '/staff/notifications/feed';

    function start() {
        // Same gate the Reverb half uses: no staff session, no polling.
        if (!document.body || !document.body.hasAttribute('data-staff-alerts')) return;

        const seen = new Set();
        let primed = false;      // the first response is the page's own state
        let inFlight = false;
        let timer = null;

        async function poll() {
            // A background tab does not need alerts badly enough to keep six
            // queries running; visibilitychange below catches it up on return.
            if (inFlight || document.hidden) return;
            inFlight = true;

            try {
                const res = await fetch(ENDPOINT, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                // Session gone: stop rather than hammer a login redirect every
                // 20 seconds for the rest of the tab's life.
                if (res.status === 401 || res.status === 419 || res.status === 403) {
                    if (timer) clearInterval(timer);
                    return;
                }
                if (!res.ok) return;

                const data = await res.json();
                const items = Array.isArray(data.items) ? data.items : [];

                // The sidebar's queue badges ride along on this response —
                // see App\Support\StaffAlerts::pendingCounts(). Dispatched
                // rather than applied here for the same reason the bell gets a
                // CustomEvent: this module should not know what the sidebar
                // looks like. resources/js/sidebar-counts.js listens.
                if (data.counts) {
                    window.dispatchEvent(new CustomEvent('staff-counts', { detail: data.counts }));
                }

                // Oldest first: the topbar prepends, so dispatching in reverse
                // leaves the newest alert at the top of the list.
                items.slice().reverse().forEach(function (item) {
                    if (!item || !item.id) return;

                    const isNew = !seen.has(item.id);
                    seen.add(item.id);

                    // Don't pop eight toasts for the things already on screen
                    // when the page loaded — only for what arrives after.
                    if (isNew && primed && typeof window.staffAlert === 'function') {
                        window.staffAlert(item);
                    }

                    window.dispatchEvent(new CustomEvent('staff-alert', { detail: item }));
                });

                primed = true;
            } catch (e) {
                // A dropped poll is not worth surfacing to the desk; the next
                // tick retries and the bell is still showing the last good list.
            } finally {
                inFlight = false;
            }
        }

        poll();
        timer = setInterval(poll, INTERVAL);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) poll();
        });

        // Reverb asking for fresh counts. Debounced, because one desk action
        // can broadcast several alerts and they must not become several polls.
        let nudge = null;
        window.addEventListener('staff-counts-stale', function () {
            clearTimeout(nudge);
            nudge = setTimeout(poll, 800);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
