{{--
    Shared shell for every HTTP error page.

    Deliberately self-contained: no @vite, no Blade components, no queries, no
    layout inheritance. An error view is the one template that has to render
    when the rest of the app cannot — a 500 from a dead database or a broken
    Vite manifest would take the normal public layout down with it, and Laravel
    would fall back to its own bare "Whoops" page. That fallback is exactly what
    these pages exist to replace, so nothing here may depend on the app being
    healthy.

    The cost of that independence is a copy of a handful of design tokens,
    inlined below. They are lifted from resources/css/public/01-tokens.css and
    03-theme-boutique.css; if the Boutique Farmstead palette is ever re-cut,
    these six or so values need re-cutting with it. That is a real maintenance
    tax, accepted on purpose — the alternative is an error page that errors.
--}}
@php
    /**
     * Who is on the other side of this, and where can they go next?
     *
     * Guard resolution reads the session and then loads the user, so on a 500
     * caused by an unreachable database this throws. Everything here is
     * therefore wrapped: an error page that cannot render is worse than one
     * that guesses "logged-out guest" and offers the front door.
     */
    $audience = 'guest';
    $actions = [];

    try {
        if (auth('staff')->check()) {
            $audience = 'staff';
            $role = auth('staff')->user()->role;
            $home = in_array($role, ['admin', 'master_admin'], true)
                ? route('staff.dashboard')
                : ($role === 'frontdesk' ? route('frontdesk.dashboard.index') : route('login'));

            $actions = [['label' => 'Back to dashboard', 'href' => $home, 'primary' => true]];
        } elseif (auth('web')->check()) {
            $audience = 'user';
            $actions = [
                ['label' => 'Back to home', 'href' => route('home'), 'primary' => true],
                ['label' => 'My bookings', 'href' => route('settings.bookings'), 'primary' => false],
            ];
        } else {
            $actions = [
                ['label' => 'Back to home', 'href' => route('home'), 'primary' => true],
                ['label' => 'Sign in', 'href' => route('login'), 'primary' => false],
            ];
        }
    } catch (\Throwable $e) {
        // Hard-coded paths, not route(), for the case where even the route
        // collection is unavailable.
        $actions = [['label' => 'Back to home', 'href' => '/', 'primary' => true]];
    }

    // Pages can add their own action ahead of the defaults (419 wants "Sign in
    // again", not "Back to home").
    $leadAction = trim($__env->yieldContent('lead_action_label'));
    if ($leadAction !== '') {
        array_unshift($actions, [
            'label' => $leadAction,
            'href' => trim($__env->yieldContent('lead_action_href')) ?: '/',
            'primary' => true,
        ]);
        // Only one primary; demote whatever used to hold the slot.
        if (isset($actions[1])) {
            $actions[1]['primary'] = false;
        }

        // The lead action often duplicates a default — 419's "Sign in again"
        // and the logged-out "Sign in" are the same URL — which would render as
        // two buttons offering the same thing under different names. First
        // label for a given destination wins, since that is the specific one.
        $seen = [];
        $actions = array_values(array_filter($actions, function ($action) use (&$seen) {
            if (in_array($action['href'], $seen, true)) {
                return false;
            }
            $seen[] = $action['href'];

            return true;
        }));
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Farmers Hostel</title>

    <style>
        /* The same three faces as the rest of the site, self-hosted.

           These were loaded from fonts.googleapis.com, which is a poor
           dependency for this page in particular: an error page should render
           when things are broken, and that one made it rely on two external
           origins being reachable. The .woff2 files below are plain static
           assets under public/vendor (synced by scripts/sync-vendor.mjs), so
           they need no build step and keep this file's self-contained promise —
           there is still no Vite tag here.

           Variable fonts, so these four faces cover every weight this page
           asks for: Playfair 400-500 upright and 400 italic, Manrope 400-600,
           Oswald 400-500. latin only; the error copy is English. */
        @font-face {
            font-family: 'Playfair Display';
            font-style: normal;
            font-weight: 400 700;
            font-display: swap;
            src: url('{{ asset('vendor/fonts/playfair-display-latin-wght-normal.woff2') }}') format('woff2-variations');
        }

        @font-face {
            font-family: 'Playfair Display';
            font-style: italic;
            font-weight: 400 700;
            font-display: swap;
            src: url('{{ asset('vendor/fonts/playfair-display-latin-wght-italic.woff2') }}') format('woff2-variations');
        }

        @font-face {
            font-family: 'Manrope';
            font-style: normal;
            font-weight: 200 800;
            font-display: swap;
            src: url('{{ asset('vendor/fonts/manrope-latin-wght-normal.woff2') }}') format('woff2-variations');
        }

        @font-face {
            font-family: 'Oswald';
            font-style: normal;
            font-weight: 200 700;
            font-display: swap;
            src: url('{{ asset('vendor/fonts/oswald-latin-wght-normal.woff2') }}') format('woff2-variations');
        }

        /* Boutique Farmstead, the six tokens this page actually needs. */
        :root {
            --cream: oklch(96.5% 0.02 90);
            --cream-warm: oklch(98% 0.012 85);
            --canvas-deep: oklch(94.5% 0.025 90);
            --ink: oklch(22% 0.02 160);
            --emerald-deep: oklch(32% 0.06 160);
            --emerald: oklch(44% 0.09 160);
            --gold: oklch(75% 0.12 85);

            --font-display: 'Playfair Display', ui-serif, Georgia, serif;
            --font-sans: 'Manrope', ui-sans-serif, system-ui, sans-serif;
            --font-label: 'Oswald', ui-sans-serif, system-ui, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--cream);
            color: var(--ink);
            font-family: var(--font-sans);
            font-size: 0.9375rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* A single warm wash behind the numeral, so the page reads as part of
           the site rather than as a browser default. */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(60rem 40rem at 50% -10%, var(--canvas-deep), transparent 70%);
            pointer-events: none;
        }

        .err-brand {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 1.75rem 1.5rem 0;
            text-decoration: none;
            color: inherit;
        }

        .err-brand img { width: 2.25rem; height: 2.25rem; object-fit: contain; }

        .err-brand-name {
            font-family: var(--font-display);
            font-size: 1.0625rem;
            letter-spacing: -0.012em;
        }

        .err-brand-name i { color: var(--emerald); font-style: italic; }

        .err-main {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            gap: 0;
            max-width: 44rem;
            width: 100%;
            margin: 0 auto;
            padding: 3.5rem 1.5rem 4rem;
        }

        .err-code {
            font-family: var(--font-label);
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--emerald);
        }

        .err-rule {
            width: 3rem;
            height: 2px;
            margin: 1.25rem 0 1.5rem;
            background: var(--gold);
            border-radius: 999px;
        }

        .err-title {
            margin: 0;
            font-family: var(--font-display);
            font-weight: 400;
            font-size: clamp(2rem, 1.35rem + 2.4vw, 3rem);
            line-height: 1.1;
            letter-spacing: -0.018em;
            text-wrap: balance;
        }

        .err-body {
            margin: 1.25rem 0 0;
            max-width: 34rem;
            color: color-mix(in oklab, var(--ink) 72%, transparent);
            text-wrap: pretty;
        }

        .err-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 2rem;
        }

        .err-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 1.6rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-family: var(--font-label);
            font-size: 12px;
            font-weight: 400;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 180ms cubic-bezier(0.22, 1, 0.36, 1),
                        color 180ms cubic-bezier(0.22, 1, 0.36, 1),
                        border-color 180ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .err-btn-primary { background: var(--emerald-deep); color: var(--cream); }
        .err-btn-primary:hover { background: var(--emerald); }

        .err-btn-ghost {
            background: var(--cream-warm);
            color: var(--ink);
            border-color: color-mix(in oklab, var(--ink) 14%, transparent);
        }
        .err-btn-ghost:hover { border-color: var(--emerald); color: var(--emerald-deep); }

        .err-btn:focus-visible,
        .err-brand:focus-visible {
            outline: 2px solid var(--gold);
            outline-offset: 3px;
        }

        .err-foot {
            position: relative;
            padding: 0 1.5rem 2rem;
            font-family: var(--font-label);
            font-size: 10px;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            /* 45% is the weight this footnote wants visually, but at 10px on
               cream it measures 2.82:1 — well under the 4.5:1 floor for text
               this size. Measured against this background: 55% → 3.75, 60% →
               4.38, 62% → 4.61, 70% → 6.06. 62% is the lightest that clears the
               floor, so it keeps the most of the intended hierarchy. */
            color: color-mix(in oklab, var(--ink) 62%, transparent);
        }

        @media (prefers-reduced-motion: reduce) {
            .err-btn { transition: none; }
        }
    </style>
</head>
<body>
    <a class="err-brand" href="/">
        {{-- A static file straight off the public disk: no <x-img>, no manifest,
             no image pipeline. alt is empty because the wordmark beside it
             already carries the name. --}}
        <img src="/image/fh-mark.png" alt="" width="60" height="60">
        <span class="err-brand-name">Farmers <i>Hostel</i></span>
    </a>

    <main class="err-main">
        <p class="err-code">@yield('code') · @yield('title')</p>
        <div class="err-rule"></div>

        <h1 class="err-title">@yield('heading')</h1>

        <p class="err-body">@yield('message')</p>

        <div class="err-actions">
            @foreach ($actions as $action)
                <a href="{{ $action['href'] }}" class="err-btn {{ $action['primary'] ? 'err-btn-primary' : 'err-btn-ghost' }}">
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </main>

    <p class="err-foot">Farmers Hostel · CLSU, Science City of Muñoz</p>
</body>
</html>
