@extends('layouts.public.auth')
@section('title', 'Farmers Hostel | Book Now')
@section('content')
    <x-booking.ui.auth-card>
        <!-- Dual Tabs -->
        <div class="flex p-1.5 gap-1.5 bg-stone-100/70 -mt-1 mb-6 rounded-2xl border border-stone-200/50">
            <button id="loginTab" class="flex-1 py-3 text-sm font-bold rounded-xl transition-all bg-white text-clsu-800 shadow-sm border border-stone-200 cursor-pointer">Log In</button>
            <button id="signupTab" class="flex-1 py-3 text-sm font-bold rounded-xl transition-all text-stone-500 hover:text-clsu-800 cursor-pointer">Sign Up</button>
        </div>

        <!-- Log In Form -->
        <div id="loginForm">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-semibold text-ink leading-tight tracking-tight font-display">Welcome back!</h2>
                <p class="text-sm font-medium text-stone-500 mt-1">Please enter your credentials to log in.</p>

                @if ($errors->any())
                    @foreach ($errors->all() as $error)
                        <div id="error-alert" class="error-alert mt-4 text-left">
                            <x-booking.ui.alert type="danger" :message="$error" />
                        </div>
                    @endforeach
                @endif

                @if (session('error'))
                    <div id="error-alert" class="error-alert mt-4 text-left">
                        <x-booking.ui.alert type="danger" :message="session('error')" />
                    </div>
                @endif
            </div>

            <form class="space-y-4" method="POST" action="{{ route('login.user') }}">
                @csrf
                <input type="email" placeholder="Email address" name="email" required
                       class="w-full px-5 py-4 bg-stone-50/60 border border-stone-200 rounded-2xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all placeholder:text-stone-400 font-medium text-sm text-stone-800">

                <input type="password" placeholder="Password" name="password" required
                       class="w-full px-5 py-4 bg-stone-50/60 border border-stone-200 rounded-2xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all placeholder:text-stone-400 font-medium text-sm text-stone-800">

                <div class="flex justify-end">
                    <a href="{{ route('password.request') }}" class="text-xs font-bold text-clsu-700 hover:text-clsu-900 transition-colors hover:underline">Forgot Password?</a>
                </div>

                <button class="w-full bg-gradient-to-b from-clsu-600 to-clsu-800 text-white font-bold py-4 rounded-2xl shadow-[0_10px_24px_-8px_rgba(17,78,40,0.5)] hover:shadow-[0_14px_30px_-8px_rgba(17,78,40,0.6)] transition-all active:scale-[0.98] cursor-pointer">
                    Log In
                </button>
            </form>
        </div>

        <!-- Sign Up Form -->
        <div id="signupForm" class="hidden-form">
            <div class="text-center mb-5">
                <h2 class="text-2xl font-semibold text-ink leading-tight tracking-tight font-display">Start Booking</h2>
                <p class="text-sm font-medium text-stone-500 mt-1">Join us at Farmers Hostel</p>
            </div>

            <form class="space-y-3.5" method="POST" action="{{ route('signup') }}">
                @csrf

                <!-- Name Row -->
                <div class="flex gap-2">
                    <input type="text" placeholder="First Name" name="first_name" required
                        class="w-2/5 px-4 py-3.5 bg-stone-50/60 border border-stone-200 rounded-xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all text-sm font-medium text-stone-800">
                    <input type="text" placeholder="M.I." name="middle_initial" maxlength="2"
                        class="w-1/5 px-3 py-3.5 bg-stone-50/60 border border-stone-200 rounded-xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white text-center outline-none transition-all text-sm font-medium text-stone-800">
                    <input type="text" placeholder="Last Name" name="last_name" required
                        class="w-2/5 px-4 py-3.5 bg-stone-50/60 border border-stone-200 rounded-xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all text-sm font-medium text-stone-800">
                </div>

                <!-- Account Details Row -->
                <div class="flex gap-2">
                    <input type="text" placeholder="Username" name="username" required
                        class="w-1/2 px-4 py-3.5 bg-stone-50/60 border border-stone-200 rounded-xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all text-sm font-medium text-stone-800">
                    <input type="email" placeholder="Email Address" name="email" required
                        class="w-1/2 px-4 py-3.5 bg-stone-50/60 border border-stone-200 rounded-xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all text-sm font-medium text-stone-800">
                </div>

                <!-- Passwords -->
                <input type="password" placeholder="Password" name="password" required
                    class="w-full px-4 py-3.5 bg-stone-50/60 border border-stone-200 rounded-xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all text-sm font-medium text-stone-800">

                <input type="password" placeholder="Confirm Password" name="password_confirmation" required
                    class="w-full px-4 py-3.5 bg-stone-50/60 border border-stone-200 rounded-xl focus:ring-2 focus:ring-clsu-200 focus:border-clsu-400 focus:bg-white outline-none transition-all text-sm font-medium text-stone-800">

                <!-- Terms -->
                <div class="flex items-start gap-2.5 px-1 pt-1">
                    <input type="checkbox" id="terms" class="mt-1 h-4 w-4 rounded border-stone-300 text-clsu-600 focus:ring-clsu-300 cursor-pointer" name="terms" required>
                    <label for="terms" class="text-xs font-medium text-stone-500 leading-tight cursor-pointer">
                        I agree to the <span class="text-clsu-700 font-bold hover:underline">Terms of Use</span> and privacy policies.
                    </label>
                </div>

                <button type="submit" class="w-full bg-gradient-to-b from-clsu-600 to-clsu-800 text-white font-bold py-4 rounded-2xl shadow-[0_10px_24px_-8px_rgba(17,78,40,0.5)] hover:shadow-[0_14px_30px_-8px_rgba(17,78,40,0.6)] transition-all active:scale-[0.98] mt-2 cursor-pointer">
                    Create Account
                </button>
            </form>
        </div>
    </x-booking.ui.auth-card>

    <p class="mt-10 text-white/60 text-xs font-semibold tracking-wider flex items-center gap-1.5">
        <span>&copy; {{ date('Y') }} Farmers Hostel.</span>
        <span>&middot;</span>
        <a href="{{ route('staff.login') }}" class="hover:text-white transition-colors flex items-center gap-0.5">
            <span class="material-icons text-[12px]">lock</span> Staff Login
        </a>
    </p>
@endsection
@push('scripts')
<script>
    const loginTab = document.getElementById('loginTab');
    const signupTab = document.getElementById('signupTab');
    const loginForm = document.getElementById('loginForm');
    const signupForm = document.getElementById('signupForm');

    const activeTab = "flex-1 py-3 text-sm font-bold rounded-xl transition-all bg-white text-clsu-800 shadow-sm border border-stone-200 cursor-pointer";
    const inactiveTab = "flex-1 py-3 text-sm font-bold rounded-xl transition-all text-stone-500 hover:text-clsu-800 cursor-pointer";

    function showSignup() {
        signupTab.className = activeTab;
        loginTab.className = inactiveTab;
        loginForm.classList.add('hidden-form');
        signupForm.classList.remove('hidden-form');
    }

    function showLogin() {
        loginTab.className = activeTab;
        signupTab.className = inactiveTab;
        signupForm.classList.add('hidden-form');
        loginForm.classList.remove('hidden-form');
    }

    signupTab.onclick = showSignup;
    loginTab.onclick = showLogin;
</script>
@endpush
