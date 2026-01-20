@extends('layouts.app') {{-- or your staff layout --}}

@section('content')
<main id="main_content">
    <section id="auth_section">
        <div class="logo">
            <img class="fhlogo" src="{{ asset('image/FHLogo.png') }}" alt="logo">
        </div>
        <div class="auth_container">
            <div id="Log_in">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-4">OTP Verification</h2>

            @if (session('status'))
                <div class="mb-4 p-3 text-sm text-green-700 bg-green-100 rounded">
                    {{ session('status') }}
                </div>
            @endif

                <form method="POST" action="{{ route('staff.otp.verify') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="otp_code" class="block text-sm font-medium text-gray-700">Enter 6-digit OTP</label>
                        <input type="text" name="otp_code" id="otp_code"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            maxlength="6" required autofocus>
                        @error('otp_code')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit"
                        class="auth_btn">
                        Verify OTP
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <form method="POST" action="{{ route('staff.otp.resend') }}">
                        @csrf
                        <button type="submit" class="auth_btn">
                            Didn’t get the code? Resend OTP
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
@include('footer')
@endsection
