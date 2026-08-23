/**
 * Staff console shell behaviours — admin *and* front desk.
 *
 * These four blocks were inline in layouts/admin.blade.php and copied byte for
 * byte into layouts/frontdesk.blade.php. That is the same divergence the
 * frontdesk layout already warned about in its own comment ("two
 * implementations meant the frontdesk console quietly missed every fix made on
 * the admin side") for the modal helpers — this is the rest of it.
 *
 * Everything here guards on its own hooks and no-ops when they're absent, so
 * one module is safe on both shells.
 */

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ── Entrance cleanup ─────────────────────────────────────────────
   fadeInUp/popIn/rowIn run with fill:forwards, and a filled opacity/transform
   animation keeps a stacking context alive forever — which traps page-level
   fixed modals below the sidebar (z-50) so the backdrop never dims the chrome.
   Clear the animation once it finishes; the inline opacity also stops Livewire
   morphs re-flashing entrances. */
document.addEventListener(
    'animationend',
    (e) => {
        const n = e.animationName;
        if (n !== 'fadeInUp' && n !== 'popIn' && n !== 'rowIn') return;
        e.target.style.animation = 'none';
        if (n !== 'rowIn') e.target.style.opacity = '1';
    },
    true,
);

/* ── Cursor-tracked spotlight on cards (CSS reads --spot-x/--spot-y) ── */
(function spotlight() {
    let raf = null;
    document.addEventListener(
        'pointermove',
        (e) => {
            if (raf) return;
            raf = requestAnimationFrame(() => {
                raf = null;
                const t = e.target.closest?.('.card, .stat-card, .mini-stat, .quick-action');
                if (!t) return;
                const r = t.getBoundingClientRect();
                t.style.setProperty('--spot-x', `${e.clientX - r.left}px`);
                t.style.setProperty('--spot-y', `${e.clientY - r.top}px`);
            });
        },
        { passive: true },
    );
})();

/* ── Animated count-up for plain numeric KPI values (skips mixed markup) ──
   The count is decorative; the number underneath it is not. Browsers suspend
   requestAnimationFrame in a hidden tab, and this used to call tick() straight
   away — so the first frame (zero) was written, the second never came, and a
   dashboard opened in a background tab sat there reporting "0 available" and
   "₱0.00" until something else repainted it. On the dashboard the 30s
   wire:poll eventually covered for it; on a page without a poll it was
   permanent. Freezing partway through, when the tab is hidden mid-count, is
   the same failure wearing a plausible number.

   So every exit lands on the true value: don't start at all when the page is
   already hidden (the server-rendered text is correct — leave it), and snap to
   target if it goes hidden while running. */
(function countUp() {
    if (reduceMotion()) return;

    const settlers = [];

    document.querySelectorAll('.stat-value, .mini-stat-value').forEach((el) => {
        if (el.children.length > 0) return;
        const m = el.textContent.trim().match(/^([₱$]?)([\d,]+)(\.\d+)?(%?)$/);
        if (!m) return;
        const target = parseFloat(m[2].replace(/,/g, '') + (m[3] || ''));
        if (!isFinite(target) || target === 0) return;

        const dec = m[3] ? m[3].length - 1 : 0;
        const dur = 560;
        const start = performance.now();
        const fmt = (v) =>
            m[1] + v.toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec }) + m[4];
        const settle = () => { el.textContent = fmt(target); };

        // Nothing will drive the animation, so writing frame zero here would
        // be writing it for good.
        if (document.hidden) return;

        settlers.push(settle);

        (function tick(t) {
            if (document.hidden) { settle(); return; }
            const p = Math.min(1, (t - start) / dur);
            el.textContent = fmt(target * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(tick);
        })(start);
    });

    if (!settlers.length) return;

    // rAF stops the moment the tab is backgrounded, which leaves whatever
    // partial figure the last frame happened to draw. Land them all.
    document.addEventListener('visibilitychange', function snap() {
        if (!document.hidden) return;
        settlers.forEach((settle) => settle());
        document.removeEventListener('visibilitychange', snap);
    });
})();

/* ── Live clock ───────────────────────────────────────────────────
   Two shells, two markups: the admin topbar has a single #liveClock line, the
   desk band splits time (#fdClock, ticking seconds) from date (#fdClockDate).

   The interval is dropped while the tab is hidden and resumed — with an
   immediate redraw so it is never briefly stale — on the way back. A desk
   machine sits on this screen all day; the seconds hand was repainting once a
   second in a background tab forever. */
(function clock() {
    const admin = document.getElementById('liveClock');
    const deskTime = document.getElementById('fdClock');
    const deskDate = document.getElementById('fdClockDate');
    if (!admin && !deskTime) return;

    // The desk shows seconds, so it needs a 1s beat; the admin line only shows
    // minutes and is happy with 30s.
    const period = deskTime ? 1000 : 30000;
    let timer = null;

    function tick() {
        const now = new Date();

        if (admin) {
            const time = now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            // Short weekday, no year. "Wednesday, Aug 19, 2026" rendered at
            // 309px in a nowrap chip, which was over a third of the topbar's
            // usable width and pushed the notification bell and the whole user
            // menu off the right edge on any viewport below ~1400px — a 1366px
            // laptop at 125% scaling has 1093px, so this was the normal case.
            // The desk clock has always used this shorter form.
            const date = now.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
            });
            admin.textContent = `${time} · ${date}`;
        }

        if (deskTime) {
            let h = now.getHours();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            deskTime.textContent = `${h}:${String(now.getMinutes()).padStart(2, '0')}:${String(
                now.getSeconds(),
            ).padStart(2, '0')} ${ampm}`;
        }
        if (deskDate) {
            deskDate.textContent = now.toLocaleDateString(undefined, {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
            });
        }
    }

    function start() {
        tick();
        if (!timer) timer = setInterval(tick, period);
    }

    function stop() {
        clearInterval(timer);
        timer = null;
    }

    start();
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
})();

/* ── Back to top (admin shell only) ───────────────────────────────── */
(function backToTop() {
    const btn = document.getElementById('backToTop');
    if (!btn) return;

    // Coalesced into a frame: this used to toggle a class straight from the
    // scroll handler on every event.
    let raf = null;
    const sync = () => {
        raf = null;
        btn.classList.toggle('visible', window.scrollY > 480);
    };
    sync();
    window.addEventListener('scroll', () => {
        if (!raf) raf = requestAnimationFrame(sync);
    }, { passive: true });

    btn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: reduceMotion() ? 'auto' : 'smooth' });
    });
})();

/* ── Copy reference codes (any .copy-ref[data-copy] in the console) ── */
document.addEventListener('click', (e) => {
    const btn = e.target.closest?.('.copy-ref');
    if (!btn) return;
    const value = btn.getAttribute('data-copy');
    if (!value) return;

    const done = () => {
        const original = btn.innerHTML;
        btn.innerHTML =
            '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width:11px;height:11px"><polyline points="20 6 9 17 4 12"/></svg> Copied';
        setTimeout(() => {
            btn.innerHTML = original;
        }, 1400);
    };

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(value).then(done).catch(() => {});
        return;
    }
    const t = document.createElement('textarea');
    t.value = value;
    document.body.appendChild(t);
    t.select();
    try {
        document.execCommand('copy');
        done();
    } catch (err) {
        /* clipboard unavailable */
    }
    document.body.removeChild(t);
});
