/**
 * ═══════════════════════════════════════════════════════════════════════
 * Farmers Hostel — Scroll-scrub effects
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Three scrubbed (position-mapped, reversible) scroll effects, ported from
 * Skiper UI's React/Framer Motion demos into the same vanilla, reduced-
 * motion-aware idiom as parallax.js:
 *
 *  [data-fx-band]   → cinematic mask reveal (Skiper 29 / siena.film): a
 *                     full-bleed band starts inset in a rounded frame and
 *                     expands to full bleed as it scrolls into view. The
 *                     image inside keeps its own parallax drift, so frame
 *                     and photo move at different rates, like a camera pull.
 *  [data-fx-thread] → scroll-drawn SVG path (Skiper 19): a gold thread
 *                     draws itself down the story section, its tip keyed to
 *                     the reading position. The path carries pathLength="1"
 *                     so the dash math is normalized and the markup can
 *                     reshape the curve freely.
 *  [data-fx-words]  → word-level materialize (Skiper 31, words not chars —
 *                     per-character 3D on a full paragraph reads as noise):
 *                     each word eases from lowered/blurred/dim to rest,
 *                     staggered by index, as the paragraph crosses the
 *                     viewport's reading band.
 *  [data-fx-card]   → cinematic card handoff (easemize cinematic-landing-hero,
 *                     GSAP "main-card takeover" ported to the scrub idiom): the
 *                     section after the hero arrives lifted, slightly scaled and
 *                     rounded — a card — and settles to full bleed as it climbs.
 *                     Its surface/shadow live on a CSS ::before driven by
 *                     --fx-card, so nothing lingers at rest.
 *
 * Direct scrub, no lerp: the effects stay welded to the scrollbar rather than
 * trailing it. No-JS / reduced-motion pages render fully static — nothing is
 * hidden by default.
 *
 * ── Layout reads ──────────────────────────────────────────────────────
 * This file used to call getBoundingClientRect() on every tracked element on
 * every frame, and the header here claimed that was free because "all styles
 * written here are paint/composite-only, so the fresh rect reads each frame
 * never trigger layout thrash".
 *
 * That was wrong, and it was the single most expensive thing on the landing
 * page. The written properties being paint-only (clip-path, dashoffset,
 * opacity, transform) does not matter: writing any of them marks style dirty,
 * and the *next* getBoundingClientRect() has to flush that pending style —
 * recalculating style and running layout — before it can answer. The loop read
 * a rect, wrote a style, read the next rect, wrote the next style, four times
 * per frame. Classic read-after-write thrash, and it sat between the other two
 * scroll loops' writes so it flushed their pending styles too.
 *
 * Rects are now cached in document space (top + scrollY) exactly the way
 * parallax.js has always done it, and re-measured only when geometry can
 * actually have changed — resize, load, element resize. The scrub maths reads
 * `docTop - scrollY` instead, which is arithmetic, not layout.
 *
 * See public/js/frame-bus.js for the shared loop this subscribes to and the
 * measurements that motivated it.
 */
