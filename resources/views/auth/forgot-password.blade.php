@extends('layouts.authlayout')
@section('title', 'Forgot Password')
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
            
            <div class="px-8 pb-10 pt-6">
                <div>
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-black text-slate-900 leading-tight">Forgot Password?</h2>
                        <p class="text-sm font-semibold text-slate-500 mt-1.5 leading-relaxed">No worries! Just enter your registered email address and we'll send you a password reset link.</p>
                        
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div id="error-alert" class="error-alert mt-4">
                                    <x-booking.alert type="danger" :message="$error" />
                                </div>
                            @endforeach
                        @endif

                        @if (session('status'))
                            <div id="success-alert" class="mt-4">
                                <x-booking.alert type="success" :message="session('status')" />
                            </div>
                        @endif
                    </div>

                    <form class="space-y-4" method="POST" action="{{ route('password.email') }}">
                        @csrf
                        
                        <div class="space-y-1">
                            <input type="email" placeholder="Email address" name="email" required
                                   class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all placeholder:text-slate-450 font-medium text-sm text-slate-800">
                        </div>

                        <button type="submit" class="w-full bg-brand hover:bg-brand-light text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-brand/10 hover:shadow-xl transition-all active:scale-[0.98] mt-2 cursor-pointer">
                            Email Password Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-6">
                        <a href="{{ route('login') }}" class="text-xs font-bold text-brand hover:text-brand-light transition-colors flex items-center justify-center gap-1">
                            <span class="material-icons text-[14px]">arrow_back</span>
                            Back to login
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-10 text-white/55 text-xs font-semibold tracking-wider">
            &copy; {{ date('Y') }} Farmers Hostel.
        </p>
@endsection