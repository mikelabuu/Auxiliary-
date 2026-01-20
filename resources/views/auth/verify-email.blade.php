@extends('layouts.app')

@section('title', 'Email Verification')

@section('content')

<main id="main_content">
    <section id="auth_section">
        <div class="logo">
            <img class="fhlogo" src="{{ asset('image/FHLogo.png') }}" alt="logo">
        </div>
        <div class="auth_container">
            <div id="Log_in">
                <h1>Email Verification Required</h1>
                <p>Thanks for signing up! Before accessing the site, please check your email for a verification link.</p>
                @if (session('message'))
                    <p style="color: green;">{{ session('message') }}</p>
                @endif
                <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="auth_btn">Resend Verification Email</button>
                </form>
            </div>
        </div>
    </section>
</main>

@include('footer')

@endsection

