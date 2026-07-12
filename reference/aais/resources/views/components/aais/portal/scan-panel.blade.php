@props([
    'title' => 'Scan or Enter Reference Code',
    'placeholder' => 'TL-2026-XXXX or scan QR...',
    'helpText' => 'Point a barcode scanner at the QR code, or type the reference number above. The document enters the office workflow only after receipt is confirmed.',
])

<div class="card card-overflow-hidden">
    <x-aais.ui.card-header
        :title="$title"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2'/><rect x='7' y='7' width='10' height='10' rx='1'/></svg>"
    />

    <div class="card-body portal-scan-body">
        <div class="scan-field portal-scan-field">
            <svg class="scan-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg>
            <input type="text" x-model="code" placeholder="{{ $placeholder }}" @keydown.enter="scan()" autofocus>
        </div>

        <div class="portal-scan-actions">
            <button class="btn btn-primary btn-lg btn-fill btn-center" @click="scan()" :disabled="scanning || !code.trim()">
                <template x-if="!scanning"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 7V5a2 2 0 012-2h2M17 3h2a2 2 0 012 2v2M21 17v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"/><rect x="7" y="7" width="10" height="10" rx="1"/></svg></template>
                <template x-if="scanning"><svg class="spinner-inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-dasharray="56" stroke-dashoffset="28"/></svg></template>
                <span x-text="scanning ? 'Looking up...' : 'Look Up Document'"></span>
            </button>
            <button class="btn btn-outline" @click="reset()">Clear</button>
        </div>

        <p class="portal-scan-help">
            {{ $helpText }}
        </p>
    </div>
</div>
