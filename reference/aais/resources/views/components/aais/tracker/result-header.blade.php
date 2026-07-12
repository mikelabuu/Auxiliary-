@props([
    'referenceXText' => "code || 'TL-2026-0412'",
    'documentLine' => 'Transcript of Records · For Employment',
    'documentLineXText' => null,
    'status' => 'process',
    'statusLabel' => 'In Process',
    'statusClassExpr' => null,
    'statusTextExpr' => null,
    'submittedDate' => 'Apr 1, 2026',
    'submittedDateXText' => null,
    'office' => 'Registrar',
    'officeXText' => null,
    'staff' => 'Ms. V. Santos',
    'staffXText' => null,
])

<div class="card card-overflow-hidden">
    <div class="tracker-header-hero">
        <div class="tracker-header-orb"></div>
        <div class="tracker-header-content">
            <div>
                <p class="kv-label tracker-header-label">Reference Code</p>
                <div class="tracker-ref-row">
                    <p class="font-mono tracker-ref-code" x-text="{{ $referenceXText }}"></p>
                    <button type="button" class="btn btn-ghost btn-sm btn-icon tracker-copy-btn" @click="typeof copyReference === 'function' && copyReference()" aria-label="Copy reference code">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    </button>
                </div>
                <p class="tracker-copy-state" x-show="typeof copyFeedback !== 'undefined' && copyFeedback" x-transition.opacity>Copied!</p>
                <p class="tracker-document-line" @if($documentLineXText) x-text="{{ $documentLineXText }}" @endif>{{ $documentLine }}</p>
            </div>
            @if($statusClassExpr || $statusTextExpr)
                <x-aais.ui.status-badge-dynamic
                    :class-expr="$statusClassExpr ?: "'status-" . $status . "'"
                    :text-expr="$statusTextExpr ?: "'" . $statusLabel . "'"
                />
            @else
                <x-aais.ui.status-badge :status="$status" :label="$statusLabel" />
            @endif
        </div>
    </div>
    <div class="tracker-summary-grid">
        <div class="tracker-summary-cell"><p class="kv-label">Submitted</p><p class="kv-value" @if($submittedDateXText) x-text="{{ $submittedDateXText }}" @endif>{{ $submittedDate }}</p></div>
        <div class="tracker-summary-cell"><p class="kv-label">Office</p><p class="kv-value" @if($officeXText) x-text="{{ $officeXText }}" @endif>{{ $office }}</p></div>
        <div class="tracker-summary-cell"><p class="kv-label">Assigned Staff</p><p class="kv-value" @if($staffXText) x-text="{{ $staffXText }}" @endif>{{ $staff }}</p></div>
    </div>
</div>
