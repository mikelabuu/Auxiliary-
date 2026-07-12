@props([
    'title' => 'Document Found',
    'statusClassExpr' => "'status-' + (result?.status || '')",
    'statusTextExpr' => 'statusLabel(result?.status)',
])

<div class="card card-overflow-hidden portal-result-card" x-show="result" x-transition :class="{ 'portal-result-logged': result?.status === 'logged' }">
    <x-aais.ui.card-header
        :title="$title"
        icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z'/><path d='M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3'/></svg>"
    >
        <x-slot:actions>
            <x-aais.ui.status-badge-dynamic
                :class-expr="$statusClassExpr"
                :text-expr="$statusTextExpr"
            />
        </x-slot:actions>
    </x-aais.ui.card-header>

    <div class="card-body" x-show="result">
        <div class="portal-result-grid">
            <div class="portal-ref-wrap">
                <p class="kv-label">Reference Code</p>
                <div class="portal-ref-row">
                    <p class="kv-value font-mono" x-text="result?.ref"></p>
                    <button type="button" class="portal-ref-copy" @click="copyResultRef()" aria-label="Copy reference code">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        <span x-text="resultRefCopied ? 'Copied' : 'Copy'"></span>
                    </button>
                </div>
            </div>
            <div>
                <p class="kv-label">Client Name</p>
                <div class="portal-client-row">
                    <span class="portal-client-avatar" x-text="clientInitials(result?.name)"></span>
                    <p class="kv-value portal-client-name" x-text="result?.name"></p>
                </div>
            </div>
            <div><p class="kv-label">Document Type</p><p class="kv-value" x-text="result?.type"></p></div>
            <div><p class="kv-label">Purpose</p><p class="kv-value" x-text="result?.purpose"></p></div>
            <div><p class="kv-label">Submitted</p><p class="kv-value" x-text="result?.submittedAt"></p></div>
            <div><p class="kv-label">Accepted</p><p class="kv-value" x-text="result?.receivedAt || 'Awaiting staff acceptance'"></p></div>
            <div><p class="kv-label">Last Updated</p><p class="kv-value" x-text="result?.lastUpdatedLabel || 'just now'"></p></div>
        </div>

        <x-aais.ui.routing-card
            label="Route to Next"
            value=""
            value-x-text="result?.next"
            meta-x-text="'Assigned: ' + (result?.staff || 'Receiving Clerk')"
            class="portal-route-card"
        />

        <div class="portal-attachment-card">
            <p class="kv-label portal-label-tight">Submitted Attachment</p>
            <p class="kv-value" x-text="result?.attachmentName || 'No attachment uploaded'"></p>
            <p class="text-muted portal-attachment-meta" x-show="result?.attachmentSizeMb" x-text="(result.attachmentSizeMb || '') + ' MB'"></p>

            <div class="portal-attachment-actions" x-show="result?.attachmentUrl">
                <button class="btn btn-outline btn-sm btn-center" @click="openAttachmentModal()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    Review Attachment
                </button>
            </div>

            <p class="text-faint portal-attachment-faint" x-show="result?.attachmentName && !result?.attachmentUrl">
                Preview link is unavailable for this file in demo mode.
            </p>
        </div>

        <x-aais.ui.modal-shell
            open="attachmentModalOpen"
            close-action="closeAttachmentModal()"
            title="Attachment Review"
            size="lg"
            max-width="980px"
        >
            <div class="portal-preview-stack">
                <div class="portal-preview-head">
                    <div>
                        <p class="kv-label">Document</p>
                        <p class="kv-value" x-text="attachmentModalName || 'Attachment'"></p>
                    </div>

                    <button class="btn btn-outline btn-sm" @click="openAttachmentInNewTab()" x-show="attachmentModalUrl">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17L17 7"/><polyline points="7 7 17 7 17 17"/><path d="M5 5h6M5 5v6"/></svg>
                        Open in New Tab
                    </button>
                </div>

                <div class="portal-preview-shell">
                    <div x-show="attachmentLoading" class="portal-preview-loading">
                        <div>
                            <p class="kv-value portal-preview-title">Loading document preview...</p>
                            <p class="text-muted portal-preview-text">Please wait while the attachment is prepared.</p>
                        </div>
                    </div>

                    <iframe
                        x-show="attachmentModalOpen && !attachmentLoading && isPdfAttachment()"
                        :src="attachmentPreviewUrl"
                        title="Document preview"
                        class="portal-preview-iframe"
                    ></iframe>

                    <div x-show="attachmentModalOpen && !attachmentLoading && isImageAttachment()" class="portal-preview-image-wrap">
                        <img :src="attachmentPreviewUrl" alt="Attachment preview" class="portal-preview-image">
                    </div>

                    <div x-show="attachmentModalOpen && !attachmentLoading && !isPdfAttachment() && !isImageAttachment()" class="portal-preview-empty">
                        <div>
                            <p class="kv-value portal-preview-title">Preview is not available for this file type.</p>
                            <p class="text-muted portal-preview-text">Use Open in New Tab to review the full document.</p>
                            <p class="text-muted portal-preview-note" x-show="attachmentError" x-text="attachmentError"></p>
                        </div>
                    </div>
                </div>
            </div>

            <x-slot:footer>
                <button class="btn btn-outline" @click="closeAttachmentModal()">Close</button>
            </x-slot:footer>
        </x-aais.ui.modal-shell>

        <div class="info-box info-box-gold portal-awaiting-box" x-show="result && !result?.accepted">
            <svg class="portal-awaiting-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
            <div>
                <p class="info-box-title portal-awaiting-title">Waiting for Receipt Confirmation</p>
                <p class="info-box-text">This kiosk submission is not yet included in admin monitoring until staff confirms receipt.</p>
                <p class="portal-awaiting-countdown">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="9"/></svg>
                    <span x-text="awaitingCountdownText()"></span>
                </p>
            </div>
        </div>

        <div x-show="result?.accepted" class="portal-review-wrap">
            <div class="portal-review-grid">
                <div>
                    <p class="kv-label portal-label-tight">Processing Status</p>
                    <select class="form-input" x-model="result.status">
                        <option value="process">In Process</option>
                        <option value="approved">Approved</option>
                        <option value="pickup">For Pickup</option>
                        <option value="complete">Completed</option>
                        <option value="void">Voided</option>
                    </select>
                </div>
                <div>
                    <p class="kv-label portal-label-tight">Assigned Staff</p>
                    <input type="text" class="form-input" x-model="result.staff" placeholder="Staff name">
                </div>
            </div>

            <div>
                <p class="kv-label portal-label-tight">Staff Remarks</p>
                <textarea class="form-input" x-model="result.remarks" rows="3" placeholder="Add processing notes or verification remarks"></textarea>
            </div>
        </div>

        <div class="portal-action-grid">
            <button class="btn btn-primary btn-center" @click="confirmReceive()" x-show="result && !result?.accepted">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Confirm Receipt
            </button>

            <button class="btn btn-primary btn-center" @click="saveReview()" x-show="result?.accepted">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg> Save Review
            </button>

            <button class="btn btn-outline btn-center portal-scan-another" @click="reset()">Scan Another</button>
        </div>
    </div>
</div>
