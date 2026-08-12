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
 *
 * Direct scrub, no lerp: on desktop Lenis already supplies the inertia, and
 * a second smoothing layer would detach the effects from the scrollbar.
 * All styles written here are paint/composite-only (clip-path, dashoffset,
 * transform, opacity, filter), so the fresh rect reads each frame never
 * trigger layout thrash. No-JS / reduced-motion pages render fully static —
 * nothing is hidden by default.
 */
(function () {
    'use strict';

    const reduceMQ = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reduceMQ.matches) return;

    const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
    // Smoothstep — softens both ends of a scrub without hiding the mapping
    const smooth = (t) => { t = clamp(t, 0, 1); return t * t * (3 - 2 * t); };

    let viewH = window.innerHeight;
    let viewW = window.innerWidth;
    // Blur is a desktop luxury — same mobile stance as parallax.js
    let allowBlur = viewW >= 768;

    // ── Collect ─────────────────────────────────────────────────────
    const bands = [];
    document.querySelectorAll('[data-fx-band]').forEach((el) => {
        bands.push({ el, done: false });
    });

    const threads = [];
    document.querySelectorAll('[data-fx-thread]').forEach((svg) => {
        const path = svg.querySelector('path');
        if (!path) return;
        path.style.strokeDasharray = '1 1';
        path.style.strokeDashoffset = '1';
        threads.push({ el: svg, path });
    });

    const wordSets = [];
    document.querySelectorAll('[data-fx-words]').forEach((p) => {
        const words = p.querySelectorAll('.fw');
        if (!words.length) return;
        // Arms the inline-block/will-change CSS — only under JS control,
        // so a no-JS page keeps plain, fully visible text.
        p.classList.add('fx-words-on');
        wordSets.push({ el: p, words });
    });

    // [data-fx-card] → cinematic card handoff (easemize cinematic-landing-hero,
    // GSAP "main-card takeover" ported to the scrub idiom): the section after
    // the hero arrives lifted, slightly scaled and rounded — a card — and
    // settles to full bleed as it climbs. Its surface/shadow live on a CSS
    // ::before driven by --fx-card, so nothing lingers at rest.
    const cards = [];
    document.querySelectorAll('[data-fx-card]').forEach((el) => {
        cards.push({ el, done: false });
    });

    if (!bands.length && !threads.length && !wordSets.length && !cards.length) return;

    // ── Frame updates ───────────────────────────────────────────────

    function updateBand(item) {
        const r = item.el.getBoundingClientRect();
        // 0 → band top at the fold; 1 → top has climbed 78% of the viewport
        const p = smooth((viewH - r.top) / (viewH * 0.78));
        if (p >= 1) {
            // Fully revealed: hand back an unclipped element so nothing is
            // left masking while the band just sits there. The compositor
            // layer goes back too — [data-fx-band] carries
            // `will-change: clip-path` in 12-scroll-effects.css, and once the
            // reveal has played there is no clip left to animate, so holding
            // the hint just pins a full-bleed layer for the rest of the
            // session. Same reasoning as .fx-words-rest below.
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
        const x = (inv * 0.07 * viewW).toFixed(1);
        const y = (inv * 0.055 * viewH).toFixed(1);
        const rad = (inv * 40).toFixed(1);
        item.el.style.clipPath = 'inset(' + y + 'px ' + x + 'px round ' + rad + 'px)';
    }

    function updateThread(item) {
        const r = item.el.getBoundingClientRect();
        // Tip enters at ~82% of the viewport, completes as the section's
        // bottom clears the reading band — the line tracks reading pace.
        const start = viewH * 0.82;
        const end = viewH * 0.42 - r.height;
        const p = smooth((start - r.top) / (start - end));
        item.path.style.strokeDashoffset = (1 - p).toFixed(4);
    }

    function updateWords(item) {
        const r = item.el.getBoundingClientRect();
        // The sweep begins as the paragraph clears the fold and completes
        // when its middle reaches the upper reading band.
        const start = viewH * 0.94;
        const end = viewH * 0.38 - r.height / 2;
        const P = clamp((start - r.top) / (start - end), 0, 1);

        // Unlike the band and card effects above, this loop had no early-out.
        // Because P is clamped, a paragraph sitting far off-screen holds P at
        // exactly 0 or 1 — and the loop still rewrote opacity + transform +
        // filter on all of its spans, every frame, for the life of the page.
        // The scrub is position-mapped, so an unchanged P can only produce the
        // values already on the element.
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

        // Once the sweep has fully played, release the compositor layers that
        // .fx-words-on pins with `will-change: transform, opacity, filter` —
        // one per word, held for the whole session otherwise. The class itself
        // stays: it also carries `display: inline-block`, and dropping that
        // would re-wrap the paragraph and shift layout.
        item.el.classList.toggle('fx-words-rest', P >= 1);
    }

    function updateCard(item) {
        const r = item.el.getBoundingClientRect();
        // 0 → section top at the fold; 1 → top has climbed 62% of the viewport
        const p = smooth((viewH - r.top) / (viewH * 0.62));
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

    // ── Loop (scroll-armed rAF, idle when still) ────────────────────
    let ticking = false;
    let dead = false;

    function render() {
        ticking = false;
        if (dead) return;
        for (const b of bands) updateBand(b);
        for (const t of threads) updateThread(t);
        for (const w of wordSets) updateWords(w);
        for (const c of cards) updateCard(c);
    }

    function requestTick() {
        if (!ticking && !dead) {
            ticking = true;
            requestAnimationFrame(render);
        }
    }

    window.addEventListener('scroll', requestTick, { passive: true });
    window.addEventListener('resize', () => {
        viewH = window.innerHeight;
        viewW = window.innerWidth;
        allowBlur = viewW >= 768;
        requestTick();
    }, { passive: true });
    // Image loads shift layout after DOMContentLoaded — repaint once settled
    window.addEventListener('load', requestTick);

    // ── Live reduced-motion toggle: return everything to natural rest ──
    reduceMQ.addEventListener('change', (e) => {
        if (!e.matches) return;
        dead = true;
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

    requestTick();
})();
