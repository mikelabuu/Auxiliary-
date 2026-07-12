@php
    $title     = 'Welcome to AAIS';
    $topbarSub = 'Academic Affairs Information System — Central Luzon State University';

    $heroStats = [
        ['value' => '1,284', 'label' => 'Docs Processed', 'icon' => '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z"/><path d="M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>'],
        ['value' => '48', 'label' => 'Today', 'icon' => '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>'],
        ['value' => '3', 'label' => 'Offices Active', 'icon' => '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="M3 21V8l9-5 9 5v13M9 21v-6h6v6"/></svg>'],
        ['value' => '99.2%', 'label' => 'SLA Met', 'icon' => '<svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'],
    ];

    $workflow = [
        ['number' => 1, 'title' => 'Client Encodes', 'description' => 'Student/client fills in document type, purpose, and attaches PDF/JPEG (max 5 MB) at the kiosk or online.', 'bg' => 'var(--color-g-50)', 'borderColor' => 'var(--color-g-200)', 'titleColor' => 'var(--color-g-900)'],
        ['number' => 2, 'title' => 'QR Code Generated', 'description' => 'System generates a unique QR reference code with routing guidance showing which office to approach first.', 'bg' => 'var(--color-au-50)', 'borderColor' => '#ecd57a', 'titleColor' => 'var(--color-au-800)'],
        ['number' => 3, 'title' => 'Office Scans & Receives', 'description' => 'Staff scans the QR code to instantly log receipt and update document status — no manual data entry.', 'bg' => 'var(--color-g-50)', 'borderColor' => 'var(--color-g-200)', 'titleColor' => 'var(--color-g-900)'],
        ['number' => 4, 'title' => 'Client Tracks & Gets Notified', 'description' => 'Automated email alerts notify clients when documents are ready for pickup. Status is live on the tracker.', 'bg' => 'var(--color-au-50)', 'borderColor' => '#ecd57a', 'titleColor' => 'var(--color-au-800)'],
    ];

    $latestActivity = [
        ['time' => '10:42 AM', 'text' => 'TL-2026-0412 received by Registrar Office', 'status' => 'process'],
        ['time' => '10:28 AM', 'text' => 'TL-2026-0411 marked ready for pickup', 'status' => 'pickup'],
        ['time' => '09:55 AM', 'text' => 'TL-2026-0410 approved by OSAS', 'status' => 'approved'],
        ['time' => '09:30 AM', 'text' => 'TL-2026-0413 encoded via self-service kiosk', 'status' => 'logged'],
    ];

    $offices = [
        ['name' => 'Registrar Office', 'location' => 'Ground Floor, Admin Bldg', 'hours' => '8:00 AM – 5:00 PM', 'open' => true],
        ['name' => 'OSAS', 'location' => '2nd Floor, Student Center', 'hours' => '8:00 AM – 5:00 PM', 'open' => true],
        ['name' => 'Admissions Office', 'location' => 'Ground Floor, Admin Bldg', 'hours' => '8:00 AM – 5:00 PM', 'open' => true],
        ['name' => 'Records Section', 'location' => 'Ground Floor, Admin Bldg', 'hours' => '8:00 AM – 12:00 PM', 'open' => false],
    ];
@endphp

@extends('layouts.client')

@section('content')
    <x-aais.home.hero-section :hero-stats="$heroStats" />

    <x-aais.home.workflow-section :workflow="$workflow" />

    <x-aais.home.system-status-section :latest-activity="$latestActivity" :offices="$offices" />
@endsection
