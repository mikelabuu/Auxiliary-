@php
    $pageTitle = $title ?? 'AAIS Admin';
    $roleLabel = $role ?? 'Admin';
    $isAdmin   = str_contains(strtolower($roleLabel), 'admin');
    $topbarSub = $topbarSub ?? 'Document tracking and processing';
@endphp

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="AAIS - Admin workspace for document tracking at CLSU">
        <title>{{ $pageTitle }} | AAIS - CLSU</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="shell-root bg-surface text-ink" x-data="{ sidebarOpen: false }">
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
                    <p class="sidebar-brand-sub"></p>
                </div>
            </div>

            <p class="sidebar-section-label">Navigation</p>
            <nav class="sidebar-nav">
                <x-aais.layout.sidebar-link
                    :href="route('aais.admin.dashboard')"
                    :active="request()->routeIs('aais.admin.dashboard')"
                    label="Dashboard"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><rect x='3' y='3' width='7' height='7' rx='1'/><rect x='14' y='3' width='7' height='7' rx='1'/><rect x='3' y='14' width='7' height='7' rx='1'/><rect x='14' y='14' width='7' height='7' rx='1'/></svg>"
                    chip="3"
                />
                <x-aais.layout.sidebar-link
                    :href="route('aais.admin.transactions')"
                    :active="request()->routeIs('aais.admin.transactions')"
                    label="Transactions"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z'/><path d='M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3'/></svg>"
                />
                <x-aais.layout.sidebar-link
                    :href="route('aais.admin.portal')"
                    :active="request()->routeIs('aais.admin.portal')"
                    label="Scan &amp; Receive"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2'/><rect x='7' y='7' width='10' height='10' rx='1'/></svg>"
                />
                <x-aais.layout.sidebar-link
                    :href="route('aais.admin.reports')"
                    :active="request()->routeIs('aais.admin.reports')"
                    label="Reports"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z'/></svg>"
                />
            </nav>

            <p class="sidebar-section-label sidebar-section-label-spaced">Client Tools</p>
            <nav class="sidebar-nav">
                <x-aais.layout.sidebar-link
                    :href="route('aais.client.kiosk')"
                    :active="request()->routeIs('aais.client.kiosk')"
                    label="Self-Service Kiosk"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><rect x='2' y='3' width='20' height='14' rx='2'/><path d='M8 21h8M12 17v4'/></svg>"
                />
                <x-aais.layout.sidebar-link
                    :href="route('aais.client.tracker')"
                    :active="request()->routeIs('aais.client.tracker')"
                    label="Client Tracker"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='11' cy='11' r='8'/><path d='m21 21-4.35-4.35'/></svg>"
                />
            </nav>

            <div class="sidebar-mini-note">
                <p class="sidebar-mini-note-title">Workspace</p>
                <p class="sidebar-mini-note-text">Manage receiving, routing, and monitoring of university documents.</p>
            </div>

            <div class="sidebar-footer">
                <div class="sidebar-user-card">
                    <span class="sidebar-user-role">{{ $isAdmin ? 'Administrator' : 'Staff' }}</span>
                    <p class="sidebar-user-name">{{ $roleLabel }}</p>
                    <p class="sidebar-user-date">{{ now()->format('l, M d, Y') }}</p>
                </div>
                <a href="{{ route('aais.home') }}" class="sidebar-action-btn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z"/></svg>
                    <span>Back to Overview</span>
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
                <button class="btn btn-ghost btn-sm btn-icon topbar-alert-btn" aria-label="View notifications">
                    <svg class="topbar-alert-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="topbar-alert-dot"></span>
                    <span class="sr-only">Unread notifications</span>
                </button>
                <div x-data="{ userMenu: false }" class="user-menu-root">
                    <button @click="userMenu = !userMenu" @click.away="userMenu = false" class="user-menu-trigger" aria-label="Open user menu" :aria-expanded="userMenu.toString()">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($roleLabel) }}&background=1fa64a&color=fff&bold=true" alt="User Avatar" class="user-menu-avatar">
                        <svg class="user-menu-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="userMenu" x-transition.opacity.duration.200ms class="user-menu-panel" x-cloak>
                        <div class="user-menu-head">
                            <p class="user-menu-name">{{ $roleLabel }}</p>
                            <p class="user-menu-role">{{ $isAdmin ? 'Administrator' : 'Staff Member' }}</p>
                        </div>
                        <a href="#" class="user-menu-link">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> Profile Settings
                        </a>
                        <a href="{{ route('aais.home') }}" class="user-menu-link user-menu-link-danger">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg> Sign Out
                        </a>
                    </div>
                </div>
            </div>
        </header>

        {{-- Main content --}}
        <main class="shell-main">
            <div class="stagger-enter shell-content-wrap">
                @yield('content')
            </div>
        </main>

        @stack('scripts')
    </body>
</html>
