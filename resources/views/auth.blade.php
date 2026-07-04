@extends('layouts.authlayout')
@section('title', 'Farmers Hostel | Book Now')
@section('content')
        <div class="bg-white/90 backdrop-blur-md w-full max-w-[500px] rounded-[32px] shadow-2xl border border-slate-100/50 overflow-hidden transition-all duration-300">
            
            <!-- Logo Section -->
            <div class="mt-8 flex flex-col items-center gap-2 transform transition-all hover:scale-105 duration-300">
                <img src="{{ asset('image/FHLogo2.png') }}" alt="Farmers Hostel" class="h-20 w-auto drop-shadow-md">
                <div class="text-center">
                    <span class="block text-lg font-black text-brand tracking-tight">Farmers Hostel</span>
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">CLSU Campus</span>
                </div>
            </div>
            
            <!-- Dual Tabs -->
            <div class="flex p-1.5 gap-1.5 bg-slate-100/60 m-6 rounded-2xl border border-slate-200/40">
                <button id="loginTab" class="flex-1 py-3 text-sm font-extrabold rounded-xl transition-all bg-white text-brand shadow-sm border border-slate-100 cursor-pointer">Log In</button>
                <button id="signupTab" class="flex-1 py-3 text-sm font-extrabold rounded-xl transition-all text-slate-500 hover:text-brand cursor-pointer">Sign Up</button>
            </div>

            <!-- Form Body -->
            <div class="px-8 pb-10">
                
                <!-- Log In Form -->
                <div id="loginForm">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-black text-slate-900 leading-tight">Welcome back!</h2>
                        <p class="text-sm font-semibold text-slate-500 mt-1">Please enter your credentials to log in.</p>
                        
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div id="error-alert" class="error-alert mt-4">
                                    <x-booking.alert type="danger" :message="$error" />
                                </div>
                            @endforeach
                        @endif

                        @if (session('error'))
                            <div id="error-alert" class="error-alert mt-4">
                                <x-booking.alert type="danger" :message="session('error')" />
                            </div>
                        @endif
                    </div>

                    <form class="space-y-4" method="POST" action="{{ route('login.user') }}">
                        @csrf

                        <div class="space-y-1">
                            <input type="email" placeholder="Email address" name="email" required
                                   class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all placeholder:text-slate-450 font-medium text-sm text-slate-800">
                        </div>
                        
                        <div class="space-y-1">
                            <input type="password" placeholder="Password" name="password" required
                                   class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all placeholder:text-slate-450 font-medium text-sm text-slate-800">
                        </div>
                        
                        <div class="flex justify-end pt-1">
                            <a href="{{ route('password.request')}}" class="text-xs font-bold text-brand hover:text-brand-light transition-colors hover:underline">Forgot Password?</a>
                        </div>

                        <button class="w-full bg-brand hover:bg-brand-light text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-brand/10 hover:shadow-xl transition-all active:scale-[0.98] mt-2 cursor-pointer">
                            Log In
                        </button>
                    </form>
                </div>

                <!-- Sign Up Form -->
                <div id="signupForm" class="hidden-form">
                    <div class="text-center mb-5">
                        <h2 class="text-2xl font-black text-slate-900 leading-tight">Start Booking</h2>
                        <p class="text-sm font-semibold text-slate-500 mt-1">Join us at Farmers Hostel</p>
                    </div>

                    <form class="space-y-3.5" method="POST" action="{{ route('signup') }}">
                        @csrf
                        
                        <!-- Name Row -->
                        <div class="flex gap-2">
                            <input type="text" placeholder="First Name" name="first_name" required
                                class="w-2/5 px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all text-sm font-medium text-slate-800">
                            <input type="text" placeholder="M.I." name="middle_initial" maxlength="2"
                                class="w-1/5 px-3 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white text-center outline-none transition-all text-sm font-medium text-slate-800">
                            <input type="text" placeholder="Last Name" name="last_name" required
                                class="w-2/5 px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all text-sm font-medium text-slate-800">
                        </div>
                        
                        <!-- Account Details Row -->
                        <div class="flex gap-2">
                            <input type="text" placeholder="Username" name="username" required
                                class="w-1/2 px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all text-sm font-medium text-slate-800">
                            <input type="email" placeholder="Email Address" name="email" required
                                class="w-1/2 px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all text-sm font-medium text-slate-800">
                        </div>
                        
                        <!-- Passwords -->
                        <input type="password" placeholder="Password" name="password" required
                            class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all text-sm font-medium text-slate-800">
                        
                        <input type="password" placeholder="Confirm Password" name="password_confirmation" required
                            class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all text-sm font-medium text-slate-800">
                        
                        <!-- Terms -->
                        <div class="flex items-start gap-2.5 px-1 pt-1">
                            <input type="checkbox" id="terms" class="mt-1 h-4 w-4 rounded border-slate-300 text-brand focus:ring-brand cursor-pointer" name="terms" required>
                            <label for="terms" class="text-xs font-medium text-slate-500 leading-tight cursor-pointer">
                                I agree to the <span class="text-brand font-bold hover:underline">Terms of Use</span> and privacy policies.
                            </label>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="w-full bg-brand hover:bg-brand-light text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-brand/10 hover:shadow-xl transition-all active:scale-[0.98] mt-2 cursor-pointer">
                            Create Account
                        </button>
                    </form>
                </div>

            </div>
        </div>

        <p class="mt-10 text-white/55 text-xs font-semibold tracking-wider flex items-center gap-1.5">
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

        function showSignup() {
            signupTab.className = "flex-1 py-3 text-sm font-extrabold rounded-xl transition-all bg-white text-brand shadow-sm border border-slate-100 cursor-pointer";
            loginTab.className = "flex-1 py-3 text-sm font-extrabold rounded-xl transition-all text-slate-500 hover:text-brand cursor-pointer";
            loginForm.classList.add('hidden-form');
            signupForm.classList.remove('hidden-form');
        }

        function showLogin() {
            loginTab.className = "flex-1 py-3 text-sm font-extrabold rounded-xl transition-all bg-white text-brand shadow-sm border border-slate-100 cursor-pointer";
            signupTab.className = "flex-1 py-3 text-sm font-extrabold rounded-xl transition-all text-slate-500 hover:text-brand cursor-pointer";
            signupForm.classList.add('hidden-form');
            loginForm.classList.remove('hidden-form');
        }

        signupTab.onclick = showSignup;
        loginTab.onclick = showLogin;
    </script>
@endpush
