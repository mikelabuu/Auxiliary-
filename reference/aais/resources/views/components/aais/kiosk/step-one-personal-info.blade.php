<div x-show="step === 1" x-transition>
    <div class="card card-body card-hoverable kiosk-form-card">
        <h2 class="section-title kiosk-form-title">Personal Information</h2>
        <p class="text-muted kiosk-form-subtitle">Please enter your details. This will be linked to your document transaction.</p>

        <div class="kiosk-fields-grid-two">
            <div class="form-group"><label class="form-label kiosk-form-label">First Name <span class="required">*</span></label><input class="form-input form-input-ring" type="text" placeholder="e.g. Maria" x-model="form.firstName"></div>
            <div class="form-group"><label class="form-label kiosk-form-label">Last Name <span class="required">*</span></label><input class="form-input form-input-ring" type="text" placeholder="e.g. Santos" x-model="form.lastName"></div>
            <div class="form-group"><label class="form-label kiosk-form-label">Email Address <span class="required">*</span></label><input class="form-input form-input-ring" type="email" placeholder="yourname@clsu.edu.ph" x-model="form.email"><p class="form-hint">Email alerts will be sent here when your document is ready.</p></div>
            <div class="form-group"><label class="form-label kiosk-form-label">Contact Number <span class="required">*</span></label><input class="form-input form-input-ring" type="tel" placeholder="09XX-XXX-XXXX" x-model="form.phone"></div>
            <div class="form-group kiosk-field-full"><label class="form-label kiosk-form-label">Student / Employee ID</label><input class="form-input form-input-ring" type="text" placeholder="e.g. 2021-00123" x-model="form.studentId"><p class="form-hint">Optional, but speeds up verification.</p></div>
        </div>

        <div class="kiosk-actions-end">
            <button class="btn btn-primary btn-lg btn-hover-scale" @click="nextStep()">Continue to Document Details <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>
        </div>
    </div>
</div>
