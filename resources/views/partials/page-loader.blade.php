@php
    // Rendered into the HTML rather than fetched, because this screen exists to
    // cover a wait — it cannot start by waiting on a request of its own.
    // `next` is the verse this page hands forward when it navigates; see the
    // handover note in App\Support\DailyVerse.
    ['current' => $loaderVerse, 'next' => $loaderNextVerse] = \App\Support\DailyVerse::pair();
@endphp

{{--
    Full-screen loading curtain for the staff consoles.

    It covers a navigation END TO END: it is raised the moment a link is
    clicked or a form is submitted, held across the document swap, and lowered
    only once the next page has finished loading.

    That span is deliberately covered from BOTH documents. The outgoing page
    raises the curtain on click; the incoming one is already wearing it at
    first paint. Neither half is enough alone — the incoming document cannot
    paint anything until its render-blocking CSS arrives (~1.1MB of CSS and JS,
    ~790ms admin / ~1070ms front desk), so a curtain that only lived there left
    the click itself feeling dead; and the outgoing document stops painting the
    instant it is swapped out, so a curtain that only lived there would vanish
    at the handover.

    Three constraints shape the implementation, none of them optional:

    1. INLINE styles and script. admin.css is the single biggest thing being
       waited on, so a loader styled by it would appear only once the wait was
       over. The sk-chase rules below are therefore a deliberate copy of the
       ones in admin/23-spinkit.css — change one, change the other.

    2. The node is PARKED, not removed, once the page has loaded. Re-showing
       the element that is already in the DOM is what makes the click-time
       raise instant, and it keeps the same verse on screen across the swap
       instead of swapping the text a frame before the page changes.

    3. A CSS backstop removes it even if the script never runs. An overlay that
       depends solely on JS to leave is one error away from a permanently
       unusable console.

    Cross-document view transitions (`@view-transition`, admin/02-base.css)
    hand this a free crossfade: the outgoing frame is a curtain and so is the
    incoming one, so the handover reads as one continuous screen rather than
    two.

    Opt out of the click-time raise with `data-no-loader` on a link, a form, or
    any ancestor — required for anything that does not actually navigate, most
    of all the report downloads, which leave the current page right where it is.
--}}
<div id="pageLoader" role="status" aria-live="polite" aria-label="Loading page"
     @if($loaderNextVerse)
        data-next-text="{{ $loaderNextVerse['text'] }}"
        data-next-reference="{{ $loaderNextVerse['reference'] }}"
     @endif>
    <div class="pl-inner">
        <div class="sk-chase" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>

        @if($loaderVerse)
            <figure class="pl-verse">
                {{-- Both carry a class: the handover script rewrites exactly
                     these two nodes and must not touch the version label. --}}
                <blockquote data-pl-text>{{ $loaderVerse['text'] }}</blockquote>
                <figcaption>
                    <span data-pl-reference>{{ $loaderVerse['reference'] }}</span>
                    <span class="pl-label">{{ $loaderVerse['label'] }}</span>
                </figcaption>
            </figure>
        @endif
    </div>
</div>

