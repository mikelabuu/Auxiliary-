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

    // ── BlurText (reactbits.dev/text-animations/blur-text port) ──────
    // Split [data-blur-text] elements into word spans (recursing through
    // inline markup like the italic gold spans, so styling is preserved).
    // The cascade itself is pure CSS, keyed off the ancestor's .aos-animate
    // reveal — same trigger, no second observer. Skipped under reduced
    // motion and without JS the markup stays untouched and fully visible.
    if (!reduceMotion) {
        document.querySelectorAll('[data-blur-text]').forEach(function (root) {
            var i = 0;
            (function split(el) {
                Array.prototype.slice.call(el.childNodes).forEach(function (node) {
                    if (node.nodeType === 3) {
                        var frag = document.createDocumentFragment();
                        node.textContent.split(/(\s+)/).forEach(function (part) {
                            if (!part) return;
                            if (/^\s+$/.test(part)) {
                                frag.appendChild(document.createTextNode(part));
                            } else {
                                var s = document.createElement('span');
                                s.className = 'bt-word';
                                s.style.setProperty('--bt-i', i++);
                                s.textContent = part;
                                frag.appendChild(s);
                            }
                        });
                        el.replaceChild(frag, node);
                    } else if (node.nodeType === 1) {
                        split(node);
                    }
                });
            })(root);
            root.classList.add('bt-on'); // arms the hidden state in app.css
        });
    }

    // ── SplitText (reactbits.dev/text-animations/split-text port) ────
    // Split [data-split-text] elements into per-character spans, grouped in
    // inline-block .split-word wrappers so words never break mid-line. Recurses
    // through inline markup (italic gold spans) so styling survives. The reveal
    // is pure CSS keyed off the ancestor's .aos-animate — same trigger as
    // BlurText. Skipped under reduced motion; without JS the text stays plain.
    if (!reduceMotion) {
        document.querySelectorAll('[data-split-text]').forEach(function (root) {
            var i = 0;
            (function split(el) {
                Array.prototype.slice.call(el.childNodes).forEach(function (node) {
                    if (node.nodeType === 3) {
                        var frag = document.createDocumentFragment();
                        node.textContent.split(/(\s+)/).forEach(function (part) {
                            if (!part) return;
                            if (/^\s+$/.test(part)) {
                                frag.appendChild(document.createTextNode(part));
                            } else {
                                var word = document.createElement('span');
                                word.className = 'split-word';
                                Array.from(part).forEach(function (ch) {
                                    var c = document.createElement('span');
                                    c.className = 'split-char';
                                    c.style.setProperty('--i', i++);
                                    c.textContent = ch;
                                    word.appendChild(c);
                                });
                                frag.appendChild(word);
                            }
                        });
                        el.replaceChild(frag, node);
                    } else if (node.nodeType === 1) {
                        split(node);
                    }
                });
            })(root);
            root.classList.add('st-on'); // arms the hidden state in app.css
        });
    }

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
