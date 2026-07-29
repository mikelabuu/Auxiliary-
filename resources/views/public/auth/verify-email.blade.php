@extends('layouts.public.auth-desk')
@section('title', 'Farmers Hostel · Verify your email')

@section('content')
<main class="fha-desk-shell">
    <section class="fha-board">
        <div class="fha-board-inner">

            @include('public.auth.partials.house')
            @include('public.auth.partials.notes')

            <div class="fha-fob">
                <span class="fha-eyebrow">One step left</span>
                <h1 class="fha-panel-title">Check your inbox.</h1>
                <p class="fha-panel-lede">
                    We've sent a verification link to your address. Open it to finish setting
                    up your account, then come back and book your room.
                </p>

                <form method="POST" action="{{ route('verification.send') }}" class="fha-form" data-busy-form>
                    @csrf
                    <button type="submit" class="fha-submit" data-busy-btn>
                        <span class="fha-submit-label">
                            @include('public.auth.partials.key-icon')
                            Send the link again
                        </span>
                        <span class="fha-submit-spin" aria-hidden="true"></span>
                    </button>
                </form>

                {{-- Signing out is a real exit from a half-finished state, but it is
                     not this screen's primary action, so it stays quiet. --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="fha-ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>
                        </svg>
                        Sign out
                    </button>
                </form>

                <p class="fha-meta">
                    Not in your inbox? Check the spam folder before sending another.
                </p>
            </div>
        </div>
    </section>

    @include('public.auth.partials.plate', [
        'title' => 'A room needs a real address.',
        'lede'  => 'A working university, and a place to stay inside it.',
    ])
</main>
@endsection

@push('scripts')
@include('public.auth.partials.form-js')
@endpush
