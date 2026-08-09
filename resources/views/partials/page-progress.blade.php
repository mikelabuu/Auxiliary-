{{--
    Page-load progress bar for the staff consoles.

    Why this is inline rather than a class in admin.css: admin.css IS most of
    what the bar is reporting on. A navigation here is ~1.2s to
    DOMContentLoaded against ~145ms TTFB — almost all of that gap is parsing
    ~1.1MB of CSS and JS. A bar styled by the stylesheet it is waiting for
    would only appear once the wait was nearly over, which is the one moment
    it is useless. So the markup, the styling and the boot are self-contained
    and run during HTML parse, before any asset request resolves.

    Why it lives in the NEW document rather than firing on link click: these
    pages enable cross-document view transitions (`@view-transition` in
    admin/02-base.css), which freezes a snapshot of the OLD page while the
    next one loads. Anything animating on the outgoing page freezes mid-motion
    and reads as a hang. The incoming document is not frozen, so that is where
    the bar belongs.

    It is genuinely indeterminate — the browser cannot report parse progress —
    so it eases toward 90% and never claims to finish until `load` actually
    fires. It never reverses and never sits at 100% while work continues.
--}}
<div id="pageProgress" aria-hidden="true"><span></span></div>

<style>
    #pageProgress {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        z-index: 500;
        pointer-events: none;
        opacity: 0;
        transition: opacity 200ms ease;
    }

    #pageProgress.is-on { opacity: 1; }

    #pageProgress > span {
        display: block;
        height: 100%;
        width: 0;
        border-radius: 0 3px 3px 0;
        /* Brand green running into amber, the same pairing as the topbar
           hairline. Literal hex because the design tokens live in the
           stylesheet that has not loaded yet. */
        background: linear-gradient(90deg, #16b364 0%, #099250 55%, #f79009 100%);
        box-shadow: 0 0 10px rgba(22, 179, 100, .45);
        transition: width 200ms ease-out;
    }

    /* Creep. Long, decelerating steps so a slow page keeps visibly moving
       without the bar ever implying it is about to finish. */
    #pageProgress.is-on > span { animation: pageProgressCreep 8s cubic-bezier(.15,.85,.3,1) forwards; }

    @keyframes pageProgressCreep {
        0%   { width: 0; }
        12%  { width: 32%; }
        35%  { width: 58%; }
        60%  { width: 74%; }
        100% { width: 90%; }
    }

    #pageProgress.is-done > span {
        animation: none;
        width: 100%;
    }

    @media (prefers-reduced-motion: reduce) {
        #pageProgress.is-on > span { animation: none; width: 60%; }
        #pageProgress > span { transition: none; }
    }
</style>

<script>
(function () {
    var el = document.getElementById('pageProgress');
    if (!el) return;

    // A page served from bfcache or a warm cache can finish before the eye
    // registers anything. Showing a bar for 80ms is a flicker, not feedback,
    // so nothing is shown unless the load is still going after this long.
    var SHOW_AFTER = 120;
    var shown = false;

    var timer = setTimeout(function () {
        // document.readyState is already 'complete' on a bfcache restore.
        if (document.readyState === 'complete') return;
        shown = true;
        el.classList.add('is-on');
    }, SHOW_AFTER);

    function finish() {
        clearTimeout(timer);
        if (!shown) return;
        shown = false;

        el.classList.add('is-done');
        // Let the run to 100% land before fading, or the bar vanishes
        // mid-travel and the completion never reads.
        setTimeout(function () { el.classList.remove('is-on', 'is-done'); }, 220);
    }

    if (document.readyState === 'complete') {
        clearTimeout(timer);
    } else {
        window.addEventListener('load', finish, { once: true });
    }

    // Coming back via the back button restores a finished page; make sure a
    // bar captured mid-run in the bfcache snapshot is not left on screen.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { clearTimeout(timer); el.classList.remove('is-on', 'is-done'); }
    });

    /* ── Livewire requests ────────────────────────────────────────────
       Pagination, filter pills, sorting and search are Livewire round-trips,
       not document loads — so none of the above fires for them, and paging a
       table gave no feedback at all.

       Measured locally these take about 36ms. The per-component
       `wire:loading.delay` indicators are set to 200ms, which is the right
       call for THEM (a chip flashing for 36ms inside a table is noise), but
       it means a fast box shows nothing anywhere. So the bar runs on a
       MINIMUM VISIBLE DURATION instead: it appears immediately and stays for
       at least MIN_VISIBLE, which turns a 36ms request into a deliberate,
       readable state change rather than either a flicker or silence. */
    var MIN_VISIBLE = 420;
    var pending = 0;
    var startedAt = 0;
    var settleTimer = null;

    // wire:poll fires commits nobody asked for — the dashboards poll every
    // 15-30s. Flashing the bar for those would read as the page twitching on
    // its own. Only commits that follow a real interaction are shown, which
    // also covers the 400ms debounce on the search inputs.
    var lastInteraction = 0;
    ['pointerdown', 'keydown'].forEach(function (evt) {
        document.addEventListener(evt, function () { lastInteraction = performance.now(); }, true);
    });

    function lwStart() {
        if (performance.now() - lastInteraction > 1500) return false;

        pending++;
        if (pending > 1) return true;

        clearTimeout(settleTimer);
        startedAt = performance.now();
        el.classList.remove('is-done');
        el.classList.add('is-on');
        return true;
    }

    function lwStop(counted) {
        if (!counted) return;

        pending = Math.max(0, pending - 1);
        if (pending > 0) return;

        // Hold the floor. Without this the bar is removed on the same frame it
        // was added and nothing is ever perceived.
        var remaining = Math.max(0, MIN_VISIBLE - (performance.now() - startedAt));

        settleTimer = setTimeout(function () {
            el.classList.add('is-done');
            setTimeout(function () { el.classList.remove('is-on', 'is-done'); }, 220);
        }, remaining);
    }

    document.addEventListener('livewire:init', function () {
        if (!window.Livewire || !Livewire.hook) return;

        Livewire.hook('commit', function (payload) {
            var counted = lwStart();
            // Both paths, or a failed request leaves the bar running forever.
            payload.succeed(function () { lwStop(counted); });
            payload.fail(function () { lwStop(counted); });
        });
    });
})();
</script>
