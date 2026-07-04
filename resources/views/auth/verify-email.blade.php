@extends('layouts.authlayout')
@section('title', 'Verify Email')
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
                        <h2 class="text-2xl font-black text-slate-900 leading-tight">Email Verification Required</h2>
                        <p class="text-sm font-semibold text-slate-500 mt-2 leading-relaxed">Thanks for signing up! Before accessing the site, please verify your email address by clicking on the link we just emailed you.</p>
                        
                        @if ($errors->any())
                            @foreach ($errors->all() as $error)
                                <div id="error-alert" class="error-alert mt-4">
                                    <x-booking.alert type="danger" :message="$error" />
                                </div>
                            @endforeach
                        @endif

                        @if (session('message'))
                            <div id="success-alert" class="mt-4">
                                <x-booking.alert type="success" :message="session('message')" />
                            </div>
                        @endif
                    </div>

                    <form class="space-y-4" method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <p class="text-xs font-bold text-slate-400 text-center">Didn't receive the email? Click below to request another.</p>
                        <button type="submit" class="w-full bg-brand hover:bg-brand-light text-white font-extrabold py-4 rounded-2xl shadow-lg shadow-brand/10 hover:shadow-xl transition-all active:scale-[0.98] mt-1 cursor-pointer">
                            Resend Verification Email
                        </button>
                    </form>

                    <div class="text-center mt-6">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf 
                            <button type="submit" class="text-xs font-bold text-red-650 hover:text-red-750 hover:underline cursor-pointer">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-10 text-white/55 text-xs font-semibold tracking-wider">
            &copy; {{ date('Y') }} Farmers Hostel.
        </p>
@endsection