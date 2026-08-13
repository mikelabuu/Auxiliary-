/**
 * ═══════════════════════════════════════════════════════════════════════
 * Farmers Hostel — shared scroll/frame scheduler
 * ═══════════════════════════════════════════════════════════════════════
 *
 * One rAF loop and one scroll listener for the whole page.
 *
 * Why this file exists. parallax.js, scroll-effects.js and home.js each ran
 * their own `scroll` listener feeding their own requestAnimationFrame loop.
 * Individually each was already tuned and each looked cheap; together they
 * were not. Measured on the live site at 6x CPU throttle, median frame time
 * during a full-page scroll:
 *
 *     all three running .................. 27.7ms/frame, ~65% frames dropped
 *     all three blocked ..................  7.1ms/frame, ~5% frames dropped
 *     any ONE of them blocked ............ ~21ms/frame
 *
 * The cost was cumulative, which is why no single-file fix moved it: three
 * loops mean three callbacks serialised on the main thread every frame, three
 * separate `window.scrollY` reads, and — because scroll-effects.js measured
 * layout inside its loop — a forced style+layout flush wedged between the
 * other two loops' style writes.
 *
 * The contract that keeps it fast:
 *
 *   • ONE loop. Subscribers get called from a single rAF callback.
 *   • Subscribers NEVER read layout. No getBoundingClientRect, offsetTop,
 *     offsetHeight, scrollHeight or getComputedStyle inside a tick. Read it
 *     in an onMeasure() callback instead — those run outside the loop, at
 *     points where layout is already clean.
 *   • Shared state is read once per frame (scrollY, viewH, viewW, dt) and
 *     handed to every subscriber, so N modules cost one layout query, not N.
 *   • The loop is self-parking. It runs only while something is moving: a
 *     tick returns `true` to ask for another frame, and when every subscriber
 *     returns falsy the loop stops until the next scroll/resize.
 *
 * Usage:
 *
 *     var bus = window.FHFrame;
 *     bus.onMeasure(function (s) { ... read layout, cache it ... });
 *     bus.onTick(function (s) { ... write styles only ... return stillMoving; });
 *     bus.request();            // wake the loop (e.g. after a state change)
 *
 * `s` is { scrollY, viewH, viewW, docH, dt, now }. `dt` is seconds since the
 * previous frame, clamped to 0.1s so a backgrounded tab can't produce a giant
 * catch-up step.
 *
 * Degrades safely: if this file fails to load, each consumer falls back to its
 * own local loop (see the `ensureBus` shim at the top of each one), so the page
 * animates exactly as before rather than going static.
 */
(function () {
    'use strict';

    if (window.FHFrame) return;

    var ticks = [];
    var measures = [];
    var ticking = false;
    var running = true;
    var lastTime = performance.now();

    var state = {
        scrollY: window.pageYOffset || 0,
        viewH: window.innerHeight,
        viewW: window.innerWidth,
        docH: 0,
        dt: 1 / 60,
        now: lastTime,
    };

    function request() {
        if (!ticking && running) {
            ticking = true;
            requestAnimationFrame(frame);
        }
    }

    function frame(now) {
        ticking = false;
        if (!running) return;

        state.dt = Math.min(Math.max((now - lastTime) / 1000, 0), 0.1) || 1 / 60;
        lastTime = now;
        state.now = now;
        // The one layout-adjacent read of the frame. scrollY is cheap (it does
        // not force layout the way an element rect does) and doing it here means
        // three subscribers share one read instead of taking one each.
        state.scrollY = window.pageYOffset || 0;

        var again = false;
        for (var i = 0; i < ticks.length; i++) {
            // A throwing subscriber must not take the other two down with it,
            // nor wedge `ticking` true and freeze the whole page's motion.
            try {
                if (ticks[i](state) === true) again = true;
            } catch (e) {
                if (window.console) console.error('[FHFrame] tick failed', e);
            }
        }
        if (again) request();
    }

    // Measurement pass. Everything that reads layout lives here: resize, load,
    // and tab-visibility returns. Debounced into a single rAF so a drag-resize
    // does not run the readers on every intermediate width, and so the reads
    // land after the browser's own layout rather than forcing one mid-event.
    var measureQueued = false;
    function measure() {
        if (measureQueued) return;
        measureQueued = true;
        requestAnimationFrame(function () {
            measureQueued = false;
            state.viewH = window.innerHeight;
            state.viewW = window.innerWidth;
            state.scrollY = window.pageYOffset || 0;
            state.docH = document.documentElement.scrollHeight;
            for (var i = 0; i < measures.length; i++) {
                try {
                    measures[i](state);
                } catch (e) {
                    if (window.console) console.error('[FHFrame] measure failed', e);
                }
            }
            request();
        });
    }

    window.addEventListener('scroll', request, { passive: true });

    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(measure, 100);
    }, { passive: true });

    // Images settling after DOMContentLoaded move everything below them, so the
    // cached rects taken at init are stale until this fires.
    window.addEventListener('load', measure);

    // ...and `load` is not the end of it. Lazy images below the fold decode as
    // the reader approaches them, and each one that lands changes the document
    // height and shifts every cached rect beneath it. Watching the root element
    // catches all of it: without this, `docH` stays at whatever the page
    // measured on load, and the reading-progress hairline (scrollY / docH)
    // tops out short of 1 — it read scaleX(0.91) at the very bottom of the
    // landing page, because the old code re-read scrollHeight every frame and
    // this one does not.
    if (window.ResizeObserver) {
        // measure() only reads, so it cannot feed back into this observer.
        new ResizeObserver(measure).observe(document.documentElement);
    }

    document.addEventListener('visibilitychange', function () {
        running = !document.hidden;
        if (running) {
            lastTime = performance.now();
            measure();
        }
    });

    window.FHFrame = {
        state: state,
        onTick: function (fn) { ticks.push(fn); request(); return fn; },
        onMeasure: function (fn) { measures.push(fn); return fn; },
        offTick: function (fn) {
            var i = ticks.indexOf(fn);
            if (i > -1) ticks.splice(i, 1);
        },
        request: request,
        measure: measure,
        stop: function () { running = false; },
    };

    // First measurement deferred a frame: at init the parser may still be
    // working, and forcing layout here to read innerHeight/scrollHeight would
    // bill the cost against page load for numbers that settle later anyway.
    requestAnimationFrame(measure);
})();
