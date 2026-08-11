@extends('layouts.public.auth-desk')
@section('title', 'Farmers Hostel · Set a new password')

@section('content')
<main class="fha-desk-shell">
    <section class="fha-board">
        <div class="fha-board-inner">

            @include('public.auth.partials.house')
            @include('public.auth.partials.notes')

            <div class="fha-fob">
                <span class="fha-eyebrow">Account recovery</span>
                <h1 class="fha-panel-title">A new key.</h1>
                <p class="fha-panel-lede">
                    Choose something you don't use anywhere else. This replaces your old
                    password everywhere you sign in.
                </p>

                <form method="POST" action="{{ route('password.update') }}" class="fha-form" novalidate data-busy-form>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="fha-field">
                        {{-- Fixed by the reset link. Read-only rather than disabled, so it
                             still posts and is still announced by screen readers. --}}
                        {{-- Readonly only when the link supplied the address, which
                             is the normal path. Without that guard a link that
                             arrived without ?email= would render a locked, empty
                             field that could never be submitted. --}}
                        <input type="email" id="email" name="email" required
                               @readonly(filled($email ?? ''))
                               placeholder=" " value="{{ old('email', $email ?? '') }}"
                               class="fha-input" autocomplete="email">
                        <label for="email" class="fha-label">Email address</label>
                    </div>

                    <div>
                        <div class="fha-field">
                            <input type="password" id="password" name="password" required
                                   placeholder=" " autocomplete="new-password"
                                   class="fha-input fha-input--pw" data-caps-for="resetCaps" autofocus
                                   minlength="8" maxlength="72"
                                   aria-describedby="resetPwHelp" data-confirm-source="password_confirmation">
                            <label for="password" class="fha-label">New password</label>
                            <span class="fha-rule" aria-hidden="true"></span>
                            <button type="button" class="fha-reveal" data-reveal="password"
                                    aria-label="Show password" aria-pressed="false">
                                @include('public.auth.partials.eye')
                            </button>
                        </div>
                        @include('public.auth.partials.caps', ['id' => 'resetCaps'])
                        {{-- Same floor as signup — NewPasswordController::store
                             validates min:8, max:72. --}}
                        <p id="resetPwHelp" class="fha-help">At least 8 characters.</p>
                    </div>

                    <div>
                        <div class="fha-field">
                            <input type="password" id="password_confirmation" name="password_confirmation" required
                                   placeholder=" " autocomplete="new-password" class="fha-input"
                                   data-confirm-of="password" aria-describedby="confirmError">
                            <label for="password_confirmation" class="fha-label">Confirm new password</label>
                            <span class="fha-rule" aria-hidden="true"></span>
                        </div>
                        <p id="confirmError" class="fha-field-error" role="alert">Both passwords must match.</p>
                    </div>

                    <button type="submit" class="fha-submit" data-busy-btn>
                        <span class="fha-submit-label">
                            @include('public.auth.partials.key-icon')
                            Save new password
                        </span>
                        <span class="fha-submit-spin" aria-hidden="true"></span>
                    </button>
                </form>

                <p class="fha-meta">
                    You'll use the new password on every device from now on.
                    <a href="{{ route('login') }}" class="fha-link">Back to sign in</a>
                </p>
            </div>
        </div>
    </section>

    @include('public.auth.partials.plate', [
        'title' => 'One new key, everywhere.',
        'lede'  => 'Rooms for guests and visitors, run by the Auxiliary Services Program.',
    ])
</main>
@endsection

@push('scripts')
@include('public.auth.partials.form-js')
@endpush
