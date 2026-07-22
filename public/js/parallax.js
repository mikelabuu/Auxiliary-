/**
 * ═══════════════════════════════════════════════════════════════════════
 * Farmers Hostel — Parallax Scroll Engine v4
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Drop-in replacement — same data attributes, same preset classes as v2/v3,
 * zero markup changes required (one new optional preset, see below).
 *
 * What's new in v4, on top of v3's frame-rate-independent easing:
 *
 *  • AOS handoff. Several elements in this template carry both `data-aos`
 *    (entrance reveal) and `data-prlx-*` (scroll parallax). Both systems
 *    write inline `transform`/`opacity` on the same element — whichever
 *    writes last wins, so v3 (like v2) could clobber AOS's fade-up-into-
 *    view reveal the instant the element entered the viewport. v4 waits
 *    for `.aos-animate` before it starts driving an element that has
 *    `data-aos`, so the entrance plays first and parallax takes over
 *    cleanly afterward. Self-heals after ~1.2s if AOS isn't present, so
 *    nothing silently breaks if that assumption doesn't hold.
 *  • Layered easing. Position now settles slightly ahead of rotation and
 *    scale (a differentiated "slow" lambda for the latter two), the way a
 *    physical object's orientation lags its motion — reads as weightier,
 *    less like everything is welded to one rigid timeline.
 *  • Organic per-instance variance. Each element gets a small (±10%),
 *    one-time jitter on its easing rate, so a repeated group (gallery
 *    tiles, feature cards) doesn't move in perfect mechanical unison.
 *  • Richer hero depth (blur/skew/mouse-drift magnitudes increased) and a
 *    touch of blur now on the hero headline as it fades, for a softer
 *    dissolve rather than a flat opacity cut.
 *  • New `.prlx-photo` preset — a depth-of-field default (blur + gentle
 *    scale) for full-bleed photography, e.g. a gallery wall, while still
 *    letting each tile override its own `data-prlx-y` for varied depth.
 *
 * Data attributes on elements:
 *  data-prlx-y="0.3"          → vertical speed multiplier (neg = opposite)
 *  data-prlx-x="0.1"          → horizontal drift multiplier
 *  data-prlx-opacity           → fade on scroll distance
 *  data-prlx-scale="0.06"     → scale delta (e.g. 0.06 → scales 0.94–1.0)
 *  data-prlx-rotate="3"       → max rotation in degrees
 *  data-prlx-origin="top"     → reference: "top" | "center" (default) | "bottom"
 *  data-prlx-ease="0.08"      → custom lerp factor (lower = laggier/smoother)
 *  data-prlx-blur="4"         → max depth blur in px (desktop only)
 *  data-prlx-skew="0.5"       → max velocity-driven skewY in deg (desktop only)
 *  data-prlx-mouse="16"       → max cursor-follow drift in px (desktop only)
 *
 * Auto-parallax (no data attrs needed — just add a CSS class):
 *  .prlx-hero-bg        → hero background (speed 0.5, top origin, +blur/skew/mouse)
 *  .prlx-hero-content   → hero text (speed -0.3, top origin, opacity+blur fade, +mouse)
 *  .prlx-float           → gentle float (speed 0.06, scale, subtle rotate)
 *  .prlx-rise             → rise into view (speed 0.12, scale)
 *  .prlx-drift-left      → drift from right to left (x -0.04, speed 0.05)
 *  .prlx-drift-right     → drift from left to right (x 0.04, speed 0.05)
 *  .prlx-photo            → photography depth-of-field (blur 3, gentle scale)
 */
