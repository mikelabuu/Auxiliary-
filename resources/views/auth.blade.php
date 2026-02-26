@extends('layouts.authlayout')
@section('title', 'Farmers Hostel | Book Now')
@section('content')
        <div class="mb-10 transform transition-hover hover:scale-105 duration-300">
            <img src="{{ asset('image/FHLogo2.png') }}" alt="FH" class="h-20 w-auto drop-shadow-2xl">
        </div>

        <div class="glass-card w-full max-w-[550px] rounded-[40px] shadow-2xl overflow-hidden">
            
            <div class="flex p-2 gap-1 bg-black/5 m-6 rounded-2xl">
                <button id="loginTab" class="flex-1 py-3 text-sm font-bold rounded-xl transition-all bg-white text-brand shadow-sm">Log In</button>
                <button id="signupTab" class="flex-1 py-3 text-sm font-bold rounded-xl transition-all text-slate-500 hover:text-brand">Sign Up</button>
            </div>

            <div class="px-8 pb-10">
                
                <div id="loginForm">
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-extrabold text-slate-900 leading-tight">Welcome back!</h2>
                        <p class="text-slate-600 mt-2">Pick up where you left off.</p>
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div id="error-alert" class="error-alert mt-3 p-3 rounded-xl bg-red-100 border border-red-300 backdrop-blur-sm transition-opacity duration-500">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-[11px] font-bold text-red-600 uppercase tracking-wider mb-1">Error</p>
                                            <p class="text-[11px] text-red-600 font-medium leading-relaxed">{{ $error }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        {{-- Session Error --}}
                        @if (session('error'))
                            <div id="error-alert" class="error alert mt-3 p-3 rounded-xl bg-red-100 border border-red-300 backdrop-blur-sm transition-opacity duration-500">
                                <div class="flex items-start gap-2">
                                    <svg class="w-4 h-4 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-[11px] font-bold text-red-600 uppercase tracking-wider mb-1">Error</p>
                                        <p class="text-[11px] text-red-600 font-medium leading-relaxed">{{ session('error') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <form class="space-y-4" method="POST" action="{{ route('login.user') }}">
                        @csrf
                        {{-- <div class="grid grid-cols-2 gap-3 mb-6">
                            <button type="button" class="flex justify-center items-center py-3 px-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 transition-all">
                                <img src="https://www.svgrepo.com/show/475656/google-color.svg" class="w-5 h-5" alt="Google">
                            </button>
                            <button type="button" class="flex justify-center items-center py-3 px-4 bg-white border border-slate-200 rounded-2xl hover:bg-slate-50 transition-all">
                                <img src="https://www.svgrepo.com/show/442910/apple-logo.svg" class="w-5 h-5" alt="Apple">
                            </button>
                        </div> --}}

                        <input type="email" placeholder="Email address" name="email"
                               class="w-full px-5 py-4 bg-white/70 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all placeholder:text-slate-400">
                        
                        <input type="password" placeholder="Password" name="password"
                               class="w-full px-5 py-4 bg-white/70 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all placeholder:text-slate-400">
                        
                        <div class="flex justify-end">
                            <a href="{{ route('password.request')}}" class="text-xs font-bold text-brand hover:underline">Forgot Password?</a>
                        </div>

                        <button type="submit" class="w-full bg-brand hover:opacity-90 text-white font-bold py-4 rounded-2xl shadow-xl transition-all active:scale-95 mt-2">
                            Log In
                        </button>
                    </form>
                </div>

                <div id="signupForm" class="hidden-form">
                    <div class="text-center mb-6">
                        <h2 class="text-3xl font-extrabold text-slate-900 leading-tight">Start Booking</h2>
                        <p class="text-slate-600 mt-1">Join us at Farmers Hostel</p>
                    </div>

                    <form class="space-y-3" method="POST" action="{{ route('signup') }}">
                        @csrf
                        <div class="flex gap-2">
                            <input type="text" placeholder="First Name" name="first_name"
                                class="w-1/2 px-4 py-4 bg-white/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all text-sm">
                            <input type="text" placeholder="M.I." name="middle_initial"
                                class="w-1/4 px-4 py-4 bg-white/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all text-sm">
                            <input type="text" placeholder="Last Name" name="last_name"
                                class="w-full px-4 py-4 bg-white/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all text-sm">
                        </div>
                        
                        <div class="flex gap-2">
                            <input type="text" placeholder="Username" name="username"
                                class="w-1/2 px-4 py-4 bg-white/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all text-sm">
                            <input type="email" placeholder="Email" name="email"
                                class="w-1/2 px-4 py-4 bg-white/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all text-sm">
                        </div>
                        
                        <input type="password" placeholder="Password" name="password"
                            class="w-full px-4 py-4 bg-white/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all text-sm">
                        
                        <input type="password" placeholder="Confirm Password" name="password_confirmation"
                            class="w-full px-4 py-4 bg-white/70 border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all text-sm">
                        
                        <div class="flex items-start gap-3 px-1 pt-1">
                            <input type="checkbox" id="terms" class="mt-1 accent-[#3B612A]" name="terms">
                            <label for="terms" class="text-[10px] text-slate-500 leading-tight">
                                I agree to the <span class="text-brand font-bold underline">Terms of Use</span> and want to receive travel tips.
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-brand hover:opacity-90 text-white font-bold py-4 rounded-2xl shadow-xl transition-all active:scale-95 mt-2">
                            Create Account
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <p class="mt-10 text-white/60 text-xs font-medium tracking-wide">
            &copy; 2026 Farmers Hostel.
        </p>
@endsection
@push('scripts')
<script>
        const loginTab = document.getElementById('loginTab');
        const signupTab = document.getElementById('signupTab');
        const loginForm = document.getElementById('loginForm');
        const signupForm = document.getElementById('signupForm');

        function showSignup() {
            signupTab.className = "flex-1 py-3 text-sm font-bold rounded-xl transition-all bg-white text-brand shadow-sm";
            loginTab.className = "flex-1 py-3 text-sm font-bold rounded-xl transition-all text-slate-500 hover:text-brand";
            loginForm.classList.add('hidden-form');
            signupForm.classList.remove('hidden-form');
        }

        function showLogin() {
            loginTab.className = "flex-1 py-3 text-sm font-bold rounded-xl transition-all bg-white text-brand shadow-sm";
            signupTab.className = "flex-1 py-3 text-sm font-bold rounded-xl transition-all text-slate-500 hover:text-brand";
            signupForm.classList.add('hidden-form');
            loginForm.classList.remove('hidden-form');
        }

        signupTab.onclick = showSignup;
        loginTab.onclick = showLogin;

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.error-alert, #success-alert').forEach(el => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    setTimeout(() => { el.style.display = 'none'; }, 500);
                }, 5000);
            });
        });
    </script>
@endpush

