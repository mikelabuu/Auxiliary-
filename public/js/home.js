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
            setChars(b.__chars, i === 0 ? 'rotateX(0deg)' : 'rotateX(90deg) translateY(20px)', i === 0 ? '1' : '0');
        });
        host.classList.add('is-live');
        timer = setInterval(function () { if (!document.hidden) swap(); }, INTERVAL);
    }

    function swap() {
        const leaving = boxes[idx];
        idx = (idx + 1) % boxes.length;
        const entering = boxes[idx];

        roll(entering.__chars,
            { transform: 'rotateX(88deg) translateY(16px)', opacity: 0 },
            { transform: 'rotateX(0deg) translateY(0px)', opacity: 1 },
            FLIP, 'rotateX(0deg)', '1');

        roll(leaving.__chars,
            { transform: 'rotateX(0deg)', opacity: 1 },
            { transform: 'rotateX(-88deg) translateY(-16px)', opacity: 0 },
            FLIP, 'rotateX(88deg) translateY(16px)', '0'); // park flipped-up, ready to re-enter

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
