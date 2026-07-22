/**
 * AnimatedContent — reactbits.dev/animations/animated-content, ported from the
 * React/GSAP component to a vanilla, data-attribute-driven initializer for our
 * Blade admin console.
 *
 * Each target starts offset + transparent and eases into place (GSAP timeline)
 * when it scrolls into view (ScrollTrigger, once). The React props map 1:1 to
 * the timeline built below; per-element overrides come from data-* attributes.
 *
 * Targets:
 *   .animate-children > *   every top-level admin content block (the layout's
 *                           content wrap carries .animate-children — this is
 *                           what replaces the old CSS .stagger-enter entrance)
 *   [data-animate]          explicit opt-in for a specific block, anywhere
 *
 * Per-element overrides (defaults mirror the ReactBits props):
 *   data-animate-distance   100      px travelled
 *   data-animate-direction  vertical | horizontal
 *   data-animate-reverse    false    travel from the opposite side
 *   data-animate-duration   0.8      seconds
 *   data-animate-ease       power3.out
 *   data-animate-delay      (auto)   seconds; omitted → index cascade (≤0.3s)
 *   data-animate-scale      1        starting scale
 *   data-animate-threshold  0.1      fraction visible before it fires
 *   data-animate-opacity    0        initialOpacity
 *   data-animate-no-opacity (flag)   present → animateOpacity = false
 *
 * GSAP is loaded on demand: only admin pages carry the markers, so the shared
 * app.js bundle (also served to the public site) never pays for it. If GSAP
 * fails to load, or under reduced motion / no-JS, everything renders visible —
 * the hidden state lives behind `html.js-anim` + a no-preference media gate.
 */

const SELECTOR = '[data-animate], .animate-children > *';

function reduceMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// Drop the pre-paint gate so the CSS hidden state releases and every block
// renders normally — the fallback for reduced motion and GSAP-load failure.
function revealAll() {
    document.documentElement.classList.remove('js-anim');
}

function num(el, attr, fallback) {
    const v = el.getAttribute(attr);
    if (v === null || v === '') return fallback;
    const n = parseFloat(v);
    return Number.isNaN(n) ? fallback : n;
}

let gsapPromise = null;
function loadGsap() {
    if (!gsapPromise) {
        gsapPromise = Promise.all([
            import('gsap'),
            import('gsap/ScrollTrigger'),
        ]).then(([g, s]) => {
            const gsap = g.gsap || g.default;
            const ScrollTrigger = s.ScrollTrigger || s.default;
            gsap.registerPlugin(ScrollTrigger);
            return { gsap, ScrollTrigger };
        });
    }
    return gsapPromise;
}

// One element's reveal — a direct transcription of the ReactBits useEffect.
function animate(el, gsap, ScrollTrigger, index) {
    el.__animated = true;

    const direction = el.getAttribute('data-animate-direction') || 'vertical';
    const axis = direction === 'horizontal' ? 'x' : 'y';
    const distance = num(el, 'data-animate-distance', 100);
    const reverse = el.getAttribute('data-animate-reverse') === 'true';
    const duration = num(el, 'data-animate-duration', 0.8);
    const ease = el.getAttribute('data-animate-ease') || 'power3.out';
    const scale = num(el, 'data-animate-scale', 1);
    const threshold = num(el, 'data-animate-threshold', 0.1);
    const animateOpacity = !el.hasAttribute('data-animate-no-opacity');
    const initialOpacity = num(el, 'data-animate-opacity', 0);
    // Explicit delay wins; otherwise cascade the load-visible blocks like the
    // old .stagger-enter did (40ms → here 60ms steps, capped so deep pages
    // don't wait forever).
    const delay = el.hasAttribute('data-animate-delay')
        ? num(el, 'data-animate-delay', 0)
        : Math.min(index * 0.06, 0.3);

    const offset = reverse ? -distance : distance;
    const startPct = (1 - threshold) * 100;

    gsap.set(el, {
        [axis]: offset,
        scale,
        opacity: animateOpacity ? initialOpacity : 1,
        visibility: 'visible',
    });

    const tl = gsap.timeline({ paused: true, delay });
    tl.to(el, {
        [axis]: 0, scale: 1, opacity: 1, duration, ease,
        // Clear GSAP's residual inline transform once the reveal is done.
        // GSAP leaves e.g. transform: translate(0px, 0px) which is visually a
        // no-op but still creates a CSS containing block — that traps any
        // position:fixed descendants (modals) and makes them scroll with the
        // page instead of sticking to the viewport.
        // Keep inline opacity:1 — the CSS gate (html.js-anim) sets opacity:0,
        // and removing the inline override would hide the content again.
        onComplete() {
            el.style.transform = '';
        },
    });

    ScrollTrigger.create({
        trigger: el,
        start: `top ${startPct}%`,
        once: true,
        onEnter: () => tl.play(),
    });
}

function init(root) {
    const els = Array.prototype.filter.call(
        (root || document).querySelectorAll(SELECTOR),
        // Skip already-animated elements AND fixed-position overlays (modals):
        // modal wrappers live inside .animate-children but are full-screen fixed
        // overlays — applying scroll-reveal transforms to them causes a visible
        // position glitch when openModal() unhides them.
        (el) => !el.__animated && !el.classList.contains('fixed')
    );
    if (!els.length) return;

    loadGsap()
        .then(({ gsap, ScrollTrigger }) => {
            els.forEach((el, i) => animate(el, gsap, ScrollTrigger, i));
            // Elements already in view fire on the first refresh.
            ScrollTrigger.refresh();
        })
        .catch(revealAll);
}

function boot() {
    if (reduceMotion()) { revealAll(); return; }
    init(document);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

// Livewire SPA navigations swap the content wrap in place — re-scan so the
// new page's blocks get the same reveal.
document.addEventListener('livewire:navigated', boot);
