@php
    $title     = 'Scan & Receive';
    $role      = 'Staff';
    $topbarSub = 'Scan QR codes or enter reference numbers to receive documents';

    $instructions = [
        ['number' => 1, 'title' => 'Ask for QR code', 'description' => 'Request the QR code or ask the client to read out their reference number.'],
        ['number' => 2, 'title' => 'Enter or scan', 'description' => 'Scan the QR code with any scanner, or manually type the reference above.'],
        ['number' => 3, 'title' => 'Confirm receipt', 'description' => 'Click "Confirm Receipt" to log the document as received and update status.'],
        ['number' => 4, 'title' => 'Review and process', 'description' => 'After acceptance, review the record, set the processing status, and add staff remarks.'],
        ['number' => 5, 'title' => 'Admin manual encode', 'description' => 'If needed, use "Encode New Document" to encode documents for walk-in clients directly from this portal.'],
        ['number' => 6, 'title' => 'Route the document', 'description' => 'Follow the routing guidance to know who handles the document next.'],
    ];

    $staffPerms = [
        ['icon' => '✓', 'text' => 'Scan & receive documents', 'ok' => true],
        ['icon' => '✓', 'text' => 'Encode documents for walk-in clients', 'ok' => true],
        ['icon' => '✓', 'text' => 'Update to In Process status', 'ok' => true],
        ['icon' => '✓', 'text' => 'Add processing remarks to records', 'ok' => true],
        ['icon' => '✗', 'text' => 'Edit final status', 'ok' => false],
        ['icon' => '✗', 'text' => 'Delete or void transactions', 'ok' => false],
        ['icon' => '✗', 'text' => 'Generate reports', 'ok' => false],
    ];
@endphp

@extends('layouts.admin')

