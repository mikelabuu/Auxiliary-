/**
 * Keeps the sidebar's queue badges live.
 *
 * The four chips — Dashboard, Reschedules, Verify Payments, Discounts — were
 * rendered from four queries in the sidebar view, so they were only ever true
 * for the request that drew them. A console left open on one screen kept
 * advertising work that had already been done, usually by the person at the
 * other desk, and the only way to find out was to reload.
 *
 * The bell already solved this: admin-notifications.js polls
 * /staff/notifications/feed and pushes what it finds onto the topbar. That
 * response now carries `counts` beside `items`, so this listens for the same
 * data on the same poll rather than adding a second timer and a second query
 * of the same four tables.
 *
 * Contract: a chip is `[data-sidebar-count="<key>"]`, the keys are those of
 * App\Support\StaffAlerts::pendingCounts(), and the element exists even at
 * zero (rendered `hidden`) so there is always something to write into.
 */

const CHIP = '[data-sidebar-count]';

function apply(counts) {
    if (!counts || typeof counts !== 'object') return;

    document.querySelectorAll(CHIP).forEach((chip) => {
        const key = chip.getAttribute('data-sidebar-count');
        if (!(key in counts)) return;

        const n = Number(counts[key]);
        if (!Number.isFinite(n)) return;

        const next = n > 0 ? String(n) : '';

        // Only touch the DOM when the number actually moved. Writing the same
        // value every 20 seconds would re-announce it to a screen reader each
        // time, because the chip is aria-live.
        if (chip.textContent === next && chip.hidden === (n < 1)) return;

        chip.textContent = next;
        chip.hidden = n < 1;

        // A brief pulse, so a badge that changes while someone is looking at
        // another part of the page is not a silent edit. Restarted rather than
        // added, or a second change inside the animation would not show.
        if (n > 0) {
            chip.classList.remove('is-bumped');
            void chip.offsetWidth;
            chip.classList.add('is-bumped');
        }
    });
}

window.addEventListener('staff-counts', (e) => apply(e.detail));

export default apply;
