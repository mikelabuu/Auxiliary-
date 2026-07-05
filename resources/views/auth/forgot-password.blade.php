@extends('layouts.authlayout')
@section('title', 'Forgot Password')
@section('content')
    <x-booking.auth-card
        title="Forgot Password?"
        subtitle="No worries! Enter your registered email and we'll send you a password reset link."
    >
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div id="error-alert" class="error-alert mb-4">
                    <x-booking.alert type="danger" :message="$error" />
                </div>
            @endforeach
        @endif

        @if (session('status'))
            <div id="success-alert" class="mb-4">
                <x-booking.alert type="success" :message="session('status')" />
            </div>
        @endif

        <form class="space-y-4" method="POST" action="{{ route('password.email') }}">
            @csrf
            <input type="email" placeholder="Email address" name="email" required
                   class="w-full px-5 py-4 bg-stone-50/60 border border-stone-200 rounded-2xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all placeholder:text-stone-400 font-medium text-sm text-stone-800">

            <button type="submit" class="w-full bg-gradient-to-b from-clsu-600 to-clsu-800 text-white font-bold py-4 rounded-2xl shadow-[0_10px_24px_-8px_rgba(17,78,40,0.5)] hover:shadow-[0_14px_30px_-8px_rgba(17,78,40,0.6)] transition-all active:scale-[0.98] cursor-pointer">
                Email Password Reset Link
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-xs font-bold text-clsu-700 hover:text-clsu-900 transition-colors flex items-center justify-center gap-1">
                <span class="material-icons text-[14px]">arrow_back</span>
                Back to login
            </a>
        </div>
    </x-booking.auth-card>

    <p class="mt-10 text-white/60 text-xs font-semibold tracking-wider">
        &copy; {{ date('Y') }} Farmers Hostel.
    </p>
@endsection
