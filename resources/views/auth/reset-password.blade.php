@extends('layouts.authlayout')
@section('title', 'Reset Password')
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
                        <h2 class="text-2xl font-black text-slate-900 leading-tight">Reset Password</h2>
                        <p class="text-sm font-semibold text-slate-500 mt-1">Please enter your email and set a new password.</p>
                        
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div id="error-alert" class="error-alert mt-4">
                                    <x-booking.alert type="danger" :message="$error" />
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <form class="space-y-4" method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <!-- Password Reset Token -->
                        <input type="hidden" name="token" value="{{ $token }}">
                        
                        <div class="space-y-1">
                            <input type="email" placeholder="Email address" name="email" value="{{ old('email', $email) }}" required readonly
                                   class="w-full px-5 py-4 bg-slate-100 border border-slate-200 rounded-2xl outline-none font-medium text-sm text-slate-500 cursor-not-allowed">
                        </div>

                        <div class="space-y-1">
                            <input type="password" placeholder="New Password" name="password" required
                                   class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all placeholder:text-slate-450 font-medium text-sm text-slate-800">
                        </div>

                        <div class="space-y-1">
                            <input type="password" placeholder="Confirm New Password" name="password_confirmation" required
                                   class="w-full px-5 py-4 bg-slate-50/50 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-brand/20 focus:border-brand focus:bg-white outline-none transition-all placeholder:text-slate-450 font-medium text-sm text-slate-800">
                        </div>

                        <button type="submit" class="w-full bg-brand hover:bg-brand-light text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-brand/10 hover:shadow-xl transition-all active:scale-[0.98] mt-2 cursor-pointer">
                            Reset Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <p class="mt-10 text-white/55 text-xs font-semibold tracking-wider">
            &copy; {{ date('Y') }} Farmers Hostel.
        </p>
@endsection