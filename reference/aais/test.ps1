
# Refactor AAIS Blade Templates to Pure Tailwind CSS
# This script writes the refactored admin.blade.php and dashboard.blade.php

$adminLayoutContent = @'
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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Geist+Mono:wght@400;500;700&display=swap" rel="stylesheet">
        <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gradient-to-br from-slate-50 via-white to-slate-50 text-slate-900 min-h-screen">
        {{-- Ambient Grid Overlay --}}
        <div class="fixed inset-0 z-0 pointer-events-none opacity-20" style="background-image: radial-gradient(circle at 1px 1px, rgba(31,166,74,0.15) 1px, transparent 1px); background-size: 48px 48px;"></div>

        {{-- Sidebar --}}
        <aside class="fixed top-0 left-0 bottom-0 w-64 z-50 flex flex-col overflow-hidden bg-gradient-to-b from-emerald-900 via-emerald-800 to-emerald-950 border-r border-emerald-700/30">
            {{-- Sidebar Glow Effects --}}
            <div class="absolute top-0 left-0 w-64 h-64 bg-gradient-radial from-amber-400/20 to-transparent rounded-full blur-3xl -translate-x-1/3 -translate-y-1/3 pointer-events-none"></div>
            <div class="absolute bottom-0 right-0 w-52 h-52 bg-gradient-radial from-emerald-400/15 to-transparent rounded-full blur-3xl translate-x-1/4 translate-y-1/4 pointer-events-none"></div>

            {{-- Logo Section --}}
            <div class="relative z-10 flex items-center gap-3 px-4.5 py-5 border-b border-white/10">
                <div class="flex items-center justify-center w-10 h-10 rounded-full border-1.5 border-amber-400/40 bg-amber-400/10">
                    <span class="text-xs font-black tracking-widest text-amber-300 uppercase">CLSU</span>
                </div>
                <div>
                    <p class="text-lg font-black tracking-tight text-white">AAIS</p>
                    <p class="mt-0.5 text-xs font-semibold tracking-wider text-white/40 uppercase">Admin Workspace</p>
                </div>
            </div>

            {{-- Navigation --}}
            <p class="relative z-10 px-4.5 pt-4 pb-1.5 text-xs font-bold tracking-widest text-white/30 uppercase">Navigation</p>
            <nav class="relative z-10 flex flex-col gap-1 px-2">
                <a href="{{ route('aais.admin.dashboard') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-sm font-semibold tracking-tight text-white/60 hover:text-white/100 transition-all duration-200 {{ request()->routeIs('aais.admin.dashboard') ? 'bg-emerald-600/30 text-emerald-100 border-l-3 border-l-amber-400' : 'hover:bg-white/8' }}">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-85" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    <span>Dashboard</span>
                    @if (request()->routeIs('aais.admin.dashboard'))
                        <span class="ml-auto inline-flex items-center justify-center min-w-5 h-5 px-1 rounded-full bg-red-500/90 text-white text-xs font-bold">3</span>
                    @endif
                </a>
                <a href="{{ route('aais.admin.portal') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-sm font-semibold tracking-tight text-white/60 hover:text-white/100 hover:bg-white/8 transition-all duration-200 {{ request()->routeIs('aais.admin.portal') ? 'bg-emerald-600/30 text-emerald-100 border-l-3 border-l-amber-400' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-85" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
                    <span>Scan &amp; Receive</span>
                </a>
                <a href="{{ route('aais.admin.reports') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-sm font-semibold tracking-tight text-white/60 hover:text-white/100 hover:bg-white/8 transition-all duration-200 {{ request()->routeIs('aais.admin.reports') ? 'bg-emerald-600/30 text-emerald-100 border-l-3 border-l-amber-400' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-85" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>Reports</span>
                </a>
            </nav>

            {{-- Client Tools --}}
            <p class="relative z-10 px-4.5 pt-5 pb-1.5 text-xs font-bold tracking-widest text-white/30 uppercase">Client Tools</p>
            <nav class="relative z-10 flex flex-col gap-1 px-2">
                <a href="{{ route('aais.client.kiosk') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-sm font-semibold tracking-tight text-white/60 hover:text-white/100 hover:bg-white/8 transition-all duration-200 {{ request()->routeIs('aais.client.kiosk') ? 'bg-emerald-600/30 text-emerald-100 border-l-3 border-l-amber-400' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-85" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                    <span>Self-Service Kiosk</span>
                </a>
                <a href="{{ route('aais.client.tracker') }}" class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-sm font-semibold tracking-tight text-white/60 hover:text-white/100 hover:bg-white/8 transition-all duration-200 {{ request()->routeIs('aais.client.tracker') ? 'bg-emerald-600/30 text-emerald-100 border-l-3 border-l-amber-400' : '' }}">
                    <svg class="w-4 h-4 flex-shrink-0 opacity-85" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <span>Client Tracker</span>
                </a>
            </nav>

            {{-- Footer --}}
            <div class="relative z-10 mt-auto px-3 pb-4 pt-3 border-t border-white/10 space-y-3">
                <div class="rounded-lg border border-white/10 bg-white/5 px-3.5 py-3 space-y-1">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                            <span class="text-xs font-bold tracking-widest text-amber-300 uppercase">{{ $isAdmin ? 'Administrator' : 'Staff' }}</span>
                        </span>
                    </div>
                    <p class="text-sm font-bold text-white">{{ $roleLabel }}</p>
                    <p class="text-xs text-white/50">{{ now()->format('l, M d, Y') }}</p>
                </div>
                <a href="{{ route('aais.home') }}" class="flex items-center gap-2 w-full px-3 py-2.5 rounded-lg border border-white/20 bg-white/6 text-white/70 hover:text-white hover:bg-white/12 hover:border-white/30 transition-all duration-200 text-xs font-bold uppercase tracking-wide">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 9.75L12 3l9 6.75V21a1 1 0 01-1 1H4a1 1 0 01-1-1V9.75z"/></svg>
                    <span>Back to Overview</span>
                </a>
            </div>
        </aside>

        {{-- Topbar --}}
        <header class="fixed top-0 right-0 left-64 h-15 z-40 flex items-center justify-between px-8 bg-white/90 backdrop-blur-xl border-b border-slate-200/50">
            <div>
                <p class="text-lg font-black tracking-tight text-emerald-900">{{ $pageTitle }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-600">{{ $topbarSub }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide border {{ $isAdmin ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200' }}">
                    {{ $isAdmin ? 'Admin' : 'Staff' }}
                </span>
                <span class="text-xs font-semibold text-slate-600 bg-white border border-slate-200 px-3 py-1.5 rounded-lg">{{ now()->format('M d, Y') }}</span>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="ml-64 mt-15 min-h-screen px-8 py-7 overflow-y-auto">
            <div class="mx-auto w-full max-w-6xl">
                @yield('content')
            </div>
        </main>

        @stack('scripts')
    </body>
</html>
'@

$dashboardContent = @'
@php
    $title     = 'Dashboard';
    $role      = 'Admin';
    $topbarSub = 'Real-time overview of all document transactions';

    $docs = [
        ['ref'=>'TL-2026-0412','name'=>'Maria Santos','type'=>'Transcript of Records','office'=>'Registrar','staff'=>'V. Santos','received'=>'Apr 1, 2026','status'=>'process'],
        ['ref'=>'TL-2026-0411','name'=>'Juan Dela Cruz','type'=>'Certificate of Enrollment','office'=>'Admissions','staff'=>'R. Reyes','received'=>'Apr 1, 2026','status'=>'pickup'],
        ['ref'=>'TL-2026-0410','name'=>'Ana Reyes','type'=>'Good Moral Certificate','office'=>'OSAS','staff'=>'P. Flores','received'=>'Mar 31, 2026','status'=>'approved'],
        ['ref'=>'TL-2026-0409','name'=>'Carlo Mendoza','type'=>'Diploma Authentication','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 31, 2026','status'=>'complete'],
        ['ref'=>'TL-2026-0408','name'=>'Rosa Garcia','type'=>'CAV Document','office'=>'Records','staff'=>'M. Torres','received'=>'Mar 30, 2026','status'=>'logged'],
        ['ref'=>'TL-2026-0407','name'=>'Kevin Lim','type'=>'Transfer Credentials','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 30, 2026','status'=>'void'],
        ['ref'=>'TL-2026-0406','name'=>'Liza Bautista','type'=>'Honorable Dismissal','office'=>'OSAS','staff'=>'P. Flores','received'=>'Mar 29, 2026','status'=>'complete'],
        ['ref'=>'TL-2026-0405','name'=>'Mark Cruz','type'=>'Transcript of Records','office'=>'Registrar','staff'=>'V. Santos','received'=>'Mar 29, 2026','status'=>'process'],
    ];

    $statusLabels = ['logged'=>'Logged','process'=>'In Process','approved'=>'Approved','pickup'=>'For Pickup','complete'=>'Completed','void'=>'Voided'];

    $kpis = [
        ['value'=>'1,284','label'=>'Total Transactions','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z"/><path d="M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>','bg'=>'emerald','trend'=>'+12 this week','up'=>true],
        ['value'=>'48','label'=>"Today's Documents",'icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>','bg'=>'amber','trend'=>'+8 vs yesterday','up'=>true],
        ['value'=>'7','label'=>'Pending / In-Process','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>','bg'=>'blue','trend'=>'-3 from last week','up'=>false],
        ['value'=>'3','label'=>'Ready for Pickup','icon'=>'<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20V10M18 20V4M6 20v-6"/></svg>','bg'=>'rose','trend'=>'Needs attention','up'=>false],
    ];
@endphp

@extends('layouts.admin')

@section('content')
<div x-data="dashboardApp()" x-cloak>
    {{-- KPI STAT CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-7">
        @foreach ($kpis as $kpi)
            @php
                $bgClasses = [
                    'emerald' => 'bg-gradient-to-br from-emerald-50 to-teal-50 border-emerald-200/60',
                    'amber' => 'bg-gradient-to-br from-amber-50 to-yellow-50 border-amber-200/60',
                    'blue' => 'bg-gradient-to-br from-blue-50 to-cyan-50 border-blue-200/60',
                    'rose' => 'bg-gradient-to-br from-rose-50 to-pink-50 border-rose-200/60',
                ];
                $iconBgClasses = [
                    'emerald' => 'bg-emerald-100 text-emerald-700',
                    'amber' => 'bg-amber-100 text-amber-700',
                    'blue' => 'bg-blue-100 text-blue-700',
                    'rose' => 'bg-rose-100 text-rose-700',
                ];
            @endphp
            <div class="group relative overflow-hidden rounded-xl border {{ $bgClasses[$kpi['bg']] }} p-5 transition-all duration-300 hover:shadow-lg hover:border-opacity-100 cursor-pointer">
                <div class="absolute inset-0 bg-gradient-to-br from-white/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="relative z-10 flex items-start justify-between mb-4">
                    <div class="flex items-center justify-center w-11 h-11 rounded-lg {{ $iconBgClasses[$kpi['bg']] }}">
                        {!! $kpi['icon'] !!}
                    </div>
                    <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold {{ $kpi['up'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="{{ $kpi['up'] ? 'M5 15l7-7 7 7' : 'M5 9l7 7 7-7' }}"/></svg>
                        <span class="text-xs">{{ $kpi['trend'] }}</span>
                    </div>
                </div>
                <p class="text-2xl xl:text-3xl font-black text-slate-900 leading-none mb-1.5">{{ $kpi['value'] }}</p>
                <p class="text-xs font-semibold tracking-wide text-slate-600 uppercase">{{ $kpi['label'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- DOCUMENT TABLE SECTION --}}
    <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden mb-7">
        {{-- Card Header --}}
        <div class="border-b border-slate-200/60 bg-gradient-to-r from-emerald-50/50 via-white to-amber-50/50 px-6 py-4 flex items-center justify-between">
            <h2 class="flex items-center gap-2.5 text-base font-black text-emerald-900">
                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z"/><path d="M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                Live Document Log
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('aais.admin.portal') }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 text-white text-xs font-bold uppercase tracking-wide shadow-md hover:shadow-lg hover:from-emerald-700 hover:to-emerald-800 transition-all duration-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
                    Scan & Receive
                </a>
                <a href="{{ route('aais.admin.reports') }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold uppercase tracking-wide hover:bg-slate-50 transition-all duration-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Reports
                </a>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="border-b border-slate-200/60 bg-slate-50/40 px-6 py-3 flex items-center gap-3 flex-wrap">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-600">Filter:</span>
            <template x-for="f in filters" :key="f">
                <button @click="activeFilter = f" :class="{ 'bg-emerald-100 text-emerald-700 border-emerald-300': activeFilter === f, 'bg-white text-slate-600 border-slate-200 hover:border-slate-300': activeFilter !== f }" class="px-2.5 py-1 rounded-lg text-xs font-semibold uppercase tracking-wide border transition-all duration-200 whitespace-nowrap cursor-pointer" x-text="f"></button>
            </template>
            <div class="ml-auto flex items-center gap-2">
                <input type="text" x-model="searchQuery" placeholder="Search by ref or name..." class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-200 w-48">
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200/60 bg-slate-50">
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Ref Code</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Client Name</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Document Type</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Office</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Staff</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Received</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-600">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/40">
                    @foreach ($docs as $idx => $doc)
                        <tr x-show="isVisible({{ $idx }})" x-transition class="hover:bg-slate-50/50 transition-colors duration-150">
                            <td class="px-5 py-3"><span class="font-mono font-bold text-xs px-2 py-1 rounded bg-emerald-100 text-emerald-700 border border-emerald-200">{{ $doc['ref'] }}</span></td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $doc['name'] }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $doc['type'] }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $doc['office'] }}</td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $doc['staff'] }}</td>
                            <td class="px-5 py-3 text-xs text-slate-500">{{ $doc['received'] }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $statusColors = [
                                        'logged' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'process' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'approved' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'pickup' => 'bg-orange-50 text-orange-700 border-orange-200',
                                        'complete' => 'bg-green-50 text-green-700 border-green-200',
                                        'void' => 'bg-red-50 text-red-700 border-red-200',
                                    ];
                                    $dotColors = [
                                        'logged' => 'bg-emerald-500',
                                        'process' => 'bg-amber-500',
                                        'approved' => 'bg-blue-500',
                                        'pickup' => 'bg-orange-500',
                                        'complete' => 'bg-green-600',
                                        'void' => 'bg-red-600',
                                    ];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide border {{ $statusColors[$doc['status']] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$doc['status']] }}"></span>
                                    {{ $statusLabels[$doc['status']] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="viewDoc({{ $idx }})" title="View Details" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <button @click="editDoc({{ $idx }})" title="Update Status" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    @if($doc['status'] !== 'void' && $doc['status'] !== 'complete')
                                        <button @click="voidDoc({{ $idx }})" title="Void" class="inline-flex items-center justify-center w-8 h-8 rounded-lg hover:bg-red-100 text-slate-600 hover:text-red-600 transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="border-t border-slate-200/60 bg-slate-50/40 px-6 py-3 flex items-center justify-between">
            <span class="text-xs text-slate-600">Showing <strong x-text="visibleCount" class="font-bold text-slate-900"></strong> of <strong x-text="docs.length" class="font-bold text-slate-900"></strong> transactions</span>
            <div class="flex items-center gap-1">
                <button disabled class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-400 bg-slate-50 cursor-not-allowed">â† Prev</button>
                <button class="px-2.5 py-1 rounded-lg border border-slate-300 bg-emerald-600 text-xs font-semibold text-white">1</button>
                <button class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">2</button>
                <button class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">3</button>
                <button class="px-2.5 py-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50">Next â†’</button>
            </div>
        </div>
    </div>

    {{-- BOTTOM GRID: Activity + Actions + Breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-7">
        {{-- Recent Activity --}}
        <div class="lg:col-span-2 rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-200/60 bg-gradient-to-r from-emerald-50/50 to-white px-6 py-4">
                <h3 class="flex items-center gap-2.5 text-base font-black text-emerald-900">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Recent Activity
                </h3>
            </div>
            <div class="px-6 py-5">
                <div class="space-y-4">
                    @php
                        $activities = [
                            ['time'=>'10:42 AM','msg'=>'TL-2026-0412 received by V. Santos â€“ status: In Process','status'=>'process','done'=>true],
                            ['time'=>'10:28 AM','msg'=>'TL-2026-0411 marked For Pickup â€“ email sent to client','status'=>'pickup','done'=>true],
                            ['time'=>'09:55 AM','msg'=>'TL-2026-0410 approved by P. Flores','status'=>'approved','done'=>true],
                            ['time'=>'09:30 AM','msg'=>'TL-2026-0413 encoded via kiosk by student','status'=>'logged','done'=>false],
                            ['time'=>'08:17 AM','msg'=>'TL-2026-0409 completed and released','status'=>'complete','done'=>true],
                        ];
                    @endphp
                    @foreach ($activities as $act)
                        <div class="flex gap-4">
                            <div class="pt-1">
                                @if ($act['done'])
                                    <div class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100">
                                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                    </div>
                                @else
                                    <div class="flex items-center justify-center w-6 h-6 rounded-full border-2 border-amber-300 bg-amber-50">
                                        <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1 pt-0.5">
                                <p class="text-sm font-medium text-slate-900">{{ $act['msg'] }}</p>
                                <div class="mt-1 flex items-center gap-2 text-xs text-slate-500">
                                    <span>{{ $act['time'] }} today</span>
                                    <span class="text-slate-300">Â·</span>
                                    @php
                                        $statusColors = [
                                            'logged' => 'bg-emerald-50 text-emerald-700',
                                            'process' => 'bg-amber-50 text-amber-700',
                                            'approved' => 'bg-blue-50 text-blue-700',
                                            'pickup' => 'bg-orange-50 text-orange-700',
                                            'complete' => 'bg-green-50 text-green-700',
                                        ];
                                    @endphp
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-bold uppercase {{ $statusColors[$act['status']] }}">{{ $statusLabels[$act['status']] }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Quick Actions + Status Breakdown (Right Column) --}}
        <div class="space-y-7">
            {{-- Quick Actions --}}
            <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200/60 bg-gradient-to-r from-amber-50/50 to-white px-6 py-4">
                    <h3 class="flex items-center gap-2.5 text-base font-black text-emerald-900">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Quick Actions
                    </h3>
                </div>
                <div class="px-5 py-4 space-y-2">
                    <a href="{{ route('aais.admin.portal') }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 text-white text-xs font-bold uppercase tracking-wide shadow-md hover:shadow-lg hover:from-emerald-700 hover:to-emerald-800 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
                        Scan Portal
                    </a>
                    <a href="{{ route('aais.client.kiosk') }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold uppercase tracking-wide hover:bg-slate-50 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                        Kiosk Entry
                    </a>
                    <a href="{{ route('aais.admin.reports') }}" class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold uppercase tracking-wide hover:bg-slate-50 transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Report
                    </a>
                </div>
            </div>

            {{-- Status Breakdown --}}
            <div class="rounded-xl border border-slate-200/60 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-200/60 bg-gradient-to-r from-cyan-50/50 to-white px-6 py-4">
                    <h3 class="flex items-center gap-2.5 text-base font-black text-emerald-900">
                        <svg class="w-5 h-5 text-cyan-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                        Status Breakdown
                    </h3>
                </div>
                <div class="px-5 py-5 space-y-3">
                    @php
                        $breakdown = [
                            ['status'=>'logged','label'=>'Logged','count'=>11,'pct'=>23],
                            ['status'=>'process','label'=>'In Process','count'=>7,'pct'=>15],
                            ['status'=>'approved','label'=>'Approved','count'=>5,'pct'=>10],
                            ['status'=>'pickup','label'=>'For Pickup','count'=>3,'pct'=>6],
                            ['status'=>'complete','label'=>'Completed','count'=>21,'pct'=>44],
                            ['status'=>'void','label'=>'Voided','count'=>1,'pct'=>2],
                        ];
                    @endphp
                    @foreach ($breakdown as $b)
                        @php
                            $statusColors = [
                                'logged' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'bar' => 'bg-emerald-500'],
                                'process' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'bar' => 'bg-amber-500'],
                                'approved' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'bar' => 'bg-blue-500'],
                                'pickup' => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-200', 'bar' => 'bg-orange-500'],
                                'complete' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'bar' => 'bg-green-600'],
                                'void' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'bar' => 'bg-red-600'],
                            ];
                        @endphp
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg border text-xs font-bold uppercase {{ $statusColors[$b['status']]['bg'] }} {{ $statusColors[$b['status']]['text'] }} {{ $statusColors[$b['status']]['border'] }} min-w-fit">{{ $b['label'] }}</span>
                            <div class="flex-1 h-2 rounded-full bg-slate-200 overflow-hidden">
                                <div class="h-full {{ $statusColors[$b['status']]['bar'] }} rounded-full transition-all duration-500" style="width: {{ $b['pct'] }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-slate-600 text-right w-7">{{ $b['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardApp() {
    const docs = @json($docs);
    const labels = @json($statusLabels);
    const filterMap = { 'All':'all','Logged':'logged','In Process':'process','Approved':'approved','For Pickup':'pickup','Completed':'complete','Voided':'void' };

    return {
        filters: ['All','Logged','In Process','Approved','For Pickup','Completed','Voided'],
        activeFilter: 'All',
        searchQuery: '',
        docs,

        matchesFilter(doc) {
            const fKey = filterMap[this.activeFilter] || 'all';
            return fKey === 'all' || doc.status === fKey;
        },

        matchesSearch(doc) {
            if (!this.searchQuery.trim()) return true;
            const q = this.searchQuery.toLowerCase();
            return doc.ref.toLowerCase().includes(q) || doc.name.toLowerCase().includes(q);
        },

        get visibleCount() {
            return this.docs.filter((doc) => this.matchesFilter(doc) && this.matchesSearch(doc)).length;
        },

        isVisible(idx) {
            const doc = this.docs[idx];
            return this.matchesFilter(doc) && this.matchesSearch(doc);
        },

        viewDoc(idx) {
            const d = this.docs[idx];
            Swal.fire({
                title: d.ref,
                html: `
                    <div style="text-align:left;font-size:14px;line-height:2;">
                        <strong>Client:</strong> ${d.name}<br>
                        <strong>Document:</strong> ${d.type}<br>
                        <strong>Office:</strong> ${d.office}<br>
                        <strong>Staff:</strong> ${d.staff}<br>
                        <strong>Received:</strong> ${d.received}<br>
                        <strong>Status:</strong> <span style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.75rem;font-weight:bold;text-transform:uppercase;" class="bg-emerald-50 text-emerald-700 border border-emerald-200">${labels[d.status]}</span>
                    </div>`,
                confirmButtonText: 'Close',
                width: 480,
            });
        },

        editDoc(idx) {
            const d = this.docs[idx];
            Swal.fire({
                title: 'Update Status',
                html: `
                    <p style="font-size:13px;color:#666;margin-bottom:12px;">Updating <strong>${d.ref}</strong> â€” ${d.name}</p>
                    <select id="swal-status" style="width:100%;padding:10px;border-radius:8px;border:1.5px solid #c8e0cc;font-size:14px;">
                        <option value="logged" ${d.status==='logged'?'selected':''}>Logged</option>
                        <option value="process" ${d.status==='process'?'selected':''}>In Process</option>
                        <option value="approved" ${d.status==='approved'?'selected':''}>Approved</option>
                        <option value="pickup" ${d.status==='pickup'?'selected':''}>For Pickup</option>
                        <option value="complete" ${d.status==='complete'?'selected':''}>Completed</option>
                    </select>`,
                confirmButtonText: 'Update Status',
                showCancelButton: true,
                preConfirm: () => document.getElementById('swal-status').value,
            }).then((result) => {
                if (result.isConfirmed) {
                    this.docs[idx].status = result.value;
                    Swal.fire({ icon:'success', title:'Updated!', text:`${d.ref} is now "${labels[result.value]}"`, timer:2000, showConfirmButton:false });
                }
            });
        },

        voidDoc(idx) {
            const d = this.docs[idx];
            Swal.fire({
                title: 'Void Transaction?',
                html: `<p style="font-size:13px;color:#666;">This will void <strong>${d.ref}</strong> for ${d.name}. This action is logged and cannot be undone.</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Void It',
                confirmButtonColor: '#c92a2a',
            }).then((result) => {
                if (result.isConfirmed) {
                    this.docs[idx].status = 'void';
                    Swal.fire({ icon:'success', title:'Voided', text:`${d.ref} has been voided.`, timer:2000, showConfirmButton:false });
                }
            });
        },
    };
}
</script>
@endpush
'@

# Write the files
$adminPath = "resources\views\layouts\admin.blade.php"
$dashboardPath = "resources\views\aais\dashboard.blade.php"

try {
    Set-Content -Path $adminPath -Value $adminLayoutContent -Encoding UTF8 -Force
    
    
    Set-Content -Path $dashboardPath -Value $dashboardContent -Encoding UTF8 -Force
    
    
    
    
    
    
    
    
    
    
    
    
} catch {
    
}

