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
                <h2>Staff Log In</h2>

                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    </ul>
                </div>
                @endif

                <!-- Staff Login Form -->
                <form class="employee_login authsection active" method="POST" action="{{ route('staff.login.submit') }}">
                    @csrf
                    <div class="form-floating">
                        <input type="email" class="form-control" name="staff_email" placeholder=" ">
                        <label for="email">Email</label>
                    </div>
                    <div class="form-floating">
                        <input type="password" class="form-control" name="staff_password" placeholder=" ">
                        <label for="password">Password</label>
                    </div>
                    <button type="submit" class="auth_btn">Log in</button>
                </form>
            </div>
        </div>
    </section>
</main>
@include('footer')
@endsection
