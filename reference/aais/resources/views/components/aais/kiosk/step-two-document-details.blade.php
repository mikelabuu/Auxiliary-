@props([
    'documentTypes' => [],
    'purposeOptions' => [],
    'fileRules' => [],
])

<div x-show="step === 2" x-transition>
    <div class="kiosk-details-grid">
        <div class="card card-body card-hoverable kiosk-form-card">
            <div class="kiosk-form-header">
                <div>
                    <h2 class="section-title kiosk-form-title">Document Details</h2>
                    <p class="text-muted kiosk-form-subtitle">Specify your document type and attach any required files.</p>
                </div>
                <div class="kiosk-form-header-meta">
                    <span class="chip chip-muted">Step 2 of 3</span>
                    <p class="kiosk-required-text">Fields marked with <span class="required">*</span> are required</p>
                </div>
            </div>

            <div class="kiosk-fields-stack">
                <div class="form-group">
                    <label class="form-label kiosk-form-label">Document Type <span class="required">*</span></label>
                    <select class="form-select form-input-ring" x-model="form.docType">
                        <option value="">- Select document type -</option>
                        @foreach ($documentTypes as $docType)
                            <option value="{{ $docType }}">{{ $docType }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label kiosk-form-label">Purpose / Reason <span class="required">*</span></label>
                    <select class="form-select form-input-ring" x-model="form.purpose">
                        <option value="">- Select purpose -</option>
                        @foreach ($purposeOptions as $purpose)
                            <option value="{{ $purpose }}">{{ $purpose }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group"><label class="form-label kiosk-form-label">Number of Copies</label><input class="form-input form-input-ring kiosk-copy-count" type="number" min="1" max="10" x-model.number="form.copies"></div>
                <div class="form-group"><label class="form-label kiosk-form-label">Remarks / Notes</label><textarea class="form-textarea form-input-ring" placeholder="Any additional instructions..." x-model="form.remarks"></textarea><p class="form-hint">e.g. For scholarship requirement</p></div>
            </div>

            <div class="kiosk-upload-wrap">
                <div class="kiosk-upload-head">
                    <label class="form-label kiosk-form-label kiosk-upload-label">Attachments <span class="text-faint kiosk-upload-note">(PDF or JPEG - max 5 MB)</span></label>
                    <div class="kiosk-upload-tools" x-show="form.fileName" x-cloak>
                        <button type="button" class="btn btn-ghost btn-sm kiosk-upload-tool" @click="triggerFilePicker()">Change</button>
                        <button type="button" class="btn btn-ghost btn-sm kiosk-upload-tool kiosk-upload-tool-danger" @click="clearFile()">Remove</button>
                    </div>
                </div>
                <label
                    class="upload-zone kiosk-upload-zone"
                    :class="{ 'has-file': !!form.fileName, 'is-invalid': form.fileSize && parseFloat(form.fileSize) > 5, 'dragging': uploadDragging }"
                    for="file-upload"
                    @dragover.prevent="uploadDragging = true"
                    @dragleave.prevent="uploadDragging = false"
                    @drop.prevent="handleFile($event)"
                >
                    <input id="file-upload" x-ref="fileInput" type="file" accept=".pdf,.jpg,.jpeg" @change="handleFile($event)">
                    <div class="kiosk-upload-main">
                        <svg class="kiosk-upload-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M16 7l-4-4-4 4M12 3v13"/></svg>
                        <div class="kiosk-upload-copy">
                            <p class="kiosk-upload-title" x-text="form.fileName || 'Click or drag to upload file'"></p>
                            <p class="kiosk-upload-meta" x-show="!form.fileName">Drop file here or click to browse.</p>
                            <p class="kiosk-upload-meta" x-show="form.fileName">Click the area to replace this file.</p>
                        </div>
                        <div class="kiosk-upload-pills" x-show="form.fileName" x-cloak>
                            <span class="chip chip-green kiosk-upload-pill" x-text="form.fileType === 'application/pdf' ? 'PDF' : 'JPEG'"></span>
                            <span class="chip chip-muted kiosk-upload-pill" x-text="form.fileSize + ' MB'"></span>
                        </div>
                    </div>
                    <template x-if="parseFloat(form.fileSize) > 5"><p class="kiosk-upload-error">File exceeds 5 MB limit.</p></template>
                </label>
            </div>

            <div class="kiosk-actions-between kiosk-actions-panel">
                <p class="kiosk-actions-note">Your reference code is generated instantly after submission.</p>
                <div class="kiosk-actions-buttons">
                    <button class="btn btn-outline kiosk-back-btn" @click="prevStep()"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back</button>
                    <button class="btn btn-primary btn-lg btn-hover-scale kiosk-submit-btn" :disabled="!form.docType || !form.purpose" @click="submit()"><svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg> Submit &amp; Generate QR</button>
                </div>
            </div>
        </div>

        <div class="kiosk-side-stack">
            <div class="card card-body">
                <p class="section-title kiosk-side-title">File Policy</p>
                <p class="kiosk-side-copy">Accepted upload formats and restrictions for this request.</p>
                @foreach ($fileRules as $rule)
                    <x-aais.ui.permission-item :icon="$rule['icon']" :text="$rule['text']" :ok="$rule['ok']" />
                @endforeach
            </div>

            <div class="card card-body">
                <p class="section-label kiosk-side-subtitle">Routing Preview</p>
                <p class="kiosk-side-copy kiosk-side-copy-tight">Your request is routed to the next processing office.</p>
                <x-aais.ui.routing-card
                    class="kiosk-routing-card"
                    label="Next Office"
                    value="Registrar's Office"
                    meta="Window 3 &middot; Ground Floor"
                />
            </div>
        </div>
    </div>
</div>
