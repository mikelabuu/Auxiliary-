@php
    $pageTitle = $title ?? 'AAIS Client';
    $topbarSub = $topbarSub ?? 'Self-service document tracking and status lookup';
    $isKioskPage = request()->routeIs('aais.client.kiosk');
@endphp

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="AAIS - Client portal for document submission and tracking at CLSU">
        <title>{{ $pageTitle }} | AAIS - CLSU</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="shell-root bg-surface text-ink {{ $isKioskPage ? 'client-kiosk-page' : '' }}" x-data="{ sidebarOpen: false, kioskFocus: false }" :class="{ 'kiosk-focus': kioskFocus }">
        <div class="grid-overlay"></div>

        {{-- Mobile overlay --}}
        <div class="sidebar-overlay" :class="{ 'open': sidebarOpen }" @click="sidebarOpen = false"></div>

        {{-- Sidebar --}}
        <aside class="shell-sidebar" :class="{ 'open': sidebarOpen }">
            <div class="sidebar-head">
                <div class="sidebar-seal sidebar-seal-image">
                    <img src="{{ asset('CLSU.png') }}" alt="CLSU Seal">
                </div>
                <div class="sidebar-brand-wrap">
                    <p class="sidebar-brand-title">AAIS</p>
                    <p class="sidebar-brand-sub">Client Portal</p>
                </div>
            </div>

            <p class="sidebar-section-label">Menu</p>
            <nav class="sidebar-nav">
                <x-aais.layout.sidebar-link
                    :href="route('aais.home')"
                    :active="request()->routeIs('aais.home')"
                    label="Overview"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z'/></svg>"
                />
                <x-aais.layout.sidebar-link
                    :href="route('aais.client.kiosk')"
                    :active="request()->routeIs('aais.client.kiosk')"
                    label="Encode &amp; Submit"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M11 4H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-4M13 4l4 4L9 16H5v-4L13 4z'/></svg>"
                />
                <x-aais.layout.sidebar-link
                    :href="route('aais.client.tracker')"
                    :active="request()->routeIs('aais.client.tracker')"
                    label="Track Document"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='11' cy='11' r='8'/><path d='m21 21-4.35-4.35'/></svg>"
                />
            </nav>

            <div class="sidebar-mini-note">
                <p class="sidebar-mini-note-title">Quick tip</p>
                <p class="sidebar-mini-note-text">Keep your QR or reference code so you can check status updates anytime.</p>
            </div>

            {{-- How it works --}}
            <div class="sidebar-info-box">
                <p class="sidebar-info-title">How it works</p>
                <x-aais.layout.sidebar-info-step number="1" text="Encode your document details at the kiosk" />
                <x-aais.layout.sidebar-info-step number="2" text="Save your QR reference code" />
                <x-aais.layout.sidebar-info-step number="3" text="Submit to the indicated office" />
                <x-aais.layout.sidebar-info-step number="4" text="Track status anytime online" />
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-user-card">
                    <p class="sidebar-user-date">Public Access</p>
                    <p class="sidebar-user-name">CLSU Student / Client</p>
                </div>
                <a href="{{ route('aais.admin.dashboard') }}" class="sidebar-action-btn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Admin Dashboard</span>
                </a>
            </div>
        </aside>

        {{-- Topbar --}}
        <header class="shell-topbar">
            <div class="topbar-leading">
                <button class="sidebar-toggle" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Toggle sidebar">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div>
                    <p class="topbar-title">{{ $pageTitle }}</p>
                    <p class="topbar-sub">{{ $topbarSub }}</p>
                </div>
            </div>
            <div class="topbar-actions">
                @if ($isKioskPage)
                    <button class="btn btn-outline btn-sm kiosk-focus-toggle" @click="kioskFocus = !kioskFocus; sidebarOpen = false" :aria-pressed="kioskFocus.toString()" aria-label="Toggle kiosk focus mode">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 00-2 2v3M16 3h3a2 2 0 012 2v3M8 21H5a2 2 0 01-2-2v-3M16 21h3a2 2 0 002-2v-3"/></svg>
                        <span x-text="kioskFocus ? 'Exit Focus' : 'Focus Mode'"></span>
                    </button>
                @endif
                <button class="btn btn-ghost btn-sm btn-icon topbar-alert-btn" aria-label="View notifications">
                    <svg class="topbar-alert-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="sr-only">Notifications</span>
                </button>
                <div x-data="{ userMenu: false }" class="user-menu-root">
                    <button @click="userMenu = !userMenu" @click.away="userMenu = false" class="user-menu-trigger" aria-label="Open user menu" :aria-expanded="userMenu.toString()">
                        <img src="https://ui-avatars.com/api/?name=Guest+User&background=cbd5e1&color=475569&bold=true" alt="User Avatar" class="user-menu-avatar">
                        <svg class="user-menu-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="userMenu" x-transition.opacity.duration.200ms class="user-menu-panel" x-cloak>
                        <div class="user-menu-head">
                            <p class="user-menu-name">Guest User</p>
                            <p class="user-menu-role">Public Access</p>
                        </div>
                        <a href="{{ route('aais.home') }}" class="user-menu-link">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z"/></svg> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="shell-main">
            <div class="stagger-enter shell-content-wrap">
                @yield('content')
            </div>
        </main>

        @stack('scripts')
    </body>
</html>
