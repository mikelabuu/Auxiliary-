@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<main id="main_content">
    <section id="auth_section">
        <div class="logo">
            <img class="fhlogo" src="{{ asset('image/FHLogo.png') }}" alt="logo">
        </div>
        <div class="auth_container">
            <div id="Log_in">
                <h2>Forgot Password</h2>
                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                    </div>
                    <button type="submit" class="auth_btn">Email Password Reset Link</button>
                </form>
            </div>
        </div>
    </section>
</main>

@include('footer')

@endsection
