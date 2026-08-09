@php
    // Rendered into the HTML rather than fetched, because this screen exists to
    // cover a wait — it cannot start by waiting on a request of its own.
    $loaderVerse = \App\Support\DailyVerse::random();
@endphp

{{--
    Full-screen loading curtain for the staff consoles.

    Covers the viewport from first paint until `load`, which on these pages is
    roughly 790ms (admin) to 1070ms (front desk) — almost all of it parsing
    ~1.1MB of CSS and JS. That gap previously had no feedback at all.

    Three constraints shape the implementation, none of them optional:

    1. INLINE styles and script. admin.css is the single biggest thing being
       waited on, so a loader styled by it would appear only once the wait was
       over. The sk-chase rules below are therefore a deliberate copy of the
       ones in admin/23-spinkit.css — change one, change the other.

    2. It lives in the INCOMING document, never on link click. These layouts
       enable cross-document view transitions (`@view-transition`,
       admin/02-base.css), which freeze a snapshot of the outgoing page;
       anything animating there stalls mid-frame and reads as a crash.

    3. A CSS backstop removes it even if the script never runs. An overlay that
       depends solely on JS to leave is one error away from a permanently
       unusable console.
--}}
<div id="pageLoader" role="status" aria-live="polite" aria-label="Loading page">
    <div class="pl-inner">
        <div class="sk-chase" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>

        @if($loaderVerse)
            <figure class="pl-verse">
                <blockquote>{{ $loaderVerse['text'] }}</blockquote>
                <figcaption>
                    {{ $loaderVerse['reference'] }}
                    <span>{{ $loaderVerse['label'] }}</span>
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
        /* Backstop: if the script never runs, this still clears the screen.
           Long enough not to cut a genuinely slow load short. */
        animation: plBackstop 0s linear 10s forwards;
    }

    @keyframes plBackstop { to { opacity: 0; visibility: hidden; } }

    #pageLoader.is-done {
        animation: none;
        opacity: 0;
        visibility: hidden;
        transition: opacity 320ms ease, visibility 0s linear 320ms;
    }

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

    .pl-verse figcaption span {
        font-weight: 600;
        letter-spacing: .04em;
        text-transform: none;
        opacity: .7;
    }

    .pl-verse figcaption span::before { content: '·'; margin: 0 7px; }

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
        #pageLoader.is-done { transition: none; }
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
    var startedAt = Date.now();
    var cleared = false;

    function clear() {
        if (cleared) return;
        cleared = true;

        var wait = Math.max(0, MIN_VISIBLE - (Date.now() - startedAt));

        setTimeout(function () {
            el.classList.add('is-done');
            // Removed outright once faded: a fixed full-screen element left in
            // the DOM keeps swallowing pointer events in some browsers even at
            // visibility:hidden.
            setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 360);
        }, wait);
    }

    if (document.readyState === 'complete') {
        clear();
    } else {
        window.addEventListener('load', clear, { once: true });
    }

    // Safety net in case `load` never fires (a stalled image or font request
    // can hold it indefinitely). Shorter than the CSS backstop so the script
    // wins when it is running at all.
    setTimeout(clear, 6000);

    // Back button restores a finished page from bfcache with the curtain baked
    // into the snapshot; drop it immediately.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted && el.parentNode) el.parentNode.removeChild(el);
    });
})();
</script>
