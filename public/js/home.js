/**
 * Landing-page behaviours (moved out of the old inline welcome.blade.php
 * script block): direct room booking, hero guests stepper, stat counters,
 * testimonials Swiper, and the mobile sticky reserve bar.
 *
 * Loaded as a blocking script after booking.js / availability-search.js so
 * `bookRoomDirect` exists before any room card can be clicked.
 */

function bookRoomDirect(roomId) {
    if (!roomId) return;
    if (window.LAST_AVAILABILITY && window.LAST_AVAILABILITY.summary) {
        const row = window.LAST_AVAILABILITY.summary.find(s => s.room_type === roomId);
        if (row && row.available <= 0) {
            alert('This room type is fully booked for the selected dates.');
            return;
        }
    }
    const checkIn = document.getElementById('widget_check_in').value;
    const checkOut = document.getElementById('widget_check_out').value;
    const guests = document.getElementById('widget_guests').value;
    let url = `/checkout?room_type=${roomId}`;
    if (checkIn) url += `&check_in=${checkIn}`;
    if (checkOut) url += `&check_out=${checkOut}`;
    if (guests) url += `&guests=${guests}`;
    window.location.href = url;
}

document.addEventListener('DOMContentLoaded', function () {
    // Guests stepper
    const minusBtn = document.getElementById('btn_minus_guests');
    const plusBtn = document.getElementById('btn_plus_guests');
    const display = document.getElementById('guests_display');
    const plural = document.getElementById('guests_plural');
    const hiddenInput = document.getElementById('widget_guests');

    // Odometer roll (vengence-ui animated-number): outgoing value slides
    // out, incoming slides in from the opposite edge based on direction.
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function setGuests(val) {
        val = Math.min(40, Math.max(1, val));
        const prev = parseInt(hiddenInput.value) || 1;
        hiddenInput.value = val;
        plural && plural.classList.toggle('hidden', val === 1);
        // If results are already on screen, re-filter room types live.
        if (window.LAST_AVAILABILITY && window.__applyGuestFilter) window.__applyGuestFilter(val);
        if (val === prev) return;

        const current = display.querySelector('span:not(.is-leaving)');
        if (reduceMotion || !current || !current.animate) {
            display.textContent = '';
            const s = document.createElement('span');
            s.textContent = val;
            display.appendChild(s);
            return;
        }

        const dir = val > prev ? 1 : -1;
        const next = document.createElement('span');
        next.textContent = val;
        display.appendChild(next);

        const easing = 'cubic-bezier(0.22, 1, 0.36, 1)';
        current.classList.add('is-leaving');
        current.animate([
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
            { transform: `translateY(${dir * -100}%)`, opacity: 0, filter: 'blur(2px)' },
        ], { duration: 260, easing, fill: 'forwards' }).onfinish = () => current.remove();
        next.animate([
            { transform: `translateY(${dir * 100}%)`, opacity: 0, filter: 'blur(2px)' },
            { transform: 'translateY(0)', opacity: 1, filter: 'blur(0px)' },
        ], { duration: 260, easing });
    }
    if (minusBtn && plusBtn && display && hiddenInput) {
        minusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) - 1); });
        plusBtn.addEventListener('click', (e) => { e.stopPropagation(); setGuests((parseInt(hiddenInput.value) || 1) + 1); });
    }

    // Stats strip: numerals roll up the first time the strip enters the
    // viewport ("₱1,600" → counts to 1,600; "24/7" → counts the 24).
    // Static markup stays the no-JS / reduced-motion fallback.
    const statEls = document.querySelectorAll('.stat-value');
    if (statEls.length && !reduceMotion && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                obs.unobserve(entry.target);
                const el = entry.target;
                const m = (el.textContent || '').trim().match(/^([^\d]*)([\d,]+)(.*)$/);
                if (!m) return;
                const prefix = m[1], target = parseInt(m[2].replace(/,/g, ''), 10), suffix = m[3];
                if (!target) return;
                const t0 = performance.now(), dur = 1400;
                const ease = t => 1 - Math.pow(1 - t, 4);
                (function tick(now) {
                    const p = Math.min(1, ((now || performance.now()) - t0) / dur);
                    el.textContent = prefix + Math.round(target * ease(p)).toLocaleString('en-PH') + suffix;
                    if (p < 1) requestAnimationFrame(tick);
                })();
            });
        }, { threshold: 0.4 });
        statEls.forEach(el => io.observe(el));
    }

    // Testimonials Swiper — stacked card deck (bundle's `cards` effect):
    // the next quote peeks from behind with a slight fan, and the deck is
    // draggable as well as button-driven. Shadows off — the glass cards
    // carry their own night shadow. Reduced-motion keeps a plain slide.
    //
    // Swiper is no longer on the page by the time this runs: partials/vendor/
    // swiper.blade.php fetches it when the deck comes within 600px of the
    // viewport, so construction waits for that. `fhVendorReady` covers the
    // case where the library landed before this handler ran.
    function initTestimonials() {
        if (typeof Swiper === 'undefined') return;

        new Swiper('.testimonials-swiper', {
            effect: reduceMotion ? 'slide' : 'cards',
            cardsEffect: { perSlideOffset: 9, perSlideRotate: 2.2, slideShadows: false },
            grabCursor: true,
            // rewind, not loop: the cards effect positions slides by real index,
            // and loop's clone/reorder machinery deadlocks it (slideNext no-ops).
            rewind: true,
            speed: 650,
            autoplay: reduceMotion ? false : { delay: 7000, pauseOnMouseEnter: true, disableOnInteraction: false },
            navigation: {
                nextEl: '.swiper-button-next-custom',
                prevEl: '.swiper-button-prev-custom',
            },
        });
    }

    if (window.fhVendorReady && window.fhVendorReady.swiper) initTestimonials();
    else document.addEventListener('fh:swiper-ready', initTestimonials, { once: true });

    // Idle the page's two endless loops while they are off-screen.
    //
    // `fh-drift` on the hero building and `fh-marquee` on the strip below it
    // are the only `infinite` animations the landing actually runs. Both are
    // transform-only and cheap per frame, but neither ever stops, so the
    // compositor keeps working on a hero the reader left twenty screens ago.
    // Pausing (not cancelling) means they resume mid-stride, and the 200px
    // margin has them moving again before they can be seen — matching the
    // rotator and wordmark observers elsewhere in this file.
    //
    // See .fh-marquee-track.is-paused / .fh-hero-build.is-paused in
    // 06-hero.css. The marquee's existing hover-to-pause rule is untouched.
    if (window.IntersectionObserver) {
        document.querySelectorAll('.fh-hero-build, .fh-marquee-track').forEach(function (el) {
            new IntersectionObserver(function (entries) {
                el.classList.toggle('is-paused', !entries[0].isIntersecting);
            }, { rootMargin: '200px 0px' }).observe(el);
        });
    }

    // Mobile sticky bar appears after the hero
    const stickyBar = document.getElementById('mobileStickyBar');
    const heroSection = document.getElementById('firstsection');
    if (stickyBar && heroSection) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                stickyBar.classList.toggle('translate-y-full', entry.isIntersecting);
            });
        }, { threshold: 0.1 });
        observer.observe(heroSection);
    }
});

