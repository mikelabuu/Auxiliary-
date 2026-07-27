/**
 * Real-time refresh for plain server-rendered staff screens.
 *
 * The Livewire consoles react to broadcasts by dispatching a component refresh,
 * and the admin Rooms page patches individual cards from its status-feed
 * endpoint. The front-desk screens are neither — they're straight Blade renders
 * with no feed endpoint of their own, so the honest way to make them live is to
 * re-request the page.
 *
 * Reloading a screen out from under someone mid-task is worse than stale data,
 * so a refresh is deferred while the user is demonstrably busy: a modal open, a
 * SweetAlert prompt up, a field focused, or any form already filled in. The
 * attempt re-arms every couple of seconds and fires the moment they're clear.
 *
 * Usage from a Blade @push('scripts') block:
 *
 *   window.liveRefresh([{ channel: 'rooms', event: 'RoomStatusChanged' }]);
 *
 * If Reverb isn't running, window.Echo never connects and this is inert — the
 * page keeps whatever manual refresh or polling it already had.
 */

const RETRY_MS = 2000;
const DEBOUNCE_MS = 600;

/** True when a reload would not destroy something the user is in the middle of. */
function safeToReload() {
    // An open modal means a task is in progress (a check-out confirmation, an
    // occupancy lookup); the modal engine marks these with [data-modal].
    if (document.querySelector('[data-modal]:not(.hidden)')) return false;

    // SweetAlert confirmations live outside the modal engine.
    if (document.querySelector('.swal2-container')) return false;

    const active = document.activeElement;
    if (active && /^(INPUT|SELECT|TEXTAREA)$/.test(active.tagName)) return false;

    // A partially completed form (walk-in booking, filters) outranks freshness.
    const dirty = Array.from(document.querySelectorAll('form input, form select, form textarea'))
        .some((field) => {
            if (field.type === 'hidden' || field.disabled) return false;
            if (field.type === 'checkbox' || field.type === 'radio') return field.checked !== field.defaultChecked;
            return field.value !== field.defaultValue && field.value !== '';
        });

    return !dirty;
}

window.liveRefresh = function liveRefresh(subscriptions, options = {}) {
    if (!window.Echo || !Array.isArray(subscriptions) || !subscriptions.length) return;

    const debounce = options.delay ?? DEBOUNCE_MS;
    let debounceTimer = null;
    let waiting = false;

    function attempt() {
        if (!safeToReload()) {
            waiting = true;
            setTimeout(attempt, RETRY_MS);
            return;
        }
        waiting = false;
        window.location.reload();
    }

    function schedule() {
        if (waiting) return; // an attempt loop is already running
        clearTimeout(debounceTimer);
        // Coalesce the burst of events a single action emits (a check-out
        // sends BookingChanged and RoomStatusChanged together).
        debounceTimer = setTimeout(attempt, debounce);
    }

    subscriptions.forEach(({ channel, event }) => {
        if (!channel || !event) return;
        window.Echo.channel(channel).listen('.' + event, schedule);
    });
};
