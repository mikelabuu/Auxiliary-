@props([
    'qrPattern' => [],
    'generatedAt' => null,
    'trackerUrl' => null,
    'kioskUrl' => null,
])

@php
    $generated = $generatedAt ?? now()->format('M d, Y · g:i A');
    $trackerHref = $trackerUrl ?? route('aais.client.tracker');
    $kioskHref = $kioskUrl ?? route('aais.client.kiosk');
@endphp

<div x-show="step === 3" x-transition>
    <div class="kiosk-result-grid">
        <div class="qr-container">
            <div class="kiosk-qr-head">
                <span class="chip chip-gold kiosk-qr-chip">Document Submitted</span>
                <p class="kiosk-qr-copy">Save or screenshot this QR code. Staff must scan this before your document appears on the office dashboard.</p>
            </div>

            <div class="qr-mock">
                <div class="kiosk-qr-grid">
                    @foreach ($qrPattern as $row)
                        @foreach ($row as $cell)
                            <div class="kiosk-qr-cell" style="background:{{ $cell ? 'var(--color-g-900)' : 'transparent' }};"></div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <p class="ref-display" x-text="refCode"></p>
            <p class="kiosk-generated-at">Generated: {{ $generated }}</p>

            <div class="kiosk-qr-actions">
                <button onclick="window.print()" class="btn btn-hover-scale kiosk-print-btn">Print QR</button>
                <a href="{{ $trackerHref }}" class="btn btn-gold btn-hover-scale">Track This</a>
            </div>
        </div>

        <div class="kiosk-side-stack">
            <div class="card card-overflow-hidden">
                <x-aais.ui.card-header title="Submission Summary">
                    <x-slot:actions>
                        <x-aais.ui.status-badge status="logged" label="Logged" />
                    </x-slot:actions>
                </x-aais.ui.card-header>
                <div class="card-body">
                    <x-aais.ui.key-value-row label="Document Type" value-x-text="form.docType || 'Transcript of Records'" />
                    <x-aais.ui.key-value-row label="Purpose" value-x-text="form.purpose || 'Employment'" />
                    <x-aais.ui.key-value-row label="Submitted At" :value="$generated" :border="false" />
                </div>
            </div>

            <div class="card card-body">
                <h3 class="section-title kiosk-side-title">Where to Go Next</h3>
                <x-aais.ui.routing-card
                    label="Report to this office"
                    value="Registrar's Office"
                    meta="Window 3 &middot; Ground Floor, Admin Building"
                    value-style="font-size:16px;"
                />
                <div class="info-box info-box-green kiosk-qr-note"><p class="info-box-text"><strong class="kiosk-qr-note-strong">Show this QR code</strong> to the staff at the window. They will scan it to confirm receipt.</p></div>
            </div>

            <div class="info-box info-box-gold">
                <svg class="kiosk-alert-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <div><p class="info-box-title kiosk-alert-title">Pending Staff Acceptance</p><p class="info-box-text">Your record is queued from kiosk. It becomes active in the office workflow only after staff confirms receipt.</p></div>
            </div>

            <a href="{{ $kioskHref }}" class="btn btn-outline btn-center">Submit Another Document</a>
        </div>
    </div>
</div>