// ── Hero rotating superlative ────────────────────────────────────
// FlipFadeText (vengenceui.com/components/flip-fade-text) ported to vanilla:
// each word's characters flip up into place on the X axis with a per-character
// stagger. The incoming word flips in (rotateX 90°→0, blur 8px→0) while the
// outgoing one flips out (0→-90°) — the FlipFadeText enter/exit, minus
// framer-motion. The first word's characters flip in via the CSS .flip-char
// entrance (shared with the static headline words); home.js reuses those same
// chars, then cycles the words with the same flip. The track's width eases to
// each word. Skips under reduced motion — the static first word stays.
(function () {
    const host = document.getElementById('heroWordRotate');
    if (!host) return;
    const reduceMQ = window.matchMedia('(prefers-reduced-motion: reduce)');
    const track = host.querySelector('.word-rotate-track');
    const words = (host.dataset.words || '').split(',').map(w => w.trim()).filter(Boolean);

    if (reduceMQ.matches) return; // reduced motion keeps the static first word

    if (!track || words.length < 2) return;

    // FlipFadeText tuning.
    const STAGGER = 34;   // ms between characters (enter stagger feel)
    const DURATION = 600; // per-character flip (letterDuration 0.6s)
    const FLIP = 'cubic-bezier(0.2, 0.65, 0.3, 0.9)'; // FlipFadeText easing
    const INTERVAL = 2600;

    // Grapheme-aware split, like ReactBits' Intl.Segmenter path.
    const seg = (window.Intl && Intl.Segmenter)
        ? new Intl.Segmenter(undefined, { granularity: 'grapheme' })
        : null;
    function graphemes(text) {
        return seg ? Array.from(seg.segment(text), s => s.segment) : Array.from(text);
    }

    // Replace a word box's text with per-character .rt-char spans.
    function buildChars(box, text) {
        box.textContent = '';
        return graphemes(text).map(function (ch) {
            const c = document.createElement('span');
            c.className = 'rt-char';
            c.textContent = ch;
            box.appendChild(c);
            return c;
        });
    }

    // One box per word; the first stays in flow so the pre-live track has a
    // size while fonts load, the clones start absolute + hidden.
    const first = track.querySelector('.word-rotate-word');
    const boxes = words.map(function (w, i) {
        if (i === 0) {
            // Reuse the Blade-rendered characters — they carry .flip-char for the
            // CSS entrance, so rebuilding them here would wipe that flip-in.
            first.__chars = Array.prototype.slice.call(first.querySelectorAll('.rt-char'));
            if (!first.__chars.length) first.__chars = buildChars(first, w);
            return first;
        }
        const box = first.cloneNode(false);
        box.classList.remove('is-active');
        box.style.position = 'absolute';
        box.style.left = '0';
        box.style.top = '0';
        box.style.visibility = 'hidden';
        track.appendChild(box);
        box.__chars = buildChars(box, w);
        return box;
    });

    function setChars(chars, transform, opacity) {
        chars.forEach(function (c) {
            c.style.transform = transform;
            c.style.opacity = opacity;
        });
    }

    // Flip a set of chars from → to with a per-character stagger, then commit
    // the resting state on finish so the WAAPI animations don't pile up.
    function roll(chars, from, to, ease, restTransform, restOpacity) {
        chars.forEach(function (c, i) {
            if (c.__a) c.__a.cancel();
            const a = c.animate([from, to], {
                duration: DURATION,
                delay: i * STAGGER,
                easing: ease,
                fill: 'both',
            });
            c.__a = a;
            a.onfinish = function () {
                c.style.transform = restTransform;
                c.style.opacity = restOpacity;
                c.style.filter = '';
                a.cancel();
                if (c.__a === a) c.__a = null;
            };
        });
    }

    let widths = [];
    let idx = 0;
    let timer = null;

    // The word boxes are taken out of flow, so the track has to *reserve* their
    // size inline — which means that reservation goes stale the moment the type
    // metrics change under it (crossing the desktop breakpoint, a late italic
    // webfont swapping in, browser zoom). A stale slot leaves the absolutely
    // positioned word rendering at its new natural size over a slot sized for
    // the old one, so it spills across the neighbouring words. Re-measure
    // instead of measuring once.
    function measure() {
        if (!boxes.length) return;
        widths = boxes.map(function (b) { return b.offsetWidth; });
        // Snap rather than glide: the width transition is for word swaps, not
        // for a resize the user is dragging through.
        const prevTransition = track.style.transition;
        track.style.transition = 'none';
        track.style.width = widths[idx] + 'px';
        track.style.height = boxes[idx].offsetHeight + 'px';
        void track.offsetWidth;
        track.style.transition = prevTransition;
    }

    function start() {
        widths = boxes.map(function (b) { return b.offsetWidth; });
        track.style.height = first.offsetHeight + 'px';
        track.style.width = widths[0] + 'px';
        // Retire the first word's CSS flip-in before we drive it via WAAPI: a
        // finished fill:both CSS animation would otherwise override our inline
        // transforms during cycling.
        first.__chars.forEach(function (c) { c.classList.remove('flip-char'); c.style.filter = ''; });
        boxes.forEach(function (b, i) {
            b.style.position = 'absolute';
            b.style.left = '0';
            b.style.top = '0';
            b.style.visibility = '';
            // First word flat at rest; the rest parked flipped-up + invisible.
            setChars(b.__chars, i === 0 ? 'rotateX(0deg)' : 'rotateX(62deg) translateY(10px)', i === 0 ? '1' : '0');
        });
        host.classList.add('is-live');

        window.addEventListener('resize', measure);
        // Watches a static sibling word rather than the track itself, so the
        // writes in measure() can't feed back into the observer.
        const ref = document.querySelector('#heroTitle .split-word');
        if (ref && window.ResizeObserver) new ResizeObserver(measure).observe(ref);

        // The rotator only pauses for a hidden *tab*. It kept cycling while the
        // hero was scrolled past, flipping twenty characters through WAAPI on a
        // timer nobody could see, and `.word-rotate.is-live .rt-char` held
        // `will-change: transform, opacity` on all twenty for the session.
        //
        // Gating on the hero's own visibility stops both: no swap runs
        // off-screen, and `is-offscreen` hands the layers back (see
        // 06-hero.css). A generous rootMargin means it is already running again
        // before it can be seen, so scrolling back up never catches a static
        // word mid-cycle.
        let onScreen = true;
        if (window.IntersectionObserver) {
            new IntersectionObserver(function (entries) {
                onScreen = entries[0].isIntersecting;
                host.classList.toggle('is-offscreen', !onScreen);
            }, { rootMargin: '200px 0px' }).observe(host);
        }

        timer = setInterval(function () {
            if (!document.hidden && onScreen) swap();
        }, INTERVAL);
    }

    function swap() {
        // Belt and braces: re-measure on the way in as well as from the
        // observers, so the slot self-corrects on the next cycle even if a
        // metrics change slipped past them.
        measure();

        const leaving = boxes[idx];
        idx = (idx + 1) % boxes.length;
        const entering = boxes[idx];

        // 62deg, not 88: a near-edge-on glyph spends most of the flip as an
        // unreadable sliver and its projection reaches into the line below.
        roll(entering.__chars,
            { transform: 'rotateX(62deg) translateY(10px)', opacity: 0 },
            { transform: 'rotateX(0deg) translateY(0px)', opacity: 1 },
            FLIP, 'rotateX(0deg)', '1');

        roll(leaving.__chars,
            { transform: 'rotateX(0deg)', opacity: 1 },
            { transform: 'rotateX(-62deg) translateY(-10px)', opacity: 0 },
            FLIP, 'rotateX(62deg) translateY(10px)', '0'); // park flipped-up, ready to re-enter

        track.style.width = widths[idx] + 'px';
    }

    // Start cycling once fonts are ready (for width measurement) AND the first
    // word's flip-in entrance has finished — so we never strip it mid-flip. Using
    // the animation's own finished promise (not a fixed timer) means it also
    // waits correctly for the intro curtain, which pauses the CSS animation.
    function armStart() {
        let started = false;
        const go = function () { if (started) return; started = true; start(); };
        const chars = first.__chars;
        const last = chars && chars[chars.length - 1];
        const a = last && last.getAnimations && last.getAnimations()[0];
        if (a && a.finished) {
            a.finished.then(function () { setTimeout(go, 140); }, go);
            setTimeout(go, 5000); // fallback if it never resolves
        } else {
            setTimeout(go, 1800); // no entrance animation (e.g. cached fonts) — original cadence
        }
    }
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(armStart);
    } else {
        setTimeout(armStart, 200);
    }

    // Live reduced-motion switch: stop and restore the static first word.
    reduceMQ.addEventListener('change', function (e) {
        if (!e.matches) return;
        clearInterval(timer);
        host.classList.remove('is-live');
        boxes.forEach(function (b, i) {
            b.__chars.forEach(function (c) {
                if (c.__a) { c.__a.cancel(); c.__a = null; }
                c.style.transform = '';
                c.style.opacity = '';
            });
            b.style.visibility = i === 0 ? '' : 'hidden';
        });
        track.style.width = '';
        track.style.height = '';
    });
})();

