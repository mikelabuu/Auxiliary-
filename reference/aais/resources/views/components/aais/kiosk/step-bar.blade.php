<div class="card" style="padding:26px 30px;margin-bottom:22px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
        <h1 class="section-title" style="font-size:18px;">Document Submission</h1>
        <span class="chip chip-muted" x-text="'Step ' + step + ' of ' + totalSteps"></span>
    </div>

    <div class="step-bar">
        <div class="step-item">
            <div class="step-circle" :class="{ 'done': step > 1, 'active': step === 1 }">
                <template x-if="step > 1"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg></template>
                <template x-if="step <= 1"><span>1</span></template>
            </div>
            <span class="step-label" :class="{ 'active': step === 1, 'done': step > 1 }">Your Info</span>
        </div>

        <div class="step-line" :class="{ 'done': step > 1 }"></div>

        <div class="step-item">
            <div class="step-circle" :class="{ 'done': step > 2, 'active': step === 2 }">
                <template x-if="step > 2"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg></template>
                <template x-if="step <= 2"><span>2</span></template>
            </div>
            <span class="step-label" :class="{ 'active': step === 2, 'done': step > 2 }">Document Details</span>
        </div>

        <div class="step-line" :class="{ 'done': step > 2 }"></div>

        <div class="step-item">
            <div class="step-circle" :class="{ 'done': step === 3 && submitted, 'active': step === 3 }">
                <template x-if="step === 3 && submitted"><svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" style="width:14px;height:14px;"><polyline points="20 6 9 17 4 12"/></svg></template>
                <template x-if="!(step === 3 && submitted)"><span>3</span></template>
            </div>
            <span class="step-label" :class="{ 'active': step === 3 }">QR Code</span>
        </div>
    </div>
</div>
