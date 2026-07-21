@extends('layouts.public.auth')
@section('title', 'Verify Email')
@section('content')
    <x-booking.ui.auth-card
        title="Email Verification Required"
        subtitle="Thanks for signing up! Before accessing the site, please verify your email by clicking the link we just emailed you."
    >
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div id="error-alert" class="error-alert mb-4">
                    <x-booking.ui.alert type="danger" :message="$error" />
                </div>
            @endforeach
        @endif

        @if (session('message'))
            <div id="success-alert" class="mb-4">
                <x-booking.ui.alert type="success" :message="session('message')" />
            </div>
        @endif

        <form class="space-y-4" method="POST" action="{{ route('verification.send') }}">
            @csrf
            <p class="text-xs font-bold text-stone-400 text-center">Didn't receive the email? Click below to request another.</p>
            <button type="submit" class="w-full bg-gradient-to-b from-clsu-600 to-clsu-800 text-white font-bold py-4 rounded-2xl shadow-[0_10px_24px_-8px_rgba(17,78,40,0.5)] hover:shadow-[0_14px_30px_-8px_rgba(17,78,40,0.6)] transition-[transform,color,background-color,border-color,box-shadow] active:scale-[0.98] cursor-pointer">
                Resend Verification Email
            </button>
        </form>

        <div class="text-center mt-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-bold text-ember-600 hover:text-ember-700 hover:underline cursor-pointer">
                    Log Out
                </button>
            </form>
        </div>
    </x-booking.ui.auth-card>

    <p class="mt-10 text-white/60 text-xs font-semibold tracking-wider">
        &copy; {{ date('Y') }} Farmers Hostel.
    </p>
@endsection
