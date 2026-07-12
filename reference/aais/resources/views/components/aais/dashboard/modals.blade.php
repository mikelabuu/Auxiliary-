<x-aais.ui.modal-shell
    open="viewModalOpen"
    close-action="closeViewModal()"
    size="lg"
    title-x-text="activeDoc?.ref || 'Transaction Details'"
    body-show="activeDoc"
>
    <div class="record-detail-panel">
        <x-aais.ui.record-detail-row label="Client" value-x-text="activeDoc?.name" />
        <x-aais.ui.record-detail-row label="Document" value-x-text="activeDoc?.type" />
        <x-aais.ui.record-detail-row label="Office" value-x-text="activeDoc?.office" />
        <x-aais.ui.record-detail-row label="Staff" value-x-text="activeDoc?.staff" />
        <x-aais.ui.record-detail-row label="Received" value-x-text="activeDoc?.received" />
        <x-aais.ui.record-detail-row label="Status">
            <x-aais.ui.status-badge-dynamic
                class-expr="'status-' + activeDoc?.status"
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
    max-width="420px"
    body-show="activeDoc"
>
    <p style="font-size:13px;color:var(--color-muted);margin-bottom:12px;">
        Updating <strong x-text="activeDoc?.ref"></strong> - <span x-text="activeDoc?.name"></span>
    </p>
    <select class="form-select" x-model="editStatusValue">
        <option value="logged">Logged</option>
        <option value="process">In Process</option>
        <option value="approved">Approved</option>
        <option value="pickup">For Pickup</option>
        <option value="complete">Completed</option>
    </select>
    <x-slot:footer>
        <button class="btn btn-outline" @click="closeEditModal()">Cancel</button>
        <button class="btn btn-primary" @click="saveEdit()">Update Status</button>
    </x-slot:footer>
</x-aais.ui.modal-shell>

<x-aais.ui.modal-shell
    open="confirmModalOpen"
    close-action="closeConfirmModal()"
    title="Void Transaction?"
    max-width="420px"
    body-show="activeDoc"
>
    <p style="font-size:13px;color:var(--color-muted);line-height:1.8;">
        This will void <strong x-text="activeDoc?.ref"></strong> for
        <span x-text="activeDoc?.name"></span>. This action is logged and cannot be undone.
    </p>
    <x-slot:footer>
        <button class="btn btn-outline" @click="closeConfirmModal()">Cancel</button>
        <button class="btn btn-danger" @click="confirmVoid()">Yes, Void It</button>
    </x-slot:footer>
</x-aais.ui.modal-shell>
