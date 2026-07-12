@props([
    'status' => 'process',
    'statusLabel' => 'In Process',
    'statusMessage' => 'Your document is being processed by the Registrar Office. Please wait for an email notification.',
    'etaLabel' => 'Estimated completion',
    'etaValue' => '2-3 Business Days',
    'routeLabel' => 'Being Processed At',
    'routeValue' => 'Registrar Office',
    'routeMeta' => 'Window 3 · Ground Floor',
    'notificationTitle' => 'Email Notifications Active',
    'notificationText' => 'We will email you automatically when your document is ready. No need to check manually.',
    'kioskUrl' => null,
    'submitLabel' => 'Submit Another Document',
])

@php
    $kioskHref = $kioskUrl ?? route('aais.client.kiosk');
@endphp

<div style="display:flex;flex-direction:column;gap:22px;position:sticky;top:calc(var(--topbar-h) + 22px);">
    <div class="card" style="overflow:hidden;">
        <x-aais.ui.card-header title="Current Status" />
        <div class="card-body" style="text-align:center;padding:32px;">
            <div style="width:76px;height:76px;border-radius:50%;background:var(--color-au-100);border:3px solid var(--color-au-300);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:34px;height:34px;color:var(--color-au-700);"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <x-aais.ui.status-badge :status="$status" :label="$statusLabel" size="lg" />
            <p style="font-size:13px;color:var(--color-muted);margin-top:16px;line-height:1.75;">{{ $statusMessage }}</p>
            <div class="info-box info-box-green" style="margin-top:18px;justify-content:center;flex-direction:column;text-align:center;">
                <p class="text-muted" style="font-size:12px;">{{ $etaLabel }}</p>
                <p style="font-size:20px;font-weight:800;color:var(--color-g-900);margin-top:4px;">{{ $etaValue }}</p>
            </div>
        </div>
    </div>

    <div class="card card-body">
        <h3 class="section-title" style="font-size:14px;margin-bottom:16px;">Routing Guide</h3>
        <x-aais.ui.routing-card
            :label="$routeLabel"
            :value="$routeValue"
            :meta="$routeMeta"
            value-style="font-size:16px;"
        />
    </div>

    <div class="info-box info-box-gold">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="color:var(--color-au-700);"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        <div>
            <p class="info-box-title" style="color:var(--color-au-800);">{{ $notificationTitle }}</p>
            <p class="info-box-text">{{ $notificationText }}</p>
        </div>
    </div>

    <a href="{{ $kioskHref }}" class="btn btn-outline btn-lg" style="justify-content:center;">{{ $submitLabel }}</a>
</div>
