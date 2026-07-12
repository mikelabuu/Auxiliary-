@props([
    'heroStats' => [],
    'institution' => 'Central Luzon State University',
    'schoolYear' => 'AY 2025–2026',
    'description' => 'A paperless document tracking platform for CLSU offices. Encode, submit, scan, and track every transaction in real-time — from submission to release.',
    'kioskUrl' => null,
    'trackerUrl' => null,
])

@php
    $kioskHref = $kioskUrl ?? route('aais.client.kiosk');
    $trackerHref = $trackerUrl ?? route('aais.client.tracker');
@endphp

<section class="hero-section">
    <div class="hero-content">
        <div class="hero-meta-row">
            <span class="chip chip-gold">{{ $institution }}</span>
            <span class="chip hero-chip-muted">{{ $schoolYear }}</span>
        </div>

        <h1 class="hero-title">
            Academic Affairs<br>
            <span class="hero-highlight">Information System</span>
        </h1>

        <p class="hero-desc">{{ $description }}</p>

        <div class="hero-actions">
            <a href="{{ $kioskHref }}" class="btn btn-gold btn-lg">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-4M13 4l4 4L9 16H5v-4L13 4z"/></svg>
                Encode Document
            </a>
            <a href="{{ $trackerHref }}" class="btn btn-lg hero-btn-secondary">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                Track Document
            </a>
        </div>
    </div>

    <div class="hero-stats">
        @foreach ($heroStats as $stat)
            <x-aais.home.hero-stat :icon="$stat['icon']" :value="$stat['value']" :label="$stat['label']" />
        @endforeach
    </div>
</section>
