@extends('layouts.public.auth-desk')
@section('title', 'Farmers Hostel · Sign in')

{{-- ═══════════════════════════════════════════════════════════════════
     DIRECTION CONTRACT — The Key Board

     THESIS: The board behind the desk, not a card on a photograph. This
     surface owns the moment a staffer lifts their tag at shift start. It
     refuses the centred glass card floating on a hostel photo — the
     arrangement this page shipped before and every login template ships.

     OWN-WORLD: Boutique Farmstead, inherited from the public site. Cream
     paper, deep evergreen, brass. Playfair 400 display, Oswald wide-tracked
     signage, Manrope copy. A brass rail, hooks, grommeted fob tags, warm
     green-shifted daylight shadow. No monospace, no glass, no glow, no
     invented proof.

     STORY: A returning operator recognises their own front desk, takes the
     right tag and is through in seconds. A first-time guest sees a real
     property with a real institution behind it.

     FIRST VIEWPORT: Cream board left, photograph right. A brass rail across
     the upper board carries two tags — SIGN IN and REGISTER; the chosen one
     hangs square and bright, the other tilts and recedes. Fields below it,
     primary action at the foot. The plate right states what actually happens
     after this form.

     FORM: The Key Board — candidate 6 of 7, seed 248843db (--scope surface
     --mode operate). Staging: the world's own; the dealt tension-commit
     staging was rejected on task grounds (drag-to-commit taxes a shift-start
     routine and breaks keyboard and password-manager flow).
     ═══════════════════════════════════════════════════════════════════ --}}