// ── Hero scroll parallax + magnetic buttons ──────────────────────
// The giant wordmark drifts up and dissolves as the hero leaves, while a
// dusk wash fades in over the sky — so the marquee below emerges out of a
// darkening frame rather than a hard cut. Both are written straight to
// style in a rAF-coalesced scroll handler (no layout reads per frame).
(function () {
    const word = document.querySelector('[data-hero-word]');
    const deepen = document.querySelector('[data-hero-deepen]');
    const hero = document.getElementById('firstsection');
    if (!hero || (!word && !deepen)) return;

    const reduceMQ = window.matchMedia('(prefers-reduced-motion: reduce)');
    let ticking = false;
    // Placeholder, not a measurement. measure() below sets the real value and
    // is the only thing that should read layout — taking offsetHeight here as
    // well forced a synchronous layout during script execution (PageSpeed:
    // 124ms of forced reflow) to produce a number that was overwritten
    // milliseconds later anyway.
    let heroH = 800;

    function paint() {
        ticking = false;
        const y = window.pageYOffset || document.documentElement.scrollTop || 0;
        const p = Math.min(1, y / Math.max(heroH, 1));
        if (word) {
            word.style.transform = 'translate3d(0, ' + (-p * 96).toFixed(1) + 'px, 0)';
            word.style.opacity = String(Math.max(0, 1 - p * 1.15));
        }
        if (deepen) deepen.style.opacity = Math.min(1, p * 1.15).toFixed(3);
    }

    function onScroll() {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(paint);
    }

    function measure() {
        heroH = hero.offsetHeight || 800;
        onScroll();
    }

    if (reduceMQ.matches) return;
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', measure);
    // Deferred a frame so the first measurement happens after the browser has
    // laid out on its own schedule, rather than forcing it mid-parse. At scroll
    // 0 the hero transform is identity, so there is nothing to see in the gap;
    // a page restored mid-scroll simply paints its offset one frame later.
    requestAnimationFrame(measure);
})();

