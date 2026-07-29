@extends('layouts.public.auth-desk')
@section('title', 'Farmers Hostel · Reset your password')

@section('content')
<main class="fha-desk-shell">
    <section class="fha-board">
        <div class="fha-board-inner">

            @include('public.auth.partials.house')
            @include('public.auth.partials.notes')

            <div class="fha-fob">
                <span class="fha-eyebrow">Account recovery</span>
                <h1 class="fha-panel-title">Lost your key.</h1>
                <p class="fha-panel-lede">
                    It happens. Give us the address on your account and we'll send a link
                    to set a new password.
                </p>

                <form method="POST" action="{{ route('password.email') }}" class="fha-form" novalidate data-busy-form>
                    @csrf

                    <div class="fha-field">
                        <input type="email" id="email" name="email" required autocomplete="email"
                               placeholder=" " value="{{ old('email') }}" class="fha-input" autofocus>
                        <label for="email" class="fha-label">Email address</label>
                        <span class="fha-rule" aria-hidden="true"></span>
                    </div>

                    <button type="submit" class="fha-submit" data-busy-btn>
                        <span class="fha-submit-label">
                            @include('public.auth.partials.key-icon')
                            Email me a reset link
                        </span>
                        <span class="fha-submit-spin" aria-hidden="true"></span>
                    </button>
                </form>

                <p class="fha-meta">
                    The link is single-use. If it doesn't arrive, check your spam folder before
                    requesting another. <a href="{{ route('login') }}" class="fha-link">Back to sign in</a>
                </p>
            </div>
        </div>
    </section>

    @include('public.auth.partials.plate', [
        'title' => 'Every key can be cut again.',
        'lede'  => 'Rooms for guests and visitors, run by the Auxiliary Services Program.',
    ])
</main>
@endsection

@push('scripts')
@include('public.auth.partials.form-js')
@endpush
