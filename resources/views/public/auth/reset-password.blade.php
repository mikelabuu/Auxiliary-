@extends('layouts.public.auth')
@section('title', 'Reset Password')
@section('content')
    <x-booking.ui.auth-card
        title="Reset Password"
        subtitle="Please enter your email and set a new password."
    >
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div id="error-alert" class="error-alert mb-4">
                    <x-booking.ui.alert type="danger" :message="$error" />
                </div>
            @endforeach
        @endif

        <form class="space-y-4" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <input type="email" placeholder="Email address" name="email" value="{{ old('email', $email) }}" required readonly
                   class="w-full px-5 py-4 bg-stone-100 border border-stone-200 rounded-2xl outline-none font-medium text-sm text-stone-500 cursor-not-allowed">

            <input type="password" placeholder="New Password" name="password" required
                   class="w-full px-5 py-4 bg-stone-50/60 border border-stone-200 rounded-2xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all placeholder:text-stone-400 font-medium text-sm text-stone-800">

            <input type="password" placeholder="Confirm New Password" name="password_confirmation" required
                   class="w-full px-5 py-4 bg-stone-50/60 border border-stone-200 rounded-2xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all placeholder:text-stone-400 font-medium text-sm text-stone-800">

            <button type="submit" class="w-full bg-gradient-to-b from-clsu-600 to-clsu-800 text-white font-bold py-4 rounded-2xl shadow-[0_10px_24px_-8px_rgba(17,78,40,0.5)] hover:shadow-[0_14px_30px_-8px_rgba(17,78,40,0.6)] transition-all active:scale-[0.98] cursor-pointer">
                Reset Password
            </button>
        </form>
    </x-booking.ui.auth-card>

    <p class="mt-10 text-white/60 text-xs font-semibold tracking-wider">
        &copy; {{ date('Y') }} Farmers Hostel.
    </p>
@endsection