// ── Wordmark proximity wave ──────────────────────────────────────
// Ported from reactbits VariableProximity (reactbits.dev/text-animations/
// variable-proximity): per-letter distance to the cursor, run through a
// gaussian falloff, drives an interpolated value on each character.
//
// One deliberate change from the original: reactbits interpolates
// font-variation-settings ('wght'). Playfair Display's weight axis changes
// glyph *advance*, so at 178-238px every mouse move would reflow the line and
// the letters would visibly jitter. This drives transform + colour instead —
// same falloff maths, but composited, so nothing reflows: letters lift and
// warm toward the brand gold as the cursor sweeps past.
(function () {
    const wrap = document.querySelector('[data-hero-word]');
    if (!wrap) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (!window.matchMedia('(pointer: fine)').matches) return;

    const chars = Array.prototype.slice.call(wrap.querySelectorAll('.fh-wm-char'));
    if (!chars.length) return;

    const RADIUS = 340;   // px of influence around the cursor
    const LIFT = 18;      // px a letter rises at full strength
    const GROW = 0.06;    // extra scale at full strength
    const GOLD = [237, 211, 155];
    const WHITE = [255, 255, 255];

    // Letter centres are cached: re-reading 13 rects per mousemove would be a
    // layout thrash, and the cached values only go stale when the line moves
    // (scroll parallax, resize) — not when we write transforms.
    let centres = null;
    const invalidate = () => { centres = null; };
    function measure() {
        centres = chars.map(function (c) {
            const r = c.getBoundingClientRect();
            return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
        });
        return centres;
    }

    window.addEventListener('resize', invalidate);
    window.addEventListener('scroll', invalidate, { passive: true });

    // The mousemove handler is rAF-throttled rather than run inline.
    //
    // Three costs stacked up in the inline version, and all three were paid at
    // the mouse's polling rate — 500-1000Hz on the gaming mice that are common
    // on Windows, i.e. up to 16 times per frame:
    //
    //  1. `centres || measure()` reads 13 getBoundingClientRect()s. The scroll
    //     listener above nulls `centres` on every scroll, so wheel-scrolling
    //     with the cursor over the page (the normal desktop posture) meant a
    //     forced synchronous layout on the very next mousemove — the classic
    //     read-after-write thrash, interleaved with the parallax loop's writes.
    //  2. Writing `style.color` repaints the glyph, and these are 178-238px
    //     Playfair characters. Transform is composited; colour is not.
    //  3. `.fh-wm-char` carries a CSS transition on transform/colour, so each
    //     write retargets a running transition instead of finishing one.
    //
    // Coalescing to one paint per frame caps all of it at 60Hz, and doing the
    // rect reads inside rAF puts them at a point in the frame where layout is
    // already clean. Pointer-coarse devices never ran any of this, which is why
    // the jank only ever showed up on desktop.
    let pending = false;
    let mouseX = 0, mouseY = 0;

    function paint() {
        pending = false;
        const cs = centres || measure();
        for (let i = 0; i < chars.length; i++) {
            const dx = mouseX - cs[i].x;
            const dy = mouseY - cs[i].y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist >= RADIUS) {
                if (chars[i].__lit) {
                    chars[i].style.transform = '';
                    chars[i].style.color = '';
                    chars[i].__lit = false;
                    chars[i].__f = -1;
                }
                continue;
            }

            // gaussian falloff, as in the reactbits original
            const f = Math.exp(-Math.pow(dist / (RADIUS / 2), 2) / 2);
            // Sub-perceptual changes still cost a repaint on a glyph this
            // large, so hold the last value until the falloff actually moves.
            if (chars[i].__f !== undefined && Math.abs(f - chars[i].__f) < 0.004) continue;
            chars[i].__f = f;
            chars[i].style.transform =
                'translate3d(0,' + (-f * LIFT).toFixed(2) + 'px,0) scale(' + (1 + f * GROW).toFixed(4) + ')';
            chars[i].style.color = 'rgb('
                + Math.round(WHITE[0] + (GOLD[0] - WHITE[0]) * f) + ','
                + Math.round(WHITE[1] + (GOLD[1] - WHITE[1]) * f) + ','
                + Math.round(WHITE[2] + (GOLD[2] - WHITE[2]) * f) + ')';
            chars[i].__lit = true;
        }
    }

    // The wave can only be seen while the wordmark is on screen, but the
    // handler ran for the whole page: every mousemove anywhere on the site
    // queued a frame that measured and wrote thirteen characters sitting far
    // above the fold. `is-offscreen` also releases the `will-change: transform`
    // those characters hold (see 06-hero.css), so the hero stops costing
    // thirteen compositor layers the moment it is scrolled past.
    let inView = true;
    if (window.IntersectionObserver) {
        new IntersectionObserver(function (entries) {
            inView = entries[0].isIntersecting;
            wrap.classList.toggle('is-offscreen', !inView);
            if (!inView) invalidate();
        }, { rootMargin: '150px 0px' }).observe(wrap);
    }

    window.addEventListener('mousemove', function (e) {
        if (!inView) return;
        mouseX = e.clientX;
        mouseY = e.clientY;
        if (!pending) { pending = true; requestAnimationFrame(paint); }
    }, { passive: true });
})();

