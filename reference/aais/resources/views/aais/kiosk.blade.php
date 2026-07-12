@php
    $title     = 'Self-Service Kiosk';
    $topbarSub = 'Encode your document details and generate a tracking reference';

    $documentTypes = [
        'Transcript of Records',
        'Certificate of Enrollment',
        'Certificate of Graduation',
        'Good Moral Certificate',
        'Diploma Authentication',
        'Honorable Dismissal',
        'Transfer Credentials',
        'CAV Document',
        'Other',
    ];

    $purposeOptions = [
        'Employment',
        'Scholarship',
        'Transfer',
        'Board Exam',
        'Graduate School',
        'Personal Record',
        'Abroad Application',
        'Other',
    ];

    $fileRules = [
        ['icon' => '✓', 'text' => 'Accepted: PDF (.pdf)', 'ok' => true],
        ['icon' => '✓', 'text' => 'Accepted: JPEG (.jpg, .jpeg)', 'ok' => true],
        ['icon' => '✗', 'text' => 'Rejected: Word, Excel, PNG, etc.', 'ok' => false],
        ['icon' => '⚠', 'text' => 'Max file size: 5 MB per file', 'ok' => null],
        ['icon' => '⚠', 'text' => 'Ensure your document is legible', 'ok' => null],
    ];

    $qrPattern = [
        [1,1,1,1,1,1,1,0,1,0,1,1,1,1,1,1,1],
        [1,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,1],
        [1,0,1,1,1,0,1,0,1,0,1,0,1,1,1,0,1],
        [1,0,1,1,1,0,1,0,0,1,1,0,1,1,1,0,1],
        [1,0,1,1,1,0,1,0,1,0,1,0,1,1,1,0,1],
        [1,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,1],
        [1,1,1,1,1,1,1,0,1,0,1,1,1,1,1,1,1],
        [0,0,0,0,0,0,0,0,1,1,0,0,0,0,0,0,0],
        [1,0,1,1,0,1,1,1,0,0,1,0,1,1,0,1,0],
        [0,0,0,0,0,0,0,0,1,0,0,1,0,0,0,0,1],
        [1,1,1,1,1,1,1,0,1,1,0,0,1,1,1,1,1],
        [1,0,0,0,0,0,1,0,0,0,1,0,0,0,0,0,1],
        [1,0,1,1,1,0,1,0,1,0,0,1,1,1,1,0,1],
        [1,0,0,0,0,0,1,0,0,1,1,0,0,0,0,0,1],
        [1,1,1,1,1,1,1,0,1,0,1,0,1,1,1,1,1],
    ];

    $generatedAt = now()->format('M d, Y · g:i A');
@endphp

@extends('layouts.client')