(function () {
    'use strict';

    // ── Accessibility bail ──────────────────────────────────────────
    const reduceMotionMQ = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reduceMotionMQ.matches) return;

    // ── Capability detection ─────────────────────────────────────────
    const pointerFineMQ = window.matchMedia('(pointer: fine)');
    let isMobile = window.innerWidth < 768;
    let mobileDampen = isMobile ? 0.5 : 1;
    // Depth flourishes (blur / velocity-skew) are a desktop luxury — keep
    // narrow viewports light and fast, exactly like v2's mobile-aware stance.
    let allowDepthFX = !isMobile;
    let allowMouseFX = allowDepthFX && pointerFineMQ.matches;

    // ── State ───────────────────────────────────────────────────────
    const items = [];          // { el, opts, rect, visible, cur, hasAos, aosDeadline }
    let viewH = window.innerHeight;
    let scrollY = window.scrollY;
    let lastScrollY = scrollY;
    let scrollVelocity = 0;    // smoothed px/sec
    let pointerX = 0.5, pointerY = 0.5;           // smoothed, normalized 0..1
    let targetPointerX = 0.5, targetPointerY = 0.5;
    let ticking = false;
    let running = true;
    let lastTime = performance.now();

    // ── Preset class mappings ───────────────────────────────────────
    // blur / skew / mouse default to 0 (off) everywhere except the hero
    // and the photo preset, per "spend the boldness in one place" — depth
    // cues stay a signature of specific moments, not noise on every card.
    const PRESETS = {
        // This cursor drift moves the whole hero bg layer (photo + gradient
        // scrims). The layer is overscanned in the markup (-inset-8 = 32px on
        // every side) so this ±22px drift never exposes the night edge behind
        // it — if you raise `mouse`, keep that overscan ≥ its value.
        'prlx-hero-bg': { y: 0.5, x: 0, opacity: false, scale: 0, rotate: 0, origin: 'top', ease: 0.06, blur: 6, skew: 0.8, mouse: 22 },
        // blur 6 + scale .04: the cinematic "camera refocus" recede — the hero
        // copy softens and pulls back as the next section arrives as a card
        // (pairs with scroll-effects.js [data-fx-card]).
        'prlx-hero-content': { y: -0.35, x: 0, opacity: true, scale: 0.04, rotate: 0, origin: 'top', ease: 0.07, blur: 6, skew: 0, mouse: -13 },
        'prlx-float': { y: 0.06, x: 0, opacity: false, scale: 0.04, rotate: 1.5, origin: 'center', ease: 0.06, blur: 0, skew: 0, mouse: 0 },
        'prlx-rise': { y: 0.12, x: 0, opacity: false, scale: 0.06, rotate: 0, origin: 'center', ease: 0.07, blur: 0, skew: 0, mouse: 0 },
        'prlx-drift-left': { y: 0.05, x: -0.04, opacity: false, scale: 0, rotate: 0, origin: 'center', ease: 0.06, blur: 0, skew: 0, mouse: 0 },
        'prlx-drift-right': { y: 0.05, x: 0.04, opacity: false, scale: 0, rotate: 0, origin: 'center', ease: 0.06, blur: 0, skew: 0, mouse: 0 },
        'prlx-photo': { y: 0.08, x: 0, opacity: false, scale: 0.03, rotate: 0, origin: 'center', ease: 0.06, blur: 3, skew: 0, mouse: 0 },
    };

    // ── Utility ─────────────────────────────────────────────────────
    const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));

    // Frame-rate-independent exponential smoothing. `ease` is the fraction
    // covered per 60fps frame (v2's semantics); we convert it once into a
    // continuous decay rate so the *real-world* speed of the ease stays
    // constant no matter the display's refresh rate or a dropped frame.
    function lambdaFromEase(ease) {
        const e = clamp(ease, 0.0001, 0.95);
        return -Math.log(1 - e) * 60;
    }
    function expAlpha(lambda, dt) {
        return 1 - Math.exp(-lambda * dt);
    }
    const POINTER_LAMBDA = lambdaFromEase(0.12);
    const VELOCITY_LAMBDA = lambdaFromEase(0.2);

    function parseOpts(el) {
        // Check for preset class first
        let base = null;
        for (const [cls, preset] of Object.entries(PRESETS)) {
            if (el.classList.contains(cls)) { base = { ...preset }; break; }
        }

        // Data attributes override preset (or provide standalone config)
        const d = el.dataset;
        const opts = {
            y: d.prlxY !== undefined ? parseFloat(d.prlxY) : (base ? base.y : 0.15),
            x: d.prlxX !== undefined ? parseFloat(d.prlxX) : (base ? base.x : 0),
            opacity: d.prlxOpacity !== undefined ? true : (base ? base.opacity : false),
            scale: d.prlxScale !== undefined ? parseFloat(d.prlxScale) : (base ? base.scale : 0),
            rotate: d.prlxRotate !== undefined ? parseFloat(d.prlxRotate) : (base ? base.rotate : 0),
            origin: d.prlxOrigin || (base ? base.origin : 'center'),
            ease: d.prlxEase !== undefined ? parseFloat(d.prlxEase) : (base ? base.ease : 0.07),
            blur: d.prlxBlur !== undefined ? parseFloat(d.prlxBlur) : (base ? base.blur : 0),
            skew: d.prlxSkew !== undefined ? parseFloat(d.prlxSkew) : (base ? base.skew : 0),
            mouse: d.prlxMouse !== undefined ? parseFloat(d.prlxMouse) : (base ? base.mouse : 0),
        };

        if (!allowDepthFX) { opts.blur = 0; opts.skew = 0; opts.mouse = 0; }
        else if (!allowMouseFX) { opts.mouse = 0; }

        // Layered easing: rotation/scale trail position a touch, like a
        // physical object's orientation lagging its motion. Plus a small,
        // one-time per-instance jitter so repeated groups (card grids,
        // gallery tiles) don't move in perfect mechanical lockstep.
        const jitter = 0.9 + Math.random() * 0.2; // 0.9–1.1
        opts.lambda = lambdaFromEase(opts.ease) * jitter;
        opts.lambdaSlow = opts.lambda * 0.65;

        return opts;
    }

    function measureRect(item) {
        const r = item.el.getBoundingClientRect();
        item.rect = {
            top: r.top + scrollY,
            height: r.height,
            center: r.top + scrollY + r.height / 2,
        };
    }

    // ── Register element ────────────────────────────────────────────
    function register(el) {
        const opts = parseOpts(el);

        // Hint the browser to composite on GPU
        el.style.willChange = 'transform';
        if (opts.opacity) el.style.willChange += ', opacity';
        if (opts.blur > 0) el.style.willChange += ', filter';

        const item = {
            el,
            opts,
            rect: null,
            visible: true,
            hasAos: el.hasAttribute('data-aos'),
            aosDeadline: null,
            cur: { y: 0, x: 0, opacity: 1, scale: 1, rotate: 0, blur: 0, skew: 0 },
        };
        measureRect(item);
        items.push(item);
        io.observe(el);
        ro.observe(el);
    }

    // ── IntersectionObserver (visibility) ────────────────────────────
    const io = new IntersectionObserver(
        (entries) => {
            let woke = false;
            for (const e of entries) {
                const item = items.find(i => i.el === e.target);
                if (item) {
                    item.visible = e.isIntersecting;
                    if (item.visible) {
                        woke = true;
                        // Give AOS a bounded window to run its own entrance
                        // first; if it never shows up, we take over anyway.
                        if (item.hasAos && item.aosDeadline === null) {
                            item.aosDeadline = performance.now() + 1200;
                        }
                    }
                }
            }
            if (woke) requestTick();
        },
        { rootMargin: '25% 0px 25% 0px', threshold: 0 }
    );

    // ── ResizeObserver (cached rects — avoids layout thrash in rAF) ─
    const ro = new ResizeObserver((entries) => {
        // Re-measure affected items on next idle
        requestIdleCallback(() => {
            scrollY = window.scrollY;
            for (const e of entries) {
                const item = items.find(i => i.el === e.target);
                if (item) measureRect(item);
            }
            requestTick();
        });
    });

    // Polyfill requestIdleCallback
    window.requestIdleCallback = window.requestIdleCallback || function (cb) { return setTimeout(cb, 1); };

    // ── The render loop ─────────────────────────────────────────────
    function render(now) {
        if (!running) { ticking = false; return; }

        // Clamp dt so a backgrounded tab or a long GC pause can't cause a
        // giant catch-up jump when the loop wakes back up.
        const dt = Math.min(Math.max((now - lastTime) / 1000, 0), 0.1) || 1 / 60;
        lastTime = now;

        scrollY = window.scrollY;
        const rawVelocity = (scrollY - lastScrollY) / dt;
        lastScrollY = scrollY;
        scrollVelocity += (rawVelocity - scrollVelocity) * expAlpha(VELOCITY_LAMBDA, dt);

        pointerX += (targetPointerX - pointerX) * expAlpha(POINTER_LAMBDA, dt);
        pointerY += (targetPointerY - pointerY) * expAlpha(POINTER_LAMBDA, dt);

        // Expose scroll state as CSS custom properties — a free hook for
        // any future CSS-only touches (progress bars, nav blur, etc.).
        const docH = document.documentElement.scrollHeight - viewH;
        const scrollProgress = docH > 0 ? clamp(scrollY / docH, 0, 1) : 0;
        const root = document.documentElement.style;
        root.setProperty('--scroll-progress', scrollProgress.toFixed(4));
        root.setProperty('--scroll-velocity', clamp(scrollVelocity / 2000, -1, 1).toFixed(3));

        let anyMoved = false;

        for (let i = 0, len = items.length; i < len; i++) {
            const item = items[i];
            if (!item.visible) continue;

            // Let AOS finish its own entrance choreography before we start
            // writing inline transform/opacity on the same element.
            if (item.hasAos && item.aosDeadline !== null && now < item.aosDeadline &&
                !item.el.classList.contains('aos-animate')) continue;

            const { opts, rect, cur } = item;

            // Element center relative to viewport center, normalized ≈ [-1.5, 1.5]
            const elScreenCenter = rect.center - scrollY;
            let progress;
            switch (opts.origin) {
                case 'top':
                    progress = (rect.top - scrollY) / viewH;
                    break;
                case 'bottom':
                    progress = (rect.top + rect.height - scrollY - viewH) / viewH;
                    break;
                default:
                    progress = (elScreenCenter - viewH * 0.5) / (viewH * 0.5);
            }
            progress = clamp(progress, -2, 2);

            // ── Targets ─────────────────────────────────────────────
            let tY = progress * opts.y * 120 * mobileDampen;
            let tX = progress * opts.x * 120 * mobileDampen;
            const tRot = progress * opts.rotate * mobileDampen;

            if (opts.mouse) {
                tX += (pointerX - 0.5) * 2 * opts.mouse;
                tY += (pointerY - 0.5) * 2 * opts.mouse * 0.6;
            }

            let tOpacity = 1;
            if (opts.opacity) {
                const absP = Math.abs(progress);
                tOpacity = clamp(1 - absP * 0.55, 0.08, 1);
            }

            let tScale = 1;
            if (opts.scale > 0) {
                const absP = Math.abs(progress);
                tScale = 1 - clamp(absP * opts.scale * 1.5, 0, opts.scale);
            }

            let tBlur = 0;
            if (opts.blur > 0) {
                tBlur = clamp(Math.abs(progress) * opts.blur, 0, opts.blur);
            }

            let tSkew = 0;
            if (opts.skew > 0) {
                tSkew = clamp(scrollVelocity / 2000, -1, 1) * opts.skew;
            }

            // ── Frame-rate-independent exponential smoothing ─────────
            // Position/opacity/blur/skew track the main ease; rotation and
            // scale use a slightly slower "trailing" ease for that layered,
            // weighted-object feel instead of everything moving as one rigid piece.
            const alpha = expAlpha(opts.lambda, dt);
            const alphaSlow = expAlpha(opts.lambdaSlow, dt);

            const dy = tY - cur.y;
            const dx = tX - cur.x;
            const dOp = tOpacity - cur.opacity;
            const dSc = tScale - cur.scale;
            const dRot = tRot - cur.rotate;
            const dBl = tBlur - cur.blur;
            const dSk = tSkew - cur.skew;

            // Only update if delta is meaningful (skip sub-pixel noise)
            const threshold = 0.05;
            if (Math.abs(dy) > threshold || Math.abs(dx) > threshold ||
                Math.abs(dOp) > 0.002 || Math.abs(dSc) > 0.0005 ||
                Math.abs(dRot) > 0.01 || Math.abs(dBl) > 0.01 || Math.abs(dSk) > 0.002) {

                cur.y += dy * alpha;
                cur.x += dx * alpha;
                cur.opacity += dOp * alpha;
                cur.scale += dSc * alphaSlow;
                cur.rotate += dRot * alphaSlow;
                cur.blur += dBl * alpha;
                cur.skew += dSk * alpha;
                anyMoved = true;

                // ── Build transform (single string concat, no array) ─
                let tf = 'translate3d(' + cur.x.toFixed(1) + 'px,' + cur.y.toFixed(1) + 'px,0)';
                if (opts.scale > 0) tf += ' scale(' + cur.scale.toFixed(4) + ')';
                if (opts.rotate) tf += ' rotate(' + cur.rotate.toFixed(2) + 'deg)';
                if (opts.skew > 0) tf += ' skewY(' + cur.skew.toFixed(3) + 'deg)';

                item.el.style.transform = tf;
                if (opts.opacity) item.el.style.opacity = cur.opacity.toFixed(3);
                if (opts.blur > 0) item.el.style.filter = cur.blur > 0.02 ? 'blur(' + cur.blur.toFixed(2) + 'px)' : '';
            }
        }

        // Keep the loop alive while settling
        ticking = false;
        if (anyMoved) requestTick();
    }

    function requestTick() {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(render);
        }
    }

    // ── Events ──────────────────────────────────────────────────────
    window.addEventListener('scroll', () => {
        scrollY = window.scrollY;
        requestTick();
    }, { passive: true });

    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            viewH = window.innerHeight;
            scrollY = window.scrollY;
            for (const item of items) measureRect(item);
            requestTick();
        }, 100);
    }, { passive: true });

    if (allowMouseFX) {
        window.addEventListener('pointermove', (e) => {
            targetPointerX = e.clientX / window.innerWidth;
            targetPointerY = e.clientY / window.innerHeight;
            requestTick();
        }, { passive: true });
        window.addEventListener('pointerleave', () => {
            targetPointerX = 0.5;
            targetPointerY = 0.5;
            requestTick();
        }, { passive: true });
    }

    // ── Live reduced-motion toggle ───────────────────────────────────
    // If the OS-level setting is switched on mid-session, stop everything
    // and hand elements back their natural, un-transformed state.
    reduceMotionMQ.addEventListener('change', (e) => {
        if (!e.matches) return;
        running = false;
        io.disconnect();
        ro.disconnect();
        for (const item of items) {
            item.el.style.transform = '';
            item.el.style.opacity = '';
            item.el.style.filter = '';
            item.el.style.willChange = '';
        }
    });

    // ── Init ────────────────────────────────────────────────────────
    function init() {
        // Collect all elements that have data-prlx-* attributes
        const dataSelector = '[data-prlx-y],[data-prlx-x],[data-prlx-opacity],[data-prlx-scale],[data-prlx-rotate]';
        // Collect all elements that have preset classes
        const classSelector = Object.keys(PRESETS).map(c => '.' + c).join(',');

        const allSelector = dataSelector + ',' + classSelector;
        const els = document.querySelectorAll(allSelector);

        const seen = new Set();
        els.forEach(el => {
            if (seen.has(el)) return;
            seen.add(el);
            register(el);
        });

        // Start the render loop. From here it's fully self-scheduling —
        // it re-arms itself every frame while anything is still settling
        // (including this very first ease-into-place on load) and goes
        // idle the instant everything is at rest. No background timer.
        requestTick();

        // Pause when tab is hidden, resume cleanly when it's shown again.
        document.addEventListener('visibilitychange', () => {
            running = !document.hidden;
            if (running) {
                lastTime = performance.now();
                scrollY = window.scrollY;
                lastScrollY = scrollY;
                for (const item of items) measureRect(item);
                requestTick();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();