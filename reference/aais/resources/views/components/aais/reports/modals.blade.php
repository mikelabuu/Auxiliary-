<x-aais.ui.modal-shell
    open="viewModalOpen"
    close-action="closeViewModal()"
    title="Transaction Details"
    size="lg"
    body-show="activeDoc"
>
    <div class="record-detail-panel">
        <x-aais.ui.record-detail-row label="Reference">
            <span class="ref-code ref-code-inline" x-text="activeDoc?.ref"></span>
        </x-aais.ui.record-detail-row>
        <x-aais.ui.record-detail-row label="Client Name" value-x-text="activeDoc?.name" />
        <x-aais.ui.record-detail-row label="Document Type" value-x-text="activeDoc?.type" />
        <x-aais.ui.record-detail-row label="Office" value-x-text="activeDoc?.office" />
        <x-aais.ui.record-detail-row label="Received" value-x-text="activeDoc?.received" />
        <x-aais.ui.record-detail-row label="Released" value-x-text="activeDoc?.released" />
        <x-aais.ui.record-detail-row label="Status">
            <x-aais.ui.status-badge-dynamic
                class-expr="'status-'+activeDoc?.status"
                text-expr="labels[activeDoc?.status]"
            />
        </x-aais.ui.record-detail-row>
    </div>
    <x-slot:footer>
        <button class="btn btn-outline" @click="closeViewModal()">Close</button>
    </x-slot:footer>
</x-aais.ui.modal-shell>

<x-aais.ui.modal-shell
    open="editModalOpen"
    close-action="closeEditModal()"
    title="Update Status"
    max-width="400px"
    body-show="activeDoc"
>
    <p class="modal-context-text">Updating <strong><span x-text="activeDoc?.ref"></span></strong> for <span x-text="activeDoc?.name"></span>.</p>
    <div class="form-group">
        <label class="form-label">New Status</label>
        <select class="form-select" x-model="editStatusValue">
            <option value="logged">Logged</option>
            <option value="process">In Process</option>
            <option value="approved">Approved</option>
            <option value="pickup">For Pickup</option>
            <option value="complete">Completed</option>
        </select>
    </div>
    <x-slot:footer>
        <button class="btn btn-outline" @click="closeEditModal()">Cancel</button>
        <button class="btn btn-primary" @click="confirmEdit()">Save Changes</button>
    </x-slot:footer>
</x-aais.ui.modal-shell>

<x-aais.ui.modal-shell
    open="confirmModalOpen"
    close-action="closeConfirmModal()"
    title-x-text="confirmData.title"
    max-width="400px"
>
    <p class="modal-confirm-text" x-text="confirmData.message"></p>
    <x-slot:footer>
        <button class="btn btn-outline" @click="closeConfirmModal()">Cancel</button>
        <button class="btn" :class="confirmData.isDanger ? 'btn-danger' : 'btn-primary'" @click="executeConfirm()" x-text="confirmData.btnText"></button>
    </x-slot:footer>
</x-aais.ui.modal-shell>

<x-aais.ui.modal-shell
    open="exportModalOpen"
    close-action="closeExportModal()"
    title="Export Transactions"
    backdrop-class="modal-backdrop-export"
>
    <div class="modal-stack">
        <div class="export-quick-actions">
            <button class="btn btn-outline btn-sm" @click="quickExportThisWeek()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                Quick Export This Week
            </button>
        </div>

        <div class="form-group">
            <label class="form-label">Export Scope</label>
            <select class="form-select" x-model="exportConfig.scope">
                <option value="all">Export All Current Results</option>
                <option value="selected" x-show="selectedRows.length > 0">Export Selected Only</option>
            </select>
        </div>

        <div class="modal-grid-two">
            <div class="form-group">
                <label class="form-label">Date Range</label>
                <select class="form-select" x-model="exportConfig.dateRange">
                    <option value="this_week">This Week</option>
                    <option value="this_month">This Month</option>
                    <option value="custom">Custom Range</option>
                    <option value="all_time">All Time</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Filter Office</label>
                <select class="form-select" x-model="exportConfig.office">
                    <option value="All Offices">All Offices</option>
                    <option value="Registrar">Registrar</option>
                    <option value="Admissions">Admissions</option>
                    <option value="OSAS">OSAS</option>
                    <option value="Records">Records</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Format</label>
            <div class="export-format-options">
                <label class="export-format-btn" :class="{'active':exportConfig.format==='csv'}">
                    <input type="radio" x-model="exportConfig.format" value="csv">
                    <svg class="export-icon export-icon-csv" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span class="export-format-label">Excel / CSV</span>
                </label>
                <label class="export-format-btn" :class="{'active':exportConfig.format==='pdf'}">
                    <input type="radio" x-model="exportConfig.format" value="pdf">
                    <svg class="export-icon export-icon-pdf" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span class="export-format-label">PDF Document</span>
                </label>
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button class="btn btn-outline" @click="closeExportModal()">Cancel</button>
        <button class="btn btn-primary export-submit-btn" @click="executeExport()">
            <template x-if="exporting"><svg class="spinner-inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-dasharray="56" stroke-dashoffset="28"/></svg></template>
            <span x-text="exporting ? 'Exporting...' : 'Generate Export'"></span>
        </button>
    </x-slot:footer>
</x-aais.ui.modal-shell>

<x-aais.ui.toast-stack items="notifications" />
