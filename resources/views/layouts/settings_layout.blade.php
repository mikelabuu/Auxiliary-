@extends('layouts.guest')
@section('title', 'User Center')
@section('content')
<div class="min-h-screen bg-canvas pt-28 pb-24">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- User Center Wrapper -->
    <div class="flex flex-col lg:flex-row gap-8 items-start">

        <!-- Navigation Menu -->
        <aside class="w-full lg:w-64 bg-white rounded-3xl border border-stone-200/70 p-5 shadow-[0_10px_30px_-18px_rgba(17,78,40,0.14)] flex-shrink-0 lg:sticky lg:top-28">
            <h2 class="text-xs font-bold text-stone-400 uppercase tracking-[0.15em] border-b border-stone-100 pb-3 mb-4 hidden lg:block">User Center</h2>

            <nav class="flex flex-row lg:flex-col overflow-x-auto lg:overflow-x-visible gap-2 pb-2 lg:pb-0">
                @php
                    $navItems = [
                        ['route' => 'settings.profile', 'icon' => 'person', 'label' => 'Profile Settings'],
                        ['route' => 'settings.bookings', 'icon' => 'book', 'label' => 'My Bookings'],
                        ['route' => 'settings.transactions', 'icon' => 'payments', 'label' => 'My Payments'],
                    ];
                @endphp
                @foreach($navItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <a href="{{ route($item['route']) }}"
                       class="flex items-center gap-2.5 px-4 py-3 text-sm font-bold rounded-xl transition-all whitespace-nowrap cursor-pointer select-none border-l-4
                       {{ $active
                          ? 'bg-clsu-50 text-clsu-800 border-clsu-600'
                          : 'text-stone-600 hover:text-clsu-800 hover:bg-stone-50 border-transparent' }}">
                        <span class="material-icons text-[20px] {{ $active ? 'text-clsu-700' : 'text-stone-400' }}">{{ $item['icon'] }}</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <!-- Main Account Content Panel -->
        <main class="flex-grow w-full bg-white rounded-3xl border border-stone-200/70 p-6 sm:p-8 shadow-[0_10px_30px_-18px_rgba(17,78,40,0.14)]">
            @if(session('status'))
                <div class="mb-6"><x-booking.alert type="success">{{ session('status') }}</x-booking.alert></div>
            @endif
            @if(session('error'))
                <div class="mb-6"><x-booking.alert type="danger">{{ session('error') }}</x-booking.alert></div>
            @endif
            @yield('settings-content')
        </main>
    </div>
</div>
</div>
@endsection