// ── Magnetic buttons ─────────────────────────────────────────────
// [data-magnetic] controls lean a few pixels toward the cursor and spring
// back on leave. Pointer-coarse devices skip it — there's no cursor to
// follow, and the transform would fight the tap highlight.
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    if (!window.matchMedia('(pointer: fine)').matches) return;

    // The hover transitions (gap/background/shadow) have to be restated here —
    // setting style.transition wholesale would otherwise drop them and the
    // buttons would snap instead of easing on hover.
    const REST = ', background .4s, color .4s, gap .4s, box-shadow .4s, border-color .4s';
    const EASE_IN = 'transform .18s cubic-bezier(.2,.7,.2,1)' + REST;
    const EASE_OUT = 'transform .5s cubic-bezier(.2,.7,.2,1)' + REST;

    document.querySelectorAll('[data-magnetic]').forEach(function (el) {
        // The rect is measured once on enter, not on every move.
        //
        // Reading getBoundingClientRect() inside the mousemove handler forced a
        // synchronous layout on each event — and because the handler's previous
        // pass had just written `transform` on this same element, that read had
        // to flush the pending style change first. Read-after-write, at the
        // mouse's polling rate, on the hero's three buttons.
        //
        // The element cannot move relative to the cursor between enter and
        // leave without the pointer crossing its edge (which re-fires enter), so
        // one measurement per hover is all the maths needs. `transform` is
        // excluded from the read by construction now — we never read it back.
        let rect = null;
        let queued = false;
        let px = 0, py = 0;

        function apply() {
            queued = false;
            if (!rect) return;
            const dx = ((px - (rect.left + rect.width / 2)) / Math.max(rect.width, 1)) * 15;
            const dy = ((py - (rect.top + rect.height / 2)) / Math.max(rect.height, 1)) * 9 - 3;
            el.style.transform = 'translate(' + dx.toFixed(1) + 'px, ' + dy.toFixed(1) + 'px)';
            el.style.transition = EASE_IN;
        }

        el.addEventListener('mouseenter', function () {
            rect = el.getBoundingClientRect();
        });
        el.addEventListener('mousemove', function (e) {
            if (!rect) rect = el.getBoundingClientRect();
            px = e.clientX;
            py = e.clientY;
            if (!queued) { queued = true; requestAnimationFrame(apply); }
        }, { passive: true });
        el.addEventListener('mouseleave', function () {
            rect = null;
            el.style.transform = 'translate(0, 0)';
            el.style.transition = EASE_OUT;
        });
    });
})();
