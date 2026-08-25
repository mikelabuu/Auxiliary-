{{-- Shared on-approach loader for vendor libraries whose UI lives far below
     the fold.

     The vendor partials used to emit their <link> and <script defer> straight
     into <head>. `defer` stops them blocking the parser, but it does not stop
     them being *downloaded* — every visitor paid for Swiper (151 KB) and
     lightbox2 (98 KB, jQuery bundled) on first load, for a testimonial deck
     ~10 screens down and a gallery ~14 screens down that most visits on a
     phone never scroll to.

     This registers the library instead and fetches it when the elements that
     need it come within `margin` of the viewport, then fires `event` on
     document so the consumer can initialise. Loading is idempotent: repeated
     triggers resolve against the same promise.

     No IntersectionObserver (or no JS module support) means we simply load it
     straight away — the old behaviour — rather than leaving the feature dead.

     Registered via @include('partials.vendor.lazy-loader') from each vendor
     partial; @once keeps it to a single copy per page. --}}
@once
<script>
(function () {
    var loaded = {};

    function inject(cfg) {
        if (loaded[cfg.name]) return loaded[cfg.name];

        loaded[cfg.name] = new Promise(function (resolve) {
            (cfg.css || []).forEach(function (href) {
                var link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = href;
                document.head.appendChild(link);
            });

            // Sequential, because a bundle's plugins can depend on its core.
            var srcs = (cfg.js || []).slice();
            (function next() {
                if (!srcs.length) return resolve();
                var s = document.createElement('script');
                s.src = srcs.shift();
                // Not async: order within one library must hold.
                s.async = false;
                s.onload = next;
                // A 404 or a blocked request should not strand the page in a
                // half-initialised state — carry on and let the consumer's
                // own guards handle the missing global.
                s.onerror = next;
                document.head.appendChild(s);
            })();
        }).then(function () {
            document.dispatchEvent(new CustomEvent(cfg.event));
            // Late subscribers can check the flag instead of waiting for an
            // event that has already fired.
            (window.fhVendorReady = window.fhVendorReady || {})[cfg.name] = true;
        });

        return loaded[cfg.name];
    }

    // The vendor partials push into <head>, so this is called while the body
    // is still being parsed and cfg.watch would match nothing. Wait for the
    // document before looking for targets.
    window.fhLazyVendor = function (cfg) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () { arm(cfg); }, { once: true });
        } else {
            arm(cfg);
        }
    };

    // Scroll position rather than IntersectionObserver.
    //
    // IO is the obvious tool here and it is what this used first, but it ties
    // delivery to the rendering lifecycle: a document that is not being
    // painted never gets a callback, so the carousel and the gallery silently
    // stayed dead in exactly the headless/background cases that are hardest to
    // notice. requestAnimationFrame has the same dependency, which rules out
    // the usual rAF-coalesced scroll handler as well. Comparing scrollY
    // against a precomputed number is plain arithmetic: it holds whether or
    // not a frame is ever produced.
    //
    // The trigger point is measured once (and again on resize / load, since
    // images settling above the target move it) so the scroll handler itself
    // reads no geometry — it compares two numbers and does nothing else, which
    // is cheaper than the rAF version it replaced and cannot force a layout on
    // the scroll path. It unhooks the moment it fires.
    function arm(cfg) {
        var targets = document.querySelectorAll(cfg.watch);
        if (!targets.length) return;

        var margin = parseInt(cfg.margin, 10);
        if (isNaN(margin)) margin = 600;
        var triggerY = Infinity;

        function measure() {
            // Distance down the page at which the nearest target is `margin`
            // away from the bottom of the viewport.
            // scrollY and innerHeight are hoisted: neither can change while
            // this loop runs, so reading them per target only repeats the same
            // two lookups N times.
            var sy = window.scrollY;
            var vh = window.innerHeight;
            var top = Infinity;
            for (var i = 0; i < targets.length; i++) {
                top = Math.min(top, targets[i].getBoundingClientRect().top + sy);
            }
            triggerY = top - vh - margin;
            check();
        }

        function check() {
            // A scroll that beat the deferred measurement below: take the
            // measurement now rather than miss the trigger. This is what lets
            // the scheduling be lazy without giving up the guarantee, and it
            // can happen at most once — measure() sets triggerY. A scroll event
            // is dispatched with the previous frame's layout already complete,
            // so it is also the cheapest moment available outside idle time.
            if (triggerY === Infinity) { measureOnce(); return; }
            if (window.scrollY < triggerY) return;
            window.removeEventListener('scroll', check);
            window.removeEventListener('resize', measure);
            window.removeEventListener('load', onLoad);
            inject(cfg);
        }

        // Images settling after load move every target beneath them, so the
        // trigger point has to be retaken — but not *during* the load burst,
        // which is where the old direct binding put it, forcing a layout while
        // the browser was still working through the last of the images. A
        // couple of hundred milliseconds later the page is quiet and the read
        // is nearly free; nothing can need the new value before then, because
        // needing it means scrolling, and check() re-measures on demand.
        function onLoad() { setTimeout(measure, 250); }

        window.addEventListener('scroll', check, { passive: true });
        window.addEventListener('resize', measure, { passive: true });
        window.addEventListener('load', onLoad);

        // The first measurement — covering an anchor landing or a reload partway
        // down — used to run synchronously right here, inside the
        // DOMContentLoaded task. That meant getBoundingClientRect() on every
        // target while the document was still settling, forcing a full layout;
        // Lighthouse billed 84 ms of forced reflow across the two callers of
        // this (swiper and lightbox). Nothing needs triggerY during that task:
        // the earliest it can matter is the first scroll event.
        //
        // The first fix for that was `requestAnimationFrame + setTimeout(0)`,
        // whichever arrived first. It moved the read out of the DOMContentLoaded
        // task and then put it down again a millisecond later, still inside the
        // busiest stretch of the page's life — PageSpeed went on billing ~106 ms
        // of forced reflow across the same two callers.
        //
        // Idle time is the honest answer. rAF is ruled out for the reason this
        // whole file exists (it never fires in a document that is not being
        // painted), but requestIdleCallback takes a timeout, so it degrades to
        // a plain deadline in exactly that case instead of never running. The
        // on-demand path in check() covers a reader who scrolls first, and the
        // setTimeout branch covers browsers without rIC.
        var measured = false;
        function measureOnce() {
            if (measured) return;
            measured = true;
            measure();
        }

        if (window.requestIdleCallback) {
            requestIdleCallback(measureOnce, { timeout: 2500 });
        } else {
            setTimeout(measureOnce, 1200);
        }
    }
})();
</script>
@endonce
