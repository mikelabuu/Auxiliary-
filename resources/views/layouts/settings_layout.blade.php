@extends('layouts.guest')
@section('title', 'User Center')
@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <!-- User Center Wrapper -->
    <div class="flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- Navigation Menu -->
        <aside class="w-full lg:w-64 bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex-shrink-0">
            <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50 pb-3 mb-4 hidden lg:block">User Center</h2>
            
            <nav class="flex flex-row lg:flex-col overflow-x-auto lg:overflow-x-visible gap-2 pb-2 lg:pb-0 scrollbar-none">
                <a href="{{ route('settings.profile') }}" 
                   class="flex items-center gap-2.5 px-4 py-3 text-sm font-bold rounded-xl transition-all whitespace-nowrap cursor-pointer select-none
                   {{ request()->routeIs('settings.profile') 
                      ? 'bg-brand-muted text-brand border-l-4 border-brand lg:border-l-4' 
                      : 'text-slate-600 hover:text-brand hover:bg-slate-50 border-l-4 border-transparent' }}">
                    <span class="material-icons text-[20px] {{ request()->routeIs('settings.profile') ? 'text-brand' : 'text-slate-400' }}">person</span>
                    Profile Settings
                </a>

                <a href="{{ route('settings.bookings') }}" 
                   class="flex items-center gap-2.5 px-4 py-3 text-sm font-bold rounded-xl transition-all whitespace-nowrap cursor-pointer select-none
                   {{ request()->routeIs('settings.bookings') 
                      ? 'bg-brand-muted text-brand border-l-4 border-brand lg:border-l-4' 
                      : 'text-slate-600 hover:text-brand hover:bg-slate-50 border-l-4 border-transparent' }}">
                    <span class="material-icons text-[20px] {{ request()->routeIs('settings.bookings') ? 'text-brand' : 'text-slate-400' }}">book</span>
                    My Bookings
                </a>

                <a href="{{ route('settings.transactions') }}" 
                   class="flex items-center gap-2.5 px-4 py-3 text-sm font-bold rounded-xl transition-all whitespace-nowrap cursor-pointer select-none
                   {{ request()->routeIs('settings.transactions') 
                      ? 'bg-brand-muted text-brand border-l-4 border-brand lg:border-l-4' 
                      : 'text-slate-600 hover:text-brand hover:bg-slate-50 border-l-4 border-transparent' }}">
                    <span class="material-icons text-[20px] {{ request()->routeIs('settings.transactions') ? 'text-brand' : 'text-slate-400' }}">payments</span>
                    My Payments
                </a>
            </nav>
        </aside>

        <!-- Main Account Content Panel -->
        <main class="flex-grow w-full bg-white rounded-2xl border border-slate-100 p-6 sm:p-8 shadow-sm">
            @yield('settings-content')
        </main>
    </div>
</div>
@endsection
