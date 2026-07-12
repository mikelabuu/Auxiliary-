<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'AAIS' }} | CLSU</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Anton&family=Libre+Franklin:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-aais-surface text-aais-ink">
        <div class="pointer-events-none fixed inset-0 -z-10 clsu-grid opacity-35"></div>
        <div class="pointer-events-none fixed -left-24 top-20 -z-10 h-72 w-72 rounded-full bg-[radial-gradient(circle,rgba(242,195,0,0.24)_0%,rgba(242,195,0,0)_70%)]"></div>
        <div class="pointer-events-none fixed -right-24 bottom-10 -z-10 h-80 w-80 rounded-full bg-[radial-gradient(circle,rgba(31,166,74,0.24)_0%,rgba(31,166,74,0)_70%)]"></div>

        @php
            $currentRole = $role ?? 'Public';
            $roleClass = str_contains(strtolower($currentRole), 'admin') ? 'role-admin' : 'role-staff';
        @endphp

        <header class="sticky top-0 z-30 border-b border-aais-border bg-white/90 backdrop-blur">
            <div class="mx-auto flex w-full max-w-390 flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-[#e8cf7f] bg-clsu-green-900 font-display text-xs text-clsu-gold-500">
                        CLSU
                    </div>
                    <div>
                        <p class="font-display text-xl leading-none text-clsu-green-900">AAIS</p>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-aais-muted">Document Tracking and Processing</p>
                    </div>
                </div>

                <nav class="order-3 flex w-full items-center gap-2 overflow-x-auto pb-1 sm:order-2 sm:w-auto sm:pb-0">
                    <a href="{{ route('aais.home') }}" class="{{ request()->routeIs('aais.home') ? 'aais-btn-primary text-white' : 'aais-btn-muted' }} rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em]">Overview</a>
                    <a href="{{ route('aais.admin.dashboard') }}" class="{{ request()->routeIs('aais.admin.dashboard') ? 'aais-btn-primary text-white' : 'aais-btn-muted' }} rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em]">Dashboard</a>
                    <a href="{{ route('aais.client.kiosk') }}" class="{{ request()->routeIs('aais.client.kiosk') ? 'aais-btn-primary text-white' : 'aais-btn-muted' }} rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em]">Kiosk</a>
                    <a href="{{ route('aais.admin.portal') }}" class="{{ request()->routeIs('aais.admin.portal') ? 'aais-btn-primary text-white' : 'aais-btn-muted' }} rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em]">Portal</a>
                    <a href="{{ route('aais.client.tracker') }}" class="{{ request()->routeIs('aais.client.tracker') ? 'aais-btn-primary text-white' : 'aais-btn-muted' }} rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em]">Tracker</a>
                    <a href="{{ route('aais.admin.reports') }}" class="{{ request()->routeIs('aais.admin.reports') ? 'aais-btn-primary text-white' : 'aais-btn-muted' }} rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-[0.08em]">Reports</a>
                </nav>

                <div class="order-2 flex items-center gap-2 sm:order-3">
                    <span class="aais-chip {{ $roleClass }}">{{ $currentRole }}</span>
                    <span class="rounded-md border border-aais-border bg-white px-2 py-1 text-[11px] font-semibold text-aais-muted">{{ now()->format('M d, Y') }}</span>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-390 px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>

        @stack('scripts')
    </body>
</html>