@section('content')
<div x-data="portalApp()" x-cloak class="portal-root">

    <div class="portal-grid">
        {{-- LEFT: Scan Area --}}
        <div class="portal-main">
            <x-aais.portal.scan-panel />

            <div class="card card-body">
                <div class="portal-manual-head">
                    <div>
                        <p class="portal-manual-title">Admin Manual Encode</p>
                        <p class="portal-manual-copy">Encode a submission on behalf of a walk-in client, with optional immediate receipt confirmation.</p>
                    </div>
                    <button class="btn btn-outline btn-sm" @click="openEncodeModal()">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        Encode New Document
                    </button>
                </div>
            </div>

            <x-aais.portal.result-panel />

            <div class="card card-overflow-hidden">
                <x-aais.ui.card-header
                    title="Pending Kiosk Submissions"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><path d='M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z'/><path d='M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3'/></svg>"
                >
                    <x-slot:actions>
                        <span class="badge badge-gold" x-text="pendingDocs.length"></span>
                    </x-slot:actions>
                </x-aais.ui.card-header>

                <div class="scroll-x">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ref Code</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Submitted</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="pendingDocs.length === 0">
                                <tr>
                                    <td colspan="5" class="portal-table-empty">
                                        <div class="portal-empty-state">
                                            <svg class="portal-empty-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h6"/></svg>
                                            <p class="portal-empty-title">No pending kiosk submissions</p>
                                            <p class="portal-empty-copy">When clients submit from kiosk, unconfirmed entries will appear here for staff review.</p>
                                            <button type="button" class="btn btn-outline btn-sm" @click="scrollToScanPanel()">Scan first document to get started</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="item in pendingDocs" :key="item.ref">
                                <tr>
                                    <td><span class="ref-code" x-text="item.ref"></span></td>
                                    <td class="cell-strong" x-text="item.name"></td>
                                    <td x-text="item.type"></td>
                                    <td class="text-muted" x-text="item.submitted"></td>
                                    <td>
                                        <button class="btn btn-ghost btn-sm" @click="loadFromQueue(item.ref)">Review</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card card-overflow-hidden">
                <x-aais.ui.card-header
                    title="Recent Accepted Documents"
                    icon="<svg fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><polyline points='12 6 12 12 16 14'/></svg>"
                >
                    <x-slot:actions>
                        <span class="badge badge-green" x-text="recentDocs.length"></span>
                    </x-slot:actions>
                </x-aais.ui.card-header>

                <div class="scroll-x">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ref Code</th>
                                <th>Client</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="recentDocs.length === 0">
                                <tr>
                                    <td colspan="4" class="portal-table-empty">
                                        <div class="portal-empty-state portal-empty-state-compact">
                                            <svg class="portal-empty-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z"/><path d="M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                                            <p class="portal-empty-title">No accepted documents yet</p>
                                            <p class="portal-empty-copy">Accepted records will appear here once staff confirms receipt from the queue.</p>
                                            <button type="button" class="btn btn-outline btn-sm" @click="goToTransactions()">View all recent &rarr;</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <template x-for="item in recentDocs" :key="item.ref + item.acceptedAt">
                                <tr>
                                    <td><span class="ref-code" x-text="item.ref"></span></td>
                                    <td class="cell-strong" x-text="item.name"></td>
                                    <td class="text-muted" x-text="item.acceptedAt"></td>
                                    <td>
                                        <x-aais.ui.status-badge-dynamic
                                            class-expr="'status-' + item.status"
                                            text-expr="statusLabel(item.status)"
                                        />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- RIGHT: Instructions --}}
        <div class="portal-aside">
            <x-aais.portal.guide-card :instructions="$instructions" />
            <x-aais.portal.staff-permissions-card :staff-perms="$staffPerms" />
        </div>
    </div>

    <x-aais.ui.modal-shell
        open="encodeModalOpen"
        close-action="closeEncodeModal()"
        title="Manual Encode Document"
        size="lg"
        max-width="820px"
    >
        <div class="portal-modal-stack">
            <div class="info-box info-box-green portal-info-tight">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6M9 16h4M5 8h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V9a1 1 0 011-1z"/><path d="M9 8V5a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                <div>
                    <p class="info-box-title">Encode as Admin / Staff</p>
                    <p class="info-box-text">Use this when a client did not use the kiosk and staff needs to encode the document manually.</p>
                </div>
            </div>

            <div class="portal-form-grid-two">
                <div>
                    <label class="form-label">First Name <span class="required">*</span></label>
                    <input type="text" class="form-input" x-model.trim="encodeForm.firstName" placeholder="First name">
                </div>
                <div>
                    <label class="form-label">Last Name <span class="required">*</span></label>
                    <input type="text" class="form-input" x-model.trim="encodeForm.lastName" placeholder="Last name">
                </div>
            </div>

            <div class="portal-form-grid-two">
                <div>
                    <label class="form-label">Document Type <span class="required">*</span></label>
                    <select class="form-select form-input" x-model="encodeForm.docType">
                        <option value="">- Select document type -</option>
                        <template x-for="docType in encodeDocTypes" :key="docType">
                            <option :value="docType" x-text="docType"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="form-label">Purpose <span class="required">*</span></label>
                    <select class="form-select form-input" x-model="encodeForm.purpose">
                        <option value="">- Select purpose -</option>
                        <template x-for="purpose in encodePurposeOptions" :key="purpose">
                            <option :value="purpose" x-text="purpose"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="portal-form-grid-two">
                <div>
                    <label class="form-label">Office</label>
                    <select class="form-select form-input" x-model="encodeForm.office">
                        <template x-for="office in officeOptions" :key="office">
                            <option :value="office" x-text="office"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="form-label">Assigned Staff</label>
                    <input type="text" class="form-input" x-model.trim="encodeForm.staff" placeholder="Staff name">
                </div>
            </div>

            <div>
                <label class="form-label">Attachment URL (Optional)</label>
                <input type="text" class="form-input" x-model.trim="encodeForm.attachmentUrl" placeholder="/test.pdf">
                <p class="text-muted portal-form-hint">Use /test.pdf for demo review preview.</p>
            </div>

            <div>
                <label class="form-label">Initial Remarks</label>
                <textarea class="form-input" rows="3" x-model.trim="encodeForm.remarks" placeholder="Add optional initial review notes"></textarea>
            </div>

            <label class="portal-checkbox-row">
                <input type="checkbox" x-model="encodeForm.acceptNow">
                <span>Mark as received immediately after encoding</span>
            </label>
        </div>

        <x-slot:footer>
            <button class="btn btn-outline" @click="closeEncodeModal()" :disabled="encoding">Cancel</button>
            <button class="btn btn-primary" @click="submitManualEncode()" :disabled="encoding">
                <template x-if="!encoding"><span>Save Encoded Document</span></template>
                <template x-if="encoding"><span>Saving...</span></template>
            </button>
        </x-slot:footer>
    </x-aais.ui.modal-shell>
