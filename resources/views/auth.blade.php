@extends('layouts.app')

@section('title', 'Farmers\' Hostel')

@section('content')
<main id="main_content">
    <section id="auth_section">
        <div class="logo">
            <img class="fhlogo" src="{{ asset('image/FHLogo.png') }}" alt="logo">
        </div>
        <div class="auth_container">

            <!-- Log In Section -->
            <div id="Log_in">
                <h2>Log In</h2>

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    </ul>
                </div>
                @endif

                <!-- User Login Form -->
                <form class="user_login authsection active" method="POST" action="{{ route('login.user') }}">
                    @csrf
                    <div class="form-floating">
                        <input type="text" class="form-control" name="username" placeholder=" ">
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="password" placeholder=" ">
                        <label for="password">Password</label>
                    </div>
                    <button type="submit" class="auth_btn">Log in</button>
                    <div class="footer_line">
                        <h6>Don't have an account? <span class="page_move_btn" onclick="signuppage()">sign up</span></h6>
                        <h6><a href="{{ route('password.request') }}">Forgot Password?</a></h6>
                    </div>
                </form>
            </div>
            <!-- Signup Section -->
            <div id="sign_up">
                <h2>Sign up</h2>
                <form class="user_signup" method="POST" action="{{ route('signup') }}">
                    @csrf
                    <div class="form-floating">
                        <input type="text" class="form-control" name="username" placeholder=" ">
                        <label for="username">Username</label>
                    </div>
                    <div class="form-floating">
                        <input type="email" class="form-control" name="email" placeholder=" ">
                        <label for="email">Email</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="password" placeholder=" ">
                        <label for="password">Password</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="password_confirmation" placeholder=" ">
                        <label for="password_confirmation">Confirm Password</label>
                    </div>
                    <button type="submit" class="auth_btn">Sign up</button>
                    <div class="footer_line">
                        <h6>Already have an account? <span class="page_move_btn" onclick="loginpage()">Log in</span></h6>
                    </div>
                </form>
            </div>

        </div>
    </section>
</main>
@include('footer')
@endsection
