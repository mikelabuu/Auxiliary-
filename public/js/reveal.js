/**
 * Farmers Hostel — scroll reveal engine.
 *
 * Local replacement for the AOS CDN pair (aos.css + aos.js). Keeps the exact
 * same markup contract so nothing else changes:
 *
 *   data-aos="fade-up"        → element starts lowered + transparent, eases in
 *   data-aos-delay="150"      → per-element stagger in ms
 *   .aos-animate               → added when revealed (parallax.js waits for
 *                                this class before driving an element, so the
 *                                handoff logic there keeps working untouched)
 *
 * The initial hidden state lives in app.css behind `.js-reveal` on <html>
 * (added by an inline snippet in the layout head), so if this file ever fails
 * to load the page renders fully visible — no invisible-content failure mode,
 * which the CDN version did have.
 *
 * Smoother than AOS in two ways: the transition uses the boutique spring
 * curve over 0.9s instead of ease-out-cubic over 0.7s, and once an element
 * that parallax also drives finishes its entrance we zero out its transition
 * so the parallax engine's own frame-rate-independent easing isn't filtered
 * through a second CSS easing (AOS left both fighting).
 */
(function () {
    'use strict';

    var els = document.querySelectorAll('[data-aos]');
    if (!els.length) return;

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Elements the parallax engine also drives — after their entrance we hand
    // transform/opacity control back to it cleanly.
    function hasParallax(el) {
        if (el.className && /(^|\s)prlx-/.test(el.className)) return true;
        for (var key in el.dataset) {
            if (key.indexOf('prlx') === 0) return true;
        }
        return false;
    }

    function finishReveal(el) {
        el.style.transitionDelay = '';
        if (hasParallax(el)) el.style.transition = 'none';
    }

    function reveal(el) {
        var delay = parseInt(el.getAttribute('data-aos-delay'), 10) || 0;
        if (delay) el.style.transitionDelay = delay + 'ms';
        el.classList.add('aos-animate');
        // 950ms transition + stagger, then release the transition so later
        // inline writes (parallax) and state toggles aren't slowed by it.
        setTimeout(function () { finishReveal(el); }, 1000 + delay);
    }

    if (reduceMotion || !('IntersectionObserver' in window)) {
        els.forEach(function (el) {
            el.classList.add('aos-animate');
            finishReveal(el);
        });
        return;
    }

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            io.unobserve(entry.target);
            reveal(entry.target);
        });
    }, {
        // Fire slightly before the element's top clears the fold, like AOS's
        // offset: 60 — reveals feel anticipatory rather than late.
        rootMargin: '0px 0px -60px 0px',
        threshold: 0,
    });

    els.forEach(function (el) { io.observe(el); });
})();