@section('content')
<main class="fha-desk-shell">

    {{-- ─────────────────────────── The board ─────────────────────────── --}}
    <section class="fha-board">
        <div class="fha-board-inner">

            @include('public.auth.partials.house')

            {{-- The rail. Both tags stay hooked so the tablist keeps normal
                 semantics and both destinations stay visible; the selected one
                 lifts square to the rail, the other hangs tilted. --}}
            <div class="fha-rail" role="tablist" aria-label="Sign in or register">
                <button type="button" id="signinTab" role="tab" aria-selected="true"
                        aria-controls="signinPanel" class="fha-tag">
                    <span class="fha-tag-ring" aria-hidden="true"></span>
                    <span class="fha-tag-body">Sign in</span>
                </button>
                <button type="button" id="registerTab" role="tab" aria-selected="false"
                        aria-controls="registerPanel" tabindex="-1" class="fha-tag">
                    <span class="fha-tag-ring" aria-hidden="true"></span>
                    <span class="fha-tag-body">Register</span>
                </button>
            </div>

            @include('public.auth.partials.notes')

            {{-- ───────────────── Sign in ───────────────── --}}
            <div id="signinPanel" role="tabpanel" aria-labelledby="signinTab" class="fha-fob" tabindex="-1">

                <h1 class="fha-panel-title">Take your key.</h1>
                <p class="fha-panel-lede">
                    One key, one door.
                    Sign in and it opens what's yours.
                </p>

                <form method="POST" action="{{ route('login.attempt') }}" class="fha-form" novalidate data-busy-form>
                    @csrf

                    <div class="fha-field">
                        <input type="email" id="email" name="email" required autocomplete="email"
                               placeholder=" " value="{{ old('email') }}" class="fha-input" autofocus>
                        <label for="email" class="fha-label">Email address</label>
                        <span class="fha-rule" aria-hidden="true"></span>
                    </div>

                    <div>
                        <div class="fha-field">
                            <input type="password" id="password" name="password" required
                                   autocomplete="current-password" placeholder=" " class="fha-input fha-input--pw"
                                   data-caps-for="signinCaps">
                            <label for="password" class="fha-label">Password</label>
                            <span class="fha-rule" aria-hidden="true"></span>
                            <button type="button" class="fha-reveal" data-reveal="password"
                                    aria-label="Show password" aria-pressed="false">
                                @include('public.auth.partials.eye')
                            </button>
                        </div>
                        @include('public.auth.partials.caps', ['id' => 'signinCaps'])
                    </div>

                    <div class="fha-forgot-row">
                        <a href="{{ route('password.request') }}" class="fha-link">Forgot your password?</a>
                    </div>

                    <button type="submit" class="fha-submit" data-busy-btn>
                        <span class="fha-submit-label">
                            @include('public.auth.partials.key-icon')
                            Sign in
                        </span>
                        <span class="fha-submit-spin" aria-hidden="true"></span>
                    </button>
                </form>

                <p class="fha-switch">
                    No account yet? <button type="button" data-goto="register" class="fha-link">Register as a guest</button>
                </p>
            </div>

            {{-- ───────────────── Register ───────────────── --}}
            <div id="registerPanel" role="tabpanel" aria-labelledby="registerTab" class="fha-fob hidden-form" tabindex="-1">

                <h1 class="fha-panel-title">Cut a new key.</h1>
                {{-- True and worth saying: staff rows are provisioned by an admin
                     through staff management, never through this form. --}}
                <p class="fha-panel-lede">
                    For guests booking a room. Staff accounts are set up for you,
                    never from this form.
                </p>

                <form method="POST" action="{{ route('signup') }}" class="fha-form" novalidate data-busy-form>
                    @csrf
                    {{-- Marks which panel a failed submit came from, so the page
                         can reopen it. Reading old('username') instead meant a
                         signup rejected for a blank username reopened the sign-in
                         panel and showed its errors over the wrong form. --}}
                    <input type="hidden" name="panel" value="register">

                    <div class="fha-row">
                        <div class="fha-field">
                            <input type="text" id="first_name" name="first_name" required placeholder=" "
                                   value="{{ old('first_name') }}" class="fha-input" autocomplete="given-name">
                            <label for="first_name" class="fha-label">First name</label>
                            <span class="fha-rule" aria-hidden="true"></span>
                        </div>
                        <div>
                            <div class="fha-field fha-field--mi">
                                <input type="text" id="middle_initial" name="middle_initial" required maxlength="2"
                                       placeholder=" " value="{{ old('middle_initial') }}" class="fha-input"
                                       autocomplete="additional-name" aria-describedby="middleInitialHelp">
                                <label for="middle_initial" class="fha-label">M.I.</label>
                                <span class="fha-rule" aria-hidden="true"></span>
                            </div>
                            {{-- The server takes a single letter. Saying so beats
                                 bouncing the form back over one character. --}}
                            <p id="middleInitialHelp" class="fha-help">One letter.</p>
                        </div>
                    </div>

                    <div class="fha-field">
                        <input type="text" id="last_name" name="last_name" required placeholder=" "
                               value="{{ old('last_name') }}" class="fha-input" autocomplete="family-name">
                        <label for="last_name" class="fha-label">Last name</label>
                        <span class="fha-rule" aria-hidden="true"></span>
                    </div>

                    <div class="fha-row">
                        <div>
                            <div class="fha-field">
                                <input type="text" id="username" name="username" required placeholder=" "
                                       value="{{ old('username') }}" class="fha-input" autocomplete="username"
                                       aria-describedby="usernameHelp">
                                <label for="username" class="fha-label">Username</label>
                                <span class="fha-rule" aria-hidden="true"></span>
                            </div>
                            {{-- The server enforces min:3|alpha_num. Saying so here
                                 removes a whole failed round-trip. --}}
                            <p id="usernameHelp" class="fha-help">3+ characters, letters and numbers only.</p>
                        </div>
                        <div>
                            <div class="fha-field">
                                <input type="email" id="register_email" name="email" required placeholder=" "
                                       value="{{ old('email') }}" class="fha-input" autocomplete="email">
                                <label for="register_email" class="fha-label">Email address</label>
                                <span class="fha-rule" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="fha-field">
                            <input type="password" id="register_password" name="password" required placeholder=" "
                                   autocomplete="new-password" class="fha-input fha-input--pw"
                                   minlength="8" maxlength="72"
                                   data-caps-for="registerCaps" aria-describedby="registerPwHelp"
                                   data-confirm-source="password_confirmation">
                            <label for="register_password" class="fha-label">Password</label>
                            <span class="fha-rule" aria-hidden="true"></span>
                            <button type="button" class="fha-reveal" data-reveal="register_password"
                                    aria-label="Show password" aria-pressed="false">
                                @include('public.auth.partials.eye')
                            </button>
                        </div>
                        @include('public.auth.partials.caps', ['id' => 'registerCaps'])
                        {{-- Must match AuthController::signup — min:8, max:72. The
                             hint said 6 after the floor was raised, so the form
                             invited a password the server then refused. --}}
                        <p id="registerPwHelp" class="fha-help">At least 8 characters.</p>
                    </div>

                    <div>
                        <div class="fha-field">
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                   placeholder=" " autocomplete="new-password" class="fha-input"
                                   data-confirm-of="register_password" aria-describedby="confirmError">
                            <label for="password_confirmation" class="fha-label">Confirm password</label>
                            <span class="fha-rule" aria-hidden="true"></span>
                        </div>
                        {{-- Checked on blur, never on keystroke — flagging a mismatch
                             while someone is still typing the word is just noise. --}}
                        <p id="confirmError" class="fha-field-error" role="alert">Both passwords must match.</p>
                    </div>

                    <label class="fha-terms">
                        <input type="checkbox" name="terms" required>
                        {{-- No Terms page exists yet, so this is plain text rather than a
                             link to "#". Point it at a real URL once one is published. --}}
                        <span>I agree to the Terms of Use and Privacy Policy.</span>
                    </label>

                    <button type="submit" class="fha-submit" data-busy-btn>
                        <span class="fha-submit-label">
                            @include('public.auth.partials.key-icon')
                            Create account
                        </span>
                        <span class="fha-submit-spin" aria-hidden="true"></span>
                    </button>
                </form>

                <p class="fha-switch">
                    Already have a key? <button type="button" data-goto="signin" class="fha-link">Sign in</button>
                </p>
            </div>
        </div>
    </section>

    {{-- ─────────────────────────── The plate ─────────────────────────── --}}
    @include('public.auth.partials.plate', [
        'title' => 'One desk, inside the university.',
        'lede'  => 'A working university, and a place to stay inside it.',
    ])