</div>
@endsection

@push('scripts')
<script>
function portalApp() {
    return {
        code: '',
        scanning: false,
        result: null,
        pendingDocs: [],
        recentDocs: [],
        encodeModalOpen: false,
        encoding: false,
        encodeDocTypes: [
            'Transcript of Records',
            'Certificate of Enrollment',
            'Certificate of Graduation',
            'Good Moral Certificate',
            'Diploma Authentication',
            'Honorable Dismissal',
            'Transfer Credentials',
            'CAV Document',
            'Other'
        ],
        encodePurposeOptions: [
            'Employment',
            'Scholarship',
            'Transfer',
            'Board Exam',
            'Graduate School',
            'Personal Record',
            'Abroad Application',
            'Other'
        ],
        officeOptions: ['Registrar Office', 'Admissions Office', 'Records Office', 'OSAS Office'],
        encodeForm: {
            firstName: '',
            lastName: '',
            docType: '',
            purpose: '',
            office: 'Registrar Office',
            staff: 'Admin Encoder',
            remarks: '',
            attachmentUrl: '/test.pdf',
            acceptNow: true,
        },
        attachmentModalOpen: false,
        attachmentModalUrl: '',
        attachmentPreviewUrl: '',
        attachmentObjectUrl: '',
        attachmentModalName: '',
        attachmentModalType: '',
        attachmentLoading: false,
        attachmentError: '',
        resultRefCopied: false,
        relativeTick: Date.now(),
        referencePattern: /^TL-\d{4}-\d{4}$/i,
        statusLabels: {
            logged: 'Logged',
            process: 'In Process',
            approved: 'Approved',
            pickup: 'For Pickup',
            complete: 'Completed',
            void: 'Voided',
        },

        init() {
            this.resetEncodeForm();
            this.refreshQueues();
            window.addEventListener('aais-demo-store-updated', () => this.refreshQueues());
            setInterval(() => {
                this.relativeTick = Date.now();
                if (this.result?.updatedAtRaw) {
                    this.result.lastUpdatedLabel = this.relativeTimeLabel(this.result.updatedAtRaw);
                }
            }, 60000);
        },

        statusLabel(status) {
            return this.statusLabels[status] || 'Unknown';
        },

        relativeTimeLabel(isoString) {
            const parsed = new Date(isoString);
            if (Number.isNaN(parsed.getTime())) {
                return 'just now';
            }

            const diffMs = Math.max(0, this.relativeTick - parsed.getTime());
            const minutes = Math.floor(diffMs / 60000);
            if (minutes <= 0) return 'just now';
            if (minutes === 1) return '1 minute ago';
            if (minutes < 60) return `${minutes} minutes ago`;

            const hours = Math.floor(minutes / 60);
            return hours === 1 ? '1 hour ago' : `${hours} hours ago`;
        },

        clientInitials(name) {
            const normalized = String(name || '').trim();
            if (!normalized) {
                return 'CL';
            }

            const parts = normalized.split(/\s+/).filter(Boolean);
            const first = (parts[0] || 'C').charAt(0);
            const second = (parts[1] || '').charAt(0);
            return `${first}${second}`.toUpperCase();
        },

        awaitingCountdownText() {
            if (!this.result || this.result.accepted) {
                return 'This will auto-expire in 30 min if not confirmed.';
            }

            const baseline = new Date(this.result.submittedAtRaw || this.result.updatedAtRaw || '');
            if (Number.isNaN(baseline.getTime())) {
                return 'This will auto-expire in 30 min if not confirmed.';
            }

            const expiresAt = baseline.getTime() + (30 * 60000);
            const remainingMs = expiresAt - this.relativeTick;

            if (remainingMs <= 0) {
                return 'Auto-expired. Please rescan and confirm to continue.';
            }

            const remainingMinutes = Math.ceil(remainingMs / 60000);
            return `This will auto-expire in ${remainingMinutes} min if not confirmed.`;
        },

        async copyResultRef() {
            const reference = String(this.result?.ref || '').trim();
            if (!reference) {
                return;
            }

            try {
                if (navigator?.clipboard?.writeText) {
                    await navigator.clipboard.writeText(reference);
                } else {
                    const temp = document.createElement('textarea');
                    temp.value = reference;
                    temp.setAttribute('readonly', '');
                    temp.style.position = 'absolute';
                    temp.style.left = '-9999px';
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }

                this.resultRefCopied = true;
                setTimeout(() => {
                    this.resultRefCopied = false;
                }, 1500);
            } catch (_) {
                Swal.fire({
                    icon: 'error',
                    title: 'Copy Failed',
                    text: 'Unable to copy this reference on your current browser.',
                    confirmButtonText: 'OK'
                });
            }
        },

        scrollToScanPanel() {
            const input = document.querySelector('.portal-scan-field input');
            if (!input) {
                return;
            }

            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
            input.focus();
        },

        goToTransactions() {
            window.location.href = @json(route('aais.admin.transactions'));
        },

        resetEncodeForm() {
            this.encodeForm = {
                firstName: '',
                lastName: '',
                docType: '',
                purpose: '',
                office: 'Registrar Office',
                staff: 'Admin Encoder',
                remarks: '',
                attachmentUrl: '/test.pdf',
                acceptNow: true,
            };
        },

        openEncodeModal() {
            this.encodeModalOpen = true;
        },

        closeEncodeModal() {
            this.encodeModalOpen = false;
            this.encoding = false;
            this.resetEncodeForm();
        },

        resolveAttachmentMeta(url) {
            const normalizedUrl = String(url || '').trim();
            if (!normalizedUrl) {
                return { name: '', type: '', url: '' };
            }

            const fileName = normalizedUrl.split('/').pop() || 'attachment';
            const lowerName = fileName.toLowerCase();
            let fileType = '';

            if (lowerName.endsWith('.pdf')) {
                fileType = 'application/pdf';
            } else if (lowerName.endsWith('.jpg') || lowerName.endsWith('.jpeg')) {
                fileType = 'image/jpeg';
            } else if (lowerName.endsWith('.png')) {
                fileType = 'image/png';
            } else if (lowerName.endsWith('.webp')) {
                fileType = 'image/webp';
            } else if (lowerName.endsWith('.gif')) {
                fileType = 'image/gif';
            }

            return {
                name: fileName,
                type: fileType,
                url: normalizedUrl,
            };
        },

        submitManualEncode() {
            if (this.encoding) {
                return;
            }

            const firstName = String(this.encodeForm.firstName || '').trim();
            const lastName = String(this.encodeForm.lastName || '').trim();
            const docType = String(this.encodeForm.docType || '').trim();
            const purpose = String(this.encodeForm.purpose || '').trim();

            if (!firstName || !lastName || !docType || !purpose) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Required Fields',
                    text: 'Please complete client name, document type, and purpose.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            const store = window.AAISDemoStore;
            if (!store) {
                Swal.fire({
                    icon: 'error',
                    title: 'Store Unavailable',
                    text: 'Unable to access demo data store. Reload the page and try again.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            this.encoding = true;

            const fullName = `${firstName} ${lastName}`.trim();
            const office = String(this.encodeForm.office || 'Registrar Office').trim() || 'Registrar Office';
            const staffName = String(this.encodeForm.staff || '').trim() || 'Admin Encoder';
            const initialRemarks = String(this.encodeForm.remarks || '').trim();
            const attachmentMeta = this.resolveAttachmentMeta(this.encodeForm.attachmentUrl);

            const created = store.createSubmission({
                name: fullName,
                type: docType,
                purpose,
                office,
                attachment: {
                    name: attachmentMeta.name,
                    type: attachmentMeta.type,
                    sizeMb: '',
                    url: attachmentMeta.url,
                },
            });

            if (!created) {
                this.encoding = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to Encode',
                    text: 'Failed to save the encoded record. Please try again.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            let finalRecord = created;

            if (this.encodeForm.acceptNow) {
                const accepted = store.acceptDocument(created.ref, {
                    staff: staffName,
                    office,
                    next: 'Processing Desk',
                });

                if (accepted) {
                    finalRecord = accepted;
                }
            }

            if (initialRemarks) {
                const reviewed = store.updateReview(finalRecord.ref, {
                    status: finalRecord.status || 'process',
                    remarks: initialRemarks,
                    staff: staffName,
                });

                if (reviewed) {
                    finalRecord = reviewed;
                }
            }

            this.result = this.mapRecord(finalRecord);
            this.resultRefCopied = false;
            this.code = finalRecord.ref;
            this.refreshQueues();

            const savedRef = finalRecord.ref;
            const isAccepted = !!finalRecord.accepted;

            this.closeEncodeModal();

            Swal.fire({
                icon: 'success',
                title: 'Document Encoded',
                text: isAccepted
                    ? `Reference ${savedRef} was encoded and received.`
                    : `Reference ${savedRef} was encoded and is waiting for receipt confirmation.`,
                timer: 2400,
                showConfirmButton: false,
            });
        },

        async openAttachmentModal() {
            if (!this.result || !this.result.attachmentUrl) {
                return;
            }

            this.attachmentModalUrl = this.result.attachmentUrl;
            this.attachmentPreviewUrl = this.result.attachmentUrl;
            this.attachmentModalName = this.result.attachmentName || 'Submitted Attachment';
            this.attachmentModalType = String(this.result.attachmentType || '').toLowerCase();
            this.attachmentLoading = true;
            this.attachmentError = '';
            this.attachmentModalOpen = true;

            await this.prepareAttachmentPreview();
        },

        clearAttachmentObjectUrl() {
            if (this.attachmentObjectUrl) {
                URL.revokeObjectURL(this.attachmentObjectUrl);
                this.attachmentObjectUrl = '';
            }
        },

        async prepareAttachmentPreview() {
            const sourceUrl = String(this.attachmentModalUrl || '');

            this.clearAttachmentObjectUrl();

            if (!sourceUrl) {
                this.attachmentPreviewUrl = '';
                this.attachmentLoading = false;
                return;
            }

            if (sourceUrl.startsWith('data:') || sourceUrl.startsWith('blob:')) {
                this.attachmentPreviewUrl = sourceUrl;
                this.attachmentLoading = false;
                return;
            }

            try {
                const response = await fetch(sourceUrl, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error(`Preview request failed: ${response.status}`);
                }

                const blob = await response.blob();
                if (blob.type) {
                    this.attachmentModalType = String(blob.type).toLowerCase();
                }

                this.attachmentObjectUrl = URL.createObjectURL(blob);
                this.attachmentPreviewUrl = this.attachmentObjectUrl;
            } catch (error) {
                this.attachmentPreviewUrl = sourceUrl;
                this.attachmentError = 'Inline preview is limited for this file. Use Open in New Tab if needed.';
            } finally {
                this.attachmentLoading = false;
            }
        },

        closeAttachmentModal() {
            this.attachmentModalOpen = false;
            this.clearAttachmentObjectUrl();
            this.attachmentModalUrl = '';
            this.attachmentPreviewUrl = '';
            this.attachmentModalName = '';
            this.attachmentModalType = '';
            this.attachmentLoading = false;
            this.attachmentError = '';
        },

        openAttachmentInNewTab() {
            const targetUrl = this.attachmentModalUrl || this.attachmentPreviewUrl;
            if (!targetUrl) {
                return;
            }

            window.open(targetUrl, '_blank', 'noopener');
        },

        isPdfAttachment() {
            const url = String(this.attachmentModalUrl || '').toLowerCase();
            const type = String(this.attachmentModalType || '').toLowerCase();
            return type.includes('pdf') || url.endsWith('.pdf') || url.startsWith('data:application/pdf');
        },

        isImageAttachment() {
            const url = String(this.attachmentModalUrl || '').toLowerCase();
            const type = String(this.attachmentModalType || '').toLowerCase();
            return type.startsWith('image/')
                || url.endsWith('.jpg')
                || url.endsWith('.jpeg')
                || url.endsWith('.png')
                || url.endsWith('.webp')
                || url.endsWith('.gif')
                || url.startsWith('data:image/');
        },

        mapRecord(record) {
            const store = window.AAISDemoStore;
            const attachmentName = String(record.attachmentName || '').trim();
            const sampleAttachmentUrl = (!record.attachmentUrl && (!attachmentName || attachmentName.toLowerCase() === 'test.pdf')) ? '/test.pdf' : '';

            return {
                ref: record.ref,
                name: record.name,
                type: record.type,
                purpose: record.purpose || 'General Request',
                office: record.office || 'Registrar Office',
                staff: record.staff || 'Receiving Clerk',
                next: record.next || 'Processing Desk',
                status: record.status || 'logged',
                accepted: !!record.accepted,
                remarks: record.remarks || '',
                attachmentName: attachmentName || (sampleAttachmentUrl ? 'test.pdf (Demo Sample)' : ''),
                attachmentType: record.attachmentType || (sampleAttachmentUrl ? 'application/pdf' : ''),
                attachmentSizeMb: record.attachmentSizeMb || '',
                attachmentUrl: record.attachmentUrl || sampleAttachmentUrl,
                submittedAtRaw: record.submittedAt || '',
                updatedAtRaw: record.updatedAt || record.receivedAt || record.submittedAt || '',
                submittedAt: store ? store.formatDateTime(record.submittedAt) : '-',
                receivedAt: record.receivedAt && store ? store.formatDateTime(record.receivedAt) : null,
                lastUpdatedLabel: this.relativeTimeLabel(record.updatedAt || record.receivedAt || record.submittedAt),
            };
        },

        refreshQueues() {
            const store = window.AAISDemoStore;
            if (!store) {
                this.pendingDocs = [];
                this.recentDocs = [];
                return;
            }

            this.pendingDocs = store.listPending().map((record) => ({
                ref: record.ref,
                name: record.name,
                type: record.type,
                submitted: store.formatDateTime(record.submittedAt),
            }));

            this.recentDocs = store.getRecentAccepted(8).map((record) => ({
                ref: record.ref,
                name: record.name,
                status: record.status,
                acceptedAt: store.formatDateTime(record.acceptedAt || record.receivedAt || record.updatedAt),
            }));
        },

        loadFromQueue(reference) {
            this.code = reference;
            this.scan();
        },

        scan() {
            const normalized = this.code.trim().toUpperCase();
            if (!normalized) return;

            this.code = normalized;
            if (!this.referencePattern.test(normalized)) {
                this.result = null;
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Reference Format',
                    text: 'Use TL-YYYY-#### (example: TL-2026-0412).',
                    confirmButtonText: 'OK'
                });
                return;
            }

            this.scanning = true;
            setTimeout(() => {
                this.scanning = false;
                this.closeAttachmentModal();

                const store = window.AAISDemoStore;
                const record = store ? store.findByReference(normalized) : null;

                if (!record) {
                    this.result = null;
                    Swal.fire({
                        icon: 'warning',
                        title: 'Reference Not Found',
                        text: 'No kiosk submission was found for this reference code.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                this.result = this.mapRecord(record);
                this.resultRefCopied = false;
            }, 450);
        },

        confirmReceive() {
            if (!this.result || this.result.accepted) {
                return;
            }

            Swal.fire({
                title: 'Confirm Document Receipt',
                text: `Confirm receipt of ${this.result.ref} from ${this.result.name}. Record will move into active processing.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Confirm Receipt',
            }).then((response) => {
                if (!response.isConfirmed) {
                    return;
                }

                const store = window.AAISDemoStore;
                const accepted = store
                    ? store.acceptDocument(this.result.ref, {
                        staff: this.result.staff || 'Receiving Clerk',
                        office: this.result.office || 'Registrar Office',
                        next: this.result.next || 'Processing Desk',
                    })
                    : null;

                if (!accepted) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Unable to Confirm Receipt',
                        text: 'Please scan again and retry.',
                        confirmButtonText: 'OK'
                    });
                    return;
                }

                this.result = this.mapRecord(accepted);
                this.resultRefCopied = false;
                this.refreshQueues();

                Swal.fire({
                    icon: 'success',
                    title: 'Received',
                    text: 'Document is now in active office processing.',
                    timer: 2200,
                    showConfirmButton: false
                });
            });
        },

        saveReview() {
            if (!this.result || !this.result.accepted) {
                return;
            }

            const store = window.AAISDemoStore;
            const updated = store
                ? store.updateReview(this.result.ref, {
                    status: this.result.status,
                    remarks: this.result.remarks,
                    staff: this.result.staff,
                    next: this.result.next,
                })
                : null;

            if (!updated) {
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to Save Review',
                    text: 'Please try again.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            this.result = this.mapRecord(updated);
                this.resultRefCopied = false;
            this.refreshQueues();

            Swal.fire({
                icon: 'success',
                title: 'Review Saved',
                text: 'Status and remarks were updated for this document.',
                timer: 1800,
                showConfirmButton: false
            });
        },

        reset() {
            this.code = '';
            this.result = null;
            this.resultRefCopied = false;
            this.closeAttachmentModal();
        },
    };
}
</script>
@endpush
