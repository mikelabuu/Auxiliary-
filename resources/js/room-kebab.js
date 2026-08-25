/**
 * Keeps the room board's "Set status" menu inside the viewport.
 *
 * The panel is `position: absolute; top: 100%` inside its card, which is the
 * right default and wrong in two places:
 *
 *   · a card near the bottom of the viewport opens a menu that runs off the
 *     bottom of the screen, and the page cannot scroll to reveal it because
 *     the menu moves with the card
 *   · a card at the right-hand edge of a narrow grid opens a 176px panel that
 *     hangs past the edge of the page
 *
 * Both boards (components/admin/rooms/room-card and the front desk's
 * staff/frontdesk/rooms/index) share the panel through
 * components/admin/rooms/status-kebab, but each has its own click handler.
 * Rather than add a third place that has to be kept in step, this watches the
 * `hidden` class those handlers toggle — so it corrects any panel any board
 * opens, including ones re-rendered by the live status poller.
 *
 * The correction is undone on close, so a panel flipped upwards once because
 * its card was low on screen is not stuck that way after a scroll.
 */

const PANEL = '[data-kebab-panel]';
const GUTTER = 10;
const RETHROTTLE = 100;

/*
 * Last known open/closed state per panel.
 *
 * This is load-bearing, not a cache. The observer below watches `class`, and
 * positioning a panel *writes* to `class` (`is-up`) — so reacting to every
 * class mutation means every placement schedules another placement, forever,
 * and the tab locks up. Comparing against the remembered state means our own
 * writes, which never change `hidden`, are recognised as no-ops.
 */
const openState = new WeakMap();

function reset(panel) {
    panel.classList.remove('is-up');
    panel.style.removeProperty('--kebab-shift');
}

function place(panel) {
    reset(panel);

    // Read after the reset, so this measures the default position rather than
    // a correction left over from the last time the panel opened.
    const rect = panel.getBoundingClientRect();
    const vh = window.innerHeight || document.documentElement.clientHeight;
    const vw = window.innerWidth || document.documentElement.clientWidth;

    // Flip up only when there is genuinely more room above. On a viewport too
    // short for either direction, flipping just moves the problem, so the
    // panel stays put and scrolls instead (max-height, 16-room-board.css).
    //
    // The button has to be on screen for any of this to mean anything. A board
    // is taller than the viewport, so most cards' panels are "past the bottom"
    // at any moment simply by being further down the page; flipping those would
    // open them upwards for no reason the moment they scrolled into view.
    const btn = panel.parentElement && panel.parentElement.querySelector('.room-kebab-btn');
    const btnRect = btn ? btn.getBoundingClientRect() : rect;
    const onScreen = btnRect.bottom > 0 && btnRect.top < vh;
    const spaceBelow = vh - rect.top;
    const spaceAbove = btnRect.top;
    if (onScreen && rect.bottom > vh - GUTTER && spaceAbove > spaceBelow) {
        panel.classList.add('is-up');
    }

    // Horizontal nudge, handed to CSS as a custom property so the stylesheet
    // keeps ownership of the transform and the entrance animation survives.
    const after = panel.getBoundingClientRect();
    let shift = 0;
    if (after.right > vw - GUTTER) shift = vw - GUTTER - after.right;
    else if (after.left < GUTTER) shift = GUTTER - after.left;
    if (shift) panel.style.setProperty('--kebab-shift', Math.round(shift) + 'px');
}

function sync(panel) {
    const isHidden = panel.classList.contains('hidden');
    if (openState.get(panel) === isHidden) return;   // our own `is-up` write
    openState.set(panel, isHidden);
    if (isHidden) reset(panel);
    else place(panel);
}

function openPanels() {
    return Array.from(document.querySelectorAll(PANEL))
        .filter((p) => !p.classList.contains('hidden'));
}

function start() {
    if (!document.querySelector(PANEL)) return;

    document.querySelectorAll(PANEL).forEach((p) => {
        openState.set(p, p.classList.contains('hidden'));
    });

    new MutationObserver((records) => {
        records.forEach((record) => {
            if (record.type === 'attributes') {
                const panel = record.target;
                if (panel.matches && panel.matches(PANEL)) sync(panel);
                return;
            }
            // The boards re-render cards in place after a status flip, so a
            // panel can arrive already open and needs positioning too.
            record.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                if (node.matches && node.matches(PANEL)) sync(node);
                else if (node.querySelectorAll) node.querySelectorAll(PANEL).forEach(sync);
            });
        });
    }).observe(document.body, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['class'],
    });

    // A scroll or resize can invalidate the choice made when the panel opened.
    // Both handlers close every other panel first, so there is at most one to
    // re-place — but the work is a forced layout either way, so it is throttled
    // and skipped entirely while nothing is open, which is almost always.
    let last = 0;
    const replace = () => {
        const open = openPanels();
        if (!open.length) return;
        const now = Date.now();
        if (now - last < RETHROTTLE) return;
        last = now;
        open.forEach(place);
    };
    window.addEventListener('resize', replace);
    window.addEventListener('scroll', replace, { passive: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
} else {
    start();
}
