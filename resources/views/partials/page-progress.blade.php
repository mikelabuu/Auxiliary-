{{--
    In-page progress bar for the staff consoles.

    ONE job: Livewire round-trips. Table pagination, filter pills, sorting and
    search all update in place, so no document ever loads and nothing else on
    the page reports them.

    It used to run on document loads as well. It no longer does — the
    full-screen curtain (partials/page-loader) now covers a navigation from
    the click that starts it, and a hairline creeping across the header behind
    a curtain is a second loading indicator for a wait that already has one.
    Anything that loads a document belongs to the curtain; anything that
    happens inside this one belongs here.

    Why this is inline rather than a class in admin.css: it has to be able to
    run before admin.css is parsed, and keeping the markup, styling and boot
    together is what guarantees that. It stays first in the body with the
    curtain for the same reason.

    Progress is indeterminate — Livewire reports no fraction — so the bar eases
    toward 90% and only claims 100% when the round-trip has actually landed.
    It never reverses and never sits at 100% while work continues.
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
        /* The console's flat brand green. Literal hex because the design
           tokens live in the stylesheet that has not loaded yet. */
        background: #0f8f51;
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

    // Coming back via the back button restores a finished page; make sure a
    // bar captured mid-run in the bfcache snapshot is not left on screen.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) el.classList.remove('is-on', 'is-done');
    });

    /* ── Livewire requests ────────────────────────────────────────────
       Pagination, filter pills, sorting and search are Livewire round-trips,
       not document loads — so nothing else on the page reports them, and
       paging a table gave no feedback at all.

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