</main>
@endsection

@push('scripts')
@include('public.auth.partials.form-js')
<script>
(() => {
    'use strict';
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── The rail ─────────────────────────────────────────────
       Two tags on two hooks. Selecting one lifts it square to the rail and
       lets the other hang. Standard tablist keyboard model: arrows move,
       Home/End jump, and only the selected tab is in the tab order. */
    const tabs = [
        { tab: document.getElementById('signinTab'),   panel: document.getElementById('signinPanel'),   key: 'signin' },
        { tab: document.getElementById('registerTab'), panel: document.getElementById('registerPanel'), key: 'register' },
    ];

    function show(key, { focusPanel = true, animate = true } = {}) {
        tabs.forEach(({ tab, panel, key: k }) => {
            const on = k === key;
            tab.setAttribute('aria-selected', on ? 'true' : 'false');
            tab.tabIndex = on ? 0 : -1;
            panel.classList.toggle('hidden-form', !on);
            if (!on) panel.classList.remove('is-entering');
        });

        const current = tabs.find(t => t.key === key);
        if (!current) return;

        // Put the panel at its offset for exactly one frame, then release it.
        // Because .fha-fob transitions rather than runs a keyframe, switching
        // again mid-flight retargets from the current position instead of
        // snapping back to the start.
        if (animate && !reduced) {
            current.panel.classList.add('is-entering');
            void current.panel.offsetWidth;
            current.panel.classList.remove('is-entering');
        }

        if (focusPanel) {
            const first = current.panel.querySelector('input:not([type=hidden])');
            (first || current.panel).focus({ preventScroll: true });
        }
    }

    tabs.forEach(({ tab, key }, i) => {
        tab.addEventListener('click', () => show(key));
        tab.addEventListener('keydown', (e) => {
            let next = null;
            if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = (i + 1) % tabs.length;
            else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = (i - 1 + tabs.length) % tabs.length;
            else if (e.key === 'Home') next = 0;
            else if (e.key === 'End') next = tabs.length - 1;
            if (next === null) return;
            e.preventDefault();
            show(tabs[next].key, { focusPanel: false });
            tabs[next].tab.focus();
        });
    });

    document.querySelectorAll('[data-goto]').forEach(btn =>
        btn.addEventListener('click', () => show(btn.dataset.goto)));

    {{-- A failed signup re-renders this page; reopen the panel the user was in.
         The register form posts a hidden `panel` marker, so this holds even when
         the field that failed is the one that was left empty. --}}
    @if ($errors->any() && old('panel') === 'register')
        show('register', { focusPanel: false, animate: false });
    @endif
})();
</script>
@endpush