(function () {
    'use strict';

    const reduceMQ = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reduceMQ.matches) return;

    // Shared scheduler. If frame-bus.js failed to load, install an equivalent
    // local one rather than going static — same API, same one-loop guarantee.
    const bus = (function () {
        if (window.FHFrame) return window.FHFrame;
        let ticks = [], measures = [], queued = false, last = performance.now();
        const s = { scrollY: window.pageYOffset || 0, viewH: window.innerHeight, viewW: window.innerWidth, docH: 0, dt: 1 / 60, now: last };
        function req() {
            if (queued) return;
            queued = true;
            requestAnimationFrame(function (n) {
                queued = false;
                s.dt = Math.min(Math.max((n - last) / 1000, 0), 0.1) || 1 / 60;
                last = n; s.now = n; s.scrollY = window.pageYOffset || 0;
                let again = false;
                for (let i = 0; i < ticks.length; i++) { try { if (ticks[i](s) === true) again = true; } catch (e) { } }
                if (again) req();
            });
        }
        function mea() {
            clearTimeout(soonT);
            s.viewH = window.innerHeight; s.viewW = window.innerWidth;
            s.scrollY = window.pageYOffset || 0; s.docH = document.documentElement.scrollHeight;
            for (let i = 0; i < measures.length; i++) { try { measures[i](s); } catch (e) { } }
            req();
        }
        // The shim carries the same debounce as the real bus: this path only
        // runs when frame-bus.js failed to load, and it would otherwise
        // reproduce exactly the measure storm that file exists to prevent.
        let soonT;
        function meaSoon() { clearTimeout(soonT); soonT = setTimeout(mea, 150); }
        window.addEventListener('scroll', req, { passive: true });
        window.addEventListener('resize', mea, { passive: true });
        window.addEventListener('load', mea);
        if (window.ResizeObserver) new ResizeObserver(meaSoon).observe(document.documentElement);
        window.FHFrame = {
            state: s,
            onTick: function (f) { ticks.push(f); req(); return f; },
            onMeasure: function (f) { measures.push(f); return f; },
            offTick: function (f) { const i = ticks.indexOf(f); if (i > -1) ticks.splice(i, 1); },
            request: req, measure: mea, measureSoon: meaSoon, stop: function () { },
        };
        requestAnimationFrame(mea);
        return window.FHFrame;
    })();

    const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
    // Smoothstep — softens both ends of a scrub without hiding the mapping
    const smooth = (t) => { t = clamp(t, 0, 1); return t * t * (3 - 2 * t); };

    // Blur is a desktop luxury — same mobile stance as parallax.js, and now
    // read the same way parallax.js reads it.
    //
    // This was `window.innerWidth >= 768`, which Chrome answers against current
    // layout — so on a deferred script with the document still parsing it forces
    // a full initial layout, the same reflow frame-bus.js was billed 138 ms for.
    // matchMedia costs nothing: it consults the media-query state, not the box
    // tree. The value here is a seed that measureAll() overwrites from
    // `s.viewW` on the first measurement pass anyway, so it was paying a layout
    // for a number that is thrown away.
    let allowBlur = window.matchMedia('(min-width: 768px)').matches;

    // ── Collect ─────────────────────────────────────────────────────
    // Every tracked item carries { el, docTop, height } — its geometry in
    // document space, refreshed only by measureAll().
    const bands = [];
    document.querySelectorAll('[data-fx-band]').forEach((el) => {
        bands.push({ el, done: false, docTop: 0, height: 0 });
    });

    const threads = [];
    document.querySelectorAll('[data-fx-thread]').forEach((svg) => {
        const path = svg.querySelector('path');
        if (!path) return;
        path.style.strokeDasharray = '1 1';
        path.style.strokeDashoffset = '1';
        threads.push({ el: svg, path, docTop: 0, height: 0 });
    });

    const wordSets = [];
    document.querySelectorAll('[data-fx-words]').forEach((p) => {
        const words = p.querySelectorAll('.fw');
        if (!words.length) return;
        // Arms the inline-block/will-change CSS — only under JS control,
        // so a no-JS page keeps plain, fully visible text.
        p.classList.add('fx-words-on');
        wordSets.push({ el: p, words, docTop: 0, height: 0 });
    });

    const cards = [];
    document.querySelectorAll('[data-fx-card]').forEach((el) => {
        cards.push({ el, done: false, docTop: 0, height: 0 });
    });

    const all = bands.concat(threads, wordSets, cards);
    if (!all.length) return;

    // ── Measure (the only place that touches layout) ─────────────────
    function measureAll(s) {
        allowBlur = s.viewW >= 768;
        for (let i = 0; i < all.length; i++) {
            const item = all[i];
            const r = item.el.getBoundingClientRect();
            item.docTop = r.top + s.scrollY;
            item.height = r.height;
        }
    }

    // Elements resizing under their own steam (a late webfont reflowing the
    // paragraph, a lazy image landing inside a band) move everything below them.
    if (window.ResizeObserver) {
        // measureSoon, not measure — see the note in frame-bus.js. A late
        // webfont reflowing a paragraph must not buy a forced layout per element.
        const reMeasure = () => (bus.measureSoon || bus.measure)();
        const ro = new ResizeObserver(reMeasure);
        all.forEach((item) => ro.observe(item.el));
    }

    // ── Frame updates — arithmetic only, no layout reads ─────────────

    function updateBand(item, s) {
        const top = item.docTop - s.scrollY;
        // 0 → band top at the fold; 1 → top has climbed 78% of the viewport
        const p = smooth((s.viewH - top) / (s.viewH * 0.78));
        if (p >= 1) {
            // Fully revealed: hand back an unclipped element so nothing is
            // left masking while the band just sits there. The compositor
            // layer goes back too — [data-fx-band] carries
            // `will-change: clip-path` in 12-scroll-effects.css, and once the
            // reveal has played there is no clip left to animate, so holding
            // the hint just pins a full-bleed layer for the rest of the
            // session. Same reasoning as .fx-words-live below.
            if (!item.done) {
                item.el.style.clipPath = '';
                item.el.style.willChange = 'auto';
                item.done = true;
            }
            return;
        }
        if (item.done) item.el.style.willChange = '';
        item.done = false;
        const inv = 1 - p;
        const x = (inv * 0.07 * s.viewW).toFixed(1);
        const y = (inv * 0.055 * s.viewH).toFixed(1);
        const rad = (inv * 40).toFixed(1);
        item.el.style.clipPath = 'inset(' + y + 'px ' + x + 'px round ' + rad + 'px)';
    }

    function updateThread(item, s) {
        const top = item.docTop - s.scrollY;
        // Tip enters at ~82% of the viewport, completes as the section's
        // bottom clears the reading band — the line tracks reading pace.
        const start = s.viewH * 0.82;
        const end = s.viewH * 0.42 - item.height;
        const p = smooth((start - top) / (start - end));
        // Sub-pixel dash changes aren't visible but still repaint the path.
        if (item.lastP !== undefined && Math.abs(p - item.lastP) < 0.0005) return;
        item.lastP = p;
        item.path.style.strokeDashoffset = (1 - p).toFixed(4);
    }

    function updateWords(item, s) {
        const top = item.docTop - s.scrollY;
        // The sweep begins as the paragraph clears the fold and completes
        // when its middle reaches the upper reading band.
        const start = s.viewH * 0.94;
        const end = s.viewH * 0.38 - item.height / 2;
        const P = clamp((start - top) / (start - end), 0, 1);

        // Hold the per-word compositor layers only while the sweep is live.
        //
        // This has to sit ABOVE the early-out below: while the paragraph is
        // still approaching, P is pinned at 0 and the early-out returns, so a
        // toggle placed after it would never see the frames where the words
        // are coming into range and would never arm.
        //
        // One viewport of lead time, because `will-change` needs a frame to
        // take effect — arming exactly as the sweep starts promotes a frame
        // too late to help the first one.
        const near = top < s.viewH * 2 && (top + item.height) > 0;
        item.el.classList.toggle('fx-words-live', near && P < 1);

        // A paragraph sitting far off-screen holds P at exactly 0 or 1, and
        // the scrub is position-mapped, so an unchanged P can only produce the
        // values already on the element. Without this the loop rewrote opacity
        // + transform + filter on every span, every frame, for the life of the
        // page.
        if (item.lastP !== undefined && Math.abs(P - item.lastP) < 0.0005) return;
        item.lastP = P;

        const n = item.words.length;
        const span = 0.35; // each word's reveal window within the sweep
        for (let i = 0; i < n; i++) {
            const offset = (i / Math.max(1, n - 1)) * (1 - span);
            const t = smooth((P - offset) / span);
            const w = item.words[i];
            w.style.opacity = (0.12 + 0.88 * t).toFixed(3);
            w.style.transform = t >= 1 ? '' : 'translateY(' + ((1 - t) * 0.35).toFixed(3) + 'em)';
            if (allowBlur) w.style.filter = t >= 1 ? '' : 'blur(' + ((1 - t) * 5).toFixed(2) + 'px)';
        }
    }

    function updateCard(item, s) {
        const top = item.docTop - s.scrollY;
        // 0 → section top at the fold; 1 → top has climbed 62% of the viewport
        const p = smooth((s.viewH - top) / (s.viewH * 0.62));
        if (p >= 1) {
            // Settled: hand back a plain, unclipped, untransformed section
            if (!item.done) {
                item.el.style.transform = '';
                item.el.style.borderRadius = '';
                item.el.style.setProperty('--fx-card', '0');
                item.done = true;
            }
            return;
        }
        item.done = false;
        const inv = 1 - p;
        item.el.style.transform =
            'translate3d(0,' + (inv * 64).toFixed(1) + 'px,0) scale(' + (1 - inv * 0.05).toFixed(4) + ')';
        item.el.style.borderRadius = (inv * 32).toFixed(1) + 'px';
        item.el.style.setProperty('--fx-card', inv.toFixed(3));
    }

    // ── Subscribe ───────────────────────────────────────────────────
    let dead = false;

    bus.onMeasure(measureAll);
    const tick = bus.onTick(function (s) {
        if (dead) return false;
        for (let i = 0; i < bands.length; i++) updateBand(bands[i], s);
        for (let i = 0; i < threads.length; i++) updateThread(threads[i], s);
        for (let i = 0; i < wordSets.length; i++) updateWords(wordSets[i], s);
        for (let i = 0; i < cards.length; i++) updateCard(cards[i], s);
        // Position-mapped, so there is nothing to settle: one pass per scroll
        // event is complete. Never asks for a follow-up frame.
        return false;
    });

    // ── Live reduced-motion toggle: return everything to natural rest ──
    reduceMQ.addEventListener('change', (e) => {
        if (!e.matches) return;
        dead = true;
        bus.offTick(tick);
        for (const c of cards) {
            c.el.style.transform = '';
            c.el.style.borderRadius = '';
            c.el.style.setProperty('--fx-card', '0');
        }
        for (const b of bands) b.el.style.clipPath = '';
        for (const t of threads) {
            t.path.style.strokeDasharray = '';
            t.path.style.strokeDashoffset = '';
        }
        for (const s of wordSets) {
            s.el.classList.remove('fx-words-on');
            s.words.forEach((w) => {
                w.style.opacity = '';
                w.style.transform = '';
                w.style.filter = '';
            });
        }
    });
})();
