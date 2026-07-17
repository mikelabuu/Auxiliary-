{{-- Cinematic intro splash. The gate below must run in <head> before first
     paint so the splash frame is the very first thing painted (no flash of
     the page behind it). Skipped for reduced-motion users and repeat visits
     this session. Splash styles live in app.css. --}}
@push('styles')
<script>
    (function () {
        try {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            if (sessionStorage.getItem('fhIntroSeen')) return;
            document.documentElement.classList.add('intro-hold');
        } catch (e) { /* storage blocked — just skip the intro */ }
    })();
</script>
@endpush

<div id="introSplash" aria-hidden="true">
    <div class="intro-curtain">
        <div class="intro-grain"></div>
    </div>
    <div class="intro-center">
        {{-- Plain text node: background-clip:text breaks in Chromium when
             descendants are compositor-animated, so the staggered reveal
             is a mask sweep on the element itself (see .intro-wordmark) --}}
        <h2 id="introWordmark" class="intro-wordmark">Farmers Hostel</h2>
        <p class="intro-tagline">
            <span class="intro-rule" aria-hidden="true"></span>
            Quality rest on campus · Est. 1998
            <span class="intro-rule" aria-hidden="true"></span>
        </p>
    </div>
</div>

<script>
    // Intro exit timeline: after a short dwell (or first interaction) the
    // wordmark FLIP-flies into the hero headline, the cream curtain wipes
    // up, the held hero entrance is released, and a gold gradient band
    // sweeps the title — the splash "delivering" its gradient.
    (function () {
        var splash = document.getElementById('introSplash');
        if (!splash) return;
        if (!document.documentElement.classList.contains('intro-hold')) {
            splash.remove(); // repeat visit / reduced motion — nothing to play
            return;
        }

        var done = false;
        function finish() {
            if (done) return;
            done = true;
            try { sessionStorage.setItem('fhIntroSeen', '1'); } catch (e) {}

            var wordmark = document.getElementById('introWordmark');
            var title = document.getElementById('heroTitle');

            // FLIP: measure the resting position of the headline's second
            // line ("Farmers Hostel") and fly the wordmark onto it.
            if (wordmark && title && wordmark.animate) {
                var lines = title.querySelectorAll('.reveal-line');
                var target = lines.length ? lines[lines.length - 1] : title;
                var from = wordmark.getBoundingClientRect();
                var to = target.getBoundingClientRect();
                var dx = (to.left + to.width / 2) - (from.left + from.width / 2);
                var dy = (to.top + to.height / 2) - (from.top + from.height / 2);
                var s = Math.max(0.35, Math.min(2.5, to.height / from.height));
                wordmark.animate([
                    { transform: 'translate(0px, 0px) scale(1)', opacity: 1 },
                    { transform: 'translate(' + dx * 0.85 + 'px, ' + dy * 0.85 + 'px) scale(' + ((1 + s) / 2) + ')', opacity: 0.55, offset: 0.7 },
                    { transform: 'translate(' + dx + 'px, ' + dy + 'px) scale(' + s + ')', opacity: 0 }
                ], { duration: 950, easing: 'cubic-bezier(0.76, 0, 0.24, 1)', fill: 'forwards' });
            }

            splash.classList.add('is-exiting');

            // Release the hero (and the retreated nav) as the curtain
            // lifts, then sweep the gradient across the landing title.
            setTimeout(function () {
                document.documentElement.classList.remove('intro-hold');
                if (title) {
                    title.classList.add('title-gradient-pass');
                    setTimeout(function () { title.classList.remove('title-gradient-pass'); }, 2400);
                }
            }, 240);

            setTimeout(function () { splash.remove(); }, 1450);
        }

        // Impatient visitors skip straight to the reveal
        ['pointerdown', 'keydown', 'wheel', 'touchstart'].forEach(function (t) {
            window.addEventListener(t, finish, { once: true, passive: true });
        });

        function arm() { setTimeout(finish, 2050); }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', arm);
        } else {
            arm();
        }
    })();
</script>
