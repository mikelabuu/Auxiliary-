@extends('layouts.public.auth-desk')
@section('title', 'Farmers Hostel · Verification code')

@section('content')
<main class="fha-desk-shell">
    <section class="fha-board">
        <div class="fha-board-inner">

            @include('public.auth.partials.house')
            @include('public.auth.partials.notes')

            <div class="fha-fob">
                <span class="fha-eyebrow">Step two of two</span>
                <h1 class="fha-panel-title">Enter your code.</h1>
                <p class="fha-panel-lede">
                    Your password checked out. We've emailed a six-digit code
                    @if (!empty($maskedEmail))
                        to <span class="fha-nowrap">{{ $maskedEmail }}</span> —
                    @else
                        to your registered address —
                    @endif
                    it's good for five minutes.
                </p>

                <form method="POST" action="{{ route('staff.otp.verify') }}" class="fha-form" data-busy-form>
                    @csrf

                    <div>
                        {{-- One real input carries the value, so paste, password
                             managers and iOS one-time-code autofill all keep
                             working. The six cells are a presentation layer
                             mirrored from it and hidden from the a11y tree. --}}
                        <div class="fha-otp">
                            <label for="otp_code" class="sr-only">Six-digit verification code</label>
                            <input type="text" id="otp_code" name="otp_code" required
                                   inputmode="numeric" pattern="[0-9]*" maxlength="6"
                                   autocomplete="one-time-code" autofocus
                                   aria-describedby="otpHint"
                                   class="fha-otp-input" data-otp>
                            <div class="fha-otp-cells" aria-hidden="true">
                                @for ($i = 0; $i < 6; $i++)
                                    <span class="fha-otp-cell" data-otp-cell="{{ $i }}"></span>
                                @endfor
                            </div>
                        </div>

                        <p id="otpHint" class="fha-hint">
                            Six digits, numbers only.
                            <span data-otp-countdown hidden>Expires in <b data-otp-remaining></b>.</span>
                        </p>
                    </div>

                    <button type="submit" class="fha-submit" data-busy-btn>
                        <span class="fha-submit-label">
                            @include('public.auth.partials.key-icon')
                            Verify and continue
                        </span>
                        <span class="fha-submit-spin" aria-hidden="true"></span>
                    </button>
                </form>

                {{-- Secondary action stays quiet — one primary CTA per screen. --}}
                <form method="POST" action="{{ route('staff.otp.resend') }}">
                    @csrf
                    <button type="submit" class="fha-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/>
                        </svg>
                        Send a new code
                    </button>
                </form>

                {{-- No resend threshold quoted here either. This screen is behind a
                     proven password, so it is far less exposed than the login — but
                     the limit is still a detail worth keeping out of the UI. If it
                     is reached, the server's own message names the wait. --}}
                <p class="fha-meta">
                    Didn't get it? Check your spam folder, or send another.
                    <a href="{{ route('login') }}" class="fha-link">Back to sign in</a>
                </p>
            </div>
        </div>
    </section>

    @include('public.auth.partials.plate', [
        'title' => 'A second key, sent to you.',
        'lede'  => 'The front desk of Farmers Hostel, inside the CLSU campus.',
    ])
</main>
@endsection

@push('scripts')
@include('public.auth.partials.form-js')
<script>
(() => {
    'use strict';

    /* ── Six paper wells, mirrored from the real input ───────────
       The input owns the value and the focus; the cells only render it. */
    const input = document.querySelector('[data-otp]');
    const cells = [...document.querySelectorAll('[data-otp-cell]')];

    if (input && cells.length) {
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        let previous = '';

        const paint = () => {
            const v = input.value.replace(/\D/g, '').slice(0, 6);
            if (v !== input.value) input.value = v;
            const focused = document.activeElement === input;

            cells.forEach((cell, i) => {
                const had = Boolean(previous[i]);
                const has = Boolean(v[i]);
                cell.textContent = v[i] || '';
                cell.classList.toggle('is-filled', has);
                cell.classList.toggle('is-active', focused && i === Math.min(v.length, 5));

                // Acknowledge only the digit that just landed — re-adding the
                // class on every repaint would keep every cell pulsing.
                if (has && !had && !reduced) {
                    cell.classList.remove('just-filled');
                    void cell.offsetWidth;
                    cell.classList.add('just-filled');
                }
            });

            previous = v;
        };

        ['input', 'focus', 'blur', 'click', 'keyup'].forEach(evt =>
            input.addEventListener(evt, paint));
        paint();

        // A complete code submits itself — the extra click is pure friction on
        // a step staff pass through at the start of every shift.
        input.addEventListener('input', () => {
            if (input.value.length === 6) input.form.requestSubmit();
        });
    }

    /* ── Expiry countdown ────────────────────────────────────
       Driven by the real expiry on the issued OTP row, not a decorative timer. */
    @if (!empty($expiresAt))
        const expiresAt = new Date(@json(\Illuminate\Support\Carbon::parse($expiresAt)->toIso8601String()));
        const wrap = document.querySelector('[data-otp-countdown]');
        const out = document.querySelector('[data-otp-remaining]');

        if (wrap && out) {
            const tick = () => {
                const left = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
                wrap.removeAttribute('hidden');
                if (left <= 0) {
                    out.textContent = 'expired — request a new one';
                    clearInterval(timer);
                    return;
                }
                out.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
            };
            const timer = setInterval(tick, 1000);
            tick();
        }
    @endif
})();
</script>
@endpush