@section('content')
<div x-data="{
    step: 1, totalSteps: 3, submitted: false,
    refCode: window.AAISDemoStore
        ? window.AAISDemoStore.generateReference()
        : ('TL-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 9000) + 1000)),
    submittedDoc: null,
    uploadDragging: false,
    form: { firstName:'', lastName:'', email:'', phone:'', studentId:'', copies: 1, docType:'', purpose:'', remarks:'', fileName:'', fileSize:'', fileType:'', filePreviewUrl:'' },
    isEmailValid(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },
    nextStep() {
        if (this.step === 1) {
            if (!this.form.firstName.trim() || !this.form.lastName.trim() || !this.form.email.trim() || !this.form.phone.trim()) {
                Swal.fire({icon:'warning',title:'Missing Fields',text:'Please fill in all required fields.',confirmButtonText:'OK'});
                return;
            }
            if (!this.isEmailValid(this.form.email.trim())) {
                Swal.fire({icon:'warning',title:'Invalid Email',text:'Please enter a valid email address.',confirmButtonText:'OK'});
                return;
            }
        }
        if(this.step < this.totalSteps) this.step++;
    },
    prevStep() { if(this.step > 1) this.step--; },
    triggerFilePicker() {
        if (this.$refs.fileInput) {
            this.$refs.fileInput.click();
        }
    },
    clearFile() {
        this.form.fileName = '';
        this.form.fileSize = '';
        this.form.fileType = '';
        this.form.filePreviewUrl = '';
        this.uploadDragging = false;
        if (this.$refs.fileInput) {
            this.$refs.fileInput.value = '';
        }
    },
    async handleFile(e) {
        const f = e?.target?.files?.[0] || e?.dataTransfer?.files?.[0] || e?.files?.[0];
        this.uploadDragging = false;
        if(!f) return;

        const allowedTypes = ['application/pdf', 'image/jpeg'];
        const sizeMb = f.size / 1048576;

        if (!allowedTypes.includes(f.type)) {
            this.clearFile();
            Swal.fire({icon:'warning',title:'Invalid File Type',text:'Only PDF and JPEG files are allowed.',confirmButtonText:'OK'});
            return;
        }

        if (sizeMb > 5) {
            this.clearFile();
            Swal.fire({icon:'warning',title:'File Too Large',text:'Please upload a file that is 5 MB or smaller.',confirmButtonText:'OK'});
            return;
        }

        this.form.fileName = f.name;
        this.form.fileSize = sizeMb.toFixed(2);
        this.form.fileType = f.type;
        this.form.filePreviewUrl = '';

        const previewLimitMb = 1.5;
        if (sizeMb <= previewLimitMb) {
            const toDataUrl = (file) => new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(String(reader.result || ''));
                reader.onerror = () => reject(new Error('Unable to read file'));
                reader.readAsDataURL(file);
            });

            try {
                this.form.filePreviewUrl = await toDataUrl(f);
            } catch (err) {
                this.form.filePreviewUrl = '';
            }
        }
    },
    submit() {
        if(!this.form.docType || !this.form.purpose) {
            Swal.fire({icon:'warning',title:'Missing Fields',text:'Please select document type and purpose.',confirmButtonText:'OK'}); return;
        }
        if (this.form.fileSize && parseFloat(this.form.fileSize) > 5) {
            Swal.fire({icon:'warning',title:'File Too Large',text:'Please upload a file that is 5 MB or smaller.',confirmButtonText:'OK'});
            return;
        }

        const fullName = `${this.form.firstName.trim()} ${this.form.lastName.trim()}`.trim();
        const office = 'Registrar Office';
        const normalizedFileName = (this.form.fileName || '').trim();
        let attachmentUrl = this.form.filePreviewUrl || '';

        if (!attachmentUrl && normalizedFileName.toLowerCase() === 'test.pdf') {
            attachmentUrl = '/test.pdf';
        }

        if (window.AAISDemoStore && !this.submitted) {
            let created = window.AAISDemoStore.createSubmission({
                ref: this.refCode,
                name: fullName,
                type: this.form.docType,
                purpose: this.form.purpose,
                office,
                attachment: {
                    name: normalizedFileName,
                    type: this.form.fileType,
                    sizeMb: this.form.fileSize,
                    url: attachmentUrl,
                },
            });

            // Retry once with a fresh reference in case of rare collisions.
            if (!created) {
                this.refCode = window.AAISDemoStore.generateReference();
                created = window.AAISDemoStore.createSubmission({
                    ref: this.refCode,
                    name: fullName,
                    type: this.form.docType,
                    purpose: this.form.purpose,
                    office,
                    attachment: {
                        name: normalizedFileName,
                        type: this.form.fileType,
                        sizeMb: this.form.fileSize,
                        url: attachmentUrl,
                    },
                });
            }

            if (!created) {
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to Save Submission',
                    text: 'Please try submitting again.',
                    confirmButtonText: 'OK'
                });
                return;
            }

            this.submittedDoc = created;
            this.refCode = created.ref;
        }

        this.submitted = true; this.step = 3;
        Swal.fire({
            icon:'success',
            title:'Document Submitted!',
            text:'Your record is now waiting for staff acceptance at the receiving window.',
            timer:2800,
            showConfirmButton:false
        });
    },
}" x-cloak>

    <x-aais.kiosk.step-bar />

    <x-aais.kiosk.step-one-personal-info />

    <x-aais.kiosk.step-two-document-details
        :document-types="$documentTypes"
        :purpose-options="$purposeOptions"
        :file-rules="$fileRules"
    />

    <x-aais.kiosk.step-three-qr :qr-pattern="$qrPattern" :generated-at="$generatedAt" />
</div>
@endsection
