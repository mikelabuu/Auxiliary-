@extends('layouts.authlayout')
@section('content')
        <div class="glass-card w-full max-w-[550px] rounded-[40px] shadow-2xl overflow-hidden">
            <div class="mt-6 flex justify-center transform transition-hover hover:scale-105 duration-300">
                <img src="{{ asset('image/FHLogo2.png') }}" alt="FH" class="h-20 w-auto drop-shadow-2xl">
            </div>
            
            <div class="px-8 pb-10">
                <div>
                    <div class="text-center mb-8">
                        <h2 class="text-3xl font-extrabold text-slate-900 leading-tight">Staff Login</h2>
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
                            <div id="error-alert" class="error-alert mt-3 p-3 rounded-xl bg-red-100 border border-red-300 backdrop-blur-sm transition-opacity duration-500">
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

                    <form class="space-y-4" method="POST" action="{{ route('staff.login.submit') }}">
                        @csrf

                        <input type="email" placeholder="Email address" name="staff_email"
                               class="w-full px-5 py-4 bg-white/70 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all placeholder:text-slate-400">
                        
                        <input type="password" placeholder="Password" name="staff_password"
                               class="w-full px-5 py-4 bg-white/70 border border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#3B612A] focus:bg-white outline-none transition-all placeholder:text-slate-400">
                               
                        <div class="flex justify-end">
                            <a href="/" class="text-xs font-bold text-brand hover:underline">Go Back</a>
                        </div>

                        <button type="submit" class="w-full bg-brand hover:opacity-90 text-white font-bold py-4 rounded-2xl shadow-xl transition-all active:scale-95 mt-2">
                            Log In
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <p class="mt-10 text-white/60 text-xs font-medium tracking-wide">
            &copy; 2026 Farmers Hostel.
        </p>
@endsection