<style>
    #pageLoader {
        position: fixed;
        inset: 0;
        z-index: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        /* Literal values: the design tokens are in the stylesheet that has not
           arrived yet. Sage-tinted white, matching --color-surface. */
        background: #f4f8f5;
        color: #099250;
        /* The raise, when the curtain is recalled for a navigation. Quicker
           than the fall: this one answers a click, so it has to feel like the
           click caused it. */
        transition: opacity 150ms ease, visibility 0s;
        /* Backstop: if the script never runs, this still clears the screen.
           Long enough not to cut a genuinely slow load short. */
        animation: plBackstop 0s linear 10s forwards;
    }

    @keyframes plBackstop { to { opacity: 0; visibility: hidden; } }

    /* Parked. Kept in the DOM so a navigation can raise it again instantly,
       but inert: pointer-events matter because a fixed full-screen element
       still swallows clicks in some browsers at visibility:hidden alone. */
    #pageLoader.is-done {
        animation: none;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity 320ms ease, visibility 0s linear 320ms;
    }

    /* Parking with no fade — used on a bfcache restore, where the curtain in
       the restored snapshot is a leftover and should never have been there. */
    #pageLoader.is-instant { transition: none; }

    .pl-inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 30px;
        max-width: 44rem;
        text-align: center;
    }

    /* ── sk-chase (SpinKit, MIT). Mirror of admin/23-spinkit.css ── */
    .sk-chase {
        width: 44px;
        height: 44px;
        position: relative;
        animation: sk-chase 2.5s infinite linear both;
    }

    .sk-chase > span {
        width: 100%;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
        animation: sk-chase-dot 2s infinite ease-in-out both;
    }

    .sk-chase > span::before {
        content: '';
        display: block;
        width: 25%;
        height: 25%;
        border-radius: 100%;
        background-color: currentColor;
        animation: sk-chase-dot-before 2s infinite ease-in-out both;
    }

    .sk-chase > span:nth-child(1), .sk-chase > span:nth-child(1)::before { animation-delay: -1.1s; }
    .sk-chase > span:nth-child(2), .sk-chase > span:nth-child(2)::before { animation-delay: -1.0s; }
    .sk-chase > span:nth-child(3), .sk-chase > span:nth-child(3)::before { animation-delay: -0.9s; }
    .sk-chase > span:nth-child(4), .sk-chase > span:nth-child(4)::before { animation-delay: -0.8s; }
    .sk-chase > span:nth-child(5), .sk-chase > span:nth-child(5)::before { animation-delay: -0.7s; }
    .sk-chase > span:nth-child(6), .sk-chase > span:nth-child(6)::before { animation-delay: -0.6s; }

    @keyframes sk-chase { 100% { transform: rotate(360deg); } }
    @keyframes sk-chase-dot { 80%, 100% { transform: rotate(360deg); } }
    @keyframes sk-chase-dot-before {
        50%      { transform: scale(0.4); }
        100%, 0% { transform: scale(1); }
    }

    /* ── Verse ── */
    .pl-verse { margin: 0; }

    .pl-verse blockquote {
        margin: 0;
        font-family: 'Sora', Georgia, serif;
        font-style: italic;
        font-size: clamp(1.02rem, 1.6vw, 1.32rem);
        line-height: 1.65;
        color: #38493f;
        /* Fades up as the spinner settles, so the screen resolves into
           something to read rather than arriving all at once. */
        animation: plVerseIn 620ms cubic-bezier(.2,.7,.2,1) 180ms both;
    }

    .pl-verse figcaption {
        margin-top: 14px;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #7c8c83;
        animation: plVerseIn 620ms cubic-bezier(.2,.7,.2,1) 300ms both;
    }

    .pl-verse figcaption .pl-label {
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: none;
        opacity: .7;
    }

    .pl-verse figcaption .pl-label::before { content: '·'; margin: 0 7px; }

    @keyframes plVerseIn {
        from { opacity: 0; transform: translateY(9px); }
        to   { opacity: 1; transform: none; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sk-chase, .sk-chase > span, .sk-chase > span::before { animation: none; }
        .sk-chase > span::before { opacity: .55; }
        .sk-chase > span:nth-child(2) { transform: rotate(60deg); }
        .sk-chase > span:nth-child(3) { transform: rotate(120deg); }
        .sk-chase > span:nth-child(4) { transform: rotate(180deg); }
        .sk-chase > span:nth-child(5) { transform: rotate(240deg); }
        .sk-chase > span:nth-child(6) { transform: rotate(300deg); }
        .pl-verse blockquote, .pl-verse figcaption { animation: none; }
        #pageLoader, #pageLoader.is-done { transition: none; }
    }
</style>

<script>
(function () {
    var el = document.getElementById('pageLoader');
    if (!el) return;

    // Long enough that the verse can actually be read rather than glimpsed,
    // short enough that a warm page does not feel padded. Loads here run
    // ~790-1070ms, so this is usually already met by the time load fires.
    var MIN_VISIBLE = 900;

    // How long a raised curtain waits for a navigation that never arrives.
    // Nothing signals "this click is not going to leave the page" — a link
    // that turns out to serve a file download simply does nothing — so the
    // only way back is a timer. Long enough to outlast a slow response,
    // short enough that a mislabelled link is an annoyance, not a lockout.
    var NAV_TIMEOUT = 8000;

    var startedAt = Date.now();
    var settled = false;      // the initial load has released the curtain
    var navigating = false;   // raised for a navigation, waiting to be swapped out
    var navTimer = null;

    /* ── One trip, one verse ─────────────────────────────────────────────
       A navigation is covered by two curtains, one per document, and each
       document picks its own verse — so the text used to change halfway
       through a single wait, which read as the screen glitching rather than
       as one continuous load.

       The outgoing page settles it. When it raises the curtain it switches to
       the verse it is carrying (data-next-*) and writes that verse to
       sessionStorage; the incoming document reads it back below, during parse,
       and overwrites its own pick before anything is painted. Per tab, so two
       windows never fight over it, and the swap on the way out happens while
       the curtain is still at opacity 0 — nobody sees either rewrite.

       If storage is unavailable (some privacy modes throw on access) the
       handover is skipped and both ends fall back to their own verse: the old
       behaviour, not a broken screen. */
    var CARRY_KEY = 'plCarriedVerse';
    var textNode = el.querySelector('[data-pl-text]');
    var refNode = el.querySelector('[data-pl-reference]');

    function paintVerse(verse) {
        // A blank carried value would wipe the verse off the screen, which is
        // worse than showing the one this page already rendered.
        if (!textNode || !verse || typeof verse.text !== 'string' || !verse.text) return;
        textNode.textContent = verse.text;
        if (refNode && verse.reference) refNode.textContent = verse.reference;
    }

    function readCarried() {
        try {
            var raw = sessionStorage.getItem(CARRY_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (err) { return null; }
    }

    // Runs during parse, before first paint: whatever the page we came from
    // was showing is what stays on screen.
    paintVerse(readCarried());

    function handOverVerse() {
        var text = el.getAttribute('data-next-text');
        if (!text) return;

        var verse = { text: text, reference: el.getAttribute('data-next-reference') || '' };
        paintVerse(verse);

        try { sessionStorage.setItem(CARRY_KEY, JSON.stringify(verse)); } catch (err) {}
    }

    function park(instant) {
        clearTimeout(navTimer);
        if (instant) el.classList.add('is-instant');
        el.classList.add('is-done');
        // Next frame, not next tick: the class has to be in effect for a
        // painted frame before transitions are allowed back on.
        if (instant) requestAnimationFrame(function () { el.classList.remove('is-instant'); });
    }

    function settle() {
        if (settled) return;
        settled = true;

        var wait = Math.max(0, MIN_VISIBLE - (Date.now() - startedAt));

        setTimeout(function () {
            // A click during the tail of the initial load raises the curtain
            // again before this fires; dropping it here would flash the page
            // the user is already leaving.
            if (!navigating) park(false);
        }, wait);
    }

    // Raise the curtain for a navigation now under way. Exposed because a few
    // places navigate from script rather than by following a link — the
    // topbar search jumping to its first result, for one.
    function raise() {
        if (navigating) return;
        navigating = true;

        clearTimeout(navTimer);
        // Before the class change, so the rewrite lands while the curtain is
        // still fully transparent.
        handOverVerse();
        el.classList.remove('is-instant');
        el.classList.remove('is-done');

        navTimer = setTimeout(function () {
            navigating = false;
            park(false);
        }, NAV_TIMEOUT);
    }
    window.showPageLoader = raise;

    if (document.readyState === 'complete') {
        settle();
    } else {
        window.addEventListener('load', settle, { once: true });
    }

    // Safety net in case `load` never fires (a stalled image or font request
    // can hold it indefinitely). Shorter than the CSS backstop so the script
    // wins when it is running at all.
    setTimeout(settle, 6000);

    // Back button restores a finished page from bfcache, curtain and all —
    // either the one this page was raising as it left, or one baked into the
    // snapshot. Neither belongs on a page that is already here.
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        navigating = false;
        settled = true;
        park(true);
    });

    /* ── Raising it on the way out ───────────────────────────────────────
       Everything below decides whether a click or a submit is actually going
       to load a new document. Getting that wrong in the permissive direction
       is the expensive mistake: a curtain raised over an action that stays on
       this page blocks the console until NAV_TIMEOUT. */

    function opensElsewhere(node) {
        var target = node.getAttribute('target');
        return !!target && target !== '_self';
    }

    function navigatesAway(a) {
        var raw = a.getAttribute('href');
        if (!raw) return false;                      // href="" reloads; href absent is not a link
        if (raw.charAt(0) === '#') return false;     // in-page anchor
        if (a.hasAttribute('download')) return false;
        if (opensElsewhere(a)) return false;
        if (a.closest('[data-no-loader]')) return false;

        var url;
        try { url = new URL(a.href, location.href); } catch (err) { return false; }

        // mailto:, tel:, javascript: — all hand off without a page load.
        if (url.protocol !== 'http:' && url.protocol !== 'https:') return false;
        if (url.host !== location.host || url.protocol !== location.protocol) return false;

        // Same document, different fragment: a scroll, not a load.
        if (url.hash && url.href.split('#')[0] === location.href.split('#')[0]) return false;

        return true;
    }

    // Deferred by a task on purpose. This script sits at the top of <body>, so
    // its listeners are registered before every other handler on the page —
    // which means preventDefault() has not been called yet when a click first
    // reaches here. Plenty of controls in these consoles are links and forms
    // that open a modal or run a Livewire round-trip instead of navigating,
    // and they all cancel synchronously; by the time a timeout runs, the
    // decision is final.
    function raiseUnlessCancelled(e) {
        setTimeout(function () { if (!e.defaultPrevented) raise(); }, 0);
    }

    document.addEventListener('click', function (e) {
        // Middle-click and modified clicks open a tab; this one stays put.
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        var a = e.target.closest && e.target.closest('a[href]');
        if (!a || !navigatesAway(a)) return;

        raiseUnlessCancelled(e);
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.nodeName !== 'FORM') return;
        if (opensElsewhere(form)) return;
        if (form.closest('[data-no-loader]')) return;

        // Livewire's wire:submit and Alpine's @submit.prevent both cancel, so
        // in-page forms are filtered by the same defaultPrevented check.
        raiseUnlessCancelled(e);
    });
})();
</script>
